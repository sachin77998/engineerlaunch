<?php

namespace App\Models\Property;

use App\Services\Property\PropertyFormat;
use Illuminate\Database\Eloquent\Builder as Query;
use Illuminate\Database\Eloquent\Model;

/** A building or society (new launch, under construction or ready) with its photo gallery, offers and listings. */
class Project extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['configurations' => 'array', 'amenities' => 'array', 'visit_days' => 'array', 'is_featured' => 'boolean', 'is_active' => 'boolean',
        'launched_on' => 'date', 'possession_on' => 'date', 'price_min' => 'float', 'price_max' => 'float', 'land_acres' => 'float'];

    public function getRouteKeyName(): string { return 'slug'; }

    public function builder() { return $this->belongsTo(Builder::class); }

    public function images() { return $this->hasMany(ProjectImage::class)->orderByDesc('is_cover')->orderBy('sort')->orderBy('id'); }

    public function offers() { return $this->hasMany(ProjectOffer::class)->latest(); }

    public function liveOffers()
    {
        return $this->offers()->where('is_active', true)->where(fn ($q) => $q->whereNull('valid_until')->orWhere('valid_until', '>=', today()));
    }

    public function properties() { return $this->hasMany(Property::class); }

    public function units() { return $this->hasMany(ProjectUnit::class)->orderBy('sort')->orderBy('area_sqft'); }

    public function highlights() { return $this->hasMany(ProjectHighlight::class)->orderBy('sort')->orderBy('id'); }

    public function videos() { return $this->hasMany(ProjectVideo::class)->orderBy('sort')->orderBy('id'); }

    public function faqs() { return $this->hasMany(ProjectFaq::class)->orderBy('sort')->orderBy('id'); }

    public function charges() { return $this->hasMany(ProjectCharge::class)->orderBy('sort')->orderBy('id'); }

    public function leads() { return $this->hasMany(ProjectLead::class)->latest(); }

    public function logoUrl(): ?string { return $this->logo_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($this->logo_path) : null; }

    /** The number buyers reach: the project's own, else the builder's. */
    public function phone(): ?string { return $this->contact_phone ?: $this->builder?->phone; }

    /**
     * Answers built only from the project's own data (possession, RERA, configurations, prices, size), followed by
     * the FAQs entered in admin. Questions without data are left out.
     */
    public function allFaqs(): array
    {
        $n = $this->name;
        $auto = array_filter([
            $this->possessionLabel() ? ["When is possession of $n?", $this->status === 'ready' ? "$n is ready to move in." : "Possession is planned for " . $this->possession_on->format('F Y') . " ({$this->statusLabel()})."] : null,
            $this->rera_number ? ["Is $n RERA registered?", "Yes. The RERA registration number is {$this->rera_number}" . ($this->rera_website ? ' — verify it on the state RERA website.' : '.')] : null,
            $this->configurations ? ["What configurations are available in $n?", implode(', ', $this->configurations) . '.'] : null,
            $this->priceRange() ? ["What is the price of $n?", "Prices range from {$this->priceRange()}" . ($this->relationLoaded('charges') && $this->charges->isNotEmpty() ? ', plus charges listed on this page.' : '.')] : null,
            $this->total_units || $this->towers || $this->land_acres ? ["How big is $n?", collect([$this->land_acres ? $this->land_acres . ' acres' : null, $this->towers ? $this->towers . ' towers' : null, $this->total_units ? number_format($this->total_units) . ' units' : null])->filter()->implode(', ') . '.'] : null,
            $this->builder ? ["Who is the developer of $n?", "$n is developed by {$this->builder->name}."] : null,
        ]);
        $manual = $this->relationLoaded('faqs') ? $this->faqs->map(fn ($f) => [$f->question, $f->answer])->all() : [];
        return array_values(array_merge($manual, $auto));
    }

    public function scopeLive(Query $q): Query { return $q->where('is_active', true); }

    public function coverUrl(): ?string
    {
        $img = $this->relationLoaded('images') ? $this->images->first() : $this->images()->first();
        return $img?->url();
    }

    public function statusLabel(): string
    {
        return config('properties.project_statuses.' . $this->status, ucfirst(str_replace('_', ' ', $this->status)));
    }

    public function priceRange(): ?string
    {
        if (!$this->price_min && !$this->price_max && $this->relationLoaded('units') && ($priced = $this->units->whereNotNull('price'))->isNotEmpty()) {
            return implode(' - ', array_unique([PropertyFormat::short($priced->min('price')), PropertyFormat::short($priced->max('price'))]));
        }
        if (!$this->price_min && !$this->price_max) return null;
        $min = $this->price_min ? PropertyFormat::short($this->price_min) : null;
        $max = $this->price_max && $this->price_max != $this->price_min ? PropertyFormat::short($this->price_max) : null;
        return implode(' - ', array_filter([$min, $max]));
    }

    public function possessionLabel(): ?string
    {
        if ($this->status === 'ready') return 'Ready to move';
        return $this->possession_on ? 'Possession ' . $this->possession_on->format('M Y') : null;
    }

    public function configurationLabel(): ?string
    {
        return $this->configurations ? implode(', ', array_slice($this->configurations, 0, 3)) : null;
    }
}
