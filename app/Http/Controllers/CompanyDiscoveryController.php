<?php
namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CompanyCategory;
use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CompanyDiscoveryController extends Controller
{
    public function index(Request $request, ?CompanyCategory $category = null): View
    {
        $filters=$request->validate(['q'=>'nullable|string|max:100','category'=>'nullable|array|max:20','category.*'=>'integer','country'=>'nullable|array|max:20','country.*'=>'string|max:80','location'=>'nullable|array|max:20','location.*'=>'string|max:100']);
        $selected=collect($filters['category']??[])->map(fn($id)=>(int)$id);
        $locationMap = config('company_discovery.locations', []);
        $selectedLocations = collect($filters['location'] ?? [])->filter(fn ($name) => isset($locationMap[$name]))->values();
        if($category)$selected->push($category->id);
        $companies=Company::query()->active()->whereHas('activeJobs')->with('categories:id,name,slug,taxonomy,symbol')->withCount('activeJobs')
            ->when($filters['q']??null,fn($q,$term)=>$q->where(function($s)use($term){$like='%'.addcslashes($term,'%_\\').'%';$s->where('name','like',$like)->orWhere('industry','like',$like)->orWhere('sector','like',$like);}))
            ->when($selected->isNotEmpty(),fn($q)=>$q->whereHas('categories',fn($c)=>$c->whereIn('company_categories.id',$selected)))
            ->when($filters['country']??null,fn($q,$countries)=>$q->whereIn('country',$countries))
            ->when($selectedLocations->isNotEmpty(), function ($query) use ($selectedLocations, $locationMap) {
                $query->whereHas('activeJobs', function ($jobs) use ($selectedLocations, $locationMap) {
                    $jobs->where(function ($locations) use ($selectedLocations, $locationMap) {
                        foreach ($selectedLocations as $name) {
                            foreach ($locationMap[$name] as $alias) {
                                $locations->orWhereRaw('LOWER(location) LIKE ?', ['%'.Str::lower($alias).'%']);
                            }
                        }
                    });
                });
            })
            ->orderByDesc('active_jobs_count')->orderBy('name')->paginate(50)->withQueryString();
        $taxonomies=Cache::remember('company-discovery:facets:v3',now()->addHour(),fn()=>CompanyCategory::where('is_active',true)
            ->withCount(['companies'=>fn($q)=>$q->active()->whereHas('activeJobs')])
            ->orderBy('sort_order')->get()->groupBy('taxonomy'));
        $featuredOrder = collect(config('company_discovery.featured_collections', []))->flip();
        $featuredCategories=collect($taxonomies->get('collection',collect()))
            ->filter(fn($item)=>$featuredOrder->has($item->name))
            ->sortBy(fn($item)=>$featuredOrder[$item->name])->values();
        $countries=Cache::remember('company-discovery:countries:v2',now()->addHour(),fn()=>Company::active()->whereHas('activeJobs')->whereNotNull('country')->selectRaw('country,count(*) companies_count')->groupBy('country')->orderByDesc('companies_count')->limit(50)->get());
        $locationFacets = Cache::remember('company-discovery:locations:v3', now()->addHour(), function () use ($locationMap) {
            $companiesByLocation = collect(array_keys($locationMap))->mapWithKeys(fn ($name) => [$name => []])->all();
            Job::query()->active()->whereNotNull('location')->select(['id','company_id','location'])->chunkById(1000, function ($jobs) use (&$companiesByLocation, $locationMap) {
                foreach ($jobs as $job) {
                    $value = Str::lower($job->location);
                    foreach ($locationMap as $name => $aliases) {
                        if ($job->company_id && Str::contains($value, $aliases)) $companiesByLocation[$name][$job->company_id] = true;
                    }
                }
            });
            return collect($companiesByLocation)->map(fn ($ids, $name) => (object)['name'=>$name,'companies_count'=>count($ids)])->sortByDesc('companies_count')->values();
        });
        return view('companies.index',compact('companies','taxonomies','featuredCategories','countries','locationFacets','selectedLocations','selected','category','filters'));
    }

    public function show(Request $request, Company $company): View
    {
        abort_unless($company->is_active, 404);
        $filters = $request->validate(['department'=>'nullable|string|max:100','discipline'=>'nullable|string|max:100','location'=>'nullable|string|max:120','experience'=>'nullable|in:entry,experienced']);
        $base = $company->activeJobs();
        $departments = (clone $base)->selectRaw("COALESCE(NULLIF(department, ''), NULLIF(role_family, ''), NULLIF(category, ''), 'Other') as label, COUNT(*) as jobs_count")->groupBy('label')->orderByDesc('jobs_count')->limit(20)->get();
        $disciplines = (clone $base)->selectRaw("COALESCE(NULLIF(engineering_discipline, ''), 'General / Interdisciplinary') as label, COUNT(*) as jobs_count")->groupBy('label')->orderByDesc('jobs_count')->get();
        $locations = (clone $base)->whereNotNull('location')->where('location','!=','')->selectRaw('location as label, COUNT(*) as jobs_count')->groupBy('location')->orderByDesc('jobs_count')->limit(25)->get();
        $jobs = $company->activeJobs()->with('skills:id,name')
            ->when($filters['department']??null,fn($q,$value)=>$q->where(function($departmentQuery)use($value){$departmentQuery->where('department',$value)->orWhere(function($legacy)use($value){$legacy->whereNull('department')->where('category',$value);});}))
            ->when($filters['discipline']??null,fn($q,$value)=>$q->where('engineering_discipline',$value))
            ->when($filters['location']??null,fn($q,$value)=>$q->where('location',$value))
            ->when(($filters['experience']??null)==='entry',fn($q)=>$q->where(fn($x)=>$x->whereNull('experience_min')->orWhere('experience_min','<=',2)))
            ->when(($filters['experience']??null)==='experienced',fn($q)=>$q->where('experience_min','>=',2))
            ->orderByDesc('posted_at')->orderByDesc('id')->paginate(50)->withQueryString();
        $company->load('categories:id,name,slug,taxonomy,symbol')->loadCount(['activeJobs','publishedReviews'])->loadAvg('publishedReviews','rating');
        $companyReviews = $company->publishedReviews()->with('reviewer:id,name')->latest()->limit(10)->get();
        $myCompanyReview = $request->user() ? $company->reviews()->where('user_id', $request->user()->id)->first() : null;
        return view('companies.show',compact('company','jobs','departments','disciplines','locations','filters','companyReviews','myCompanyReview'));
    }
}
