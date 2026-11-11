<?php

namespace App\Http\Controllers;

use App\Models\Property\LocalityReview;
use App\Models\Property\Property;
use App\Services\Property\LocalityRatings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// Locality reviews: the locality page, writing a review, helpful votes and moderation.
class LocalityReviewController extends Controller
{
    public function __construct(private LocalityRatings $ratings) {}

    public function show(Request $request, string $city, string $locality)
    {
        [$cityName, $name] = $this->names($city, $locality);
        $sort = array_key_exists($request->query('sort'), LocalityRatings::SORTS) ? $request->query('sort') : 'recommended';
        $listings = Property::live()->where('city_slug', $city)->whereRaw('lower(locality) = ?', [mb_strtolower($name)])
            ->with(['media', 'rentTerms', 'saleTerms', 'pgTerms', 'owner:id,owner_type,is_featured'])->latest('published_at')->limit(12)->get();
        return view('properties.locality', [
            'city' => $city, 'cityName' => $cityName, 'locality' => $locality, 'name' => $name, 'sort' => $sort,
            'summary' => $this->ratings->summary($city, $locality), 'reviews' => $this->ratings->reviews($city, $locality, $sort),
            'listings' => $listings, 'mine' => auth()->check() ? LocalityReview::where(['user_id' => auth()->id(), 'city_slug' => $city, 'locality_key' => $locality])->first() : null,
            'voted' => auth()->check() ? DB::table('locality_review_votes')->where('user_id', auth()->id())->pluck('helpful', 'locality_review_id')->all() : [],
        ]);
    }

    public function create(Request $request, string $city, string $locality)
    {
        [$cityName, $name] = $this->names($city, $locality);
        $review = LocalityReview::where(['user_id' => $request->user()->id, 'city_slug' => $city, 'locality_key' => $locality])->first();
        return view('properties.locality-review', compact('city', 'cityName', 'locality', 'name', 'review'));
    }

    public function store(Request $request, string $city, string $locality)
    {
        [$cityName, $name] = $this->names($city, $locality);
        $stars = 'required|integer|between:1,5';
        $data = $request->validate(['reviewer_role' => 'required|in:' . implode(',', array_keys(config('properties.reviewer_roles'))), 'rating' => $stars,
            'connectivity' => $stars, 'lifestyle' => $stars, 'safety' => $stars, 'green_area' => $stars, 'society' => 'nullable|string|max:150',
            'positives' => 'nullable|required_without:negatives|string|min:20|max:2000', 'negatives' => 'nullable|required_without:positives|string|min:20|max:2000'],
            ['positives.required_without' => 'Tell us what is good or what is not (at least 20 characters).']);
        LocalityReview::updateOrCreate(['user_id' => $request->user()->id, 'city_slug' => $city, 'locality_key' => $locality],
            $data + ['city' => $cityName, 'locality' => $name, 'status' => 'published']);
        $this->ratings->forget($city, $locality);
        return redirect()->route('localities.show', [$city, $locality])->with('success', 'Thanks for reviewing ' . $name . '.');
    }

    public function vote(Request $request, LocalityReview $review)
    {
        $data = $request->validate(['helpful' => 'required|boolean']);
        abort_if($review->user_id === $request->user()->id, 422, 'You cannot vote on your own review.');
        DB::table('locality_review_votes')->updateOrInsert(['locality_review_id' => $review->id, 'user_id' => $request->user()->id], ['helpful' => (bool) $data['helpful'], 'created_at' => now()]);
        $counts = DB::table('locality_review_votes')->where('locality_review_id', $review->id)->selectRaw('sum(helpful) as yes, sum(1 - helpful) as no')->first();
        $review->update(['helpful_yes' => (int) $counts->yes, 'helpful_no' => (int) $counts->no]);
        return $request->expectsJson() ? response()->json(['yes' => $review->helpful_yes, 'no' => $review->helpful_no]) : back();
    }

    public function admin(Request $request)
    {
        $reviews = LocalityReview::with('user:id,name,email')->when($request->query('q'), fn ($q, $v) => $q->where(fn ($w) => $w->where('locality', 'like', "%$v%")->orWhere('city', 'like', "%$v%")))
            ->latest()->paginate(50)->withQueryString();
        return view('properties.locality-admin', compact('reviews'));
    }

    public function toggle(LocalityReview $review)
    {
        $review->update(['status' => $review->status === 'published' ? 'hidden' : 'published']);
        $this->ratings->forget($review->city_slug, $review->locality_key);
        return back()->with('success', $review->status === 'hidden' ? 'Review hidden.' : 'Review published.');
    }

    /** Display names for the city and locality slugs: from listings, earlier reviews or the known-localities list. */
    private function names(string $city, string $locality): array
    {
        $cityName = app(PropertyController::class)->cities()->first(fn ($c) => Str::slug($c) === $city)
            ?? Property::where('city_slug', $city)->value('city') ?? LocalityReview::where('city_slug', $city)->value('city');
        abort_unless($cityName, 404);
        $name = LocalityReview::where(['city_slug' => $city, 'locality_key' => $locality])->value('locality')
            ?? Property::where('city_slug', $city)->whereNotNull('locality')->distinct()->pluck('locality')->first(fn ($l) => LocalityReview::key($l) === $locality)
            ?? collect(config("properties.localities.$cityName", []))->first(fn ($l) => LocalityReview::key($l) === $locality);
        abort_unless($name, 404);
        return [$cityName, $name];
    }
}
