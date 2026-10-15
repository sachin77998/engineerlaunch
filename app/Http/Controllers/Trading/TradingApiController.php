<?php

namespace App\Http\Controllers\Trading;

use App\Http\Controllers\Controller;
use App\Models\Trading\CalculatorRun;
use App\Models\Trading\MfOrder;
use App\Models\Trading\MfScheme;
use App\Services\Trading\Calculators;
use App\Services\Trading\FeeCalculator;
use App\Services\Trading\FundRecommender;
use App\Services\Trading\MarketCalendar;
use App\Services\Trading\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Trading JSON API (session or Sanctum token). Orders: place → verify (OTP) → submitted → allotted.
 * Endpoints are lean (one or two queries, cached scheme lookups) so responses stay fast.
 */
class TradingApiController extends Controller
{
    public function __construct(private OrderService $orders) {}

    /** Fee deduction preview for an amount. */
    public function fees(Request $request, FeeCalculator $fees): JsonResponse
    {
        $data = $request->validate(['side' => ['required', Rule::in(['buy', 'sell'])], 'amount' => 'required|numeric|min:1|max:100000000']);
        return response()->json(['data' => $fees->quote($data['side'], (float) $data['amount'])]);
    }

    /** Place an order. Send an Idempotency-Key header to make retries safe. */
    public function place(Request $request): JsonResponse
    {
        $data = $request->validate([
            'scheme_code' => 'required|integer',
            'side' => ['required', Rule::in(['buy', 'sell'])],
            'amount' => 'required_if:side,buy|nullable|numeric|min:1|max:100000000',
            'units' => 'nullable|numeric|min:0.0001',
            'all_units' => 'nullable|boolean',
            'order_type' => ['nullable', Rule::in(['lumpsum', 'sip'])],
            'payment_mode' => ['nullable', Rule::in(['netbanking', 'upi', 'mandate'])],
        ]);
        $key = $request->header('Idempotency-Key');
        if ($key !== null && !preg_match('/^[A-Za-z0-9_-]{8,64}$/', $key)) return response()->json(['message' => 'Idempotency-Key must be 8-64 letters, digits, - or _.'], 422);
        $order = $this->orders->place($request->user(), $data, $key);
        return response()->json(['data' => $this->present($order), 'message' => 'Enter the OTP sent to your registered email to confirm.'], $order->wasRecentlyCreated ? 201 : 200);
    }

    /** View one order with its status history. */
    public function show(Request $request, MfOrder $order): JsonResponse
    {
        $this->authorizeOrder($request, $order);
        return response()->json(['data' => $this->present($order->load(['events', 'scheme']), true)]);
    }

    public function index(Request $request): JsonResponse
    {
        $orders = MfOrder::with('scheme:scheme_code,name,plan,option,amc')->where('user_id', $request->user()->id)
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))->latest('id')->paginate(min(100, (int) $request->query('per_page', 20)));
        return response()->json(['data' => $orders->getCollection()->map(fn ($o) => $this->present($o)), 'meta' => ['total' => $orders->total(), 'page' => $orders->currentPage(), 'last_page' => $orders->lastPage()]]);
    }

    /** OTP verification of an order. */
    public function verify(Request $request, MfOrder $order): JsonResponse
    {
        $this->authorizeOrder($request, $order, false);
        $data = $request->validate(['otp' => 'required|digits:6']);
        $order = $this->orders->verify($order, $data['otp']);
        return response()->json(['data' => $this->present($order), 'message' => 'Order confirmed and submitted for NAV of ' . $order->applicable_nav_date->format('d M Y') . '.']);
    }

    public function cancel(Request $request, MfOrder $order): JsonResponse
    {
        $this->authorizeOrder($request, $order, false);
        $order = $this->orders->cancel($order, 'client', 'Cancelled by the investor');
        return response()->json(['data' => $this->present($order)]);
    }

    /** Status update (owner/back office only). */
    public function updateStatus(Request $request, MfOrder $order): JsonResponse
    {
        abort_unless($request->user()?->isOwner(), 403);
        $data = $request->validate(['status' => ['required', Rule::in(array_keys(OrderService::TRANSITIONS))], 'note' => 'nullable|string|max:250', 'nav' => 'nullable|numeric|min:0.0001']);
        if ($data['status'] === 'allotted') {
            abort_unless(isset($data['nav']), 422, 'NAV is required to allot an order.');
            $this->orders->allot($order, (float) $data['nav'], $order->applicable_nav_date->toDateString());
        } else {
            $this->orders->transition($order, $data['status'], 'admin', $data['note'] ?? 'Updated by back office');
        }
        return response()->json(['data' => $this->present($order->fresh()->load('events'), true)]);
    }

    public function funds(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q'));
        $funds = MfScheme::where('is_active', true)
            ->when($q !== '', fn ($query) => strlen($q) >= 3 ? $query->whereFullText('name', self::words($q), ['mode' => 'boolean']) : $query->where('name', 'like', $q . '%'))
            ->when($request->query('amc'), fn ($query, $v) => $query->where('amc', $v))
            ->when($request->query('category'), fn ($query, $v) => $query->where('category', $v))
            ->when($request->query('plan'), fn ($query, $v) => $query->where('plan', $v))
            ->when($request->query('option'), fn ($query, $v) => $query->where('option', $v))
            ->orderByRaw('return_3y IS NULL')->orderByDesc('return_3y')->limit(min(50, (int) $request->query('limit', 20)))
            ->get(['scheme_code', 'name', 'amc', 'category', 'plan', 'option', 'nav', 'nav_date', 'return_1y', 'return_3y', 'return_5y']);
        return response()->json(['data' => $funds]);
    }

    public function recommendations(MfScheme $scheme, FundRecommender $recommender): JsonResponse
    {
        $fields = fn ($c) => $c->map->only(['scheme_code', 'name', 'amc', 'category', 'plan', 'option', 'nav', 'return_1y', 'return_3y'])->values();
        $r = $recommender->for($scheme);
        return response()->json(['data' => ['same_amc' => $fields($r['same_amc']), 'same_category' => $fields($r['same_category']), 'also_bought' => $fields($r['also_bought'])]]);
    }

    public function calculate(Request $request, string $type, Calculators $calculators): JsonResponse
    {
        abort_unless(in_array($type, Calculators::TYPES, true), 404);
        $rules = [
            'lumpsum' => ['amount' => 'required|numeric|min:1|max:1000000000', 'rate' => 'required|numeric|min:-50|max:100', 'years' => 'required|numeric|min:0.1|max:50'],
            'sip' => ['monthly' => 'required|numeric|min:1|max:10000000', 'rate' => 'required|numeric|min:-50|max:100', 'years' => 'required|numeric|min:0.1|max:50', 'step_up' => 'nullable|numeric|min:0|max:100'],
            'emi' => ['principal' => 'required|numeric|min:1|max:10000000000', 'rate' => 'required|numeric|min:0|max:60', 'months' => 'required|integer|min:1|max:600'],
            'amortization' => ['principal' => 'required|numeric|min:1|max:10000000000', 'rate' => 'required|numeric|min:0|max:60', 'months' => 'required|integer|min:1|max:600', 'prepayment' => 'nullable|numeric|min:0', 'prepay_month' => 'nullable|integer|min:0|max:600'],
            'margin' => ['symbol' => 'nullable|string|max:30', 'price' => 'required|numeric|min:0.01|max:10000000', 'quantity' => 'required|integer|min:1|max:10000000', 'product' => ['required', Rule::in(['delivery', 'intraday'])], 'margin_pct' => 'nullable|numeric|min:1|max:100'],
        ][$type];
        $inputs = $request->validate($rules);
        $result = $calculators->run($type, $inputs);
        CalculatorRun::create(['user_id' => $request->user()?->id, 'type' => $type, 'inputs' => $inputs, 'result' => $type === 'amortization' ? array_diff_key($result, ['schedule' => 1]) : $result]);
        return response()->json(['data' => $result]);
    }

    public function market(MarketCalendar $calendar): JsonResponse
    {
        $now = $calendar->now();
        return response()->json(['data' => ['open_today' => $calendar->isBusinessDay($now), 'reason' => $calendar->closedReason($now), 'now' => $now->toIso8601String(),
            'next_business_day' => $calendar->nextBusinessDay($now)->toDateString(),
            'upcoming_holidays' => collect($calendar->holidays())->filter(fn ($d, $date) => $date >= $now->toDateString())->take(10)]]);
    }

    private function authorizeOrder(Request $request, MfOrder $order, bool $allowOwner = true): void
    {
        $user = $request->user();
        abort_unless($user && ($order->user_id === $user->id || ($allowOwner && $user->isOwner())), 404);
    }

    private function present(MfOrder $order, bool $withEvents = false): array
    {
        $scheme = $order->relationLoaded('scheme') ? $order->scheme : null;
        $out = [
            'order_no' => $order->order_no, 'status' => $order->status, 'status_reason' => $order->status_reason, 'side' => $order->side, 'order_type' => $order->order_type,
            'scheme_code' => $order->scheme_code, 'scheme' => $scheme ? trim($scheme->name . ' · ' . $scheme->plan . ' · ' . $scheme->option, ' ·') : null,
            'amount' => $order->amount, 'units' => $order->units, 'all_units' => $order->all_units,
            'fees' => ['stamp_duty' => $order->stamp_duty, 'transaction_charge' => $order->transaction_charge, 'platform_fee' => $order->platform_fee, 'gst' => $order->gst, 'total' => $order->total_fees],
            'net_amount' => $order->net_amount, 'applicable_nav_date' => $order->applicable_nav_date?->toDateString(), 'nav' => $order->nav, 'allotted_units' => $order->allotted_units,
            'channel' => $order->channel, 'reference' => $order->external_ref, 'otp_expires_at' => $order->status === 'otp_pending' ? $order->otp_expires_at?->toIso8601String() : null,
            'placed_at' => $order->created_at?->toIso8601String(), 'verified_at' => $order->verified_at?->toIso8601String(), 'submitted_at' => $order->submitted_at?->toIso8601String(), 'allotted_at' => $order->allotted_at?->toIso8601String(),
        ];
        if ($withEvents) $out['history'] = $order->events->map(fn ($e) => ['from' => $e->from_status, 'to' => $e->to_status, 'by' => $e->actor, 'note' => $e->note, 'at' => $e->created_at?->toIso8601String()])->all();
        return $out;
    }

    /** Boolean full-text query requiring every word (prefix match): "hdfc flexi" → "+hdfc* +flexi*". */
    private static function words(string $q): string
    {
        return collect(preg_split('/[^\pL\pN]+/u', mb_strtolower($q)))->filter(fn ($w) => mb_strlen($w) >= 2)->map(fn ($w) => '+' . $w . '*')->implode(' ');
    }
}
