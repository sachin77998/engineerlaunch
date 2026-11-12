<?php

namespace App\Models\Property;

use App\Models\User;
use App\Services\Property\PropertyFormat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/** A listing: residential or commercial, for rent or sale. Terms live in the rent, sale and PG tables. */
class Property extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];
    protected $casts = ['features' => 'array', 'highlights' => 'array', 'furnishings' => 'array', 'amenities' => 'array', 'details' => 'array', 'steps_done' => 'array',
        'visit_all_day' => 'boolean', 'is_verified' => 'boolean', 'verified_at' => 'datetime', 'published_at' => 'datetime', 'closed_at' => 'datetime', 'area_value' => 'float', 'area_sqft' => 'float', 'carpet_area_sqft' => 'float'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function owner()
    {
        return $this->belongsTo(PropertyOwner::class, 'owner_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function rentTerms()
    {
        return $this->hasOne(PropertyRentTerm::class);
    }

    public function saleTerms()
    {
        return $this->hasOne(PropertySaleTerm::class);
    }

    public function pgTerms()
    {
        return $this->hasOne(PropertyPgTerm::class);
    }

    public function media()
    {
        return $this->hasMany(PropertyMedia::class)->orderBy('sort')->orderBy('id');
    }

    public function photos()
    {
        return $this->media()->where('type', 'photo');
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function commercialDetail()
    {
        return $this->hasOne(PropertyCommercialDetail::class);
    }

    /** Facility keys (popt_facilities) ticked for a commercial listing. */
    public function facilities()
    {
        return $this->hasMany(PropertyFacility::class);
    }

    public function leads()
    {
        return $this->hasMany(PropertyLead::class)->latest();
    }

    public function scopeLive(Builder $q): Builder
    {
        return $q->where('status', 'active');
    }

    public function isPg(): bool
    {
        return in_array($this->kind, ['pg', 'flatmate'], true);
    }

    public function isLand(): bool
    {
        return $this->kind === 'land';
    }

    /** Search tab this listing belongs to: buy, rent, commercial-rent or commercial-buy. */
    public function mode(): string
    {
        return $this->segment === 'commercial' ? ($this->listing === 'rent' ? 'commercial-rent' : 'commercial-buy') : ($this->listing === 'rent' ? 'rent' : 'buy');
    }

    public function kindLabel(): string
    {
        return config("properties.kinds.{$this->segment}.{$this->kind}", 'Property');
    }

    public function bhkLabel(): ?string
    {
        return $this->bhk ? config('properties.bhk.' . $this->bhk) : null;
    }

    public function typeLabel(): string
    {
        if ($this->segment === 'commercial') return $this->kindLabel();
        if ($this->isPg() || $this->isLand()) return $this->kindLabel();
        return config("properties.building_types.residential.{$this->building_type}", 'House');
    }

    /** "4 Bedroom House for sale in Mount Avenue, Hoshiarpur", "Plot for sale in …", "Shop for rent in …". */
    public function makeTitle(): string
    {
        $noun = ['apartment' => 'Flat', 'studio' => 'Studio Flat', 'penthouse' => 'Penthouse', 'independent_house' => 'House', 'villa' => 'Villa', 'gated_community' => 'Villa',
            'builder_floor' => 'Builder Floor', 'farm_house' => 'Farm House', 'service_apartment' => 'Service Apartment'][$this->building_type] ?? 'House';
        $rooms = match (true) {
            $this->bhk === '1rk' => '1 RK ',
            (bool) $this->bhk => $this->bhk . ' Bedroom ',
            default => '',
        };
        $what = match (true) {
            $this->kind === 'pg' => 'PG / Hostel' . ($this->pgTerms?->gender && $this->pgTerms->gender !== 'any' ? ' for ' . config('properties.pg.gender.' . $this->pgTerms->gender) : ''),
            $this->kind === 'flatmate' => 'Flatmate wanted' . ($this->bhkLabel() ? ' in ' . $this->bhkLabel() . ' flat' : ''),
            $this->isLand() => 'Plot',
            $this->segment === 'commercial' => $this->kindLabel(),
            default => $rooms . $noun,
        };
        $purpose = $this->isPg() ? '' : ($this->listing === 'rent' ? ' for rent' : ' for sale');
        $where = collect([$this->locality ?: $this->society, $this->city])->filter()->unique()->implode(', ');
        return Str::limit($what . $purpose . ($where ? ' in ' . $where : ''), 190, '');
    }

    /** "Ready · 10+ yrs", "Under construction · by Dec 2027", "New launch · Ready". */
    public function statusTag(): ?string
    {
        $age = ['lt1' => '< 1 yr', '1_5' => '1-5 yrs', '5_10' => '5-10 yrs', 'gt10' => '10+ yrs'][$this->age] ?? null;
        if ($this->listing === 'sale' && $this->saleTerms) {
            if ($this->saleTerms->possession === 'under_construction') return 'Under construction' . ($this->saleTerms->possession_by ? ' · by ' . $this->saleTerms->possession_by->format('M Y') : '');
            if ($this->age === 'lt1') return ($this->saleTerms->is_new_project ? 'New launch · ' : '') . 'Newly built · 0-1 yr';
            return ($this->saleTerms->is_new_project ? 'New launch · ' : '') . 'Ready' . ($age ? ' · ' . $age : '');
        }
        if ($this->listing === 'rent') {
            $from = $this->rentTerms?->available_from ?? $this->pgTerms?->available_from;
            return ($from && $from->isFuture() ? 'From ' . $from->format('d M') : 'Ready to move') . ($age ? ' · ' . $age : '');
        }
        return $age;
    }

    public function refreshTitleAndSlug(): void
    {
        $this->title = $this->makeTitle();
        if ($this->status === 'draft' || str_starts_with((string) $this->slug, 'draft-')) {
            $this->slug = Str::limit(Str::slug($this->title), 150, '') . '-' . base_convert((string) ($this->id * 7919 + 10007), 10, 36);
        }
    }

    /** Monthly rent, lowest bed rent, or sale price. */
    public function headlineAmount(): ?float
    {
        if ($this->isPg()) return $this->pgTerms?->lowestRent();
        return $this->listing === 'rent' ? ($this->rentTerms ? (float) $this->rentTerms->rent : null) : ($this->saleTerms ? (float) $this->saleTerms->price : null);
    }

    public function headlinePrice(): string
    {
        $amount = $this->headlineAmount();
        if ($amount === null) return 'Price on request';
        if ($this->isPg()) return PropertyFormat::full($amount, $this->currency) . ($this->kind === 'pg' ? ' /bed' : ' /month');
        return $this->listing === 'rent' ? PropertyFormat::full($amount, $this->currency) . ' /month' : PropertyFormat::short($amount, $this->currency);
    }

    public function money($amount): ?string
    {
        return PropertyFormat::full($amount, $this->currency);
    }

    public function areaLabel(): ?string
    {
        return $this->area_value ? PropertyFormat::area($this->area_value, $this->area_unit) : null;
    }

    public function coverUrl(): ?string
    {
        $photo = $this->relationLoaded('media') ? $this->media->firstWhere('type', 'photo') : $this->photos()->first();
        return $photo?->url();
    }

    public function floorLabel(): ?string
    {
        if (!$this->floor) return null;
        $f = config('properties.commercial.floors.' . $this->floor) ?? ($this->floor === '0' ? 'Ground' : $this->floor);
        $t = $this->total_floors ? (config('properties.commercial.total_floors.' . $this->total_floors) ?? $this->total_floors) : null;
        return $t ? "$f of $t" : $f;
    }

    public function reports()
    {
        return $this->hasMany(PropertyReport::class)->latest();
    }

    /** Listing-page heading: "4 BHK Independent House/Villa for sale", "Residential Plot for sale", "Shop for rent". */
    public function heading(): string
    {
        $type = match (true) {
            $this->isLand() => 'Residential Plot',
            $this->isPg() => $this->kindLabel(),
            $this->segment === 'commercial' => $this->kindLabel(),
            default => collect(config('properties.home_types'))->first(fn ($t) => in_array($this->building_type, $t[2] ?? [], true))[0] ?? $this->typeLabel(),
        };
        $rooms = !$this->isLand() && $this->segment === 'residential' && $this->bhk ? config('properties.bhk.' . $this->bhk) . ' ' : '';
        return $rooms . ucwords($type) . ($this->isPg() ? '' : ($this->listing === 'rent' ? ' for rent' : ' for sale'));
    }

    /** Indicative monthly EMI on a sale listing (share of the price, rate and years from config). */
    public function emi(): ?float
    {
        if ($this->listing !== 'sale' || !$this->saleTerms) return null;
        $c = config('properties.emi');
        $loan = (float) $this->saleTerms->price * $c['loan_share'];
        $r = $c['rate'] / 1200;
        $n = $c['years'] * 12;
        return $loan > 0 ? round($loan * $r * (1 + $r) ** $n / ((1 + $r) ** $n - 1)) : null;
    }

    /** "Ground + 1 Floors" for houses, "4 Floors" for buildings, "Ground only". */
    public function totalFloorsLabel(): ?string
    {
        if (!$this->total_floors) return null;
        if ($this->total_floors === 'ground_only') return 'Ground only';
        $n = (int) $this->total_floors;
        if (in_array($this->building_type, ['independent_house', 'villa', 'farm_house', 'gated_community', 'independent_shop', 'standalone_building'], true)) {
            return $n <= 1 ? 'Ground only' : 'Ground + ' . ($n - 1) . ' Floor' . ($n - 1 === 1 ? '' : 's');
        }
        return $n . ' Floor' . ($n === 1 ? '' : 's');
    }

    public function address(): string
    {
        return collect([$this->street, $this->society, $this->locality, $this->city, $this->state, $this->pincode])->filter()->unique()->implode(', ');
    }
}
