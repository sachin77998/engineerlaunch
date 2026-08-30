<?php
namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CompanyCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class CompanyDiscoveryController extends Controller
{
    public function index(Request $request, ?CompanyCategory $category = null): View
    {
        $filters=$request->validate(['q'=>'nullable|string|max:100','category'=>'nullable|array|max:20','category.*'=>'integer','country'=>'nullable|array|max:20','country.*'=>'string|max:80']);
        $selected=collect($filters['category']??[])->map(fn($id)=>(int)$id);
        if($category)$selected->push($category->id);
        $companies=Company::query()->active()->with('categories:id,name,slug,taxonomy,symbol')->withCount('activeJobs')
            ->when($filters['q']??null,fn($q,$term)=>$q->where(function($s)use($term){$like='%'.addcslashes($term,'%_\\').'%';$s->where('name','like',$like)->orWhere('industry','like',$like)->orWhere('sector','like',$like);}))
            ->when($selected->isNotEmpty(),fn($q)=>$q->whereHas('categories',fn($c)=>$c->whereIn('company_categories.id',$selected)))
            ->when($filters['country']??null,fn($q,$countries)=>$q->whereIn('country',$countries))
            ->orderByDesc('active_jobs_count')->orderBy('name')->paginate(50)->withQueryString();
        $taxonomies=Cache::remember('company-discovery:facets:v2',now()->addHour(),fn()=>CompanyCategory::where('is_active',true)
            ->withCount(['companies'=>fn($q)=>$q->active()->whereHas('activeJobs')])
            ->orderBy('sort_order')->get()->groupBy('taxonomy'));
        $featuredCategories=collect($taxonomies->get('collection',collect()))
            ->filter(fn($item)=>$item->companies_count>0)->take(10);
        $countries=Cache::remember('company-discovery:countries:v2',now()->addHour(),fn()=>Company::active()->whereHas('activeJobs')->whereNotNull('country')->selectRaw('country,count(*) companies_count')->groupBy('country')->orderByDesc('companies_count')->limit(50)->get());
        return view('companies.index',compact('companies','taxonomies','featuredCategories','countries','selected','category','filters'));
    }

    public function show(Request $request, Company $company): View
    {
        abort_unless($company->is_active, 404);
        $filters = $request->validate(['department'=>'nullable|string|max:100','location'=>'nullable|string|max:120','experience'=>'nullable|in:entry,experienced']);
        $base = $company->activeJobs();
        $departments = (clone $base)->selectRaw("COALESCE(NULLIF(category, ''), 'Other') as label, COUNT(*) as jobs_count")->groupBy('label')->orderByDesc('jobs_count')->limit(12)->get();
        $locations = (clone $base)->whereNotNull('location')->where('location','!=','')->selectRaw('location as label, COUNT(*) as jobs_count')->groupBy('location')->orderByDesc('jobs_count')->limit(25)->get();
        $jobs = $company->activeJobs()->with('skills:id,name')
            ->when($filters['department']??null,fn($q,$value)=>$q->where('category',$value))
            ->when($filters['location']??null,fn($q,$value)=>$q->where('location',$value))
            ->when(($filters['experience']??null)==='entry',fn($q)=>$q->where(fn($x)=>$x->whereNull('experience_min')->orWhere('experience_min','<=',2)))
            ->when(($filters['experience']??null)==='experienced',fn($q)=>$q->where('experience_min','>=',2))
            ->orderByDesc('posted_at')->orderByDesc('id')->paginate(50)->withQueryString();
        $company->load('categories:id,name,slug,taxonomy,symbol')->loadCount('activeJobs');
        return view('companies.show',compact('company','jobs','departments','locations','filters'));
    }
}
