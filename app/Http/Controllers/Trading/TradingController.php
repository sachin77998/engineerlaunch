<?php

namespace App\Http\Controllers\Trading;

use App\Http\Controllers\Controller;
use App\Models\Trading\MfHolding;
use App\Models\Trading\MfOrder;
use App\Models\Trading\MfScheme;
use App\Models\Trading\TradingClient;
use App\Services\Trading\FeeCalculator;
use App\Services\Trading\FundRecommender;
use App\Services\Trading\MarketCalendar;
use App\Services\Trading\OrderExporter;
use App\Services\Trading\TradingAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/** "Do Trading" pages: fund explorer, fund page with buy/redeem and recommendations, calculators, orders and holdings. */
class TradingController extends Controller
{
    public function index(Request $request, MarketCalendar $calendar)
    {
        $filters = $request->only(['q', 'amc', 'asset_class', 'category', 'plan', 'option', 'sort']);
        $q = trim((string) ($filters['q'] ?? ''));
        $sort = in_array($filters['sort'] ?? '', ['return_1y', 'return_3y', 'return_5y', 'name', 'nav'], true) ? $filters['sort'] : 'return_3y';
        $funds = MfScheme::where('is_active', true)->where('scheme_type', 'Open Ended')
            ->when($q !== '', fn ($query) => mb_strlen($q) >= 3 ? $query->where(fn ($w) => $w->whereFullText('name', self::words($q), ['mode' => 'boolean'])->orWhere('amc', 'like', '%' . $q . '%')) : $query->where('name', 'like', $q . '%'))
            ->when($filters['amc'] ?? null, fn ($query, $v) => $query->where('amc', $v))
            ->when($filters['asset_class'] ?? null, fn ($query, $v) => $query->where('asset_class', $v))
            ->when($filters['category'] ?? null, fn ($query, $v) => $query->where('category', $v))
            ->when($filters['plan'] ?? 'Direct', fn ($query, $v) => $v === 'all' ? $query : $query->where('plan', $v))
            ->when($filters['option'] ?? 'Growth', fn ($query, $v) => $v === 'all' ? $query : $query->where('option', $v))
            ->when($sort === 'name', fn ($query) => $query->orderBy('name'), fn ($query) => $query->orderByRaw($sort . ' IS NULL')->orderByDesc($sort))
            ->paginate(30)->withQueryString();
        $facets = Cache::remember('trading:fund-facets', 3600, fn () => [
            'amc' => MfScheme::where('is_active', true)->distinct()->orderBy('amc')->pluck('amc'),
            'asset_class' => MfScheme::where('is_active', true)->whereNotNull('asset_class')->distinct()->orderBy('asset_class')->pluck('asset_class'),
            'category' => MfScheme::where('is_active', true)->whereNotNull('category')->distinct()->orderBy('category')->pluck('category'),
            'count' => MfScheme::where('is_active', true)->count(),
            'nav_date' => MfScheme::where('is_active', true)->max('nav_date'),
        ]);
        $now = $calendar->now();
        return view('trading.index', ['funds' => $funds, 'filters' => $filters + ['plan' => 'Direct', 'option' => 'Growth'], 'facets' => $facets,
            'marketClosed' => $calendar->closedReason($now), 'nextDay' => $calendar->nextBusinessDay($now)]);
    }

    public function fund(Request $request, MfScheme $scheme, FundRecommender $recommender, MarketCalendar $calendar, FeeCalculator $fees)
    {
        $client = $request->user() ? TradingClient::where('user_id', $request->user()->id)->first() : null;
        $holding = $client ? MfHolding::where('trading_client_id', $client->id)->where('scheme_code', $scheme->scheme_code)->first() : null;
        $owned = $client ? MfHolding::where('trading_client_id', $client->id)->where('units', '>', 0)->pluck('scheme_code')->all() : [];
        $history = DB::table('mf_nav_history')->where('scheme_code', $scheme->scheme_code)->orderBy('nav_date')->get(['nav_date', 'nav']);
        $now = $calendar->now();
        return view('trading.fund', [
            'scheme' => $scheme, 'client' => $client, 'holding' => $holding, 'history' => $history,
            'recommended' => $recommender->for($scheme, 6, $owned),
            'variants' => MfScheme::where('is_active', true)->where('amc', $scheme->amc)->where('name', $scheme->name)->where('scheme_code', '!=', $scheme->scheme_code)->get(['scheme_code', 'plan', 'option', 'nav']),
            'marketClosed' => $calendar->closedReason($now), 'navDate' => $calendar->applicableNavDate($scheme), 'sampleFees' => $fees->quote('buy', 10000),
        ]);
    }

    public function calculators()
    {
        return view('trading.calculators');
    }

    public function orders(Request $request)
    {
        $client = TradingClient::where('user_id', $request->user()->id)->first();
        $orders = MfOrder::with('scheme:scheme_code,name,plan,option')->where('user_id', $request->user()->id)
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))->latest('id')->paginate(20)->withQueryString();
        $holdings = $client ? MfHolding::with('scheme:scheme_code,name,plan,option,nav,nav_date,amc,category')->where('trading_client_id', $client->id)->where('units', '>', 0)->get() : collect();
        return view('trading.orders', compact('client', 'orders', 'holdings'));
    }

    /** The investor's own orders as Excel or PDF. */
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

    /** Boolean full-text query requiring every word (prefix match): "hdfc flexi" → "+hdfc* +flexi*". */
    private static function words(string $q): string
    {
        return collect(preg_split('/[^\pL\pN]+/u', mb_strtolower($q)))->filter(fn ($w) => mb_strlen($w) >= 2)->map(fn ($w) => '+' . $w . '*')->implode(' ');
    }
}
