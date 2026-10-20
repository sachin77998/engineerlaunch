<?php

namespace App\Http\Controllers;

use App\Services\Market\MarketData;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/** Financial calculators: a hub of cards and one page per calculator (formulas run in the browser). */
class CalculatorController extends Controller
{
    public function index()
    {
        $items = collect(config('calculators.items'));
        return view('calculators.index', ['groups' => collect(config('calculators.groups'))->map(fn ($title, $key) => ['title' => $title, 'items' => $items->where('group', $key)])]);
    }

    public function show(string $slug)
    {
        $item = config("calculators.items.{$slug}");
        abort_unless($item, 404);
        $metal = isset($item['metal']) ? $this->metalHistory($item['metal']) : null;
        $related = collect(config('calculators.items'))->where('group', $item['group'])->except($slug)->take(6);
        return view('calculators.show', compact('slug', 'item', 'metal', 'related'));
    }

    /** Daily price history (₹ per unit) of gold, silver or copper; weekly points beyond the last year to keep the page light. */
    private function metalHistory(string $commodity): ?array
    {
        return Cache::remember('calculators:metal:' . $commodity . ':' . now()->toDateString(), now()->addHours(6), function () use ($commodity) {
            $rows = DB::table('commodity_prices')->where('commodity', $commodity)->where('variant', 'international')
                ->where('priced_on', '>=', now()->subYears(5)->toDateString())->orderBy('priced_on')->get(['priced_on', 'price', 'unit']);
            if ($rows->isEmpty()) return null;
            $cutoff = now()->subYear()->toDateString();
            $points = $rows->filter(fn ($r, $i) => $r->priced_on >= $cutoff || $i % 5 === 0 || $i === $rows->count() - 1)->values();
            return ['unit' => $rows->last()->unit, 'latest' => (float) $rows->last()->price, 'latest_on' => $rows->last()->priced_on,
                'dates' => $points->pluck('priced_on'), 'prices' => $points->pluck('price')->map(fn ($p) => (float) $p), 'source' => MarketData::METAL_SOURCE];
        });
    }
}
