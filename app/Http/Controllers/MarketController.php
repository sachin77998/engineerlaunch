<?php

namespace App\Http\Controllers;

use App\Services\Market\MarketData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

/** Market data: model-wise vehicle sales analytics and commodity prices, plus the related news topics. */
class MarketController extends Controller
{
    public function __construct(private MarketData $market) {}

    public function index(Request $request)
    {
        $ready = Schema::hasTable('vehicle_sales');
        $segments = $ready ? $this->market->segments() : collect();
        $segment = $request->query('segment', $segments->first() ?? 'car');
        $brand = $request->query('brand');
        return view('market.index', [
            'segments' => $segments, 'segment' => $segment, 'brand' => $brand,
            'brands' => $ready ? $this->market->brandTotals($segment) : collect(),
            'models' => $ready ? $this->market->vehicleTable($segment, $brand) : collect(),
            'prices' => $ready ? $this->market->latestPrices() : collect(),
        ]);
    }

    /** CSV templates for the Production Setup upload. */
    public function template(string $type)
    {
        abort_unless(in_array($type, ['vehicles', 'prices'], true), 404);
        $rows = $type === 'vehicles'
            ? [MarketData::VEHICLE_COLUMNS, ['car', 'Maruti Suzuki', 'Baleno', '2026-09', '15000', '14200', '', 'Company press release', 'https://...']]
            : [MarketData::PRICE_COLUMNS, ['copper', 'LME grade A', 'kg', '865.50', '', '2026-10-12', 'MCX']];
        $csv = implode("\n", array_map(fn ($r) => implode(',', $r), $rows)) . "\n";
        return response($csv, 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename=\"market-{$type}-template.csv\""]);
    }

    public function upload(Request $request)
    {
        $data = $request->validate(['type' => 'required|in:vehicles,prices', 'file' => 'required|file|mimes:csv,txt|max:5120']);
        try {
            $count = $this->market->import($data['type'], $request->file('file')->getRealPath());
        } catch (\Throwable $e) {
            return back()->withErrors(['file' => $e->getMessage()]);
        }
        return back()->with('success', "Imported {$count} rows into " . ($data['type'] === 'vehicles' ? 'vehicle sales' : 'commodity prices') . '.');
    }
}
