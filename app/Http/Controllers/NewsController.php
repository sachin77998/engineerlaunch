<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Models\NewsArticle;
use App\Models\NewsCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NewsController extends Controller
{
    public function index(Request $r): View
    {
        $categories = NewsCategory::where('is_active', true)->orderBy('sort_order')->get();
        $articles = $this->query($r)->paginate(18)->withQueryString();
        $mechanicalArticles = NewsArticle::with(['category', 'source'])->where('is_published', true)->whereHas('category', fn($query) => $query->whereIn('slug', ['mechanical-engineering', 'manufacturing', 'automobile', 'tractors-agriculture', 'forging', 'industrial-automation', 'electric-vehicles', 'steel-metals', 'robotics', 'cad-cae']))->latest('published_at')->limit(6)->get();
        return view('news.index', compact('categories', 'articles', 'mechanicalArticles'));
    }
    public function ajax(Request $r): JsonResponse
    {
        return response()->json($this->query($r)->paginate(18));
    }
    public function show(string $slug): View
    {
        $article = NewsArticle::with(['category', 'source', 'tags', 'industryInsights', 'careerInsights', 'salaryInsights', 'companies'])->where('slug', $slug)->where('is_published', true)->firstOrFail();
        $skills = collect($article->recommended_skills ?? [])->merge($article->skills ?? [])->merge($article->careerInsights->flatMap(fn($i) => $i->recommended_skills ?? []))->filter(fn($skill) => is_string($skill) && mb_strlen(trim($skill)) > 1)->unique()->values();
        $roles = collect($article->roles ?? [])->merge($article->careerInsights->pluck('role'))->filter(fn($role) => is_string($role) && mb_strlen(trim($role)) > 2)->unique()->values();
        $relatedArticles = NewsArticle::with(['category', 'source'])->where('is_published', true)->whereKeyNot($article->id)->when($article->category_id, fn($query) => $query->where('category_id', $article->category_id))->latest('published_at')->limit(6)->get();
        $terms = $skills->take(5)->merge($roles->take(3))->filter()->unique();
        // Jobs whose title names one of the terms: the ids come from the title index first. Searching descriptions with LIKE or
        // phrase FULLTEXT read every job ("Process Engineering" took 70s per query). The open ones are checked on the open-jobs
        // index and kept for 30 minutes (the same terms recur across articles).
        $related = \App\Support\Stale::remember('news-related-jobs:' . md5($terms->sort()->implode('|')), now()->addMinutes(30), function () use ($terms) {
            if ($terms->isEmpty()) return ['ids' => Job::query()->active()->latest('posted_at')->limit(6)->pluck('id')->all(), 'count' => Job::query()->forceIndex('jobs_open_company_posted_index')->active()->count()];
            $ids = DB::table('jobs')->where(function ($q) use ($terms) {
                foreach ($terms as $term) $q->orWhere('title', 'like', '%' . addcslashes((string) $term, '%_\\') . '%');
            })->limit(5000)->pluck('id');
            $all = array_keys(Job::openIdsAmong($ids));
            return ['ids' => array_slice($all, 0, 6), 'count' => count($all)];
        });
        $relatedJobCount = $related['count'];
        $relatedJobs = Job::with('company')->whereIntegerInRaw('id', $related['ids'])->get()->sortBy(fn ($job) => array_search($job->id, $related['ids']))->values();
        $score = max(70, min(98, (int)round((float)$article->relevance_score)));
        $intelligence = ['overall' => $score, 'market' => min(100, $score + 4), 'career' => min(100, $score + 2), 'technology' => min(100, $score + min(8, $skills->count())), 'hiring' => max(40, min(100, $score - 8 + min(15, intdiv($relatedJobCount, 10))))];
        return view('news.show', compact('article', 'skills', 'roles', 'relatedArticles', 'relatedJobs', 'relatedJobCount', 'intelligence'));
    }
    private function query(Request $r)
    {
        $d = $r->validate(['category' => 'nullable|string|max:100', 'search' => 'nullable|string|max:150']);
        return NewsArticle::with(['category', 'source'])->where('is_published', true)->when($d['category'] ?? null, fn($q, $v) => $q->whereHas('category', fn($c) => $c->where('slug', $v)))->when($d['search'] ?? null, fn($q, $v) => $q->where(fn($s) => $s->where('title', 'like', "%$v%")->orWhere('excerpt', 'like', "%$v%")))->latest('published_at');
    }
}
