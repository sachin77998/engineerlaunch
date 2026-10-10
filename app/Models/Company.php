<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Company extends Model
{
    use HasFactory;

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected $fillable = [
        'name',
        'slug',
        'description',
        'website',
        'careers_url',
        'ats_provider',
        'ats_identifier',
        'jobs_feed_url',
        'sync_enabled',
        'last_synced_at',
        'country',
        'logo_url',
        'industry',
        'sector',
        'employee_count',
        'company_type',
        'is_active',
        'company_email',
        'phone_country_code',
        'phone_number',
        'organization_type',
        'business_type',
        'registry_cin',
        'registry_status',
        'registered_state',
        'registry_source_url',
        'brands',
        'headquarters',
        'products',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sync_enabled' => 'boolean',
        'last_synced_at' => 'datetime',
        'brands' => 'array',
    ];

    /** Name without legal suffixes and punctuation, so registry, catalog and feed spellings match. */
    public static function nameKey(?string $name): string
    {
        $key = \Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii((string) $name));
        $key = preg_replace('/\(.*?\)/', ' ', $key);
        $key = preg_replace('/\b(private|pvt|limited|ltd|llp|llc|inc|incorporated|corporation|corp|company|co|plc|gmbh|ag|sa|bv|nv|pte|pty|the|and|india)\b\.?/', ' ', $key);
        $key = preg_replace('/[^a-z0-9]+/', '', $key);
        return \Illuminate\Support\Str::limit($key !== '' ? $key : \Illuminate\Support\Str::slug((string) $name), 190, '');
    }

    private static ?bool $hasNameKey = null;

    protected static function booted(): void
    {
        static::saving(function (Company $company) {
            if ($company->isDirty('name') || blank($company->getAttribute('name_key'))) {
                if (static::$hasNameKey ??= \Illuminate\Support\Facades\Schema::hasColumn('companies', 'name_key')) $company->setAttribute('name_key', static::nameKey($company->name));
            }
        });
    }

    public function getLogoUrlAttribute(?string $value): ?string
    {
        if (filled($value)) return $value;
        if (! filled($this->website)) return null;

        return 'https://www.google.com/s2/favicons?domain_url=' . rawurlencode($this->website) . '&sz=128';
    }
    public function jobs(): HasMany
    {
        return $this->hasMany(Job::class);
    }
    public function activeJobs(): HasMany
    {
        return $this->jobs()->active();
    }
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(CompanyCategory::class, 'company_category_company')->withTimestamps();
    }
    /** [sector, subsector] categories from the sector catalog, or nulls. Uses loaded categories when available. */
    public function sectorTrail(): array
    {
        $categories = $this->relationLoaded('categories') ? $this->categories : $this->categories()->get();
        $sectors = $categories->where('taxonomy', 'sector');
        $sub = $sectors->firstWhere('parent_id', '!=', null);
        $parent = $sub ? ($sectors->firstWhere('id', $sub->parent_id) ?? CompanyCategory::find($sub->parent_id)) : $sectors->firstWhere('parent_id', null);
        return [$parent, $sub];
    }
    public function facilities(): HasMany
    {
        return $this->hasMany(CompanyFacility::class);
    }
    public function reviews(): HasMany
    {
        return $this->hasMany(CompanyReview::class);
    }
    public function publishedReviews(): HasMany
    {
        return $this->reviews()->published();
    }
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    public function scopeCountry($query, $country)
    {
        return $query->where('country', $country);
    }
    public function scopeSector($query, $sector)
    {
        return $query->where('sector', $sector);
    }
}
