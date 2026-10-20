<?php

namespace App\Http\Controllers\Trading;

use App\Http\Controllers\Controller;
use App\Jobs\RunMaintenanceStep;
use App\Models\Trading\MfAmc;
use App\Models\Trading\MfCategory;
use App\Models\Trading\MfCollection;
use App\Models\Trading\MfHolding;
use App\Models\Trading\MfOrder;
use App\Models\Trading\MfScheme;
use App\Models\Trading\TradingClient;
use App\Services\Trading\FeeCalculator;
use App\Services\Trading\FundCatalogue;
use App\Services\Trading\FundQuery;
use App\Services\Trading\FundRecommender;
use App\Services\Trading\MarketCalendar;
use App\Services\Trading\OrderExporter;
use App\Services\Trading\TradingAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/** "Do Trading" mutual fund pages: explore, screener, collections, fund houses, fund page, calculators, orders. */
class TradingController extends Controller
{
    /** Explore: collections, popular funds, fund houses and categories. */
    public function index(MarketCalendar $calendar)
    {
        $this->ensureData();
        $data = Cache::remember('trading:catalogue', 900, fn () => [
            'collections' => MfCollection::where('is_active', true)->orderBy('sort_order')->get(),
            'popular' => app(FundQuery::class)->build(['min_rating' => 4, 'sub_category' => ['Flexi Cap', 'Mid Cap', 'Small Cap', 'Large Cap'], 'sort' => 'return_3y'])->limit(40)->get()->unique('amc')->take(8)->values(),
            'amcs' => MfAmc::orderByDesc('scheme_count')->limit(18)->get(),
            'categories' => MfCategory::whereIn('asset_class', ['Equity', 'Debt', 'Hybrid', 'Commodities'])->where('scheme_count', '>', 20)->orderBy('sort_order')->orderByDesc('scheme_count')->get()->groupBy('asset_class'),
            'stats' => ['schemes' => MfScheme::where('is_active', true)->count(), 'amcs' => MfAmc::count(), 'nav_date' => MfScheme::max('nav_date')],
        ]);
        $now = $calendar->now();
        return view('trading.explore', $data + ['marketClosed' => $calendar->closedReason($now), 'nextDay' => $calendar->nextBusinessDay($now)]);
    }

    /** Screener: every fund with Groww-style filters. */
    public function funds(Request $request)
    {
        return $this->screener($request, $request->all(), ['title' => 'All mutual funds']);
    }

    public function collection(Request $request, MfCollection $collection)
    {
        return $this->screener($request, array_merge($collection->rules, array_filter($request->all(), fn ($v) => $v !== null && $v !== '')), ['title' => $collection->title, 'description' => $collection->description, 'collection' => $collection]);
    }

    public function amc(Request $request, MfAmc $amc)
    {
        return $this->screener($request, array_merge($request->all(), ['amc' => [$amc->name]]), ['title' => $amc->short_name . ' Mutual Fund', 'description' => number_format($amc->scheme_count) . ' schemes', 'amcRow' => $amc]);
    }

    public function category(Request $request, MfCategory $category)
    {
        return $this->screener($request, array_merge($request->all(), ['asset_class' => [$category->asset_class], 'sub_category' => [$category->sub_category]]),
            ['title' => $category->sub_category . ' funds', 'description' => $category->asset_class . ' · ' . $category->risk_level . ' risk · ' . number_format($category->scheme_count) . ' schemes']);
    }

    private function screener(Request $request, array $filters, array $meta)
    {
        $this->ensureData();
        $funds = app(FundQuery::class)->build($filters)->with('fundHouse:id,name,short_name,color,slug')->paginate(25)->withQueryString();
        $facets = Cache::remember('trading:fund-facets', 3600, fn () => [
            'categories' => MfCategory::orderBy('sort_order')->orderByDesc('scheme_count')->get(['id', 'asset_class', 'sub_category', 'scheme_count'])->groupBy('asset_class'),
            'amcs' => MfAmc::orderBy('short_name')->get(['id', 'name', 'short_name', 'scheme_count']),
            'collections' => MfCollection::where('is_active', true)->orderBy('sort_order')->get(['slug', 'title']),
        ]);
        return view('trading.funds', ['funds' => $funds, 'filters' => $filters, 'facets' => $facets, 'meta' => $meta, 'risks' => FundCatalogue::RISK_LEVELS]);
    }

    public function fund(Request $request, MfScheme $scheme, FundRecommender $recommender, MarketCalendar $calendar, FeeCalculator $fees)
    {
        $client = $request->user() ? TradingClient::where('user_id', $request->user()->id)->first() : null;
        $holding = $client ? MfHolding::where('trading_client_id', $client->id)->where('scheme_code', $scheme->scheme_code)->first() : null;
        $owned = $client ? MfHolding::where('trading_client_id', $client->id)->where('units', '>', 0)->pluck('scheme_code')->all() : [];
        $now = $calendar->now();
        $scheme->load('fundHouse', 'categoryRow');
        return view('trading.fund', [
            'scheme' => $scheme, 'client' => $client, 'holding' => $holding,
            'history' => DB::table('mf_nav_history')->where('scheme_code', $scheme->scheme_code)->orderBy('nav_date')->get(['nav_date', 'nav']),
            'recommended' => $recommender->for($scheme, 6, $owned),
            'variants' => MfScheme::where('is_active', true)->where('amc', $scheme->amc)->where('name', $scheme->name)->where('scheme_code', '!=', $scheme->scheme_code)->get(['scheme_code', 'plan', 'option', 'nav']),
            'peers' => MfScheme::where('is_active', true)->where('sub_category', $scheme->sub_category)->where('plan', $scheme->plan)->where('option', $scheme->option)->whereNotNull('return_3y')
                ->selectRaw('count(*) n, avg(return_1y) r1, avg(return_3y) r3, avg(return_5y) r5')->first(),
            'ranks' => \Illuminate\Support\Facades\Cache::remember('fund-ranks:' . $scheme->scheme_code . ':' . now()->toDateString(), now()->addHours(12), function () use ($scheme) {
                $peers = fn () => MfScheme::where('is_active', true)->where('sub_category', $scheme->sub_category)->where('plan', $scheme->plan)->where('option', $scheme->option);
                $out = [];
                foreach (['return_6m', 'return_1y', 'return_3y', 'return_5y'] as $k) {
                    $out[$k] = $scheme->{$k} === null ? null : ['rank' => $peers()->where($k, '>', $scheme->{$k})->count() + 1, 'of' => $peers()->whereNotNull($k)->count(), 'avg' => $peers()->avg($k)];
                }
                return $out;
            }),
            'ratios' => rescue(fn () => app(\App\Services\Trading\FundHistory::class)->ratios(app(\App\Services\Trading\FundHistory::class)->points((int) $scheme->scheme_code)), [], false),
            'marketClosed' => $calendar->closedReason($now), 'navDate' => $calendar->applicableNavDate($scheme), 'sampleFees' => $fees->quote('buy', 10000),
        ]);
    }

    /** Daily NAV history (JSON) for the fund chart and SIP returns table. */
    public function fundHistory(MfScheme $scheme, \App\Services\Trading\FundHistory $history)
    {
        return response()->json(['points' => $history->points((int) $scheme->scheme_code)])->header('Cache-Control', 'public, max-age=3600');
    }

    public function calculators()
    {
        return view('trading.calculators');
    }

    public function orders(Request $request)
    {
        $client = TradingClient::where('user_id', $request->user()->id)->first();
        $orders = MfOrder::with('scheme:scheme_code,name,plan,option,amc')->where('user_id', $request->user()->id)
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))->latest('id')->paginate(20)->withQueryString();
        $holdings = $client ? MfHolding::with('scheme:scheme_code,name,plan,option,nav,nav_date,amc,category,sub_category')->where('trading_client_id', $client->id)->where('units', '>', 0)->get() : collect();
        return view('trading.orders', compact('client', 'orders', 'holdings'));
    }

    public function exportMine(Request $request, string $format, OrderExporter $exporter, TradingAudit $audit)
    {
        abort_unless(in_array($format, ['xlsx', 'pdf'], true), 404);
        $orders = MfOrder::with(['scheme:scheme_code,name,plan,option', 'client:id,client_code'])->where('user_id', $request->user()->id)
            ->when($request->query('from'), fn ($q, $d) => $q->whereDate('created_at', '>=', $d))->when($request->query('to'), fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->latest('id')->limit(5000)->get();
        $audit->log('orders.exported', null, ['format' => $format, 'count' => $orders->count()]);
        $name = 'my-orders-' . now('Asia/Kolkata')->format('Y-m-d');
        $rows = $exporter->rows($orders);
        return $format === 'xlsx'
            ? response()->download($exporter->xlsx($rows, 'My orders'), $name . '.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])->deleteFileAfterSend()
            : $exporter->pdf($rows, 'My orders · ' . $request->user()->name)->download($name . '.pdf');
    }

    /** First visit after a deployment with an empty catalogue: load the market data in the background (once an hour at most). */
    private function ensureData(): void
    {
        if (MfScheme::query()->exists() && MfCollection::query()->exists()) return;
        if (!Cache::add('trading:bootstrap-queued', true, now()->addHour())) return;
        RunMaintenanceStep::markQueued('trading', 'Do Trading market data');
        RunMaintenanceStep::dispatch('trading', 'Do Trading market data', 'trading:sync', []);
    }
}
