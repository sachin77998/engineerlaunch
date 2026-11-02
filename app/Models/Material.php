<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Material extends Model
{
    protected $guarded = [];

    protected $casts = [
        'collections' => 'array', 'tags' => 'array', 'variants' => 'array', 'bulk_tiers' => 'array', 'images' => 'array', 'sell_price' => 'float', 'sellable' => 'boolean',
        'price' => 'float', 'mrp' => 'float', 'unit_size' => 'float', 'in_stock' => 'boolean', 'is_listed' => 'boolean',
        'source_created_at' => 'datetime', 'source_updated_at' => 'datetime', 'sitemap_lastmod' => 'datetime',
    ];

    public function prices(): HasMany
    {
        return $this->hasMany(MaterialPrice::class)->orderByDesc('recorded_on');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(MaterialReview::class)->where('status', 'published')->latest();
    }

    /** Products the store sells: listed, priced, not switched off by the owner. */
    public function scopeForSale(Builder $q): Builder
    {
        return $q->listed()->where('sellable', true);
    }

    /** Selling price of a pack. The owner's own price, when set, scales every pack by the same ratio. */
    public function packPrice(?array $variant = null): float
    {
        $base = (float) ($variant['price'] ?? $this->price);
        return $this->sell_price && $this->price ? round($base * $this->sell_price / $this->price, 2) : $base;
    }

    public function canBuy(): bool
    {
        return $this->sellable && $this->is_listed && $this->in_stock && $this->price > 0;
    }

    public function gallery(): array
    {
        return array_values(array_unique(array_filter(array_merge([$this->image_url], $this->images ?? []))));
    }

    public function scopeListed(Builder $q): Builder
    {
        return $q->where('is_listed', true)->whereNotNull('price');
    }

    public function getRouteKeyName(): string
    {
        return 'handle';
    }

    public function discountPercent(): int
    {
        return $this->mrp && $this->price && $this->mrp > $this->price ? (int) round(($this->mrp - $this->price) / $this->mrp * 100) : 0;
    }

    /** Bulk materials sold by weight, volume or length, where a price per unit means something. */
    public const UNIT_PRICED = ['cement', 'tiling', 'fevicol', 'waterproofing', 'painting', 'putty-primer', 'gypsum-drywall-false-ceiling', 'sand-plaster-mortars', 'wires'];

    /** Price per kg / L / m, when the pack size is known. */
    public function unitPrice(): ?float
    {
        return in_array($this->category, self::UNIT_PRICED, true) && $this->unit_size > 0 && $this->price ? round($this->price / $this->unit_size, 2) : null;
    }

    /** Unit price for buying $qty, after the store's bulk tiers. */
    public function priceFor(int $qty): float
    {
        $price = $this->packPrice();
        foreach ($this->bulk_tiers ?? [] as $tier) if ($qty >= $tier['qty'] && $this->packPrice(['price' => $tier['price']]) < $price) $price = $this->packPrice(['price' => $tier['price']]);
        return $price;
    }

    public function storeUrl(): string
    {
        return rtrim(config('materials.source.base'), '/') . '/products/' . $this->handle;
    }
}
