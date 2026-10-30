<?php

namespace App\Http\Controllers\Trading;

use App\Http\Controllers\Controller;
use App\Models\Trading\MarketInstrument;
use App\Models\Trading\StockOrder;
use App\Services\Trading\KiteService;
use App\Services\Trading\StockOrderService;
use App\Services\Trading\TradingAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

/** Stocks and ETFs: lists, instrument page with live ticks, Zerodha connection, stock orders. */
class MarketController extends Controller
{
    public const SORTS = ['change_pct' => 'Change %', 'volume' => 'Volume', 'turnover_lakhs' => 'Turnover', 'last_price' => 'Price', 'symbol' => 'Symbol'];

    public function __construct(private KiteService $kite, private StockOrderService $orders) {}

    public function stocks(Request $request)
    {
        return $this->list($request, 'STOCK', 'Stocks');
    }
    public function etfs(Request $request)
    {
        return $this->list($request, 'ETF', 'ETFs');
    }
    private function list(Request $request, string $type, string $title)
    {
        $q = trim((string) $request->query('q'));
        $exchange = in_array($request->query('exchange'), ['NSE', 'BSE'], true) ? $request->query('exchange') : 'NSE';
        $sort = array_key_exists($request->query('sort'), self::SORTS) ? $request->query('sort') : 'turnover_lakhs';
        $dir = $request->query('dir') === 'asc' ? 'asc' : 'desc';
        $instruments = MarketInstrument::where('instrument_type', $type)->where('exchange', $exchange)->where('is_active', true)
            ->when($request->query('class'), fn ($w, $c) => $w->where('etf_class', $c))
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('symbol', 'like', strtoupper($q) . '%')->orWhere('name', 'like', '%' . $q . '%')))
            ->orderByRaw($sort . ' IS NULL')->orderBy($sort, $sort === 'symbol' ? ($request->query('dir') === 'desc' ? 'desc' : 'asc') : $dir)->paginate(50)->withQueryString();
        $movers = \App\Support\Stale::remember('trading:movers:' . $type, 900, fn () => [
            'gainers' => MarketInstrument::where('instrument_type', $type)->where('exchange', 'NSE')->where('is_active', true)->where('turnover_lakhs', '>', 100)->orderByDesc('change_pct')->limit(5)->get(),
            'losers' => MarketInstrument::where('instrument_type', $type)->where('exchange', 'NSE')->where('is_active', true)->where('turnover_lakhs', '>', 100)->orderBy('change_pct')->limit(5)->get(),
            'active' => MarketInstrument::where('instrument_type', $type)->where('exchange', 'NSE')->where('is_active', true)->orderByDesc('turnover_lakhs')->limit(5)->get(),
            'counts' => MarketInstrument::where('instrument_type', $type)->where('is_active', true)->selectRaw('exchange, count(*) c')->groupBy('exchange')->pluck('c', 'exchange'),
            'classes' => $type === 'ETF' ? MarketInstrument::where('instrument_type', 'ETF')->where('is_active', true)->whereNotNull('etf_class')->selectRaw('etf_class, count(*) c')->groupBy('etf_class')->pluck('c', 'etf_class') : collect(),
            'price_date' => MarketInstrument::where('instrument_type', $type)->max('price_date'),
        ]);
        return view('trading.instruments', ['instruments' => $instruments, 'type' => $type, 'title' => $title, 'movers' => $movers, 'exchange' => $exchange, 'sort' => $sort, 'dir' => $dir,
            'connected' => $request->user() ? $this->kite->connection($request->user()) : null, 'status' => $this->orders->marketStatus()]);
    }
    public function instrument(Request $request, string $exchange, string $symbol)
    {
        $instrument = MarketInstrument::where('exchange', strtoupper($exchange))->where('symbol', strtoupper($symbol))->firstOrFail();
        $instrument->load('company:id,name,slug,industry,sector,website');
        $connected = $request->user() ? $this->kite->connection($request->user()) : null;
        $myOrders = $request->user() ? StockOrder::where('user_id', $request->user()->id)->where('market_instrument_id', $instrument->id)->latest('id')->limit(10)->get() : collect();
        return view('trading.instrument', ['instrument' => $instrument, 'connected' => $connected, 'kiteReady' => $this->kite->configured(), 'status' => $this->orders->marketStatus(),
            'myOrders' => $myOrders, 'sample' => $instrument->last_price ? $this->orders->charges('BUY', 'CNC', 1, $instrument->last_price) : null]);
    }
    /** Search box: symbols and names (JSON). */
    public function search(Request $request)
    {
        $q = trim((string) $request->query('q'));
        if (mb_strlen($q) < 1) return response()->json(['data' => []]);
        $rows = MarketInstrument::where('is_active', true)->where(fn ($w) => $w->where('symbol', 'like', strtoupper($q) . '%')->orWhere('name', 'like', '%' . $q . '%'))
            ->orderByRaw("exchange = 'NSE' DESC")->orderByDesc('turnover_lakhs')->limit(12)->get(['exchange', 'symbol', 'name', 'instrument_type', 'last_price', 'change_pct']);
        return response()->json(['data' => $rows]);
    }
    /** Charges preview for an order. */
    public function charges(Request $request)
    {
        $d = $request->validate(['side' => ['required', Rule::in(['BUY', 'SELL'])], 'product' => ['required', Rule::in(['CNC', 'MIS'])], 'quantity' => 'required|integer|min:1|max:1000000', 'price' => 'required|numeric|min:0.01']);
        return response()->json(['data' => $this->orders->charges($d['side'], $d['product'], (int) $d['quantity'], (float) $d['price'])]);
    }

    public function place(Request $request, string $exchange, string $symbol)
    {
        $instrument = MarketInstrument::where('exchange', strtoupper($exchange))->where('symbol', strtoupper($symbol))->firstOrFail();
        $d = $request->validate([
            'side' => ['required', Rule::in(['BUY', 'SELL'])], 'product' => ['required', Rule::in(['CNC', 'MIS'])], 'order_type' => ['required', Rule::in(['MARKET', 'LIMIT', 'SL', 'SL-M'])],
            'quantity' => 'required|integer|min:1|max:1000000', 'price' => 'nullable|numeric|min:0.01', 'trigger_price' => 'nullable|numeric|min:0.01',
        ]);
        $key = $request->header('Idempotency-Key');
        if ($key !== null && !preg_match('/^[A-Za-z0-9_-]{8,64}$/', $key)) return response()->json(['message' => 'Invalid Idempotency-Key.'], 422);
        $order = $this->orders->place($request->user(), $instrument, $d, $key);
        return response()->json(['data' => $this->present($order), 'message' => $order->status === 'rejected' ? $order->status_message : 'Order sent to Zerodha.'], $order->status === 'rejected' ? 422 : 201);
    }
    public function showOrder(Request $request, StockOrder $order)
    {
        abort_unless($order->user_id === $request->user()->id || $request->user()->isOwner(), 404);
        return response()->json(['data' => $this->present($this->orders->refresh($order)->load('events'), true)]);
    }
    public function cancelOrder(Request $request, StockOrder $order)
    {
        abort_unless($order->user_id === $request->user()->id, 404);
        return response()->json(['data' => $this->present($this->orders->cancel($order))]);
    }
    public function stockOrders(Request $request)
    {
        $orders = StockOrder::where('user_id', $request->user()->id)->latest('id')->paginate(30)->withQueryString();
        $open = $orders->getCollection()->filter(fn ($o) => !in_array($o->status, StockOrderService::FINAL, true));
        foreach ($open as $o) $this->orders->refresh($o);
        $connection = $this->kite->connection($request->user());
        $portfolio = ['holdings' => [], 'positions' => [], 'funds' => null, 'error' => null];
        if ($connection) {
            try {
                $portfolio['holdings'] = Cache::remember('kite:holdings:' . $request->user()->id, 60, fn () => $this->kite->holdings($connection));
                $portfolio['positions'] = Cache::remember('kite:positions:' . $request->user()->id, 30, fn () => $this->kite->positions($connection)['net'] ?? []);
                $portfolio['funds'] = Cache::remember('kite:funds:' . $request->user()->id, 60, fn () => $this->kite->funds($connection));
            } catch (Throwable $e) {
                $portfolio['error'] = $e->getMessage();
            }
        }
        return view('trading.stock-orders', ['orders' => StockOrder::where('user_id', $request->user()->id)->latest('id')->paginate(30)->withQueryString(), 'connected' => $connection, 'portfolio' => $portfolio]);
    }

    // ---- Zerodha connection -------------------------------------------------------------------------------

    public function connect(Request $request, TradingAudit $audit)
    {
        if (!$this->kite->configured()) return back()->withErrors(['broker' => 'Zerodha trading is not switched on yet (the Kite API secret is not set).']);
        $state = Str::random(32);
        $request->session()->put('kite_state', $state);
        $audit->log('broker.connect_started', null, ['broker' => 'kite']);
        return redirect()->away($this->kite->loginUrl($state));
    }

    public function callback(Request $request, TradingAudit $audit)
    {
        abort_unless($request->query('status') === 'success' && $request->query('request_token'), 400, 'Zerodha login was cancelled.');
        $expected = $request->session()->pull('kite_state');
        abort_unless($expected && hash_equals($expected, (string) $request->query('state')), 403, 'This login link has expired. Please connect again.');
        try {
            $c = $this->kite->connect($request->user(), (string) $request->query('request_token'));
        } catch (Throwable $e) {
            return redirect()->route('trading.stocks')->withErrors(['broker' => $e->getMessage()]);
        }
        $audit->log('broker.connected', $c, ['broker' => 'kite', 'client' => $c->broker_user_id]);
        return redirect()->intended(route('trading.stocks'))->with('success', 'Zerodha account ' . $c->broker_user_id . ' connected until 6 AM tomorrow.');
    }
    public function disconnect(Request $request, TradingAudit $audit)
    {
        $this->kite->disconnect($request->user());
        $audit->log('broker.disconnected', null, ['broker' => 'kite']);
        return back()->with('success', 'Zerodha disconnected.');
    }
    /** Kite postback (order status pushed by Zerodha); verified by checksum. */
    public function postback(Request $request)
    {
        $payload = $request->json()->all() ?: $request->all();
        if (!$this->kite->validPostback($payload)) return response()->json(['status' => 'invalid'], 403);
        if ($order = StockOrder::where('broker_order_id', $payload['order_id'] ?? '')->first()) $this->orders->apply($order, $payload, 'postback');
        return response()->json(['status' => 'ok']);
    }

    /** Ticker credentials for the investor's own browser (their own token, short-lived). */
    public function ticker(Request $request)
    {
        $c = $this->kite->connection($request->user());
        abort_unless($c, 404);
        return response()->json(['data' => ['url' => 'wss://ws.kite.trade?api_key=' . $this->kite->apiKey() . '&access_token=' . $c->access_token]])->header('Cache-Control', 'no-store');
    }

    private function present(StockOrder $o, bool $events = false): array
    {
        $out = ['order_no' => $o->order_no, 'exchange' => $o->exchange, 'symbol' => $o->symbol, 'side' => $o->side, 'product' => $o->product, 'order_type' => $o->order_type,
            'quantity' => $o->quantity, 'price' => $o->price, 'trigger_price' => $o->trigger_price, 'status' => $o->status, 'message' => $o->status_message,
            'broker_order_id' => $o->broker_order_id, 'filled_quantity' => $o->filled_quantity, 'average_price' => $o->average_price, 'charges' => $o->charges, 'placed_at' => $o->created_at?->toIso8601String()];
        if ($events) $out['history'] = $o->events->map(fn ($e) => ['status' => $e->status, 'source' => $e->source, 'message' => $e->message, 'at' => $e->created_at?->toIso8601String()])->all();
        return $out;
    }
}
