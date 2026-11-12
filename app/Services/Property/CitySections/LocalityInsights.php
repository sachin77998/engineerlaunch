<?php

namespace App\Services\Property\CitySections;

use App\Models\Property\LocalityReview;
use App\Services\Property\CityContext;
use Illuminate\Support\Facades\DB;

// Localities with the most listings: asking price per sq ft (and its change against the previous six months), rating and reviews.
class LocalityInsights extends CitySection
{
    public int $minutes = 30;

    public function data(CityContext $c, array $options): ?array
    {
        $rows = $c->listings()->whereNotNull('locality')->groupBy('locality')
            ->selectRaw("locality, count(*) as n,
                avg(case when listing = 'sale' and area_sqft > 0 and price_sort > 0 and published_at >= ? then price_sort / area_sqft end) as psf_now,
                avg(case when listing = 'sale' and area_sqft > 0 and price_sort > 0 and published_at < ? and published_at >= ? then price_sort / area_sqft end) as psf_before",
                [now()->subMonths(6), now()->subMonths(6), now()->subMonths(12)])
            ->orderByDesc('n')->limit($options['limit'] ?? 8)->get();
        if ($rows->isEmpty()) return null;
        $reviews = DB::table('locality_reviews')->where('city_slug', $c->slug())->where('status', 'published')->groupBy('locality_key')->selectRaw('locality_key, count(*) as n')->pluck('n', 'locality_key');
        $ratings = app(\App\Services\Property\LocalityRatings::class)->forCity($c->slug());
        $items = $rows->map(function ($r) use ($c, $reviews, $ratings) {
            $key = LocalityReview::key($r->locality);
            return ['name' => $r->locality, 'listings' => (int) $r->n, 'psf' => $r->psf_now ? round($r->psf_now) : null,
                'change' => $r->psf_now && $r->psf_before ? round(($r->psf_now - $r->psf_before) / $r->psf_before * 100, 1) : null,
                'rating' => $ratings[$key] ?? null, 'reviews' => (int) ($reviews[$key] ?? 0), 'url' => route('localities.show', [$c->slug(), $key])];
        });
        return ['items' => $items];
    }
}
