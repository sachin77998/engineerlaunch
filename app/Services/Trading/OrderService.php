<?php

namespace App\Services\Trading;

use App\Mail\OtpMail;
use App\Models\Trading\MfHolding;
use App\Models\Trading\MfOrder;
use App\Models\Trading\MfOrderEvent;
use App\Models\Trading\MfScheme;
use App\Models\Trading\TradingClient;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Order lifecycle: otp_pending → verified → submitted → allotted, or cancelled / failed / rejected.
 * Every change is recorded in mf_order_events and the audit log. Allotment uses the NAV of the applicable date.
 */
class OrderService
{
    public const TRANSITIONS = [
        'otp_pending' => ['verified', 'cancelled', 'failed'],
        'verified' => ['submitted', 'cancelled', 'failed'],
        'submitted' => ['allotted', 'rejected', 'cancelled'],
        'allotted' => [],
        'rejected' => [],
        'cancelled' => [],
        'failed' => [],
    ];

    public function __construct(private MarketCalendar $calendar, private FeeCalculator $fees, private TradingSettings $settings, private TradingAudit $audit) {}

    /** Scheme lookup used on every order request (cached briefly; NAVs change once a day). */
    public function scheme(int $code): ?MfScheme
    {
        $scheme = Cache::remember('trading:scheme:' . $code, 300, fn () => MfScheme::where('scheme_code', $code)->first());
        return $scheme instanceof MfScheme ? $scheme : null;
    }

    public function place(User $user, array $data, ?string $idempotencyKey): MfOrder
    {
        $client = TradingClient::where('user_id', $user->id)->first();
        if (!$client || !$client->isVerified()) $this->fail('client', 'Complete onboarding and KYC verification before placing orders.');
        if ($idempotencyKey && ($existing = MfOrder::where('user_id', $user->id)->where('idempotency_key', $idempotencyKey)->first())) return $existing;

        $today = $this->calendar->now();
        if ($reason = $this->calendar->closedReason($today)) $this->fail('date', $reason . ' Orders can be placed on the next business day.');

        $scheme = $this->scheme((int) $data['scheme_code']);
        if (!$scheme || !$scheme->is_active) $this->fail('scheme_code', 'This scheme is not available.');
        if ($scheme->scheme_type !== 'Open Ended' || $scheme->asset_class === 'ETF') $this->fail('scheme_code', 'Only open-ended schemes can be bought or redeemed here (ETFs trade on the exchange).');

        $side = $data['side'];
        $amount = $units = null;
        $allUnits = false;
        if ($side === 'buy') {
            $amount = round((float) $data['amount'], 2);
            $min = max($this->settings->number('order.min_amount'), (float) $scheme->min_purchase);
            if ($amount < $min) $this->fail('amount', 'Minimum amount is ₹' . number_format($min) . '.');
            if ($amount > $this->settings->number('order.max_amount')) $this->fail('amount', 'Amount above ₹' . number_format($this->settings->number('order.max_amount')) . ' needs a manual review; please contact support.');
        } else {
            $holding = MfHolding::where('trading_client_id', $client->id)->where('scheme_code', $scheme->scheme_code)->first();
            $allUnits = (bool) ($data['all_units'] ?? false);
            $units = $allUnits ? (float) ($holding->units ?? 0) : round((float) ($data['units'] ?? 0), 4);
            $pending = (float) MfOrder::where('trading_client_id', $client->id)->where('scheme_code', $scheme->scheme_code)->where('side', 'sell')->whereIn('status', ['otp_pending', 'verified', 'submitted'])->sum('units');
            if ($units <= 0 || !$holding || $units > $holding->units - $pending + 0.00001) $this->fail('units', 'You can redeem up to ' . number_format(max(0, ($holding->units ?? 0) - $pending), 4) . ' units.');
            $amount = round($units * (float) $scheme->nav, 2);   // estimate; final value uses the applicable NAV
        }

        $quote = $this->fees->quote($side, $amount);
        $otp = (string) random_int(100000, 999999);
        if (app()->environment(['local', 'testing'])) $otp = '123456';
        $order = DB::transaction(function () use ($user, $client, $scheme, $side, $amount, $units, $allUnits, $quote, $otp, $idempotencyKey, $data) {
            $order = MfOrder::create([
                'order_no' => $this->orderNumber(),
                'trading_client_id' => $client->id,
                'user_id' => $user->id,
                'scheme_code' => $scheme->scheme_code,
                'side' => $side,
                'order_type' => $side === 'buy' ? ($data['order_type'] ?? 'lumpsum') : 'redeem',
                'amount' => $amount,
                'units' => $units,
                'all_units' => $allUnits,
                'status' => 'otp_pending',
                'idempotency_key' => $idempotencyKey,
                'applicable_nav_date' => $this->calendar->applicableNavDate($scheme)->toDateString(),
                'stamp_duty' => $quote['stamp_duty'], 'transaction_charge' => $quote['transaction_charge'], 'platform_fee' => $quote['platform_fee'],
                'gst' => $quote['gst'], 'total_fees' => $quote['total_fees'], 'net_amount' => $quote['net_amount'],
                'payment_mode' => $side === 'buy' ? ($data['payment_mode'] ?? 'netbanking') : null,
                'otp_hash' => $this->otpHash($otp, null),
                'otp_expires_at' => now()->addMinutes((int) $this->settings->get('order.otp_minutes')),
                'channel' => (string) $this->settings->get('execution.channel'),
                'ip' => request()?->ip(),
                'user_agent' => Str::limit((string) request()?->userAgent(), 250, ''),
            ]);
            $order->forceFill(['otp_hash' => $this->otpHash($otp, $order->order_no)])->save();
            $this->event($order, null, 'otp_pending', 'client', 'Order created; OTP sent to ' . $this->maskEmail($user->email));
            return $order;
        });
        $this->sendOtp($user, $order, $otp);
        $this->audit->log('order.placed', $order, ['order_no' => $order->order_no, 'side' => $side, 'scheme_code' => $scheme->scheme_code, 'amount' => $amount, 'units' => $units]);
        return $order;
    }

    /** OTP verification; on success the order is submitted to the execution channel. */
    public function verify(MfOrder $order, string $otp): MfOrder
    {
        return DB::transaction(function () use ($order, $otp) {
            $order = MfOrder::whereKey($order->id)->lockForUpdate()->first();
            if ($order->status !== 'otp_pending') $this->fail('otp', 'This order is already ' . str_replace('_', ' ', $order->status) . '.');
            if ($order->otp_expires_at?->isPast()) {
                $this->transition($order, 'failed', 'system', 'OTP expired');
                $this->fail('otp', 'The OTP has expired. Please place the order again.');
            }
            if (!hash_equals((string) $order->otp_hash, $this->otpHash(trim($otp), $order->order_no))) {
                $order->increment('otp_attempts');
                $this->audit->log('order.otp_failed', $order, ['attempt' => $order->otp_attempts]);
                if ($order->otp_attempts >= (int) $this->settings->get('order.otp_attempts')) {
                    $this->transition($order, 'failed', 'system', 'Too many wrong OTP attempts');
                    $this->fail('otp', 'Too many wrong attempts. The order was cancelled for your security.');
                }
                $this->fail('otp', 'Incorrect OTP.');
            }
            $order->forceFill(['otp_hash' => null, 'verified_at' => now()])->save();
            $this->transition($order, 'verified', 'client', 'OTP verified');
            $this->submit($order);
            return $order->fresh();
        });
    }

    /** Sends a verified order to the execution channel. */
    public function submit(MfOrder $order): void
    {
        if ($order->channel !== 'simulated') {
            // A registered channel (e.g. BSE StAR MF, needs an AMFI ARN and exchange membership) is called here.
            $this->transition($order, 'failed', 'system', 'Execution channel "' . $order->channel . '" is not connected.');
            return;
        }
        $order->forceFill(['submitted_at' => now(), 'external_ref' => 'SIM' . strtoupper(Str::random(10))])->save();
        $this->transition($order, 'submitted', 'system', 'Submitted for NAV of ' . $order->applicable_nav_date->format('d M Y') . ' (simulated execution: no money moves)');
    }

    /** Allots submitted orders whose NAV date has a published NAV. Returns the number allotted. */
    public function allotDue(): int
    {
        $count = 0;
        MfOrder::where('status', 'submitted')->whereDate('applicable_nav_date', '<=', now()->toDateString())->orderBy('id')
            ->chunkById(200, function ($orders) use (&$count) {
                foreach ($orders as $order) {
                    $nav = DB::table('mf_nav_history')->where('scheme_code', $order->scheme_code)->where('nav_date', '>=', $order->applicable_nav_date->toDateString())
                        ->orderBy('nav_date')->first();
                    if (!$nav) continue;
                    DB::transaction(fn () => $this->allot(MfOrder::whereKey($order->id)->lockForUpdate()->first(), (float) $nav->nav, $nav->nav_date));
                    $count++;
                }
            });
        return $count;
    }

    public function allot(MfOrder $order, float $nav, string $navDate): void
    {
        if ($order->status !== 'submitted' || $nav <= 0) return;
        $holding = MfHolding::firstOrNew(['trading_client_id' => $order->trading_client_id, 'scheme_code' => $order->scheme_code]);
        if ($order->side === 'buy') {
            $units = floor($order->net_amount / $nav * 10000) / 10000;
            $holding->units = round((float) $holding->units + $units, 4);
            $holding->invested = round((float) $holding->invested + $order->net_amount, 2);
            $order->allotted_units = $units;
        } else {
            $units = min((float) $order->units, (float) $holding->units);
            $value = round($units * $nav, 2);
            $costShare = $holding->units > 0 ? $holding->invested * $units / $holding->units : 0;
            $holding->units = round((float) $holding->units - $units, 4);
            $holding->invested = max(0, round((float) $holding->invested - $costShare, 2));
            $order->allotted_units = $units;
            $order->amount = $value;
            $order->net_amount = round($value - $order->total_fees, 2);
        }
        $holding->avg_nav = $holding->units > 0 ? round($holding->invested / $holding->units, 4) : null;
        $holding->save();
        $order->nav = $nav;
        $order->allotted_at = now();
        $order->save();
        $this->transition($order, 'allotted', 'system', number_format($order->allotted_units, 4) . ' units at NAV ₹' . number_format($nav, 4) . ' of ' . $navDate);
    }

    /** Moves an order to a new status if the transition is allowed. */
    public function transition(MfOrder $order, string $to, string $actor, ?string $note = null, array $meta = []): MfOrder
    {
        $from = $order->status;
        if (!in_array($to, self::TRANSITIONS[$from] ?? [], true)) $this->fail('status', "An order that is {$from} cannot become {$to}.");
        $order->status = $to;
        if (in_array($to, ['rejected', 'cancelled', 'failed'], true)) $order->status_reason = Str::limit((string) $note, 250, '');
        $order->save();
        $this->event($order, $from, $to, $actor, $note, $meta);
        $this->audit->log('order.' . $to, $order, ['from' => $from, 'note' => $note] + $meta);
        Cache::forget('trading:order:' . $order->order_no);
        return $order;
    }

    public function cancel(MfOrder $order, string $actor, string $reason): MfOrder
    {
        return DB::transaction(function () use ($order, $actor, $reason) {
            $order = MfOrder::whereKey($order->id)->lockForUpdate()->first();
            if ($order->status === 'submitted' && $actor === 'client') {
                $cutoffPassed = $order->applicable_nav_date->isToday() && $this->calendar->applicableNavDate($order->scheme)->gt($order->applicable_nav_date);
                if ($order->applicable_nav_date->isPast() || $cutoffPassed) $this->fail('status', 'The cut-off has passed; this order can no longer be cancelled.');
            }
            return $this->transition($order, 'cancelled', $actor, $reason);
        });
    }

    private function event(MfOrder $order, ?string $from, string $to, string $actor, ?string $note, array $meta = []): void
    {
        MfOrderEvent::create(['mf_order_id' => $order->id, 'from_status' => $from, 'to_status' => $to, 'actor' => $actor,
            'actor_id' => $actor === 'system' ? null : auth()->id(), 'note' => Str::limit((string) $note, 250, ''), 'meta' => $meta ?: null]);
    }

    private function sendOtp(User $user, MfOrder $order, string $otp): void
    {
        if (app()->environment(['local', 'testing'])) return;
        try {
            Mail::to($user->email)->queue(new OtpMail($otp, 'Your OTP for order ' . $order->order_no . ' (valid ' . $this->settings->get('order.otp_minutes') . ' minutes)'));
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function otpHash(string $otp, ?string $orderNo): string
    {
        return hash_hmac('sha256', $otp . '|' . $orderNo, (string) config('app.key'));
    }

    private function orderNumber(): string
    {
        return 'DT' . now('Asia/Kolkata')->format('ymd') . strtoupper(Str::random(8));
    }

    private function maskEmail(?string $email): string
    {
        if (!$email || !str_contains($email, '@')) return 'your email';
        [$name, $domain] = explode('@', $email, 2);
        return mb_substr($name, 0, 2) . str_repeat('*', max(1, mb_strlen($name) - 2)) . '@' . $domain;
    }

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
