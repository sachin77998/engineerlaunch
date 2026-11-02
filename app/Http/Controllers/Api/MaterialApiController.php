<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\MaterialFeedback;
use App\Models\MaterialOrder;
use App\Models\MaterialReview;
use App\Services\Materials\MaterialCollections;
use App\Services\Materials\MaterialStore;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

// Home Building store API: categories, products (description, images, packs, bulk prices, ratings), reviews,
// feedback, a price quote for any cart, and the signed-in customer's orders (Sanctum token).
class MaterialApiController extends Controller
{
    public function __construct(private MaterialStore $store, private MaterialCollections $collections) {}

    public function categories()
    {
        return response()->json(['data' => collect($this->collections->all())->map(fn ($c, $h) => ['handle' => $h, 'title' => $c['title'], 'group' => $c['group'],
            'group_title' => config('materials.groups.' . $c['group']), 'products' => $c['count'], 'image' => $c['image'], 'url' => route('materials.category', $h)])->values()]);
    }

    public function products(Request $request)
    {
        $f = $request->validate(['q' => 'nullable|string|max:100', 'category' => 'nullable|string|max:120', 'brand' => 'nullable|string|max:120', 'type' => 'nullable|string|max:120',
            'min' => 'nullable|numeric|min:0', 'max' => 'nullable|numeric|min:0', 'in_stock' => 'nullable|boolean', 'sort' => 'nullable|in:popular,price_asc,price_desc,discount,new', 'per_page' => 'nullable|integer|min:1|max:100']);
        $q = Material::forSale()->withAvg('reviews', 'rating')->withCount('reviews')
            ->when($f['category'] ?? null, fn ($w, $c) => $w->whereJsonContains('collections', $c))
            ->when($f['brand'] ?? null, fn ($w, $b) => $w->where('brand_slug', Str::slug($b)))
            ->when($f['type'] ?? null, fn ($w, $t) => $w->where('product_type', $t))
            ->when(isset($f['min']), fn ($w) => $w->where('price', '>=', $f['min']))
            ->when(isset($f['max']), fn ($w) => $w->where('price', '<=', $f['max']))
            ->when($f['in_stock'] ?? false, fn ($w) => $w->where('in_stock', true));
        foreach (array_slice(preg_split('/\s+/', Str::lower(trim($f['q'] ?? '')), -1, PREG_SPLIT_NO_EMPTY), 0, 6) as $word) {
            $like = '%' . addcslashes($word, '%_\\') . '%';
            $q->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('brand', 'like', $like)->orWhere('product_type', 'like', $like));
        }
        match ($f['sort'] ?? 'popular') {
            'price_asc' => $q->orderBy('price'), 'price_desc' => $q->orderByDesc('price'), 'new' => $q->orderByDesc('source_created_at'),
            'discount' => $q->orderByRaw('case when mrp > price then (mrp - price) / mrp else 0 end desc'),
            default => $q->orderByDesc('in_stock')->orderByDesc('quantity_available'),
        };
        $page = $q->paginate($f['per_page'] ?? 24)->withQueryString();
        return response()->json(['data' => collect($page->items())->map(fn ($m) => $this->summary($m)),
            'meta' => ['page' => $page->currentPage(), 'pages' => $page->lastPage(), 'total' => $page->total(), 'per_page' => $page->perPage()]]);
    }

    public function product(Material $material)
    {
        abort_unless($material->is_listed && $material->sellable, 404);
        $material->loadAvg('reviews', 'rating')->loadCount('reviews');
        return response()->json(['data' => $this->summary($material) + [
            'description_html' => $material->description, 'summary' => $material->summary, 'images' => $material->gallery(), 'tags' => $material->tags,
            'categories' => collect($material->collections)->filter(fn ($h) => $this->collections->find($h))->values(),
            'packs' => collect($material->variants)->map(fn ($v, $i) => ['index' => $i, 'title' => $v['title'], 'price' => $material->packPrice($v),
                'mrp' => isset($v['mrp']) ? $material->packPrice(['price' => $v['mrp']]) : null, 'size' => $v['size'], 'unit' => $v['unit'], 'available' => $v['available']])->values(),
            'bulk_prices' => collect($material->bulk_tiers)->map(fn ($t) => ['min_qty' => $t['qty'], 'price' => $material->packPrice(['price' => $t['price']])])->values(),
            'rating_breakdown' => $this->store->ratings($material),
            'price_history' => $material->prices()->limit(90)->get(['recorded_on', 'price', 'mrp']),
        ]]);
    }

    public function reviews(Material $material)
    {
        $page = $material->reviews()->with('user:id,name')->paginate(20);
        return response()->json(['data' => collect($page->items())->map(fn ($r) => ['id' => $r->id, 'rating' => $r->rating, 'title' => $r->title, 'body' => $r->body,
            'verified_purchase' => $r->verified, 'author' => Str::of($r->user?->name ?? 'Customer')->before(' '), 'date' => $r->created_at->toDateString()]),
            'rating' => $this->store->ratings($material), 'meta' => ['page' => $page->currentPage(), 'pages' => $page->lastPage(), 'total' => $page->total()]]);
    }

    public function storeReview(Request $request, Material $material)
    {
        $data = $request->validate(['rating' => 'required|integer|between:1,5', 'title' => 'nullable|string|max:120', 'body' => 'nullable|string|max:3000']);
        $review = MaterialReview::updateOrCreate(['material_id' => $material->id, 'user_id' => $request->user()->id],
            $data + ['verified' => $this->store->purchased($request->user(), $material), 'status' => 'published']);
        return response()->json(['data' => ['id' => $review->id, 'rating' => $review->rating, 'verified_purchase' => $review->verified]], 201);
    }

    public function feedback(Request $request)
    {
        $data = $request->validate(['kind' => 'required|in:' . implode(',', array_keys(MaterialFeedback::KINDS)), 'message' => 'required|string|min:5|max:3000',
            'name' => 'nullable|string|max:120', 'email' => 'nullable|email|max:191', 'product' => 'nullable|string|max:191']);
        $user = $request->user('sanctum');
        if (!$user && empty($data['email'])) return response()->json(['message' => 'Email is required.'], 422);
        $fb = MaterialFeedback::create(['user_id' => $user?->id, 'kind' => $data['kind'], 'message' => $data['message'], 'name' => $data['name'] ?? $user?->name,
            'email' => $data['email'] ?? $user?->email, 'material_id' => !empty($data['product']) ? Material::where('handle', $data['product'])->value('id') : null]);
        return response()->json(['data' => ['id' => $fb->id, 'status' => $fb->status]], 201);
    }

    /** Prices a cart without storing it: [{handle, qty, pack}] → lines with bulk prices, offer, delivery fee and total. */
    public function quote(Request $request)
    {
        $data = $request->validate(['items' => 'required|array|max:60', 'items.*.handle' => 'required|string|max:191', 'items.*.qty' => 'required|integer|min:1|max:100000', 'items.*.pack' => 'nullable|integer|min:0|max:500']);
        $cart = collect($data['items'])->mapWithKeys(fn ($i) => [$i['handle'] . '#' . ($i['pack'] ?? 0) => ['handle' => $i['handle'], 'variant' => (int) ($i['pack'] ?? 0), 'qty' => $i['qty']]])->all();
        $rows = $this->store->rows($cart);
        return response()->json(['lines' => $rows->map(fn ($r) => ['handle' => $r['m']->handle, 'title' => $r['m']->title, 'pack' => $r['variant']['title'] ?? null, 'qty' => $r['qty'],
            'unit_price' => $r['unit'], 'total' => $r['total'], 'bulk_saving' => $r['saving'], 'available' => $r['available']]), 'totals' => $this->store->totals($rows)]);
    }

    public function orders(Request $request)
    {
        $page = MaterialOrder::where('user_id', $request->user()->id)->with('items')->latest()->paginate(20);
        return response()->json(['data' => collect($page->items())->map(fn ($o) => $this->orderJson($o)), 'meta' => ['page' => $page->currentPage(), 'pages' => $page->lastPage(), 'total' => $page->total()]]);
    }

    public function order(Request $request, MaterialOrder $order)
    {
        abort_unless($order->user_id === $request->user()->id, 404);
        return response()->json(['data' => $this->orderJson($order->load('items'))]);
    }

    private function summary(Material $m): array
    {
        $price = $m->packPrice();
        $mrp = $m->mrp ? $m->packPrice(['price' => $m->mrp]) : null;
        return ['handle' => $m->handle, 'title' => $m->title, 'brand' => $m->brand, 'type' => $m->product_type, 'image' => $m->image_url,
            'price' => $price, 'mrp' => $mrp && $mrp > $price ? $mrp : null, 'discount_percent' => $m->discountPercent(), 'in_stock' => $m->canBuy(),
            'packs' => count($m->variants ?? []), 'has_bulk_prices' => (bool) $m->bulk_tiers, 'unit_price' => $m->unitPrice(), 'unit' => $m->unitPrice() ? $m->unit : null,
            'rating' => isset($m->reviews_avg_rating) ? round((float) $m->reviews_avg_rating, 1) : null, 'reviews' => $m->reviews_count ?? null,
            'url' => route('materials.show', $m)];
    }

    private function orderJson(MaterialOrder $o): array
    {
        return ['order_no' => $o->order_no, 'status' => $o->status, 'status_label' => $o->statusLabel(), 'payment_method' => $o->payment_method, 'payment_status' => $o->payment_status,
            'subtotal' => $o->subtotal, 'discount' => $o->discount, 'delivery_fee' => $o->delivery_fee, 'total' => $o->total, 'placed_at' => $o->created_at->toIso8601String(),
            'delivery' => ['name' => $o->name, 'city' => $o->city, 'pincode' => $o->pincode, 'slot' => $o->slot, 'tracking' => $o->tracking],
            'items' => $o->items->map(fn ($i) => ['handle' => $i->handle, 'title' => $i->title, 'pack' => $i->variant_title, 'qty' => $i->qty, 'unit_price' => $i->unit_price, 'total' => $i->total])];
    }
}
