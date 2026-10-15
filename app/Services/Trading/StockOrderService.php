<?php

namespace App\Services\Trading;

use App\Models\Trading\MarketInstrument;
use App\Models\Trading\StockOrder;
use App\Models\Trading\StockOrderEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Stock/ETF orders placed in the investor's own Zerodha account through Kite Connect.
 * Closed on weekends and exchange holidays; outside 09:15–15:30 IST orders go as after-market orders (AMO).
 */
class StockOrderService
{
    public const FINAL = ['complete', 'rejected', 'cancelled', 'failed'];

    public function __construct(private KiteService $kite, private MarketCalendar $calendar, private TradingAudit $audit) {}

    public function marketStatus(): array
    {
        $now = $this->calendar->now();
        if ($reason = $this->calendar->closedReason($now)) return ['open' => false, 'session' => 'closed', 'message' => $reason];
        $open = $now->copy()->setTime(9, 15);
        $close = $now->copy()->setTime(15, 30);
        if ($now->lt($open)) return ['open' => false, 'session' => 'pre_open', 'message' => 'Market opens at 9:15 AM. Orders now are placed as after-market orders.'];
        if ($now->gt($close)) return ['open' => false, 'session' => 'post_close', 'message' => 'Market closed at 3:30 PM. Orders now are placed as after-market orders for the next session.'];
        return ['open' => true, 'session' => 'open', 'message' => 'Market is open.'];
    }

    /** Charges estimate (Zerodha-style equity rates). */
    public function charges(string $side, string $product, int $quantity, float $price): array
    {
        $value = $quantity * $price;
        $delivery = $product === 'CNC';
        $brokerage = $delivery ? 0.0 : min(20.0, $value * 0.0003);
        $stt = $delivery ? $value * 0.001 : ($side === 'SELL' ? $value * 0.00025 : 0.0);
        $exchange = $value * 0.0000297;
        $sebi = $value * 10 / 10000000;
        $stamp = $side === 'BUY' ? $value * ($delivery ? 0.00015 : 0.00003) : 0.0;
        $gst = ($brokerage + $exchange + $sebi) * 0.18;
        $total = $brokerage + $stt + $exchange + $sebi + $stamp + $gst;
        return array_map(fn ($v) => round($v, 2), ['value' => $value, 'brokerage' => $brokerage, 'stt' => $stt, 'exchange' => $exchange, 'sebi' => $sebi, 'stamp_duty' => $stamp, 'gst' => $gst, 'total' => $total,
            'net' => $side === 'BUY' ? $value + $total : $value - $total]);
    }

    public function place(User $user, MarketInstrument $instrument, array $d, ?string $idempotencyKey): StockOrder
    {
        if ($idempotencyKey && ($existing = StockOrder::where('user_id', $user->id)->where('idempotency_key', $idempotencyKey)->first())) return $existing;
        $status = $this->marketStatus();
        if ($status['session'] === 'closed') $this->fail('market', $status['message']);
        $connection = $this->kite->connection($user);
        if (!$connection) $this->fail('broker', 'Connect your Zerodha account to trade stocks.');
        if (!$instrument->is_active) $this->fail('symbol', 'This instrument is not tradable.');
        if ($d['quantity'] % max(1, $instrument->lot_size) !== 0) $this->fail('quantity', 'Quantity must be a multiple of ' . $instrument->lot_size . '.');
        if (in_array($d['order_type'], ['LIMIT', 'SL'], true) && empty($d['price'])) $this->fail('price', 'Enter a limit price.');
        if (in_array($d['order_type'], ['SL', 'SL-M'], true) && empty($d['trigger_price'])) $this->fail('trigger_price', 'Enter a trigger price.');
        if (!empty($d['price']) && $instrument->tick_size > 0 && fmod(round($d['price'] * 100), round($instrument->tick_size * 100)) != 0.0) $this->fail('price', 'Price must be a multiple of ₹' . $instrument->tick_size . '.');
        $estPrice = (float) ($d['price'] ?? $instrument->last_price ?? 0);

        $order = StockOrder::create([
            'order_no' => 'DS' . now('Asia/Kolkata')->format('ymd') . strtoupper(Str::random(8)), 'user_id' => $user->id, 'market_instrument_id' => $instrument->id,
            'exchange' => $instrument->exchange, 'symbol' => $instrument->symbol, 'side' => $d['side'], 'product' => $d['product'], 'order_type' => $d['order_type'],
            'validity' => 'DAY', 'quantity' => $d['quantity'], 'price' => $d['price'] ?? null, 'trigger_price' => $d['trigger_price'] ?? null, 'status' => 'created',
            'charges' => $estPrice > 0 ? $this->charges($d['side'], $d['product'], (int) $d['quantity'], $estPrice) : null,
            'idempotency_key' => $idempotencyKey, 'ip' => request()?->ip(),
        ]);
        $this->event($order, 'created', 'client', 'Order created' . ($status['open'] ? '' : ' as after-market order'));
        try {
            $brokerId = $this->kite->placeOrder($connection, ['symbol' => $instrument->symbol, 'exchange' => $instrument->exchange] + $d + ['tag' => $order->order_no], $status['open'] ? 'regular' : 'amo');
            $order->forceFill(['broker_order_id' => $brokerId, 'status' => 'sent'])->save();
            $this->event($order, 'sent', 'broker', 'Accepted by Zerodha (order ' . $brokerId . ')');
        } catch (Throwable $e) {
            $order->forceFill(['status' => 'rejected', 'status_message' => Str::limit($e->getMessage(), 250, '')])->save();
            $this->event($order, 'rejected', 'broker', $e->getMessage());
        }
        $this->audit->log('stock_order.' . $order->status, $order, ['symbol' => $order->symbol, 'side' => $order->side, 'qty' => $order->quantity, 'type' => $order->order_type]);
        return $order;
    }

    /** Pulls the latest status from Zerodha. */
    public function refresh(StockOrder $order): StockOrder
    {
        if (!$order->broker_order_id || in_array($order->status, self::FINAL, true)) return $order;
        $user = User::find($order->user_id);
        $connection = $user ? $this->kite->connection($user) : null;
        if (!$connection) return $order;
        try {
            $this->apply($order, $this->kite->orderStatus($connection, $order->broker_order_id), 'broker');
        } catch (Throwable $e) {
            report($e);
        }
        return $order->fresh();
    }

    public function cancel(StockOrder $order): StockOrder
    {
        if (in_array($order->status, self::FINAL, true)) $this->fail('status', 'This order is already ' . $order->status . '.');
        $connection = $this->kite->connection(User::findOrFail($order->user_id)) ?? $this->fail('broker', 'Connect your Zerodha account again to cancel.');
        try {
            $this->kite->cancelOrder($connection, $order->broker_order_id, $order->status === 'sent' && !$this->marketStatus()['open'] ? 'amo' : 'regular');
        } catch (Throwable $e) {
            $this->fail('status', $e->getMessage());
        }
        $this->event($order, 'cancel_requested', 'client', 'Cancellation sent to Zerodha');
        return $this->refresh($order);
    }

    /** Applies a Kite order payload (from the API, a postback or a WebSocket order update). */
    public function apply(StockOrder $order, array $k, string $source): void
    {
        $map = ['COMPLETE' => 'complete', 'REJECTED' => 'rejected', 'CANCELLED' => 'cancelled', 'OPEN' => 'open', 'TRIGGER PENDING' => 'open', 'AMO REQ RECEIVED' => 'sent'];
        $status = $map[strtoupper((string) ($k['status'] ?? ''))] ?? $order->status;
        DB::transaction(function () use ($order, $k, $status, $source) {
            $changed = $status !== $order->status || (int) ($k['filled_quantity'] ?? 0) !== $order->filled_quantity;
            $order->forceFill(['status' => $status, 'status_message' => Str::limit((string) ($k['status_message'] ?? $order->status_message), 250, '') ?: null,
                'filled_quantity' => (int) ($k['filled_quantity'] ?? $order->filled_quantity), 'average_price' => isset($k['average_price']) && $k['average_price'] > 0 ? (float) $k['average_price'] : $order->average_price])->save();
            if ($changed) $this->event($order, $status, $source, $k['status_message'] ?? null, array_intersect_key($k, array_flip(['status', 'filled_quantity', 'pending_quantity', 'average_price', 'exchange_timestamp'])));
        });
    }

    private function event(StockOrder $order, string $status, string $source, ?string $message, array $payload = []): void
    {
        StockOrderEvent::create(['stock_order_id' => $order->id, 'status' => $status, 'source' => $source, 'message' => Str::limit((string) $message, 250, ''), 'payload' => $payload ?: null]);
    }

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
