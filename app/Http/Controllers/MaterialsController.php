<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Services\Materials\MaterialCollections;
use App\Services\Materials\MaterialPricing;
use App\Services\Materials\MaterialStore;
use App\Services\Trading\KycService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// Home Building store: every category and product of the synced catalogue, price lists by brand and category,
// the build-cost calculator, room estimators and the cart (kept in the session). Checkout and orders: MaterialShopController.
class MaterialsController extends Controller
{
    private const SORTS = ['popular' => 'Relevance', 'discount' => 'Biggest discount', 'price_asc' => 'Price: low to high', 'price_desc' => 'Price: high to low', 'new' => 'Newest', 'rating' => 'Top rated'];

    public function __construct(private MaterialPricing $pricing, private MaterialStore $store, private MaterialCollections $collections) {}

    public function index()
    {
        $base = fn () => Material::forSale()->where('in_stock', true)->withAvg('reviews', 'rating')->withCount('reviews');
        return view('materials.index', ['stats' => $this->stats(), 'collections' => $this->collections->byGroup(),
            'deals' => $base()->whereColumn('mrp', '>', 'price')->orderByRaw('(mrp - price) / mrp desc')->limit(8)->get(),
            'fresh' => $base()->orderByDesc('source_created_at')->limit(8)->get(),
            'bulk' => $base()->whereJsonLength('bulk_tiers', '>', 0)->get()->sortByDesc(fn ($m) => $this->bulkSaving($m))->take(8)->values(),
        ]);
    }

    public function categories()
    {
        return view('materials.categories', ['collections' => $this->collections->byGroup(), 'stats' => $this->stats()]);
    }

    public function category(Request $request, string $slug)
    {
        $cat = $this->collections->find($slug) ?? abort(404);
        return $this->listing($request, $cat['title'], fn ($q) => $q->whereJsonContains('collections', $slug), ['category' => $slug, 'group' => $cat['group']]);
    }

    public function search(Request $request)
    {
        $q = trim((string) $request->query('q'));
        return $this->listing($request, $q !== '' ? "Results for “{$q}”" : 'All products', fn ($query) => $q === '' ? $query : $this->matchText($query, $q), ['q' => $q]);
    }

    /** Search-as-you-type suggestions for the header box. */
    public function suggest(Request $request)
    {
        $q = trim((string) $request->query('q'));
        if (mb_strlen($q) < 2) return response()->json(['products' => [], 'categories' => []]);
        $products = $this->matchText(Material::forSale(), $q)->orderByDesc('in_stock')->orderByDesc('quantity_available')->limit(6)->get()
            ->map(fn ($m) => ['title' => $m->title, 'url' => route('materials.show', $m), 'image' => $m->image_url, 'price' => $m->packPrice()]);
        $cats = collect($this->collections->all())->filter(fn ($c) => Str::contains(Str::lower($c['title']), Str::lower($q)))->take(4)
            ->map(fn ($c, $h) => ['title' => $c['title'], 'url' => route('materials.category', $h), 'count' => $c['count']])->values();
        return response()->json(['products' => $products, 'categories' => $cats]);
    }

    public function show(Material $material)
    {
        abort_unless($material->is_listed, 404);
        $history = $material->prices()->limit(60)->get();
        $similar = Material::forSale()->where('id', '!=', $material->id)->where('product_type', $material->product_type)->orderByDesc('in_stock')->orderBy('price')->limit(8)->get();
        if ($similar->count() < 4 && $material->category) $similar = $similar->merge(Material::forSale()->where('id', '!=', $material->id)->where('category', $material->category)->whereNotIn('id', $similar->pluck('id'))->limit(8 - $similar->count())->get());
        $user = auth()->user();
        return view('materials.show', ['m' => $material, 'history' => $history, 'similar' => $similar,
            'reviews' => $material->reviews()->with('user:id,name')->limit(30)->get(), 'ratings' => $this->store->ratings($material),
            'mine' => $user ? $material->reviews()->where('user_id', $user->id)->first() : null,
            'cats' => collect($material->collections ?? [])->map(fn ($h) => [$h, $this->collections->find($h)])->filter(fn ($c) => $c[1])->take(4)]);
    }

    public function priceLists()
    {
        $brands = Material::forSale()->whereNotNull('brand_slug')->select('brand', 'brand_slug', DB::raw('count(*) as n'), DB::raw('min(price) as low'))->groupBy('brand', 'brand_slug')->orderByDesc('n')->get();
        return view('materials.price-lists', ['collections' => $this->collections->all(), 'brands' => $brands]);
    }

    public function priceList(string $slug)
    {
        $cat = $this->collections->find($slug);
        $query = Material::forSale();
        if ($cat) {
            $title = $cat['title'];
            $query->whereJsonContains('collections', $slug);
        } else {
            $title = Material::where('brand_slug', $slug)->value('brand') ?? abort(404);
            $query->where('brand_slug', $slug);
        }
        $items = $query->orderBy('product_type')->orderBy('price')->get();
        abort_if($items->isEmpty(), 404);
        // Price a week ago (or the earliest since then) for the change column.
        $then = DB::table('material_prices')->whereIn('material_id', $items->pluck('id'))->where('recorded_on', '<=', today()->subDays(7))
            ->select('material_id', DB::raw('max(recorded_on) as d'))->groupBy('material_id')->get()
            ->mapWithKeys(fn ($r) => [$r->material_id => DB::table('material_prices')->where('material_id', $r->material_id)->where('recorded_on', $r->d)->value('price')]);
        return view('materials.price-list', ['title' => $title, 'slug' => $slug, 'isCategory' => (bool) $cat, 'items' => $items, 'then' => $then,
            'updated' => $items->max('updated_at')]);
    }

    public function calculator(Request $request)
    {
        $in = $request->validate(['state' => 'nullable|string|max:60', 'city' => 'nullable|string|max:80', 'area' => 'nullable|numeric|min:100|max:500000', 'unit' => 'nullable|in:sqft,sqm']);
        $lines = $this->pricing->calculatorLines();
        $factors = collect(config('materials.city_tiers'))->map(fn ($t) => ['factor' => $t[0], 'cities' => $t[1]]);
        return view('materials.calculator', ['lines' => $lines, 'in' => $in, 'factors' => $factors, 'states' => config('geography.states.India'),
            'cities' => collect(config('geography.cities'))->filter(fn ($c, $k) => str_starts_with($k, 'India|'))->mapWithKeys(fn ($c, $k) => [substr($k, 6) => $c])]);
    }

    public function estimator(string $type)
    {
        $meta = config("materials.estimators.$type") ?? abort(404);
        $c = config('materials.calculator');
        $packs = match ($type) {
            'paint' => ['paint' => $this->pricing->packs($c['paint'][3]['live']), 'putty' => $this->pricing->packs($c['putty'][3]['live']), 'primer' => $this->pricing->packs($c['primer'][3]['live'])],
            'tiles' => ['adhesive' => $this->pricing->packs($c['tile_adhesive'][3]['live']), 'grout' => $this->pricing->packs(['collections' => ['tiling'], 'type' => 'grout', 'per' => 'kg'])],
            'waterproofing' => ['coating' => $this->pricing->packs(['collections' => ['waterproofing'], 'type' => ['coating', 'waterproof', 'membrane'], 'per' => 'L'])],
        };
        return view('materials.estimator', ['type' => $type, 'meta' => $meta, 'packs' => $packs, 'cfg' => config("materials.$type")]);
    }

    public function pincode(Request $request, KycService $kyc)
    {
        $pin = (string) $request->query('pin');
        if (!preg_match('/^[1-9]\d{5}$/', $pin)) return response()->json(['error' => 'Enter a 6-digit PIN code.'], 422);
        $place = $kyc->pincode($pin);
        $district = isset($place['district']) ? trim(preg_replace('/\s+(Urban|Rural)$/i', '', $place['district'])) : null;
        if ($district && Str::lower($district) === 'bangalore') $district = 'Bengaluru';
        $fast = $this->store->express($pin);
        $ok = $this->store->serviceable($pin);
        session(['materials.pin' => $pin]);
        return response()->json(['pin' => $pin, 'district' => $district, 'state' => $place['state'] ?? null, 'fast' => $fast && $ok, 'serviceable' => $ok,
            'message' => !$ok ? 'We do not deliver to this PIN code yet.'
                : ($fast ? 'Delivery in ' . config('materials.store.delivery.express_label') . '. Pay online or on delivery.' : 'We deliver here. Choose a delivery slot at checkout.'),
            'factor' => $this->pricing->cityFactor($district)]);
    }

    // ---- Cart (session) ----

    public function list()
    {
        $rows = $this->store->rows();
        return view('materials.list', ['rows' => $rows, 't' => $this->store->totals($rows)]);
    }

    public function listAdd(Request $request)
    {
        $data = $request->validate(['items' => 'required|array|max:60', 'items.*.handle' => 'required|string|max:191', 'items.*.qty' => 'required|integer|min:1|max:100000', 'items.*.variant' => 'nullable|integer|min:0|max:500']);
        $count = $this->store->add($data['items']);
        if ($request->wantsJson()) return response()->json(['count' => $count]);
        return $request->boolean('buy_now') ? redirect()->route('materials.checkout') : back()->with('success', 'Added to your cart.')->with('mt-added', true);
    }

    public function listUpdate(Request $request)
    {
        $data = $request->validate(['qty' => 'array', 'qty.*' => 'integer|min:0|max:100000']);
        $this->store->update($data['qty'] ?? []);
        return redirect()->route('materials.list');
    }

    public function listClear()
    {
        $this->store->clear();
        return redirect()->route('materials.list');
    }

    public function listExport()
    {
        $rows = $this->store->rows();
        $out = fopen('php://temp', 'r+');
        fputcsv($out, ['Product', 'Pack', 'Brand', 'Quantity', 'Unit price (Rs)', 'Total (Rs)', 'Bulk saving (Rs)', 'Link']);
        foreach ($rows as $r) fputcsv($out, [$r['m']->title, $r['variant']['title'] ?? '', $r['m']->brand, $r['qty'], $r['unit'], $r['total'], $r['saving'], route('materials.show', $r['m'])]);
        $t = $this->store->totals($rows);
        fputcsv($out, ['Total', '', '', '', '', $t['total'], $t['bulk_saving'], '']);
        rewind($out);
        return response(stream_get_contents($out), 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="material-cart-' . today()->toDateString() . '.csv"']);
    }

    // ---- helpers ----

    private function listing(Request $request, string $title, callable $scope, array $context)
    {
        $f = $request->validate(['brand' => 'nullable|array', 'brand.*' => 'string|max:120', 'type' => 'nullable|string|max:120', 'min' => 'nullable|numeric|min:0', 'max' => 'nullable|numeric|min:0',
            'stock' => 'nullable|boolean', 'bulk' => 'nullable|boolean', 'sort' => 'nullable|in:' . implode(',', array_keys(self::SORTS))]);
        $base = $scope(Material::forSale());
        $facets = (clone $base)->select('brand', 'brand_slug', 'product_type')->get();
        $q = (clone $base)->withAvg('reviews', 'rating')->withCount('reviews')
            ->when($f['brand'] ?? null, fn ($q, $b) => $q->whereIn('brand_slug', $b))
            ->when($f['type'] ?? null, fn ($q, $t) => $q->where('product_type', $t))
            ->when(isset($f['min']), fn ($q) => $q->where('price', '>=', $f['min']))
            ->when(isset($f['max']), fn ($q) => $q->where('price', '<=', $f['max']))
            ->when($f['stock'] ?? false, fn ($q) => $q->where('in_stock', true))
            ->when($f['bulk'] ?? false, fn ($q) => $q->whereJsonLength('bulk_tiers', '>', 0));
        match ($f['sort'] ?? 'popular') {
            'discount' => $q->orderByRaw('case when mrp > price then (mrp - price) / mrp else 0 end desc'),
            'price_asc' => $q->orderBy('price'),
            'price_desc' => $q->orderByDesc('price'),
            'new' => $q->orderByDesc('source_created_at'),
            'rating' => $q->orderByDesc('reviews_avg_rating')->orderByDesc('reviews_count'),
            default => $q->orderByDesc('in_stock')->orderByDesc('quantity_available'),
        };
        return view('materials.listing', ['title' => $title, 'items' => $q->paginate(24)->withQueryString(), 'f' => $f, 'context' => $context, 'sorts' => self::SORTS,
            'allCollections' => $this->collections->all(),
            'brands' => $facets->whereNotNull('brand_slug')->groupBy('brand_slug')->map(fn ($g) => [$g->first()->brand, $g->count()])->sortByDesc(fn ($b) => $b[1]),
            'types' => $facets->whereNotNull('product_type')->countBy('product_type')->sortDesc()]);
    }

    private function matchText($query, string $text)
    {
        foreach (array_slice(preg_split('/\s+/', Str::lower($text)), 0, 6) as $word) {
            $like = '%' . addcslashes($word, '%_\\') . '%';
            $query->where(fn ($q) => $q->where('title', 'like', $like)->orWhere('brand', 'like', $like)->orWhere('product_type', 'like', $like));
        }
        return $query;
    }

    private function stats(): array
    {
        return Cache::remember('materials:stats', now()->addHour(), fn () => [
            'products' => Material::forSale()->count(), 'in_stock' => Material::forSale()->where('in_stock', true)->count(),
            'brands' => Material::forSale()->distinct('brand_slug')->count('brand_slug'), 'categories' => count($this->collections->all()),
            'updated' => Material::forSale()->max('updated_at'),
        ]);
    }

    private function bulkSaving(Material $m): int
    {
        $best = collect($m->bulk_tiers)->min('price');
        return $best && $m->price ? (int) round(($m->price - $best) / $m->price * 100) : 0;
    }
}
