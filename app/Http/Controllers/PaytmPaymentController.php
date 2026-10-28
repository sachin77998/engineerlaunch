<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\PremiumOrder;
use App\Services\Payments\PaytmGateway;
use App\Services\PremiumPlans;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// Paytm checkout for premium orders. The amount always comes from the stored order, never from the browser, and an
// order is marked paid only after Paytm's Transaction Status API confirms success (the callback alone is not trusted).
class PaytmPaymentController extends Controller
{
    public function __construct(private PaytmGateway $paytm, private PremiumPlans $premium) {}

    /** Starts a payment attempt and sends the browser to Paytm's payment page. */
    public function initiate(Request $request, string $orderNo)
    {
        abort_unless($this->paytm->ready(), 503, 'Paytm is not configured.');
        $order = PremiumOrder::where('order_no', $orderNo)->where('user_id', $request->user()->id)->firstOrFail();
        if ($order->status !== 'created') return redirect()->route('premium.pay', $orderNo)->withErrors(['payment' => 'This order is already ' . $order->status . '.']);
        $amountPaise = (int) round($order->total_amount * 100);
        abort_if($amountPaise <= 0, 422, 'Invalid order amount.');

        $payment = Payment::create(['user_id' => $request->user()->id, 'order_id' => $order->order_no . '_' . strtoupper(Str::random(5)),
            'payable_type' => 'premium_order', 'payable_id' => $order->id, 'provider' => 'paytm', 'amount_paise' => $amountPaise]);
        try {
            $token = $this->paytm->initiate($payment->order_id, $amountPaise, 'CUST' . $request->user()->id, $request->user()->email);
        } catch (\RuntimeException $e) {
            $payment->update(['status' => 'failed', 'gateway_response' => ['error' => $e->getMessage()]]);
            report($e);
            return redirect()->route('premium.pay', $orderNo)->withErrors(['payment' => 'Paytm is not responding right now. Please try again shortly.']);
        }
        return view('payments.paytm-redirect', ['url' => $this->paytm->paymentPageUrl($payment->order_id), 'mid' => $this->paytm->mid(), 'orderId' => $payment->order_id, 'token' => $token]);
    }

    /** Paytm posts here after the customer pays or cancels (CSRF-exempt; authenticity comes from the checksum). */
    public function callback(Request $request)
    {
        $params = $request->post();
        $payment = Payment::where('order_id', (string) ($params['ORDERID'] ?? ''))->where('provider', 'paytm')->first();
        abort_unless($payment, 404);
        if (!$this->paytm->verifyCallback($params)) {
            $payment->update(['gateway_response' => ['callback' => $params, 'error' => 'checksum mismatch']]);
            return $this->finish($payment, 'We could not verify the response from Paytm. If money was debited, it will be confirmed or refunded automatically.');
        }
        $this->reconcile($payment, $params);
        return $this->finish($payment->fresh());
    }

    /** Polls the final status (for a payment left pending). */
    public function status(Request $request, string $orderId)
    {
        $payment = Payment::where('order_id', $orderId)->where('user_id', $request->user()->id)->firstOrFail();
        if ($payment->status === 'pending') $this->reconcile($payment);
        return response()->json(['order_id' => $payment->order_id, 'status' => $payment->fresh()->status]);
    }

    /** Asks Paytm for the final status and applies it once. */
    public function reconcile(Payment $payment, array $callback = []): void
    {
        $s = $this->paytm->status($payment->order_id);
        DB::transaction(function () use ($payment, $s, $callback) {
            $payment = Payment::lockForUpdate()->find($payment->id);
            if ($payment->status === 'success') return;
            $paidPaise = $s['amount'] !== null ? (int) round((float) $s['amount'] * 100) : null;
            $update = ['gateway_txn_id' => $s['txn_id'], 'bank_txn_id' => $s['bank_txn_id'], 'payment_mode' => $s['mode'],
                'gateway_response' => ['status' => $s['raw'], 'callback' => $callback ?: null]];
            if ($s['status'] === 'success' && $paidPaise === $payment->amount_paise) {
                $payment->update($update + ['status' => 'success', 'verified_at' => now()]);
                $order = PremiumOrder::lockForUpdate()->find($payment->payable_id);
                if ($order && $order->status === 'created') {
                    $order->update(['status' => 'paid', 'gateway' => 'paytm', 'gateway_order_id' => $payment->order_id, 'gateway_payment_id' => $s['txn_id'], 'paid_at' => now()]);
                    $this->premium->activate($order);
                }
            } elseif ($s['status'] === 'failed') {
                $payment->update($update + ['status' => 'failed']);
            } else {
                // Pending, unknown, or an amount that does not match: never mark paid.
                $payment->update($update);
            }
        });
    }

    private function finish(Payment $payment, ?string $error = null)
    {
        $order = PremiumOrder::find($payment->payable_id);
        $route = $order ? route('premium.pay', $order->order_no) : route('premium.index');
        return match (true) {
            $error !== null => redirect($route)->withErrors(['payment' => $error]),
            $payment->status === 'success' => redirect()->route('premium.index')->with('success', 'Payment received. Premium is active until ' . $order?->fresh()->premium_until?->format('d M Y') . '.'),
            $payment->status === 'failed' => redirect($route)->withErrors(['payment' => 'The payment did not go through. You can try again.']),
            default => redirect($route)->with('warning', 'Your payment is being confirmed by the bank. This page will update once Paytm confirms it.'),
        };
    }
}
