<?php

namespace App\Http\Controllers;

use App\Events\Property\ProjectViewed;
use App\Models\Property\Builder;
use App\Models\Property\NearbyPlace;
use App\Models\Property\Project;
use App\Models\Property\PropertyCity;
use App\Models\NewsArticle;
use App\Models\Property\LocalityReview;
use App\Models\Property\ProjectLead;
use App\Models\Property\Property;
use App\Services\Property\Visitor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

// Projects (buildings / societies) by city with filters, a project's page and a builder's page.
class ProjectController extends Controller
{
    public function index(Request $request, ?string $city = null)
    {
        $f = $request->validate(['status' => 'nullable|string|max:20', 'possession' => 'nullable|string|max:10', 'builder' => 'nullable|string|max:190', 'q' => 'nullable|string|max:100']);
        $year = (int) date('Y');
        $projects = Project::live()->with(['images', 'builder:id,name,slug', 'liveOffers'])
            ->when($city, fn ($q) => $q->where('city_slug', $city))
            ->when($f['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($f['builder'] ?? null, fn ($q, $v) => $q->whereHas('builder', fn ($b) => $b->where('slug', $v)))
            ->when($f['q'] ?? null, fn ($q, $v) => $q->where(fn ($w) => $w->where('name', 'like', "%$v%")->orWhere('locality', 'like', "%$v%")))
            ->when($f['possession'] ?? null, fn ($q, $v) => match (true) {
                $v === 'ready' => $q->where(fn ($w) => $w->where('status', 'ready')->orWhere('possession_on', '<=', today())),
                $v === 'later' => $q->where('status', '!=', 'ready')->whereYear('possession_on', '>', $year + 2),
                ctype_digit($v) => $q->where('status', '!=', 'ready')->where('possession_on', '>', today())->whereYear('possession_on', (int) $v),
                default => $q,
            })
            ->orderByDesc('is_featured')->latest('updated_at')->paginate(18)->withQueryString();
        $cityName = $city ? (PropertyCity::where('slug', $city)->value('name') ?? Project::where('city_slug', $city)->value('city')) : null;
        abort_if($city && !$cityName, 404);
        return view('properties.projects.index', compact('projects', 'city', 'cityName', 'f'));
    }

    public function show(Project $project)
    {
        abort_unless($project->is_active || auth()->user()?->isOwner(), 404);
        event(new ProjectViewed($project, Visitor::id()));
        $project->load(['images', 'builder' => fn ($q) => $q->withCount(['projects' => fn ($p) => $p->where('is_active', true)]), 'liveOffers', 'units', 'highlights', 'videos', 'faqs', 'charges']);
        $listings = $project->properties()->live()->with(['media', 'rentTerms', 'saleTerms', 'pgTerms', 'owner:id,owner_type,is_featured'])->latest('published_at')->limit(12)->get();
        $nearby = ($key = NearbyPlace::geoKey($project->latitude, $project->longitude))
            ? NearbyPlace::where('geo_key', $key)->where('category', '!=', '_none')->orderBy('distance_m')->get() : collect();
        $more = Project::live()->where('city_slug', $project->city_slug)->whereKeyNot($project->id)->with(['images', 'builder:id,name'])
            ->orderByRaw('builder_id <=> ? desc', [$project->builder_id])->orderByRaw('locality = ? desc', [$project->locality])->limit(6)->get();
        $user = auth()->user();
        return view('properties.projects.show', ['p' => $project, 'listings' => $listings, 'nearby' => $nearby, 'more' => $more,
            'locality' => $this->localityFacts($project), 'articles' => $this->articles($project),
            'shortlisted' => $user && DB::table('project_shortlists')->where(['user_id' => $user->id, 'project_id' => $project->id])->exists(),
            'revealed' => $user && ProjectLead::where(['project_id' => $project->id, 'user_id' => $user->id, 'kind' => 'phone'])->exists(),
            'visitSlots' => app(\App\Services\Property\VisitSlots::class)->available($project),
            'myVisit' => $user ? \App\Models\Property\SiteVisit::upcoming()->where(['user_id' => $user->id, 'visitable_type' => 'project', 'visitable_id' => $project->id])->first() : null]);
    }

    /** "View number": signed-in buyers see the builder's number; each reveal is a lead. */
    public function phone(Request $request, Project $project)
    {
        abort_unless($project->is_active, 404);
        $project->load('builder');
        abort_unless($phone = $project->phone(), 404, 'No phone number listed for this project yet.');
        $u = $request->user();
        ProjectLead::firstOrCreate(['project_id' => $project->id, 'user_id' => $u->id, 'kind' => 'phone'], ['name' => $u->name, 'email' => $u->email]);
        return response()->json(['phone' => $phone, 'name' => $project->builder?->name ?? $project->name]);
    }

    /** "Request callback" (optionally for one floor plan) and "Contact builder". Guests give a name and phone. */
    public function enquire(Request $request, Project $project)
    {
        abort_unless($project->is_active, 404);
        $u = $request->user();
        $data = $request->validate([
            'kind' => 'required|in:callback,contact', 'unit_id' => ['nullable', Rule::exists('project_units', 'id')->where('project_id', $project->id)],
            'name' => [$u ? 'nullable' : 'required', 'string', 'max:120'], 'phone' => [$u ? 'nullable' : 'required', 'regex:/^\+?[0-9 ()-]{7,20}$/'],
            'email' => 'nullable|email|max:191', 'message' => 'nullable|string|max:1000',
        ]);
        ProjectLead::create(array_merge($data, ['project_id' => $project->id, 'user_id' => $u?->id,
            'name' => $data['name'] ?? $u?->name, 'email' => $data['email'] ?? $u?->email, 'phone' => $data['phone'] ?? null]));
        $msg = $data['kind'] === 'callback' ? 'Callback requested — the builder will call you.' : 'Message sent to the builder.';
        return $request->expectsJson() ? response()->json(['message' => $msg]) : back()->with('success', $msg);
    }

    /** Brochure download for signed-in buyers (a lead for the builder). */
    public function brochure(Request $request, Project $project)
    {
        abort_unless($project->is_active && $project->brochure_path && Storage::disk('public')->exists($project->brochure_path), 404);
        $u = $request->user();
        ProjectLead::firstOrCreate(['project_id' => $project->id, 'user_id' => $u->id, 'kind' => 'brochure'], ['name' => $u->name, 'email' => $u->email]);
        return Storage::disk('public')->download($project->brochure_path, \Illuminate\Support\Str::slug($project->name) . '-brochure.pdf');
    }

    public function shortlist(Request $request, Project $project)
    {
        $where = ['user_id' => $request->user()->id, 'project_id' => $project->id];
        $on = !DB::table('project_shortlists')->where($where)->delete();
        if ($on) DB::table('project_shortlists')->insert($where + ['created_at' => now()]);
        return $request->expectsJson() ? response()->json(['shortlisted' => $on]) : back();
    }

    /** Locality block: listings, asking price per sq ft and rating for the project's locality. */
    private function localityFacts(Project $p): ?array
    {
        if (!$p->locality) return null;
        $key = LocalityReview::key($p->locality);
        $row = Property::live()->where('city_slug', $p->city_slug)->where('locality', $p->locality)
            ->selectRaw("count(*) as n, avg(case when listing = 'sale' and area_sqft > 0 and price_sort > 0 then price_sort / area_sqft end) as psf, sum(listing = 'rent') as rent")->first();
        $ratings = app(\App\Services\Property\LocalityRatings::class);
        return ['name' => $p->locality, 'listings' => (int) $row->n, 'rent' => (int) $row->rent, 'psf' => $row->psf ? round($row->psf) : null,
            'summary' => $ratings->summary($p->city_slug, $key), 'url' => route('localities.show', [$p->city_slug, $key]),
            'buy' => route('properties.search', ['buy', $p->city_slug]) . '?' . http_build_query(['loc' => [$p->locality]]),
            'rent_url' => route('properties.search', ['rent', $p->city_slug]) . '?' . http_build_query(['loc' => [$p->locality]])];
    }

    /** Real-estate news naming the project, its locality or city. */
    private function articles(Project $p)
    {
        $terms = array_values(array_filter(array_unique([$p->name, $p->locality, $p->city])));
        return NewsArticle::where('is_published', true)
            ->whereHas('category', fn ($q) => $q->whereIn('slug', ['real-estate', 'property-tax-legal', 'home-buying-guides', 'real-estate-investment']))
            ->orderByRaw('(' . implode(' or ', array_fill(0, count($terms), 'title like ?')) . ') desc', array_map(fn ($t) => "%$t%", $terms))
            ->latest('published_at')->limit(6)->get(['id', 'title', 'slug', 'image_url', 'published_at']);
    }

    public function builder(Builder $builder)
    {
        abort_unless($builder->is_active, 404);
        $projects = $builder->projects()->live()->with('images')->orderByDesc('is_featured')->latest('launched_on')->paginate(18);
        $cities = $builder->projects()->live()->groupBy('city', 'city_slug')->selectRaw('city, city_slug, count(*) as n')->orderByDesc('n')->get();
        return view('properties.projects.builder', compact('builder', 'projects', 'cities'));
    }
}
