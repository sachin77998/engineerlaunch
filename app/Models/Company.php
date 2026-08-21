<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasFactory;

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
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sync_enabled' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    /**
     * Get all jobs for this company
     */
    public function jobs(): HasMany
    {
        return $this->hasMany(Job::class);
    }

    /**
     * Get active jobs for this company
     */
    public function activeJobs(): HasMany
    {
        return $this->jobs()->where('is_active', true);
    }

    /**
     * Scope to only active companies
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by country
     */
    public function scopeCountry($query, $country)
    {
        return $query->where('country', $country);
    }

    /**
     * Scope to filter by sector
     */
    public function scopeSector($query, $sector)
    {
        return $query->where('sector', $sector);
    }
}
