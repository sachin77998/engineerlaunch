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
        $taxonomies=Cache::remember('company-discovery:facets:v1',now()->addHour(),fn()=>CompanyCategory::where('is_active',true)->withCount(['companies'=>fn($q)=>$q->active()])->orderBy('sort_order')->get()->groupBy('taxonomy'));
        $countries=Cache::remember('company-discovery:countries:v1',now()->addHour(),fn()=>Company::active()->whereNotNull('country')->selectRaw('country,count(*) companies_count')->groupBy('country')->orderByDesc('companies_count')->limit(50)->get());
        return view('companies.index',compact('companies','taxonomies','countries','selected','category','filters'));
    }
}
