<?php

namespace App\Services\Payments;

use App\Models\RentTransfer;
use Illuminate\Support\Facades\Http;
use RuntimeException;

// Cashfree Payouts v2 (your own account): standard transfer to a bank account or UPI ID, and transfer status.
// Production also needs your server IP whitelisted (or 2FA public key) in the Cashfree dashboard. Test in sandbox first.
class CashfreePayouts
{
    public function ready(): bool
    {
        return filled(config('services.cashfree_payouts.client_id')) && filled(config('services.cashfree_payouts.client_secret'));
    }

    private function base(): string
    {
        return config('services.cashfree_payouts.environment') === 'production' ? 'https://api.cashfree.com/payout' : 'https://sandbox.cashfree.com/payout';
    }

    private function client()
    {
        return Http::timeout(30)->acceptJson()->withHeaders([
            'x-client-id' => (string) config('services.cashfree_payouts.client_id'),
            'x-client-secret' => (string) config('services.cashfree_payouts.client_secret'),
            'x-api-version' => (string) config('services.cashfree_payouts.api_version'),
        ]);
    }

    /** Starts the transfer; returns Cashfree's response. Throws when the request is rejected. */
    public function transfer(RentTransfer $t): array
    {
        $instrument = $t->method === 'upi' ? ['vpa' => $t->payee_vpa] : ['bank_account_number' => $t->payee_account, 'bank_ifsc' => $t->payee_ifsc];
        $res = $this->client()->post($this->base() . '/transfers', [
            'transfer_id' => $t->reference,
            'transfer_amount' => $t->amount,
            'transfer_mode' => $t->method === 'upi' ? 'upi' : 'banktransfer',
            'transfer_remarks' => substr(preg_replace('/[^A-Za-z0-9 ]/', '', $t->purposeLabel() . ' ' . $t->reference), 0, 70),
            'beneficiary_details' => [
                'beneficiary_name' => $t->payee_name,
                'beneficiary_instrument_details' => $instrument,
                'beneficiary_contact_details' => ['beneficiary_phone' => $t->payee_phone],
            ],
        ]);
        if (!$res->successful()) throw new RuntimeException('Cashfree rejected the transfer: ' . ($res->json('message') ?: 'HTTP ' . $res->status()));
        return (array) $res->json();
    }

    /** @return array{status:string, raw:array} status: completed | failed | pending */
    public function status(string $transferId): array
    {
        $res = $this->client()->get($this->base() . '/transfers', ['transfer_id' => $transferId]);
        $raw = (array) $res->json();
        return ['status' => self::map($raw['status'] ?? null), 'raw' => $raw];
    }

    public static function map(?string $status): string
    {
        return match (strtoupper((string) $status)) {
            'SUCCESS' => 'completed',
            'FAILED', 'REJECTED', 'REVERSED' => 'failed',
            default => 'pending',
        };
    }
}
