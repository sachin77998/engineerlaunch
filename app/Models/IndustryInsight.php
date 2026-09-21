<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IndustryInsight extends Model
{
    protected $guarded = [];
    protected $casts = ['demand_drivers' => 'array'];
    public function article()
    {
        return $this->belongsTo(NewsArticle::class, 'news_article_id');
    }
}
