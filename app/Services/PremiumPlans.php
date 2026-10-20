<?php

namespace App\Services;

use App\Models\PremiumOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/** Premium plans: fee breakdown, orders, Razorpay payment and activation. */
class PremiumPlans
{
    /** @return array<string, array> plan key => name, months, price, gst, total, per_month */
    public function plans(): array
    {
        $rate = (float) config('premium.gst_rate', 18);
        $monthly = (float) config('premium.plans.monthly.price');
        return collect(config('premium.plans'))->map(function ($plan) use ($rate, $monthly) {
            $gst = round($plan['price'] * $rate / 100, 2);
            return $plan + ['gst_rate' => $rate, 'gst' => $gst, 'total' => round($plan['price'] + $gst, 2),
                'per_month' => round($plan['price'] / $plan['months'], 2),
                'saving' => $monthly > 0 && $plan['months'] > 1 ? max(0, round(100 - $plan['price'] / ($monthly * $plan['months']) * 100)) : 0];
        })->all();
    }

    public function gatewayReady(): bool
    {
        return filled(config('premium.razorpay.key_id')) && filled(config('premium.razorpay.key_secret'));
    }

    public function createOrder(User $user, string $planKey): PremiumOrder
    {
        $plan = $this->plans()[$planKey] ?? throw new RuntimeException('Unknown plan');
        $order = PremiumOrder::create([
            'order_no' => 'PRM' . now()->format('ymd') . strtoupper(Str::random(6)), 'user_id' => $user->id, 'plan' => $planKey, 'months' => $plan['months'],
            'base_amount' => $plan['price'], 'gst_rate' => $plan['gst_rate'], 'gst_amount' => $plan['gst'], 'total_amount' => $plan['total'],
            'status' => 'created', 'gateway' => $this->gatewayReady() ? 'razorpay' : null,
        ]);
        if ($this->gatewayReady()) {
            $response = Http::withBasicAuth(config('premium.razorpay.key_id'), config('premium.razorpay.key_secret'))->timeout(20)
                ->post('https://api.razorpay.com/v1/orders', ['amount' => (int) round($order->total_amount * 100), 'currency' => 'INR', 'receipt' => $order->order_no,
                    'notes' => ['plan' => $planKey, 'user_id' => (string) $user->id]]);
            if (!$response->successful()) {
                $order->update(['status' => 'failed', 'notes' => 'Razorpay order failed: HTTP ' . $response->status()]);
                throw new RuntimeException('The payment gateway is not responding. Please try again shortly.');
            }
            $order->update(['gateway_order_id' => $response->json('id')]);
        }
        return $order;
    }

    /** Razorpay checkout callback: the signature proves the payment belongs to this order. */
    public function verifyPayment(PremiumOrder $order, string $paymentId, string $signature): bool
    {
        $expected = hash_hmac('sha256', $order->gateway_order_id . '|' . $paymentId, (string) config('premium.razorpay.key_secret'));
        if (!$order->gateway_order_id || !hash_equals($expected, $signature)) {
            $order->update(['status' => 'failed', 'notes' => 'Payment signature did not match']);
            return false;
        }
        $order->update(['status' => 'paid', 'gateway_payment_id' => $paymentId, 'paid_at' => now()]);
        $this->activate($order);
        return true;
    }

    /** Adds the plan's months to the user's premium (from today, or from the current expiry if still active). */
    public function activate(PremiumOrder $order): PremiumOrder
    {
        if ($order->activated_at) return $order;
        return DB::transaction(function () use ($order) {
            $user = User::lockForUpdate()->findOrFail($order->user_id);
            $from = $user->premium_expires_at && $user->premium_expires_at->isFuture() ? $user->premium_expires_at : now();
            $until = $from->copy()->addMonths($order->months);
            $user->forceFill(['is_premium' => true, 'premium_started_at' => $user->premium_started_at ?? now(), 'premium_expires_at' => $until])->save();
            $order->update(['status' => 'activated', 'activated_at' => now(), 'premium_until' => $until]);
            return $order->fresh();
        });
    }
}
