<?php

namespace App\Http\Controllers;

use App\Models\UsListedCompany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class UsMarketController extends Controller
{
    private const SORTS = ['market_cap' => 'Market cap', 'change_pct' => 'Change %', 'volume' => 'Volume', 'last_price' => 'Price', 'name' => 'Name'];

    public function index(Request $request)
    {
        $f = $request->validate(['q' => 'nullable|string|max:80', 'exchange' => 'nullable|string|max:20', 'sector' => 'nullable|string|max:80','sort' => 'nullable|in:' . implode(',', array_keys(self::SORTS)), 'dir' => 'nullable|in:asc,desc']);
        $sort = $f['sort'] ?? 'market_cap';
        $dir = $f['dir'] ?? ($sort === 'name' ? 'asc' : 'desc');
        $stocks = UsListedCompany::query()->with('company:id,slug')->when($f['q'] ?? null, fn ($q, $term) => $q->where(fn ($w) => $w->where('ticker', strtoupper($term))->orWhere('ticker', 'like', strtoupper($term) . '%')->orWhere('name', 'like', '%' . addcslashes($term, '%_\\') . '%')))->when($f['exchange'] ?? null, fn ($q, $v) => $q->where('exchange', $v))->when($f['sector'] ?? null, fn ($q, $v) => $q->where('sector', $v))->orderByRaw($sort . ' IS NULL')->orderBy($sort, $dir)->paginate(50)->withQueryString();
        $facets = Cache::remember('us-market:facets', now()->addHours(6), fn () => ['exchanges' => DB::table('us_listed_companies')->whereNotNull('exchange')->distinct()->orderBy('exchange')->pluck('exchange'),'sectors' => DB::table('us_listed_companies')->whereNotNull('sector')->distinct()->orderBy('sector')->pluck('sector'),]);
        $movers = Cache::remember('us-market:movers:' . DB::table('us_listed_companies')->max('updated_at'), now()->addHour(), function () {
            $base = fn () => UsListedCompany::query()->with('company:id,slug')->where('market_cap', '>=', 2e9)->whereNotNull('change_pct');
            return ['gainers' => $base()->orderByDesc('change_pct')->limit(6)->get(), 'losers' => $base()->orderBy('change_pct')->limit(6)->get(),'active' => UsListedCompany::query()->with('company:id,slug')->whereNotNull('volume')->orderByDesc('volume')->limit(6)->get(),'breadth' => ['up' => DB::table('us_listed_companies')->where('change_pct', '>', 0)->count(), 'down' => DB::table('us_listed_companies')->where('change_pct', '<', 0)->count()],'price_date' => DB::table('us_listed_companies')->max('price_date')];
        });
        return view('markets.us', ['stocks' => $stocks, 'f' => $f, 'sort' => $sort, 'dir' => $dir, 'sorts' => self::SORTS, 'facets' => $facets, 'movers' => $movers]);
    }

    public function history(string $ticker, Request $request)
    {
        $listing = UsListedCompany::where('ticker', strtoupper($ticker))->firstOrFail();
        return response()->json(['ticker' => $listing->ticker] + self::prices($listing->ticker, $request->boolean('intraday')));
    }

    /** NSE-listed company: the same chart from the ".NS" series (prices in rupees, India time). */
    public function nseHistory(string $symbol, Request $request)
    {
        $symbol = DB::table('listed_companies')->where('symbol', strtoupper($symbol))->value('symbol') ?? abort(404);
        return response()->json(['ticker' => $symbol] + self::prices($symbol . '.NS', $request->boolean('intraday')));
    }

    /**
     * Daily closes for five years (cached 6 hours), or 5-minute prices for the last five sessions in the exchange's local
     * time with the previous close (cached 5 minutes) for the 1D / 2D / 5D views.
     */
    private static function prices(string $yahoo, bool $intraday): array
    {
        $key = 'market-prices:' . ($intraday ? 'intraday:' : 'daily:') . $yahoo;
        $data = Cache::get($key);
        if ($data !== null) return $data;
        try {
            $json = Http::withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0 Safari/537.36'])
                ->timeout(15)->get('https://query1.finance.yahoo.com/v8/finance/chart/' . rawurlencode($yahoo), $intraday ? ['range' => '5d', 'interval' => '5m'] : ['range' => '5y', 'interval' => '1d'])->json('chart.result.0');
        } catch (\Throwable $e) {
            report($e);
            $json = null;
        }
        $offset = $intraday ? (int) ($json['meta']['gmtoffset'] ?? 0) : 0;
        $points = [];
        foreach ($json['timestamp'] ?? [] as $i => $ts) {
            $close = $json['indicators']['quote'][0]['close'][$i] ?? null;
            if ($close) $points[] = [gmdate($intraday ? 'Y-m-d H:i' : 'Y-m-d', $ts + $offset), round((float) $close, 2)];
        }
        $prev = $json['meta']['chartPreviousClose'] ?? null;
        $data = ['points' => $points, 'previous_close' => $prev ? round((float) $prev, 2) : null];
        // An empty answer (source unreachable) is retried after a minute rather than kept for hours.
        Cache::put($key, $data, $points ? ($intraday ? now()->addMinutes(5) : now()->addHours(6)) : now()->addMinute());
        return $data;
    }
}
