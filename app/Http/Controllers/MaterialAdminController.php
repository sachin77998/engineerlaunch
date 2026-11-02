<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\MaterialFeedback;
use App\Models\MaterialOrder;
use App\Models\MaterialReview;
use App\Services\Materials\MaterialStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

// Owner's back office for the Home Building store: orders and their status, feedback, reviews and product prices.
class MaterialAdminController extends Controller
{
    public function __construct(private MaterialStore $store) {}

    public function index(Request $request)
    {
        $tab = in_array($request->query('tab'), ['orders', 'feedback', 'reviews', 'products'], true) ? $request->query('tab') : 'orders';
        $status = $request->query('status');
        $q = trim((string) $request->query('q'));
        $data = ['tab' => $tab, 'status' => $status, 'q' => $q,
            'counts' => MaterialOrder::selectRaw('status, count(*) n')->groupBy('status')->pluck('n', 'status'),
            'revenue' => MaterialOrder::where('payment_status', 'paid')->orWhere(fn ($w) => $w->where('payment_method', 'cod')->where('status', 'delivered'))->sum('total'),
            'openFeedback' => MaterialFeedback::where('status', 'open')->count()];
        $data['rows'] = match ($tab) {
            'orders' => MaterialOrder::with('items', 'user:id,name,email')->when($status, fn ($w) => $w->where('status', $status))
                ->when($q, fn ($w) => $w->where(fn ($x) => $x->where('order_no', 'like', "%$q%")->orWhere('phone', 'like', "%$q%")->orWhere('name', 'like', "%$q%")))->latest()->paginate(25)->withQueryString(),
            'feedback' => MaterialFeedback::with('material:id,handle,title', 'order:id,order_no')->orderByRaw("status = 'open' desc")->latest()->paginate(25)->withQueryString(),
            'reviews' => MaterialReview::with('material:id,handle,title', 'user:id,name')->latest()->paginate(25)->withQueryString(),
            'products' => Material::listed()->when($q, fn ($w) => $w->where('title', 'like', "%$q%"))->orderByDesc('sell_price')->orderBy('title')->paginate(40)->withQueryString(),
        };
        return view('materials.admin', $data);
    }

    public function updateOrder(Request $request, MaterialOrder $order)
    {
        $data = $request->validate(['status' => 'required|in:' . implode(',', array_keys(MaterialOrder::STATUSES)), 'tracking' => 'nullable|string|max:200', 'cod_paid' => 'nullable|boolean']);
        if ($data['status'] === 'cancelled' && $order->status !== 'cancelled') $this->store->cancel($order, 'owner');
        else $order->update(['status' => $data['status'], 'tracking' => $data['tracking'] ?? $order->tracking, 'delivered_at' => $data['status'] === 'delivered' ? ($order->delivered_at ?? now()) : $order->delivered_at]);
        if ($request->boolean('cod_paid') && $order->payment_method === 'cod' && $order->payment_status !== 'paid') $order->update(['payment_status' => 'paid', 'paid_at' => now()]);
        return back()->with('success', "Order {$order->order_no} updated.");
    }

    public function replyFeedback(Request $request, MaterialFeedback $feedback)
    {
        $data = $request->validate(['reply' => 'nullable|string|max:3000', 'status' => 'required|in:open,answered,closed']);
        $feedback->update($data);
        return back()->with('success', 'Feedback updated.');
    }

    public function moderateReview(Request $request, MaterialReview $review)
    {
        $review->update(['status' => $request->validate(['status' => 'required|in:published,hidden'])['status']]);
        return back()->with('success', 'Review ' . $review->status . '.');
    }

    public function updateProduct(Request $request, Material $material)
    {
        $data = $request->validate(['sell_price' => 'nullable|numeric|min:1|max:10000000', 'sellable' => 'nullable|boolean']);
        $material->update(['sell_price' => $data['sell_price'] ?? null, 'sellable' => $request->boolean('sellable')]);
        Cache::forget('materials:collections');
        Cache::forget('materials:stats');
        return back()->with('success', "Saved {$material->title}.");
    }
}
