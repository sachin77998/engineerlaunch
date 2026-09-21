<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsSource extends Model
{
    protected $guarded = [];
    protected $casts = ['is_active' => 'boolean', 'attribution_required' => 'boolean', 'priority' => 'integer', 'last_fetched_at' => 'datetime'];
    public function articles()
    {
        return $this->hasMany(NewsArticle::class, 'source_id');
    }
}
