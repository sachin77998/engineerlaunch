<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\MaterialFeedback;
use App\Models\MaterialOrder;
use App\Models\MaterialReview;
use App\Models\Payment;
use App\Services\Materials\MaterialStore;
use App\Services\Payments\PaytmGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

// Home Building store: checkout, orders, online payment (Paytm) or pay on delivery, reviews and feedback.
// Order amounts always come from the stored order; payment success is confirmed with Paytm's status API (PaytmPaymentController).
class MaterialShopController extends Controller
{
    public function __construct(private MaterialStore $store, private PaytmGateway $paytm) {}

    public function checkout(Request $request)
    {
        $rows = $this->store->rows();
        if ($rows->isEmpty()) return redirect()->route('materials.list');
        $last = MaterialOrder::where('user_id', $request->user()->id)->latest()->first();
        return view('materials.checkout', ['rows' => $rows, 't' => $this->store->totals($rows), 'paytm' => $this->paytm->ready(), 'cod' => config('materials.store.cod'),
            'prefill' => $last?->only(['name', 'phone', 'address_line1', 'address_line2', 'city', 'state', 'pincode']) ?? ['name' => $request->user()->name, 'pincode' => session('materials.pin')],
            'states' => config('geography.states.India'), 'slots' => config('materials.store.delivery.slots')]);
    }

    public function place(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120', 'phone' => ['required', 'regex:/^[6-9]\d{9}$/'], 'address_line1' => 'required|string|max:200', 'address_line2' => 'nullable|string|max:200',
            'city' => 'required|string|max:80', 'state' => 'required|string|max:60', 'pincode' => ['required', 'regex:/^[1-9]\d{5}$/'],
            'slot' => 'nullable|string|in:' . implode(',', config('materials.store.delivery.slots')), 'notes' => 'nullable|string|max:500',
            'payment_method' => 'required|in:paytm,cod',
        ], ['phone.regex' => 'Enter a 10-digit mobile number.', 'pincode.regex' => 'Enter a 6-digit PIN code.']);
        if ($data['payment_method'] === 'paytm' && !$this->paytm->ready()) return back()->withInput()->withErrors(['payment_method' => 'Online payment is not available right now. Choose pay on delivery.']);
        $method = $data['payment_method'];
        unset($data['payment_method']);
        $order = $this->store->place($request->user(), $data, $method);
        if ($method === 'cod') return redirect()->route('materials.orders.show', $order)->with('success', 'Order placed. Pay ₹' . number_format($order->total) . ' on delivery.');
        return $this->startPayment($request, $order);
    }

    /** (Re)starts online payment for an unpaid order. */
    public function pay(Request $request, MaterialOrder $order)
    {
        abort_unless($order->user_id === $request->user()->id, 404);
        if ($order->payment_status === 'paid' || $order->status === 'cancelled') return redirect()->route('materials.orders.show', $order);
        return $this->startPayment($request, $order);
    }

    private function startPayment(Request $request, MaterialOrder $order)
    {
        abort_unless($this->paytm->ready(), 503, 'Paytm is not configured.');
        $amountPaise = (int) round($order->total * 100);
        abort_if($amountPaise <= 0, 422, 'Invalid order amount.');
        $order->update(['payment_method' => 'paytm']);
        $payment = Payment::create(['user_id' => $request->user()->id, 'order_id' => $order->order_no . '_' . strtoupper(Str::random(5)),
            'payable_type' => 'material_order', 'payable_id' => $order->id, 'provider' => 'paytm', 'amount_paise' => $amountPaise]);
        try {
            $token = $this->paytm->initiate($payment->order_id, $amountPaise, 'CUST' . $request->user()->id, $request->user()->email, $order->phone);
        } catch (\RuntimeException $e) {
            $payment->update(['status' => 'failed', 'gateway_response' => ['error' => $e->getMessage()]]);
            report($e);
            return redirect()->route('materials.orders.show', $order)->withErrors(['payment' => 'Paytm is not responding right now. Try again, or switch to pay on delivery.']);
        }
        return view('payments.paytm-redirect', ['url' => $this->paytm->paymentPageUrl($payment->order_id), 'mid' => $this->paytm->mid(), 'orderId' => $payment->order_id, 'token' => $token]);
    }

    /** Switches an unpaid online order to pay on delivery. */
    public function switchToCod(Request $request, MaterialOrder $order)
    {
        abort_unless($order->user_id === $request->user()->id, 404);
        $cod = config('materials.store.cod');
        if ($order->status !== 'created' || $order->payment_status === 'paid') return back();
        if (!$cod['enabled'] || $order->total > $cod['max_order']) return back()->withErrors(['payment' => 'Pay on delivery is available for orders up to ₹' . number_format($cod['max_order']) . '.']);
        $order->update(['payment_method' => 'cod', 'status' => 'confirmed']);
        return back()->with('success', 'Order confirmed. Pay on delivery.');
    }

    public function orders(Request $request)
    {
        return view('materials.orders', ['orders' => MaterialOrder::where('user_id', $request->user()->id)->with('items')->latest()->paginate(15)]);
    }

    public function order(Request $request, MaterialOrder $order)
    {
        abort_unless($order->user_id === $request->user()->id || $request->user()->isOwner(), 404);
        return view('materials.order', ['order' => $order->load('items.material'), 'payments' => Payment::where('payable_type', 'material_order')->where('payable_id', $order->id)->latest()->get(),
            'paytm' => $this->paytm->ready()]);
    }

    public function cancel(Request $request, MaterialOrder $order)
    {
        abort_unless($order->user_id === $request->user()->id, 404);
        $this->store->cancel($order);
        return back()->with('success', 'Order cancelled.' . ($order->payment_status === 'refunded' ? ' Your payment will be refunded to the same account.' : ''));
    }

    /** Re-adds an order's products to the cart. */
    public function reorder(Request $request, MaterialOrder $order)
    {
        abort_unless($order->user_id === $request->user()->id, 404);
        $items = $order->items->map(fn ($i) => ['handle' => $i->handle, 'qty' => $i->qty,
            'variant' => max(0, (int) collect($i->material?->variants ?? [])->search(fn ($v) => $v['title'] === $i->variant_title))])->all();
        $this->store->add($items);
        return redirect()->route('materials.list')->with('success', 'Items added to your cart.');
    }

    public function review(Request $request, Material $material)
    {
        $data = $request->validate(['rating' => 'required|integer|between:1,5', 'title' => 'nullable|string|max:120', 'body' => 'nullable|string|max:3000']);
        MaterialReview::updateOrCreate(['material_id' => $material->id, 'user_id' => $request->user()->id],
            $data + ['verified' => $this->store->purchased($request->user(), $material), 'status' => 'published']);
        return redirect(route('materials.show', $material) . '#reviews')->with('success', 'Thank you — your review is published.');
    }

    public function feedback(Request $request)
    {
        $data = $request->validate(['kind' => 'required|in:' . implode(',', array_keys(MaterialFeedback::KINDS)), 'message' => 'required|string|min:5|max:3000',
            'name' => 'nullable|string|max:120', 'email' => 'nullable|email|max:191', 'material' => 'nullable|string|max:191', 'order' => 'nullable|string|max:24']);
        $user = $request->user();
        if (!$user && empty($data['email'])) return back()->withErrors(['email' => 'Add your email so we can reply.'])->withInput();
        MaterialFeedback::create(['user_id' => $user?->id, 'kind' => $data['kind'], 'message' => $data['message'],
            'name' => $data['name'] ?? $user?->name, 'email' => $data['email'] ?? $user?->email,
            'material_id' => !empty($data['material']) ? Material::where('handle', $data['material'])->value('id') : null,
            'material_order_id' => !empty($data['order']) && $user ? MaterialOrder::where('order_no', $data['order'])->where('user_id', $user->id)->value('id') : null]);
        return back()->with('success', 'Thanks — we have your message and will reply by email.');
    }
}
