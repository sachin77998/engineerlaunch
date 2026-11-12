<?php

namespace App\Http\Controllers;

use App\Jobs\FetchNearbyPlaces;
use App\Models\Property\Property;
use App\Models\Property\PropertyCity;
use App\Services\Property\CityContext;
use App\Services\Property\CityDirectory;
use App\Services\Property\CityPage;
use App\Services\Property\PropertyCache;
use App\Services\Property\Visitor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// City landing pages: /real-estate (all cities) and /real-estate/{city}?zone=… built from config/property_city.php,
// plus the visitor's search history and "locate me".
class CityPageController extends Controller
{
    public function index(CityDirectory $directory)
    {
        $counts = PropertyCache::remember('_all', 'city-counts', now()->addMinutes(30), fn () => Property::live()->whereNotNull('city_slug')->groupBy('city_slug')->selectRaw('city_slug, count(*) as n')->pluck('n', 'city_slug')->all());
        $cities = PropertyCity::where('is_active', true)->orderBy('sort')->orderBy('name')->get()->groupBy('country');
        return view('properties.city.index', compact('cities', 'counts') + ['directory' => $directory->all(), 'trail' => $directory->trail(Visitor::ids())]);
    }

    public function show(Request $request, string $city, CityPage $page, CityDirectory $directory)
    {
        $model = PropertyCity::where('slug', $city)->where('is_active', true)->first();
        if (!$model) {
            // A city with live listings but not yet in the cities table still gets a page.
            $name = Property::live()->where('city_slug', $city)->value('city');
            abort_unless($name, 404);
            $model = new PropertyCity(['name' => $name, 'slug' => $city, 'country' => Property::live()->where('city_slug', $city)->value('country') ?: 'India']);
        }
        $zones = (array) ($model->zones ?? []);
        $zone = in_array($request->query('zone'), $zones, true) ? $request->query('zone') : null;
        $context = new CityContext($model, $zone, Visitor::ids());
        $trail = $directory->trail($context->visitorIds);
        $directory->remember($model->slug, $model->name, $zone);
        return view('properties.city.show', ['city' => $model, 'zone' => $zone, 'zones' => $zones, 'blocks' => $page->build($context), 'context' => $context,
            'cities' => app(PropertyController::class)->cities(), 'directory' => $directory->all(), 'trail' => $trail,
            'shortlisted' => auth()->check() ? DB::table('property_shortlists')->where('user_id', auth()->id())->pluck('property_id')->all() : []]);
    }

    /** Star click on "How would you rate your locality?" → that locality's review form. */
    public function rate(Request $request, string $city)
    {
        $data = $request->validate(['locality' => 'required|string|max:150', 'stars' => 'nullable|integer|between:1,5']);
        return redirect()->route('localities.review', [$city, Str::limit(Str::slug($data['locality']), 160, '')] + (isset($data['stars']) ? ['stars' => $data['stars']] : []));
    }

    /** "Locate me": the nearest city we cover and, within it, the nearest locality that has listings. */
    public function locate(Request $request, CityDirectory $directory)
    {
        $data = $request->validate(['lat' => 'required|numeric|between:-90,90', 'lng' => 'required|numeric|between:-180,180']);
        $city = $directory->nearest((float) $data['lat'], (float) $data['lng']);
        if (!$city) return response()->json(['found' => false, 'message' => "We don't cover your area yet."]);
        $locality = Property::live()->where('city_slug', $city['slug'])->whereNotNull('locality')->whereNotNull('latitude')
            ->selectRaw('locality, latitude, longitude')->limit(2000)->get()
            ->map(fn ($p) => ['name' => $p->locality, 'm' => FetchNearbyPlaces::metres($data['lat'], $data['lng'], (float) $p->latitude, (float) $p->longitude)])
            ->filter(fn ($p) => $p['m'] <= 5000)->sortBy('m')->first();
        return response()->json(['found' => true, 'city' => $city['name'], 'slug' => $city['slug'], 'locality' => $locality['name'] ?? null,
            'url' => route('real-estate.city', $city['slug'])]);
    }

    /** "View all searches": the visitor's search history. */
    public function searches()
    {
        $rows = DB::table('property_search_logs')->whereIn('visitor', Visitor::ids())->latest('created_at')->limit(100)->get(['id', 'label', 'url', 'created_at']);
        return view('properties.city.searches', compact('rows'));
    }

    public function clearSearches(Request $request)
    {
        $q = DB::table('property_search_logs')->whereIn('visitor', Visitor::ids());
        $request->filled('id') ? $q->where('id', (int) $request->input('id'))->delete() : $q->delete();
        return back()->with('success', $request->filled('id') ? 'Search removed.' : 'Search history cleared.');
    }
}
