<?php

namespace App\Http\Controllers;

use App\Models\Property\Property;
use App\Models\Property\PropertyLead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

// Property marketplace for visitors: search (Buy / Rent / Commercial), results, the listing page and contacting the owner.
class PropertyController extends Controller
{
    public const SORTS = ['newest' => 'Newest first', 'price_asc' => 'Price: low to high', 'price_desc' => 'Price: high to low', 'area_desc' => 'Largest first'];

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
        $f = $request->validate([
            'q' => 'nullable|array|max:3', 'q.*' => 'nullable|string|max:100',
            'kind' => 'nullable|string|max:24',
            'type' => 'nullable|array', 'type.*' => 'in:' . implode(',', array_keys($o['home_types'])),
            'bhk' => 'nullable|array', 'bhk.*' => 'in:' . implode(',', array_unique(array_merge(array_keys($o['bhk']), array_keys($o['bhk_groups'])))),
            'possession' => 'nullable|array', 'possession.*' => 'in:new_launch,under_construction,ready', 'new_projects' => 'nullable|boolean',
            'posted_by' => 'nullable|array', 'posted_by.*' => 'in:' . implode(',', array_keys($o['posted_by'])),
            'furnishing' => 'nullable|array', 'furnishing.*' => 'in:' . implode(',', array_keys($o['furnishing'])),
            'tenant' => 'nullable|in:' . implode(',', array_keys($o['preferred_tenants'])), 'gender' => 'nullable|in:male,female',
            'min' => 'nullable|numeric|min:0', 'max' => 'nullable|numeric|min:0',
            'area_min' => 'nullable|numeric|min:0', 'area_max' => 'nullable|numeric|min:0', 'area_unit' => 'nullable|in:' . implode(',', array_keys($o['area_units'])),
            'loc' => 'nullable|array|max:30', 'loc.*' => 'string|max:150', 'project' => 'nullable|array|max:30', 'project.*' => 'string|max:160',
            'amen' => 'nullable|array', 'amen.*' => 'in:' . implode(',', $o['amenity_filters']),
            'verified' => 'nullable|boolean', 'photos' => 'nullable|boolean', 'videos' => 'nullable|boolean', 'rera' => 'nullable|boolean',
            'sort' => 'nullable|in:' . implode(',', array_keys(self::SORTS)),
        ]);
        $cityName = $city ? ($this->cities()->first(fn ($c) => Str::slug($c) === $city) ?? Property::where('city_slug', $city)->value('city')) : null;
        abort_if($city && !$cityName, 404);
        $areas = collect($f['q'] ?? [])->map(fn ($v) => trim((string) $v))->filter()->take(3)->values();
        $bhk = collect($f['bhk'] ?? [])->flatMap(fn ($b) => $o['bhk_groups'][$b] ?? [$b])->unique()->values()->all();
        $unit = $f['area_unit'] ?? 'sqft';
        $sqft = fn ($v) => \App\Services\Property\PropertyFormat::toSqft((float) $v, $unit);
        $base = fn () => $this->modeQuery($mode)->when($city, fn ($q) => $q->where('city_slug', $city));

        $query = $base()
            ->when($areas->isNotEmpty(), fn ($q) => $q->where(fn ($w) => $areas->each(fn ($a) => $w->orWhere('locality', 'like', "%$a%")->orWhere('society', 'like', "%$a%")
                ->orWhere('landmark', 'like', "%$a%")->orWhere('street', 'like', "%$a%"))))
            ->when($f['kind'] ?? null, fn ($q, $v) => $q->where('kind', $v))
            // Type of property: a plot is the land kind; the rest are building types of a house or flat.
            ->when($f['type'] ?? null, fn ($q, $types) => $q->where(fn ($w) => collect($types)->each(fn ($t) => $o['home_types'][$t][2] === null
                ? $w->orWhere('kind', 'land')
                : $w->orWhere(fn ($x) => $x->where('kind', '!=', 'land')->whereIn('building_type', $o['home_types'][$t][2])))))
            ->when($bhk, fn ($q, $v) => $q->whereIn('bhk', $v))
            ->when($f['furnishing'] ?? null, fn ($q, $v) => $q->whereIn('furnishing', $v))
            ->when($f['possession'] ?? null, fn ($q, $v) => $q->whereHas('saleTerms', fn ($s) => $s->where(function ($w) use ($v) {
                $stages = array_values(array_diff($v, ['new_launch']));
                if ($stages) $w->whereIn('possession', $stages);
                if (in_array('new_launch', $v, true)) $w->orWhere('is_new_project', true);
            })))
            ->when($f['new_projects'] ?? null, fn ($q) => $q->whereHas('saleTerms', fn ($s) => $s->where('is_new_project', true)))
            ->when($f['posted_by'] ?? null, fn ($q, $v) => $q->whereHas('owner', fn ($w) => $w->where(function ($x) use ($v) {
                foreach ($v as $by) match ($by) {
                    'owner' => $x->orWhere('owner_type', 'owner'), 'builder' => $x->orWhere('owner_type', 'builder'),
                    'dealer' => $x->orWhere('owner_type', 'agent'), 'featured' => $x->orWhere(fn ($y) => $y->where('owner_type', 'agent')->where('is_featured', true)),
                };
            })))
            ->when($f['tenant'] ?? null, fn ($q, $v) => $q->whereHas('rentTerms', fn ($r) => $r->where(fn ($w) => $w->whereJsonContains('preferred_tenants', $v)->orWhereJsonContains('preferred_tenants', 'any')->orWhereNull('preferred_tenants'))))
            ->when($f['gender'] ?? null, fn ($q, $v) => $q->whereHas('pgTerms', fn ($p) => $p->whereIn('gender', [$v, 'any'])))
            ->when(isset($f['min']), fn ($q) => $q->where('price_sort', '>=', $f['min']))
            ->when(isset($f['max']), fn ($q) => $q->where('price_sort', '<=', $f['max']))
            ->when(isset($f['area_min']), fn ($q) => $q->where('area_sqft', '>=', $sqft($f['area_min'])))
            ->when(isset($f['area_max']), fn ($q) => $q->where('area_sqft', '<=', $sqft($f['area_max'])))
            ->when($f['verified'] ?? null, fn ($q) => $q->where('is_verified', true))
            ->when($f['photos'] ?? null, fn ($q) => $q->whereHas('media', fn ($m) => $m->where('type', 'photo')))
            ->when($f['videos'] ?? null, fn ($q) => $q->whereHas('media', fn ($m) => $m->where('type', 'video')))
            // RERA approved: posted by someone who gave a RERA registration number.
            ->when($f['rera'] ?? null, fn ($q) => $q->whereHas('owner', fn ($w) => $w->whereNotNull('rera_number')->where('rera_number', '!=', '')))
            ->when($f['loc'] ?? null, fn ($q, $v) => $q->whereIn('locality', $v))
            ->when($f['project'] ?? null, fn ($q, $v) => $q->where(fn ($w) => $w->whereIn('society', $v)->orWhereHas('saleTerms', fn ($s) => $s->whereIn('project_name', $v))))
            // Every ticked amenity must be present; "gated" also counts the gated-security detail and highlight, "parking" any parking count.
            ->when($f['amen'] ?? null, function ($q, $v) {
                foreach ($v as $a) $q->where(fn ($w) => $w->whereJsonContains('amenities', $a)
                    ->when($a === 'gated', fn ($x) => $x->orWhereJsonContains('highlights', 'gated')->orWhere('details->gated', 'yes'))
                    ->when($a === 'parking', fn ($x) => $x->orWhere('details->covered_parking', '>', 0)->orWhere('details->open_parking', '>', 0)));
                return $q;
            });
        match ($f['sort'] ?? 'newest') {
            'price_asc' => $query->orderByRaw('price_sort is null')->orderBy('price_sort'),
            'price_desc' => $query->orderByDesc('price_sort'),
            'area_desc' => $query->orderByDesc('area_sqft'),
            default => $query->latest('published_at'),
        };
        $results = $query->with(['media', 'rentTerms', 'saleTerms', 'pgTerms', 'owner:id,owner_type,is_featured'])->paginate(20)->withQueryString();
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
            Property::whereKey($property->id)->increment('views');
            // One count per visitor per listing per day, for "N people viewed this property yesterday".
            if (cache()->add('property-view:' . $property->id . ':' . (auth()->id() ?: sha1(request()->ip() . request()->userAgent())) . ':' . today()->toDateString(), 1, now()->endOfDay())) {
                DB::table('property_view_days')->upsert([['property_id' => $property->id, 'day' => today()->toDateString(), 'views' => 1]], ['property_id', 'day'], ['views' => DB::raw('views + 1')]);
            }
        }
        $property->load(['media', 'rentTerms', 'saleTerms', 'pgTerms', 'owner']);
        $similar = $this->modeQuery($property->mode())->where('id', '!=', $property->id)->where('city_slug', $property->city_slug)->where('kind', $property->kind)
            ->with(['media', 'rentTerms', 'saleTerms', 'pgTerms', 'owner:id,name,owner_type,company_name,is_featured'])->orderByRaw('locality = ? desc', [$property->locality])->latest('published_at')->limit(6)->get();
        $user = auth()->user();
        $ratings = app(\App\Services\Property\LocalityRatings::class);
        $localityKey = $property->locality && $property->city_slug ? \App\Models\Property\LocalityReview::key($property->locality) : null;
        $reviews = $localityKey ? ['summary' => $ratings->summary($property->city_slug, $localityKey), 'reviews' => $ratings->reviews($property->city_slug, $localityKey, 'recommended', 30),
            'localityKey' => $localityKey, 'voted' => auth()->check() ? DB::table('locality_review_votes')->where('user_id', auth()->id())->pluck('helpful', 'locality_review_id')->all() : []] : null;
        $saves = DB::table('property_shortlists')->where('property_id', $property->id)->selectRaw('count(*) as total, sum(created_at >= ?) as recent', [now()->subDay()])->first();
        return view('properties.show', [
            'p' => $property, 'similar' => $similar, 'mine' => $mine, 'saves' => $saves, 'localityReviews' => $reviews,
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
        $m = config("properties.modes.$mode");
        return Property::live()->where('listing', $m['listing'])->where('segment', $m['segment'])->when($m['kinds'] ?? null, fn ($q, $kinds) => $q->whereIn('kind', $kinds));
    }
}
