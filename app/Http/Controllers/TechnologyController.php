<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Models\Technology;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

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
        $technologies = \App\Support\Stale::remember('technologies:directory', now()->addHour(), fn () => Technology::query()->orderByDesc('jobs_count')->orderByDesc('popularity')->orderBy('name')->get(['id', 'name', 'slug', 'category', 'jobs_count', 'popularity']));
        $sections = collect(self::SECTIONS)->map(fn ($categories) => $technologies->whereIn('category', $categories)->values());
        $other = $technologies->whereNotIn('category', collect(self::SECTIONS)->flatten())->values();
        if ($other->isNotEmpty()) $sections['Other Technologies'] = $other;
        return view('technologies.index', ['sections' => $sections->filter(fn ($list) => $list->isNotEmpty()), 'total' => $technologies->count(),
            'withJobs' => $technologies->where('jobs_count', '>', 0)->count()]);
    }
    public function show(Request $request, string $slug)
    {
        $technology = Technology::where('slug', $slug)->firstOrFail();
        $location = trim((string) $request->query('location'));
        // Open ids newest first, kept 20 minutes; then one page of rows by primary key.
        // Typed locations only get a short plain cache (stale copies are kept for days).
        $open = ($location === '' ? [\App\Support\Stale::class, 'remember'] : [Cache::class, 'remember'])('technology-open:' . $technology->id . ':' . md5($location), now()->addMinutes(20), function () use ($technology, $location) {
            // Job ids from the pivot first: filtering every active job through the pivot took 20-30s for a common technology.
            $jobIds = DB::table('job_technology')->where('technology_id', $technology->id)->pluck('job_id');
            // A location narrows the ids on the small (location, id) index before full job rows are read.
            if ($location !== '') $jobIds = DB::table(DB::raw('`jobs` FORCE INDEX (jobs_location_index)'))->whereIntegerInRaw('id', $jobIds)->where('location', 'like', '%' . addcslashes($location, '%_\\') . '%')->pluck('id');
            // Checked on the open-jobs index alone: against full job rows 14,000 ids took 3.6s.
            return array_keys(Job::openIdsAmong($jobIds));
        });
        $jobs = Job::pageOf($open, 20, $request);
        // Technologies that appear in the same job postings most often.
        $related = \App\Support\Stale::remember('technology-related:' . $technology->id, now()->addHours(6), fn () => Technology::query()->select('technologies.id', 'name', 'slug', 'jobs_count')->join('job_technology as jt', 'jt.technology_id', '=', 'technologies.id')->whereIn('jt.job_id', fn ($q) => $q->select('job_id')->from('job_technology')->where('technology_id', $technology->id))->where('technologies.id', '!=', $technology->id)->groupBy('technologies.id', 'name', 'slug', 'jobs_count')->orderByRaw('COUNT(*) DESC')->limit(12)->get());
        return view('technologies.show', compact('technology', 'jobs', 'related'));
    }
}
