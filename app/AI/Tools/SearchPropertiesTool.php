<?php

namespace App\AI\Tools;

use App\Models\Property\Project;
use App\Models\Property\Property;
use App\Models\Property\PropertyCity;
use App\Services\Property\PropertyFormat;
use Illuminate\Support\Str;

// Reads a property question ("2 bhk flat for rent in whitewfield bangalore under 30k") into filters and returns
// matching live listings and projects from our own database, plus the search page that shows all of them.
class SearchPropertiesTool
{
    private const CITY_ALIASES = ['bangalore' => 'bengaluru', 'gurgaon' => 'gurugram', 'bombay' => 'mumbai', 'calcutta' => 'kolkata', 'madras' => 'chennai', 'poona' => 'pune', 'delhi ncr' => 'delhi'];

    public function parse(string $message): array
    {
        $m = ' ' . mb_strtolower($message) . ' ';
        $f = ['mode' => 'buy'];
        $commercial = (bool) preg_match('/\b(office|shop|showroom|warehouse|godown|commercial|co-?working|retail|pre-?leased)\b/', $m);
        $rent = (bool) preg_match('/\b(rent|rental|lease|on rent|kiraye|kiraya|pg|paying guest|flatmate|hostel)\b/', $m) && !str_contains($m, 'pre-leased') && !str_contains($m, 'preleased');
        $f['mode'] = $commercial ? ($rent ? 'commercial-rent' : 'commercial-buy') : ($rent ? 'rent' : 'buy');
        if (preg_match('/\b(pg|paying guest|hostel)\b/', $m)) $f['kind'] = 'pg';
        if (preg_match('/\b(\d)\s*(?:bhk|bed ?rooms?|bedroom)\b/', $m, $x)) $f['bhk'] = $x[1];
        if (preg_match('/\b(1\s*rk|studio)\b/', $m)) $f['bhk'] = '1rk';
        foreach (['plot' => '/\b(plot|land)\b/', 'independent_house' => '/\b(villa|independent house|bungalow|kothi)\b/', 'builder_floor' => '/\bbuilder floor\b/', 'apartment' => '/\b(flat|apartment)\b/'] as $type => $re) {
            if (!$commercial && preg_match($re, $m)) { $f['type'] = $type; break; }
        }
        if ($commercial && preg_match('/\bpre-?leased\b/', $m)) $f['pre_leased'] = true;

        // Budget: "under 50 lakh", "below 1.5 cr", "max 25k", "upto 30000"
        if (preg_match('/(?:under|below|upto|up to|max(?:imum)?|within|less than|budget(?: of)?)\s*(?:rs\.?|₹|inr)?\s*([\d.,]+)\s*(cr(?:ore)?s?|l(?:akh|ac|acs|akhs)?|k|thousand)?\b/u', $m, $x)) {
            $n = (float) str_replace(',', '', $x[1]);
            $unit = $x[2] ?? '';
            $n *= match (true) { str_starts_with($unit, 'c') => 1e7, str_starts_with($unit, 'l') => 1e5, $unit === 'k' || $unit === 'thousand' => 1e3, default => 1 };
            if ($n > 0) $f['max'] = $n;
        }

        // City: our cities table first, then common old names.
        $cities = PropertyCity::where('is_active', true)->get(['name', 'slug'])->sortByDesc(fn ($c) => mb_strlen($c->name));
        foreach ($cities as $c) if (str_contains($m, ' ' . mb_strtolower($c->name)) || str_contains($m, ' ' . str_replace('-', ' ', $c->slug))) { $f['city'] = $c->slug; $f['city_name'] = $c->name; break; }
        if (empty($f['city'])) foreach (self::CITY_ALIASES as $alias => $slug) if (str_contains($m, $alias) && ($c = $cities->firstWhere('slug', $slug))) { $f['city'] = $slug; $f['city_name'] = $c->name; break; }

        // Locality: one we have live listings in (for that city when known).
        if (preg_match('/\b(?:in|at|near|around)\s+([a-z][a-z .\-]{2,40}?)(?=\s+(?:in|under|below|for|with|near|,)|[,?.!]|\s*$)/', trim($m), $x)) {
            $cand = trim($x[1]);
            if (empty($f['city']) || mb_strtolower($f['city_name']) !== $cand) {
                $loc = Property::live()->when($f['city'] ?? null, fn ($q, $c) => $q->where('city_slug', $c))->whereNotNull('locality')
                    ->where('locality', 'like', '%' . $cand . '%')->value('locality');
                if ($loc) $f['locality'] = $loc;
            }
        }
        return $f;
    }

    public function execute(array $f): array
    {
        $mode = config('properties.modes.' . $f['mode']) ?? config('properties.modes.buy');
        $types = config('properties.home_types');
        $q = Property::live()->where('listing', $mode['listing'])->where('segment', $mode['segment'])
            ->when($mode['kinds'] ?? null, fn ($x, $k) => $x->whereIn('kind', $k))
            ->when($f['kind'] ?? null, fn ($x, $k) => $x->where('kind', $k))
            ->when($f['city'] ?? null, fn ($x, $c) => $x->where('city_slug', $c))
            ->when($f['locality'] ?? null, fn ($x, $l) => $x->where('locality', $l))
            ->when($f['bhk'] ?? null, fn ($x, $b) => $x->where('bhk', $b))
            ->when(isset($f['type']), fn ($x) => $types[$f['type']][2] === null ? $x->where('kind', 'land') : $x->whereIn('building_type', $types[$f['type']][2]))
            ->when($f['max'] ?? null, fn ($x, $max) => $x->where('price_sort', '<=', $max))
            ->when($f['pre_leased'] ?? null, fn ($x) => $x->whereHas('commercialDetail', fn ($c) => $c->where('is_pre_leased', true)));
        $total = (clone $q)->count();
        $listings = $q->with(['rentTerms', 'saleTerms', 'pgTerms', 'commercialDetail'])->latest('published_at')->limit(6)->get()->map(fn ($p) => [
            'title' => $p->title, 'price' => $p->headlinePrice(), 'locality' => $p->locality, 'city' => $p->city, 'area' => $p->areaLabel(),
            'pre_leased_yield' => $p->commercialDetail?->roi($p->headlineAmount()), 'url' => route('properties.show', $p),
        ])->all();
        $projects = $f['mode'] === 'buy' || $f['mode'] === 'commercial-buy'
            ? Project::live()->with('builder:id,name')->when($f['city'] ?? null, fn ($x, $c) => $x->where('city_slug', $c))
                ->when($f['locality'] ?? null, fn ($x, $l) => $x->where('locality', $l))
                ->where('segment', $mode['segment'])->when($f['max'] ?? null, fn ($x, $max) => $x->where(fn ($w) => $w->whereNull('price_min')->orWhere('price_min', '<=', $max)))
                ->orderByDesc('is_featured')->latest('updated_at')->limit(4)->get()
                ->map(fn ($p) => ['name' => $p->name, 'builder' => $p->builder?->name, 'locality' => $p->locality, 'price' => $p->priceRange(), 'status' => $p->possessionLabel() ?? $p->statusLabel(), 'url' => route('projects.show', $p)])->all()
            : [];
        $query = array_filter(['bhk' => isset($f['bhk']) ? [$f['bhk']] : null, 'type' => isset($f['type']) ? [$f['type']] : null, 'max' => $f['max'] ?? null,
            'kind' => $f['kind'] ?? null, 'loc' => isset($f['locality']) ? [$f['locality']] : null]);
        return [
            'filters' => array_merge($f, ['max_label' => isset($f['max']) ? PropertyFormat::short($f['max']) : null]),
            'total' => $total, 'listings' => $listings, 'projects' => $projects,
            'search_url' => route('properties.search', array_filter([$f['mode'], $f['city'] ?? null])) . ($query ? '?' . http_build_query($query) : ''),
            'city_url' => isset($f['city']) ? route('real-estate.city', $f['city']) : null,
        ];
    }

    public static function describe(array $f): string
    {
        $what = isset($f['bhk']) ? strtoupper($f['bhk']) . ' ' : '';
        $what .= isset($f['type']) ? Str::lower(config('properties.home_types.' . $f['type'] . '.1', 'homes')) : (str_starts_with($f['mode'], 'commercial') ? 'commercial spaces' : 'properties');
        $what .= str_contains($f['mode'], 'rent') ? ' for rent' : ' for sale';
        if (!empty($f['locality'])) $what .= ' in ' . $f['locality'];
        if (!empty($f['city_name'])) $what .= (empty($f['locality']) ? ' in ' : ', ') . $f['city_name'];
        if (!empty($f['max_label'])) $what .= ' under ' . $f['max_label'];
        return $what;
    }
}
