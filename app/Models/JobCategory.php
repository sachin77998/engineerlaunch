<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class JobCategory extends Model
{
    use HasFactory;

    protected $table = 'job_categories';

    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    /**
     * Get all jobs for this category
     */
    public function jobs(): BelongsToMany
    {
        return $this->belongsToMany(Job::class, 'job_category', 'job_categories_id', 'job_id');
    }

    /**
     * Scope to search by name
     */
    public function scopeSearch($query, $term)
    {
        return $query->where('name', 'LIKE', "%{$term}%");
    }
}
