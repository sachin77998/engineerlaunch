<?php

namespace App\Services\Property;

use App\Models\Property\LocalityReview;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

// Locality review summaries: average out of 5, how many gave each star, feature averages and the share of positive reviews.
class LocalityRatings
{
    public const SORTS = ['recommended' => 'Recommended', 'negative' => 'Negative first', 'recent' => 'Recent first'];

    public function summary(string $citySlug, string $localityKey): ?array
    {
        return Cache::remember("locality-rating:$citySlug:$localityKey", now()->addMinutes(10), function () use ($citySlug, $localityKey) {
            $rows = LocalityReview::published()->where(['city_slug' => $citySlug, 'locality_key' => $localityKey])->get();
            if ($rows->isEmpty()) return null;
            return [
                'count' => $rows->count(),
                'average' => round($rows->avg(fn ($r) => $r->score()), 1),
                'stars' => collect([5, 4, 3, 2, 1])->mapWithKeys(fn ($s) => [$s => $rows->filter(fn ($r) => (int) round($r->score()) === $s)->count()])->all(),
                'features' => collect(array_keys(config('properties.review_features')))->mapWithKeys(fn ($f) => [$f => round($rows->avg($f), 1)])->all(),
                'positive' => (int) round($rows->filter(fn ($r) => $r->score() >= 3)->count() / $rows->count() * 100),
            ];
        });
    }

    public function reviews(string $citySlug, string $localityKey, string $sort = 'recommended', ?int $limit = null): Collection
    {
        $q = LocalityReview::published()->with('user:id,name')->where(['city_slug' => $citySlug, 'locality_key' => $localityKey]);
        match ($sort) {
            'negative' => $q->orderBy('rating')->orderByDesc('created_at'),
            'recent' => $q->latest(),
            // Most found helpful first, then the more detailed ones.
            default => $q->orderByRaw('(helpful_yes - helpful_no) desc')->orderByRaw('length(coalesce(positives, \'\')) + length(coalesce(negatives, \'\')) desc'),
        };
        return $limit ? $q->limit($limit)->get() : $q->get();
    }

    /** [locality_key => average] for a city, for the star badges in the results sidebar. */
    public function forCity(string $citySlug): array
    {
        return Cache::remember("locality-ratings:$citySlug", now()->addMinutes(10), fn () => LocalityReview::published()->where('city_slug', $citySlug)->get()
            ->groupBy('locality_key')->map(fn ($g) => round($g->avg(fn ($r) => $r->score()), 1))->all());
    }

    public function forget(string $citySlug, string $localityKey): void
    {
        Cache::forget("locality-rating:$citySlug:$localityKey");
        Cache::forget("locality-ratings:$citySlug");
    }
}
