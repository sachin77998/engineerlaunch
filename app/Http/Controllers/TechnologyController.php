<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Models\Technology;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/** Every programming language, framework, database and tool found by technologies:sync, each opening its jobs. */
class TechnologyController extends Controller
{
    // Stored categories (Wikidata classes, older seed data, skill taxonomy) -> directory sections.
    public const SECTIONS = [
        'Programming Languages' => ['Programming Languages', 'Backend'],
        'Frameworks & Libraries' => ['Frameworks', 'Frontend', 'Mobile'],
        'Databases' => ['Databases', 'Database'],
        'DevOps, Cloud & Tools' => ['DevOps & Cloud', 'DevOps', 'Cloud', 'Tools', 'Operating System'],
        'Data, AI & Machine Learning' => ['Libraries, Data & ML', 'ML/AI', 'Data & Messaging', 'Data Infrastructure'],
        'Skills & Practices' => ['Computer Science & IT', 'Architecture', 'Methodology'],
    ];
    public function index()
    {
        $technologies = Cache::remember('technologies:directory', now()->addHour(), fn () => Technology::query()->orderByDesc('jobs_count')->orderByDesc('popularity')->orderBy('name')->get(['id', 'name', 'slug', 'category', 'jobs_count', 'popularity']));
        $sections = collect(self::SECTIONS)->map(fn ($categories) => $technologies->whereIn('category', $categories)->values());
        $other = $technologies->whereNotIn('category', collect(self::SECTIONS)->flatten())->values();
        if ($other->isNotEmpty()) $sections['Other Technologies'] = $other;
        return view('technologies.index', ['sections' => $sections->filter(fn ($list) => $list->isNotEmpty()), 'total' => $technologies->count(),
            'withJobs' => $technologies->where('jobs_count', '>', 0)->count()]);
    }
    public function show(Request $request, string $slug)
    {
        $technology = Technology::where('slug', $slug)->firstOrFail();
        $jobs = Job::active()->whereHas('technologies', fn ($q) => $q->where('technologies.id', $technology->id))->with('company:id,name,slug,website,logo_url')->when($request->query('location'), fn ($q, $location) => $q->where('location', 'like', '%' . addcslashes($location, '%_\\') . '%'))->latest('posted_at')->paginate(20)->withQueryString();
        // Technologies that appear in the same job postings most often.
        $related = Technology::query()->select('technologies.id', 'name', 'slug', 'jobs_count')->join('job_technology as jt', 'jt.technology_id', '=', 'technologies.id')->whereIn('jt.job_id', fn ($q) => $q->select('job_id')->from('job_technology')->where('technology_id', $technology->id))->where('technologies.id', '!=', $technology->id)->groupBy('technologies.id', 'name', 'slug', 'jobs_count')->orderByRaw('COUNT(*) DESC')->limit(12)->get();
        return view('technologies.show', compact('technology', 'jobs', 'related'));
    }
}
