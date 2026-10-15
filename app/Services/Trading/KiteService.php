<?php

namespace App\Services\Trading;

use App\Models\Trading\BrokerConnection;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Zerodha Kite Connect v3. Each investor connects their own Zerodha account (Kite login → request_token →
 * access_token, valid until 6 AM the next day); orders are placed in that account. Live ticks come straight from
 * Kite's WebSocket (wss://ws.kite.trade) in the investor's browser.
 */
class KiteService
{
    private const API = 'https://api.kite.trade';

    public function configured(): bool
    {
        return filled(config('services.kite.api_key')) && filled(config('services.kite.api_secret'));
    }

    public function apiKey(): ?string
    {
        return config('services.kite.api_key');
    }

    public function loginUrl(string $state): string
    {
        return 'https://kite.zerodha.com/connect/login?' . http_build_query(['v' => 3, 'api_key' => $this->apiKey(), 'redirect_params' => 'state=' . $state]);
    }

    /** Exchanges the request token from the login redirect for an access token and stores it for the user. */
    public function connect(User $user, string $requestToken): BrokerConnection
    {
        $checksum = hash('sha256', $this->apiKey() . $requestToken . config('services.kite.api_secret'));
        $r = Http::asForm()->withHeaders(['X-Kite-Version' => '3'])->timeout(20)->post(self::API . '/session/token', ['api_key' => $this->apiKey(), 'request_token' => $requestToken, 'checksum' => $checksum]);
        $data = $r->json('data');
        if (!$r->successful() || !is_array($data)) throw new RuntimeException('Zerodha login failed: ' . ($r->json('message') ?? 'HTTP ' . $r->status()));
        $expires = Carbon::now('Asia/Kolkata')->addDay()->setTime(6, 0);
        return BrokerConnection::updateOrCreate(['user_id' => $user->id, 'broker' => 'kite'], [
            'broker_user_id' => $data['user_id'] ?? null, 'access_token' => $data['access_token'], 'public_token' => $data['public_token'] ?? null,
            'login_at' => now(), 'expires_at' => $expires,
            'profile' => ['name' => $data['user_name'] ?? null, 'short_name' => $data['user_shortname'] ?? null, 'broker' => $data['broker'] ?? 'ZERODHA',
                'exchanges' => $data['exchanges'] ?? [], 'products' => $data['products'] ?? []],
        ]);
    }

    public function connection(User $user): ?BrokerConnection
    {
        $c = BrokerConnection::where('user_id', $user->id)->where('broker', 'kite')->first();
        return $c && $c->access_token && $c->expires_at?->isFuture() ? $c : null;
    }

    public function disconnect(User $user): void
    {
        if ($c = BrokerConnection::where('user_id', $user->id)->where('broker', 'kite')->first()) {
            try { $this->call($c, 'DELETE', '/session/token', ['api_key' => $this->apiKey(), 'access_token' => $c->access_token]); } catch (\Throwable) {}
            $c->forceFill(['access_token' => null, 'public_token' => null, 'expires_at' => now()])->save();
        }
    }

    /** @return string broker order id */
    public function placeOrder(BrokerConnection $c, array $p, string $variety = 'regular'): string
    {
        $data = $this->call($c, 'POST', '/orders/' . $variety, array_filter([
            'tradingsymbol' => $p['symbol'], 'exchange' => $p['exchange'], 'transaction_type' => $p['side'], 'order_type' => $p['order_type'],
            'quantity' => $p['quantity'], 'product' => $p['product'], 'validity' => $p['validity'] ?? 'DAY', 'price' => $p['price'] ?? null,
            'trigger_price' => $p['trigger_price'] ?? null, 'tag' => substr((string) ($p['tag'] ?? ''), 0, 20) ?: null,
        ], fn ($v) => $v !== null && $v !== ''));
        return (string) ($data['order_id'] ?? throw new RuntimeException('Zerodha did not return an order id.'));
    }

    public function cancelOrder(BrokerConnection $c, string $orderId, string $variety = 'regular'): void
    {
        $this->call($c, 'DELETE', '/orders/' . $variety . '/' . rawurlencode($orderId));
    }

    /** Latest state of one order (last entry of its history). */
    public function orderStatus(BrokerConnection $c, string $orderId): array
    {
        $history = $this->call($c, 'GET', '/orders/' . rawurlencode($orderId));
        return is_array($history) && $history ? end($history) : [];
    }

    public function holdings(BrokerConnection $c): array
    {
        return (array) $this->call($c, 'GET', '/portfolio/holdings');
    }

    public function positions(BrokerConnection $c): array
    {
        return (array) $this->call($c, 'GET', '/portfolio/positions');
    }

    public function funds(BrokerConnection $c): array
    {
        return (array) $this->call($c, 'GET', '/user/margins/equity');
    }

    /** Postback checksum: sha256(order_id + order_timestamp + api_secret). */
    public function validPostback(array $payload): bool
    {
        $expected = hash('sha256', ($payload['order_id'] ?? '') . ($payload['order_timestamp'] ?? '') . config('services.kite.api_secret'));
        return isset($payload['checksum']) && hash_equals($expected, (string) $payload['checksum']);
    }

    private function call(BrokerConnection $c, string $method, string $path, array $params = [])
    {
        $request = Http::withHeaders(['X-Kite-Version' => '3', 'Authorization' => 'token ' . $this->apiKey() . ':' . $c->access_token])->timeout(15);
        $r = match ($method) {
            'GET' => $request->get(self::API . $path, $params),
            'DELETE' => $request->delete(self::API . $path . ($params ? '?' . http_build_query($params) : '')),
            default => $request->asForm()->post(self::API . $path, $params),
        };
        if ($r->status() === 403 && $r->json('error_type') === 'TokenException') {
            $c->forceFill(['expires_at' => now()])->save();
            throw new RuntimeException('Your Zerodha session has expired. Please connect again.');
        }
        if (!$r->successful() || $r->json('status') !== 'success') throw new RuntimeException($r->json('message') ?? ('Zerodha returned HTTP ' . $r->status()));
        return $r->json('data');
    }
}
