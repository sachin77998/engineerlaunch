<?php

namespace App\Http\Controllers;

use App\Models\PremiumOrder;
use App\Services\PremiumPlans;
use Illuminate\Http\Request;


class PremiumController extends Controller
{
    public function __construct(private PremiumPlans $premium) {}
    public function index(Request $request)
    {
        $user = $request->user();
        return view('premium.index', ['plans' => $this->premium->plans(), 'features' => config('premium.features'), 'gateway' => $this->premium->gatewayReady(),'user' => $user, 'orders' => $user ? PremiumOrder::where('user_id', $user->id)->latest('id')->limit(10)->get() : collect(),]);
    }
    public function checkout(Request $request)
    {
        $data = $request->validate(['plan' => 'required|in:' . implode(',', array_keys(config('premium.plans')))]);
        try {
            $order = $this->premium->createOrder($request->user(), $data['plan']);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['plan' => $e->getMessage()]);
        }
        return redirect()->route('premium.pay', $order->order_no);
    }
    public function pay(Request $request, string $orderNo)
    {
        $order = PremiumOrder::where('order_no', $orderNo)->where('user_id', $request->user()->id)->firstOrFail();
        return view('premium.pay', ['order' => $order, 'plan' => $this->premium->plans()[$order->plan] ?? null, 'gateway' => $order->gateway === 'razorpay' && $this->premium->gatewayReady(),
            'keyId' => config('premium.razorpay.key_id')]);
    }
    public function verify(Request $request, string $orderNo)
    {
        $order = PremiumOrder::where('order_no', $orderNo)->where('user_id', $request->user()->id)->firstOrFail();
        $data = $request->validate(['razorpay_payment_id' => 'required|string|max:64', 'razorpay_order_id' => 'required|string|max:64', 'razorpay_signature' => 'required|string|max:128']);
        abort_unless($data['razorpay_order_id'] === $order->gateway_order_id, 422);
        if (!$this->premium->verifyPayment($order, $data['razorpay_payment_id'], $data['razorpay_signature'])) {
            return redirect()->route('premium.pay', $order->order_no)->withErrors(['payment' => 'We could not verify this payment. If money was debited, contact support with order ' . $order->order_no . '.']);
        }
        return redirect()->route('premium.index')->with('success', 'Payment received. Premium is active until ' . $order->fresh()->premium_until->format('d M Y') . '.');
    }
    public function adminOrders(Request $request)
    {
        $orders = PremiumOrder::with('user:id,name,email')->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))->latest('id')->paginate(30)->withQueryString();
        return view('premium.admin', ['orders' => $orders]);
    }

    public function adminActivate(PremiumOrder $order)
    {
        $order = $this->premium->activate($order);
        return back()->with('success', "Order {$order->order_no} activated: premium until {$order->premium_until->format('d M Y')}.");
    }
}
