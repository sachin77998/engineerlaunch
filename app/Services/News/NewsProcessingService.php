<?php

namespace App\Services\News;

use App\Models\{CareerInsight, Company, IndustryInsight, NewsArticle, NewsCategory, NewsTag};
use Illuminate\Support\Str;

class NewsProcessingService
{
    public function process(NewsArticle $a): void
    {
        $text = mb_strtolower($a->title . ' ' . $a->raw_content . ' ' . $a->excerpt);
        $slug = 'technology';
        $best = 0;
        foreach (config('news.keywords', []) as $candidate => $words) {
            $score = collect($words)->sum(fn($w) => substr_count($text, $w));
            if ($score > $best) {
                $best = $score;
                $slug = $candidate;
            }
        }
        $skills = collect(config('recruitment.skills', []))->filter(fn($v) => str_contains($text, mb_strtolower($v)))->values()->take(15);
        $roles = collect(config('recruitment.roles', []))->filter(fn($v) => str_contains($text, mb_strtolower(str_replace(['Senior ', 'Lead '], '', $v))))->values()->take(12);
        $companies = Company::where(fn($q) => $q->whereRaw('? like concat("%", lower(name), "%")', [$text]))->limit(10)->get();
        $locations = collect(['India', 'Bengaluru', 'Hyderabad', 'Pune', 'Mumbai', 'Delhi', 'Gurugram', 'Chandigarh', 'Mohali'])->filter(fn($v) => str_contains($text, mb_strtolower($v)))->values();
        $summary = Str::limit(trim($a->raw_content ?: $a->excerpt), 700);
        $relevance = min(100, 30 + $best * 10 + $skills->count() * 3 + $roles->count() * 4);
        $a->update(['category_id' => NewsCategory::where('slug', $slug)->value('id') ?: $a->category_id, 'summary' => $summary, 'industry_impact' => 'This development may change investment priorities, technology adoption and workforce demand in ' . $slug . '.', 'career_impact' => 'Professionals should connect the underlying industry change with relevant engineering skills and delivery experience.', 'skills' => $skills, 'roles' => $roles, 'entities' => $companies->pluck('name'), 'locations' => $locations, 'relevance_score' => $relevance, 'career_impact_level' => $relevance >= 75 ? 'high' : ($relevance >= 50 ? 'medium' : 'low'), 'processing_status' => 'processed', 'processed_at' => now()]);
        foreach ($skills as $skill) {
            $tag = NewsTag::firstOrCreate(['slug' => Str::slug($skill)], ['name' => $skill]);
            $a->tags()->syncWithoutDetaching($tag);
        }
        foreach ($companies as $company) $a->companies()->syncWithoutDetaching([$company->id => ['company_name' => $company->name, 'relationship_type' => 'mentioned']]);
        foreach ($roles as $role) CareerInsight::updateOrCreate(['news_article_id' => $a->id, 'role' => $role], ['impact_level' => $a->career_impact_level, 'recommended_skills' => $skills, 'analysis' => $a->career_impact]);
        IndustryInsight::updateOrCreate(['news_article_id' => $a->id, 'industry' => $slug], ['analysis' => $a->industry_impact, 'demand_drivers' => $skills]);
    }
}
