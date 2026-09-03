<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name','slug','description','website',
        'careers_url','ats_provider','ats_identifier',
        'jobs_feed_url','sync_enabled',
        'last_synced_at','country',
        'logo_url','industry','sector',
        'employee_count','company_type','is_active',
        'company_email','phone_country_code','phone_number','organization_type','business_type',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sync_enabled' => 'boolean',
        'last_synced_at' => 'datetime',
    ];
    public function jobs(): HasMany
    {
        return $this->hasMany(Job::class);
    }
    public function activeJobs(): HasMany
    {
        return $this->jobs()->where('is_active', true);
    }
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(CompanyCategory::class, 'company_category_company')->withTimestamps();
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
