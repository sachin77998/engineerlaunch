<?php

namespace App\Services\Materials;

use App\Models\Material;
use App\Models\MaterialOrder;
use App\Models\MaterialReview;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * The Home Building store: cart (kept in the session so guests can shop), totals with bulk prices, the large-order
 * offer and delivery slabs, and orders. Prices always come from the database, never from the browser.
 */
class MaterialStore
{
    public function cart(): array
    {
        return session('materials.list', []);
    }

    public function cartCount(): int
    {
        return count($this->cart());
    }

    public function add(array $items): int
    {
        $cart = $this->cart();
        $known = Material::forSale()->whereIn('handle', collect($items)->pluck('handle'))->get()->keyBy('handle');
        $max = config('materials.store.max_qty');
        foreach ($items as $item) {
            $m = $known[$item['handle']] ?? null;
            $variant = (int) ($item['variant'] ?? 0);
            if (!$m || !isset(($m->variants ?? [])[$variant]) && $variant !== 0) continue;
            $key = $m->handle . '#' . $variant;
            $cart[$key] = ['handle' => $m->handle, 'variant' => $variant, 'qty' => min($max, ($cart[$key]['qty'] ?? 0) + (int) $item['qty'])];
        }
        session(['materials.list' => $cart]);
        return count($cart);
    }

    public function update(array $quantities): void
    {
        $cart = $this->cart();
        foreach ($quantities as $key => $qty) {
            if (!isset($cart[$key])) continue;
            if ((int) $qty <= 0) unset($cart[$key]); else $cart[$key]['qty'] = min(config('materials.store.max_qty'), (int) $qty);
        }
        session(['materials.list' => $cart]);
    }

    public function clear(): void
    {
        session()->forget('materials.list');
    }

    /** Cart lines priced now: pack price, bulk price for the quantity, total, saving and the next bulk tier. */
    public function rows(?array $cart = null): Collection
    {
        $cart ??= $this->cart();
        $materials = Material::whereIn('handle', collect($cart)->pluck('handle'))->get()->keyBy('handle');
        return collect($cart)->map(function ($item, $key) use ($materials) {
            $m = $materials[$item['handle']] ?? null;
            if (!$m) return null;
            $variant = ($m->variants ?? [])[$item['variant']] ?? null;
            $single = $m->packPrice($variant);
            // Bulk tiers are set for the product's default pack; other packs sell at their own price.
            $tiered = !$variant || count($m->variants) === 1 || $variant['price'] == $m->price;
            $unit = $tiered ? min($single, $m->priceFor($item['qty'])) : $single;
            $available = $m->canBuy() && (!$variant || $variant['available']);
            return ['key' => $key, 'm' => $m, 'variant' => $variant, 'variant_index' => $item['variant'], 'qty' => $item['qty'], 'single' => $single, 'unit' => $unit,
                'mrp' => isset($variant['mrp']) ? $m->packPrice(['price' => $variant['mrp']]) : null, 'available' => $available,
                'total' => round($unit * $item['qty'], 2), 'saving' => round(($single - $unit) * $item['qty'], 2),
                'next' => $tiered ? collect($m->bulk_tiers)->first(fn ($t) => $t['qty'] > $item['qty']) : null];
        })->filter()->values();
    }

    /** Subtotal (after bulk prices), offer discount, delivery fee and total for the lines that can be bought. */
    public function totals(Collection $rows): array
    {
        $buy = $rows->where('available', true);
        $subtotal = round($buy->sum('total'), 2);
        $offer = config('materials.store.offer');
        $discount = $subtotal >= $offer['min_order'] ? round($subtotal * $offer['percent'] / 100, 2) : 0;
        $fee = $subtotal > 0 ? $this->deliveryFee($subtotal - $discount) : 0;
        return ['subtotal' => $subtotal, 'bulk_saving' => round($buy->sum('saving'), 2), 'discount' => $discount, 'delivery_fee' => $fee,
            'total' => round($subtotal - $discount + $fee, 2), 'mrp_saving' => round($buy->sum(fn ($r) => max(0, (($r['mrp'] ?? $r['single']) - $r['single']) * $r['qty'])), 2),
            'to_offer' => max(0, $offer['min_order'] - $subtotal), 'to_free' => $this->toFreeDelivery($subtotal - $discount), 'unavailable' => $rows->where('available', false)->count()];
    }

    public function deliveryFee(float $amount): float
    {
        foreach (config('materials.store.delivery.slabs') as [$from, $fee]) if ($amount >= $from) return (float) $fee;
        return 0;
    }

    private function toFreeDelivery(float $amount): float
    {
        $free = collect(config('materials.store.delivery.slabs'))->first(fn ($s) => $s[1] == 0);
        return $free && $amount < $free[0] ? $free[0] - $amount : 0;
    }

    public function serviceable(string $pin): bool
    {
        $prefixes = config('materials.store.delivery.serviceable_prefixes');
        return !$prefixes || collect($prefixes)->contains(fn ($p) => str_starts_with($pin, (string) $p));
    }

    public function express(?string $pin): bool
    {
        $pins = config('materials.store.delivery.express_pins') ?: Cache::get('materials:pins', []);
        return $pin && in_array($pin, $pins, true);
    }

    /** Places the order from the session cart and empties it. */
    public function place(User $user, array $address, string $method): MaterialOrder
    {
        $rows = $this->rows();
        if ($rows->isEmpty()) throw ValidationException::withMessages(['cart' => 'Your cart is empty.']);
        if ($n = $rows->where('available', false)->count()) throw ValidationException::withMessages(['cart' => "$n item(s) in your cart are out of stock. Remove them to continue."]);
        if (!$this->serviceable($address['pincode'])) throw ValidationException::withMessages(['pincode' => 'We do not deliver to PIN ' . $address['pincode'] . ' yet.']);
        $t = $this->totals($rows);
        $cod = config('materials.store.cod');
        if ($method === 'cod' && (!$cod['enabled'] || $t['total'] > $cod['max_order'])) throw ValidationException::withMessages(['payment_method' => 'Pay on delivery is available for orders up to ₹' . number_format($cod['max_order']) . '. Please pay online.']);

        $order = DB::transaction(function () use ($user, $address, $method, $rows, $t) {
            $order = MaterialOrder::create($address + [
                'order_no' => 'HB' . now()->format('ymd') . strtoupper(Str::random(6)), 'user_id' => $user->id,
                'status' => $method === 'cod' ? 'confirmed' : 'created', 'payment_method' => $method, 'payment_status' => 'pending',
                'subtotal' => $t['subtotal'], 'bulk_saving' => $t['bulk_saving'], 'discount' => $t['discount'], 'delivery_fee' => $t['delivery_fee'], 'total' => $t['total'],
            ]);
            foreach ($rows as $r) {
                $order->items()->create(['material_id' => $r['m']->id, 'handle' => $r['m']->handle, 'title' => $r['m']->title, 'variant_title' => $r['variant']['title'] ?? null,
                    'image_url' => $r['m']->image_url, 'qty' => $r['qty'], 'unit_price' => $r['unit'], 'mrp' => $r['mrp'], 'total' => $r['total']]);
            }
            return $order;
        });
        $this->clear();
        return $order;
    }

    /** Called once Paytm confirms the payment (amount already checked against the order). */
    public function markPaid(MaterialOrder $order, string $txnId): void
    {
        if ($order->payment_status === 'paid') return;
        $order->update(['payment_status' => 'paid', 'paid_at' => now(), 'status' => $order->status === 'created' ? 'confirmed' : $order->status, 'tracking' => $order->tracking ?: 'Paytm ' . $txnId]);
    }

    public function cancel(MaterialOrder $order, string $by = 'customer'): void
    {
        if (!$order->cancellable() && $by === 'customer') throw ValidationException::withMessages(['order' => 'This order can no longer be cancelled.']);
        $order->update(['status' => 'cancelled', 'cancelled_at' => now(), 'payment_status' => $order->payment_status === 'paid' ? 'refunded' : $order->payment_status]);
    }

    /** True when the user has a delivered order with this product (reviews are marked "Verified purchase"). */
    public function purchased(User $user, Material $material): bool
    {
        return MaterialOrder::where('user_id', $user->id)->where('status', 'delivered')->whereHas('items', fn ($q) => $q->where('material_id', $material->id))->exists();
    }

    /** Average, count and 5→1 star distribution of published reviews. */
    public function ratings(Material $material): array
    {
        $counts = MaterialReview::where('material_id', $material->id)->where('status', 'published')->select('rating', DB::raw('count(*) as n'))->groupBy('rating')->pluck('n', 'rating');
        $total = (int) $counts->sum();
        return ['count' => $total, 'average' => $total ? round($counts->map(fn ($n, $r) => $n * $r)->sum() / $total, 1) : null,
            'bars' => collect([5, 4, 3, 2, 1])->mapWithKeys(fn ($r) => [$r => (int) ($counts[$r] ?? 0)])->all()];
    }
}
