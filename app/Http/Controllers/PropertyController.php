<?php

namespace App\Http\Controllers;

use App\Events\Property\PropertySearched;
use App\Events\Property\PropertyViewed;
use App\Models\Property\NearbyPlace;
use App\Models\Property\Property;
use App\Models\Property\PropertyLead;
use App\Services\Property\PropertySearch;
use App\Services\Property\Visitor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

// Property marketplace for visitors: search (Buy / Rent / Commercial), results, the listing page and contacting the owner.
class PropertyController extends Controller
{
    public const SORTS = PropertySearch::SORTS;

    public function index()
    {
        $latest = collect(config('properties.modes'))->map(fn ($_, $mode) => $this->modeQuery($mode)->with(['media', 'rentTerms', 'saleTerms', 'pgTerms'])
            ->latest('published_at')->limit(6)->get())->filter->isNotEmpty();
        return view('properties.index', ['cities' => $this->cities(), 'latest' => $latest,
            'counts' => Cache::remember('properties:counts', now()->addMinutes(10), fn () => Property::live()->selectRaw('count(*) as n, count(distinct city_slug) as cities')->first())]);
    }

    /** /properties/{mode}/{city?}: buy, rent, commercial-rent, commercial-buy. */
    public function search(Request $request, string $mode, ?string $city = null)
    {
        abort_unless(isset(config('properties.modes')[$mode]), 404);
        $o = config('properties');
        $f = $request->validate(PropertySearch::rules());
        $cityName = $city ? ($this->cities()->first(fn ($c) => Str::slug($c) === $city) ?? Property::where('city_slug', $city)->value('city')) : null;
        abort_if($city && !$cityName, 404);
        $areas = PropertySearch::areas($f);
        $base = fn () => $this->modeQuery($mode)->when($city, fn ($q) => $q->where('city_slug', $city));
        $query = PropertySearch::sort(PropertySearch::query($mode, $city, $f), $f['sort'] ?? null);
        $results = $query->with(['media', 'rentTerms', 'saleTerms', 'pgTerms', 'owner:id,owner_type,is_featured'])->paginate(20)->withQueryString();
        if (!$request->has('page')) {
            $label = PropertySearch::label($mode, $f, $cityName);
            event(new PropertySearched(Visitor::id(), auth()->id(), $mode, $city, $areas->first(), $f['type'][0] ?? null, $label, $request->fullUrl()));
        }
        $shortlisted = auth()->check() ? DB::table('property_shortlists')->where('user_id', auth()->id())->whereIn('property_id', $results->pluck('id'))->pluck('property_id')->all() : [];

        // Sidebar lists: localities and projects / societies with live listings in this city and tab (plus the city's known localities).
        $localities = $base()->whereNotNull('locality')->groupBy('locality')->selectRaw('locality as name, count(*) as n')->orderByDesc('n')->limit(40)->pluck('n', 'name');
        foreach ($o['localities'][$cityName] ?? [] as $known) if (!$localities->keys()->contains(fn ($k) => strcasecmp($k, $known) === 0)) $localities[$known] = 0;
        $projects = $base()->whereNotNull('society')->groupBy('society')->selectRaw('society as name, count(*) as n')->pluck('n', 'name');
        DB::table('property_sale_terms')->whereIn('property_id', $base()->select('id'))->whereNotNull('project_name')->groupBy('project_name')
            ->selectRaw('project_name as name, count(*) as n')->pluck('n', 'name')->each(fn ($n, $name) => $projects[$name] = max($n, $projects[$name] ?? 0));
        $projects = $projects->sortDesc()->take(30);

        // Budget steps follow the city's currency (INR when no city is chosen).
        $country = $cityName ? (collect($o['cities'])->search(fn ($list) => in_array($cityName, $list, true)) ?: 'India') : 'India';
        $currency = $o['countries'][$country]['currency'] ?? 'INR';
        return view('properties.results', compact('mode', 'city', 'cityName', 'areas', 'f', 'results', 'shortlisted', 'localities', 'projects', 'currency') + ['cities' => $this->cities(),
            'localityRatings' => $city ? app(\App\Services\Property\LocalityRatings::class)->forCity($city) : []]);
    }

    public function show(Property $property)
    {
        $mine = auth()->id() === $property->user_id || auth()->user()?->isOwner();
        abort_unless($property->status === 'active' || $mine, 404);
        if (!$mine) {
            event(new PropertyViewed($property, Visitor::id(), auth()->id()));
            Property::whereKey($property->id)->increment('views');
            // One count per visitor per listing per day, for "N people viewed this property yesterday".
            if (cache()->add('property-view:' . $property->id . ':' . (auth()->id() ?: sha1(request()->ip() . request()->userAgent())) . ':' . today()->toDateString(), 1, now()->endOfDay())) {
                DB::table('property_view_days')->upsert([['property_id' => $property->id, 'day' => today()->toDateString(), 'views' => 1]], ['property_id', 'day'], ['views' => DB::raw('views + 1')]);
            }
        }
        $property->load(['media', 'rentTerms', 'saleTerms', 'pgTerms', 'owner', 'commercialDetail', 'facilities', 'project.images', 'project.builder']);
        $nearby = ($geo = NearbyPlace::geoKey($property->latitude, $property->longitude))
            ? NearbyPlace::where('geo_key', $geo)->where('category', '!=', '_none')->orderBy('distance_m')->get()->groupBy('category') : collect();
        $similar = $this->modeQuery($property->mode())->where('id', '!=', $property->id)->where('city_slug', $property->city_slug)->where('kind', $property->kind)
            ->with(['media', 'rentTerms', 'saleTerms', 'pgTerms', 'owner:id,name,owner_type,company_name,is_featured'])->orderByRaw('locality = ? desc', [$property->locality])->latest('published_at')->limit(6)->get();
        $user = auth()->user();
        $ratings = app(\App\Services\Property\LocalityRatings::class);
        $localityKey = $property->locality && $property->city_slug ? \App\Models\Property\LocalityReview::key($property->locality) : null;
        $reviews = $localityKey ? ['summary' => $ratings->summary($property->city_slug, $localityKey), 'reviews' => $ratings->reviews($property->city_slug, $localityKey, 'recommended', 30),
            'localityKey' => $localityKey, 'voted' => auth()->check() ? DB::table('locality_review_votes')->where('user_id', auth()->id())->pluck('helpful', 'locality_review_id')->all() : []] : null;
        $saves = DB::table('property_shortlists')->where('property_id', $property->id)->selectRaw('count(*) as total, sum(created_at >= ?) as recent', [now()->subDay()])->first();
        return view('properties.show', [
            'p' => $property, 'similar' => $similar, 'nearby' => $nearby, 'mine' => $mine,
            'visitSlots' => $mine ? [] : app(\App\Services\Property\VisitSlots::class)->available($property),
            'myVisit' => $user ? \App\Models\Property\SiteVisit::upcoming()->where(['user_id' => $user->id, 'visitable_type' => 'property', 'visitable_id' => $property->id])->first() : null, 'saves' => $saves, 'localityReviews' => $reviews,
            'viewedYesterday' => (int) DB::table('property_view_days')->where(['property_id' => $property->id, 'day' => today()->subDay()->toDateString()])->value('views'),
            'ownerListings' => Property::live()->where('owner_id', $property->owner_id)->get(['locality', 'city']),
            'revealed' => $user && ($mine || PropertyLead::where(['property_id' => $property->id, 'user_id' => $user->id, 'kind' => 'phone'])->exists()),
            'shortlisted' => $user && DB::table('property_shortlists')->where(['user_id' => $user->id, 'property_id' => $property->id])->exists(),
        ]);
    }

    /** Signed-in visitors see the owner's number; each reveal is a lead for the owner. */
    public function phone(Request $request, Property $property)
    {
        abort_unless($property->status === 'active', 404);
        if ($request->user()->id !== $property->user_id) {
            PropertyLead::firstOrCreate(['property_id' => $property->id, 'user_id' => $request->user()->id, 'kind' => 'phone'], ['name' => $request->user()->name, 'email' => $request->user()->email]);
        }
        $owner = $property->owner;
        $digits = preg_replace('/\D/', '', $owner->phone);
        if (strlen($digits) === 10 && $property->country === 'India') $digits = '91' . $digits;   // WhatsApp needs the country code
        return response()->json(['phone' => $owner->phone, 'whatsapp' => $owner->whatsapp ? 'https://wa.me/' . $digits : null, 'name' => $owner->name]);
    }

    public function message(Request $request, Property $property)
    {
        abort_unless($property->status === 'active', 404);
        $dials = collect(config('properties.countries'))->pluck('dial')->unique()->all();
        $data = $request->validate(['sender_type' => 'nullable|in:individual,dealer', 'name' => 'required|string|max:120', 'dial' => 'nullable|in:' . implode(',', $dials),
            'phone' => ['required', 'string', 'max:20', 'regex:/^\+?[0-9 ()-]{7,20}$/'], 'email' => 'nullable|email:filter|max:190',
            'message' => 'nullable|string|max:400', 'visit_at' => 'nullable|date|after:now', 'agree' => 'accepted'],
            ['agree.accepted' => 'Please agree to the privacy policy to send your enquiry.']);
        $phone = str_starts_with(trim($data['phone']), '+') || empty($data['dial']) ? trim($data['phone']) : $data['dial'] . ' ' . trim($data['phone']);
        $lead = PropertyLead::create(['property_id' => $property->id, 'user_id' => $request->user()?->id, 'kind' => !empty($data['visit_at']) ? 'visit' : 'message', 'sender_type' => $data['sender_type'] ?? 'individual',
            'name' => $data['name'], 'phone' => $phone, 'email' => $data['email'] ?? null, 'message' => $data['message'] ?? null, 'visit_at' => $data['visit_at'] ?? null]);
        // The owner hears about it by email as well as in My properties.
        $to = $property->owner->email ?: $property->user?->email;
        if ($to) rescue(fn () => \Illuminate\Support\Facades\Mail::to($to)->queue((new \App\Mail\PropertyEnquiryMail($property, $lead))->onQueue('emails')), null);
        return redirect()->to(route('properties.show', $property) . '#owner')->with('success', !empty($data['visit_at']) ? 'Visit request sent to the ' . strtolower($property->owner->typeLabel()) . '.' : 'Enquiry sent to the ' . strtolower($property->owner->typeLabel()) . '.');
    }

    /** "Property sold out? Incorrect data?" — one open report per visitor per listing. */
    public function report(Request $request, Property $property)
    {
        abort_unless($property->status === 'active', 404);
        $data = $request->validate(['reason' => 'required|in:' . implode(',', array_keys(config('properties.report_reasons'))), 'details' => 'nullable|string|max:1000']);
        $who = $request->user()?->id;
        $dupe = $who && $property->reports()->where(['user_id' => $who, 'status' => 'open'])->exists();
        if (!$dupe) $property->reports()->create($data + ['user_id' => $who]);
        return redirect()->to(route('properties.show', $property) . '#report')->with('success', 'Thanks — our team will check this listing.');
    }

    public function shortlist(Request $request, Property $property)
    {
        $key = ['user_id' => $request->user()->id, 'property_id' => $property->id];
        $on = !DB::table('property_shortlists')->where($key)->exists();
        $on ? DB::table('property_shortlists')->insert($key) : DB::table('property_shortlists')->where($key)->delete();
        return $request->expectsJson() ? response()->json(['shortlisted' => $on]) : back();
    }

    public function shortlisted(Request $request)
    {
        $items = Property::live()->whereIn('id', DB::table('property_shortlists')->where('user_id', $request->user()->id)->pluck('property_id'))
            ->with(['media', 'rentTerms', 'saleTerms', 'pgTerms'])->latest('published_at')->paginate(20);
        return view('properties.shortlist', ['items' => $items]);
    }

    /** Locality / society / landmark suggestions for the search box, from live listings in the city. */
    public function suggest(Request $request)
    {
        $data = $request->validate(['city' => 'nullable|string|max:90', 'q' => 'required|string|min:2|max:60']);
        $q = $data['q'];
        $rows = Property::live()->when($data['city'] ?? null, fn ($w, $v) => $w->where('city_slug', Str::slug($v)))
            ->where(fn ($w) => $w->where('locality', 'like', "%$q%")->orWhere('society', 'like', "%$q%")->orWhere('landmark', 'like', "%$q%"))
            ->limit(200)->get(['locality', 'society', 'landmark']);
        $known = collect(config('properties.localities'))->filter(fn ($_, $c) => empty($data['city']) || Str::slug($c) === Str::slug($data['city']))->flatten();
        $names = $rows->flatMap(fn ($r) => [$r->locality, $r->society, $r->landmark])->merge($known)->filter(fn ($v) => $v && stripos($v, $q) !== false)
            ->unique(fn ($v) => mb_strtolower($v))->take(10)->values();
        return response()->json($names);
    }

    /** Address → map position for the post-property form (OpenStreetMap Nominatim, cached; their policy allows light use). */
    public function geocode(Request $request)
    {
        $data = $request->validate(['q' => 'required|string|min:3|max:200']);
        $hit = Cache::remember('property:geocode:' . md5(mb_strtolower($data['q'])), now()->addDays(30), function () use ($data) {
            $r = rescue(fn () => Http::timeout(8)->withHeaders(['User-Agent' => config('properties.brand') . ' property listings (' . config('app.url') . ')'])
                ->get('https://nominatim.openstreetmap.org/search', ['q' => $data['q'], 'format' => 'json', 'limit' => 1])->json(), [], false);
            return isset($r[0]['lat']) ? ['lat' => (float) $r[0]['lat'], 'lng' => (float) $r[0]['lon'], 'label' => $r[0]['display_name'] ?? null] : null;
        });
        return $hit ? response()->json($hit) : response()->json(['message' => 'Location not found'], 404);
    }

    /** City dropdown: configured cities first (India first), then any city with live listings. */
    public function cities()
    {
        return Cache::remember('properties:cities', now()->addMinutes(30), function () {
            $configured = collect(config('properties.cities'))->flatten();
            $live = Property::live()->whereNotNull('city')->distinct()->pluck('city');
            return $configured->merge($live)->unique(fn ($c) => Str::slug($c))->values();
        });
    }

    private function modeQuery(string $mode)
    {
        return PropertySearch::mode($mode);
    }
}
