<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsArticle extends Model
{
    protected $guarded = [];
    protected $casts = ['skills' => 'array', 'recommended_skills' => 'array', 'roles' => 'array', 'entities' => 'array', 'technologies' => 'array', 'industries' => 'array', 'locations' => 'array', 'intelligence_score' => 'integer', 'is_featured' => 'boolean', 'is_published' => 'boolean', 'published_at' => 'datetime', 'source_published_at' => 'datetime', 'processed_at' => 'datetime', 'moderated_at' => 'datetime'];
    public function category()
    {
        return $this->belongsTo(NewsCategory::class, 'category_id');
    }
    public function source()
    {
        return $this->belongsTo(NewsSource::class, 'source_id');
    }
    public function tags()
    {
        return $this->belongsToMany(NewsTag::class, 'news_article_tag');
    }
    public function industryInsights()
    {
        return $this->hasMany(IndustryInsight::class, 'news_article_id');
    }
    public function careerInsights()
    {
        return $this->hasMany(CareerInsight::class, 'news_article_id');
    }
    public function salaryInsights()
    {
        return $this->hasMany(SalaryInsight::class, 'news_article_id');
    }
    public function companies()
    {
        return $this->belongsToMany(Company::class, 'news_article_companies')->withPivot(['company_name', 'relationship_type', 'parent_company_id']);
    }
}
