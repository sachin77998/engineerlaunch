<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Technology extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'category',
        'aliases',
        'wikidata_id',
        'popularity',
        'jobs_count',
    ];

    protected $casts = ['aliases' => 'array', 'popularity' => 'integer', 'jobs_count' => 'integer'];

    /**
     * Get all jobs that require this technology
     */
    public function jobs(): BelongsToMany
    {
        return $this->belongsToMany(Job::class, 'job_technology', 'technology_id', 'job_id');
    }

    /**
     * Scope to filter by category
     */
    public function scopeCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to search by name
     */
    public function scopeSearch($query, $term)
    {
        return $query->where('name', 'LIKE', "%{$term}%");
    }
}
