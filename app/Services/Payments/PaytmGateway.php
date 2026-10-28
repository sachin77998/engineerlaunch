<?php

namespace App\Services\Payments;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Paytm Payment Gateway (JS Checkout / redirect flow) without the SDK:
 *  1. initiate(): Initiate Transaction API → txnToken (request signed with the merchant key).
 *  2. The customer pays on Paytm's page (showPaymentPage, posted with mid / orderId / txnToken).
 *  3. Paytm posts the result to our callback; verifyCallback() checks CHECKSUMHASH.
 *  4. status(): Transaction Status API — the only answer trusted to mark an order paid.
 * Checksum = Paytm's documented scheme: SHA-256 of (payload|salt) + salt, AES-128-CBC with the merchant key.
 */
class PaytmGateway
{
    private const IV = '@@@@&&&&####$$$$';

    public function ready(): bool
    {
        return filled(config('services.paytm.mid')) && filled(config('services.paytm.merchant_key'));
    }

    public function host(): string
    {
        return config('services.paytm.environment') === 'production' ? 'https://secure.paytmpayments.com' : 'https://securestage.paytmpayments.com';
    }

    public function mid(): string
    {
        return (string) config('services.paytm.mid');
    }

    /** @return string txnToken */
    public function initiate(string $orderId, int $amountPaise, string $customerId, ?string $email = null, ?string $mobile = null): string
    {
        $body = [
            'requestType' => 'Payment', 'mid' => $this->mid(), 'websiteName' => (string) config('services.paytm.website', 'WEBSTAGING'),
            'orderId' => $orderId, 'callbackUrl' => config('services.paytm.callback_url') ?: route('payments.paytm.callback'),
            'txnAmount' => ['value' => number_format($amountPaise / 100, 2, '.', ''), 'currency' => 'INR'],
            'userInfo' => array_filter(['custId' => $customerId, 'email' => $email, 'mobile' => $mobile]),
        ];
        $json = json_encode($body, JSON_UNESCAPED_SLASHES);
        $res = Http::timeout(20)->withBody(json_encode(['body' => $body, 'head' => ['signature' => $this->sign($json)]], JSON_UNESCAPED_SLASHES), 'application/json')
            ->post($this->host() . '/theia/api/v1/initiateTransaction?mid=' . rawurlencode($this->mid()) . '&orderId=' . rawurlencode($orderId));
        $token = $res->json('body.txnToken');
        if (!$res->successful() || !$token || $res->json('body.resultInfo.resultStatus') !== 'S') {
            throw new RuntimeException('Paytm did not start the payment: ' . ($res->json('body.resultInfo.resultMsg') ?: 'HTTP ' . $res->status()));
        }
        return $token;
    }

    /** Paytm's hosted payment page; the browser posts mid / orderId / txnToken to it. */
    public function paymentPageUrl(string $orderId): string
    {
        return $this->host() . '/theia/api/v1/showPaymentPage?mid=' . rawurlencode($this->mid()) . '&orderId=' . rawurlencode($orderId);
    }

    /** Callback form fields: every field except CHECKSUMHASH, sorted by key, joined with "|". */
    public function verifyCallback(array $params): bool
    {
        $hash = (string) ($params['CHECKSUMHASH'] ?? '');
        unset($params['CHECKSUMHASH']);
        ksort($params);
        $payload = implode('|', array_map(fn ($v) => $v === null || strtolower((string) $v) === 'null' ? '' : (string) $v, $params));
        return $hash !== '' && $this->verify($payload, $hash);
    }

    /**
     * Transaction Status API.
     * @return array{status:string, txn_id:?string, bank_txn_id:?string, mode:?string, amount:?string, raw:array}
     *   status: success | failed | pending
     */
    public function status(string $orderId): array
    {
        $body = ['mid' => $this->mid(), 'orderId' => $orderId];
        $json = json_encode($body, JSON_UNESCAPED_SLASHES);
        $res = Http::timeout(20)->withBody(json_encode(['body' => $body, 'head' => ['signature' => $this->sign($json)]], JSON_UNESCAPED_SLASHES), 'application/json')
            ->post($this->host() . '/v3/order/status');
        $b = (array) $res->json('body', []);
        $code = $b['resultInfo']['resultStatus'] ?? null;
        return ['status' => match ($code) { 'TXN_SUCCESS' => 'success', 'TXN_FAILURE' => 'failed', default => 'pending' },
            'txn_id' => $b['txnId'] ?? null, 'bank_txn_id' => $b['bankTxnId'] ?? null, 'mode' => $b['paymentMode'] ?? null, 'amount' => $b['txnAmount'] ?? null,
            'message' => $b['resultInfo']['resultMsg'] ?? null, 'raw' => $b];
    }

    public function sign(string $payload): string
    {
        $salt = Str::random(4);
        return $this->encrypt(hash('sha256', $payload . '|' . $salt) . $salt);
    }

    public function verify(string $payload, string $signature): bool
    {
        $plain = $this->decrypt($signature);
        if ($plain === null || strlen($plain) < 5) return false;
        $salt = substr($plain, -4);
        return hash_equals(substr($plain, 0, -4), hash('sha256', $payload . '|' . $salt));
    }

    private function encrypt(string $text): string
    {
        return (string) openssl_encrypt($text, 'AES-128-CBC', html_entity_decode((string) config('services.paytm.merchant_key')), 0, self::IV);
    }

    private function decrypt(string $text): ?string
    {
        $out = openssl_decrypt($text, 'AES-128-CBC', html_entity_decode((string) config('services.paytm.merchant_key')), 0, self::IV);
        return $out === false ? null : $out;
    }
}
