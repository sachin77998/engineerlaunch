<?php

namespace App\Services\Payments;

use App\Models\RentTransfer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

// Moves a card-paid rent transfer to the payee and keeps its status in step with Cashfree. Every failure after the card is charged
// is flagged payout_failed and emailed to the owner, who must refund the customer.
class RentPayouts
{
    public function __construct(private CashfreePayouts $cashfree, private PaytmGateway $paytm) {}

    /** Charging cards is allowed only when the owner has switched it on and both gateways have keys. */
    public function live(): bool
    {
        return config('pay_rent.live') && $this->paytm->ready() && $this->cashfree->ready();
    }

    public function send(RentTransfer $t): void
    {
        $claimed = DB::transaction(function () use ($t) {
            $row = RentTransfer::lockForUpdate()->find($t->id);
            if (!$row || $row->status !== 'paid') return false;
            $row->update(['status' => 'payout_pending']);
            return true;
        });
        if (!$claimed) return;
        $t->refresh();
        try {
            $res = $this->cashfree->transfer($t);
            $state = CashfreePayouts::map($res['status'] ?? null);
            $t->update(['payout_ref' => $res['cf_transfer_id'] ?? null, 'payout_response' => $res] + $this->apply($state, $res));
        } catch (\Throwable $e) {
            report($e);
            $t->update(['status' => 'payout_failed', 'failure_reason' => substr($e->getMessage(), 0, 250)]);
        }
        $this->alertIfFailed($t->fresh());
    }

    /** Polls Cashfree for a transfer that is still on its way. */
    public function sync(RentTransfer $t): void
    {
        if ($t->status !== 'payout_pending') return;
        try {
            $s = $this->cashfree->status($t->reference);
        } catch (\Throwable $e) {
            report($e);
            return;
        }
        $t->update(['payout_response' => $s['raw']] + $this->apply($s['status'], $s['raw']));
        $this->alertIfFailed($t->fresh());
    }

    private function apply(string $state, array $raw): array
    {
        return match ($state) {
            'completed' => ['status' => 'completed', 'completed_at' => now()],
            'failed' => ['status' => 'payout_failed', 'failure_reason' => substr((string) ($raw['status_description'] ?? $raw['message'] ?? 'Transfer failed'), 0, 250)],
            default => ['status' => 'payout_pending'],
        };
    }

    private function alertIfFailed(RentTransfer $t): void
    {
        if ($t->status !== 'payout_failed' || !($to = config('owner.email'))) return;
        $body = "A card payment was charged but the transfer to the payee failed — refund the customer or retry.\n\n"
            . "Reference: {$t->reference}\nCustomer: {$t->user?->name} ({$t->user?->email})\nAmount: ₹{$t->amount} (card charged ₹{$t->total})\n"
            . "Payee: {$t->payee_name}, {$t->payeeLabel()}\nReason: {$t->failure_reason}\n\n" . route('admin.pay-rent.index');
        rescue(fn () => Mail::to($to)->queue((new \App\Mail\HiringMail('Rent payout failed: ' . $t->reference, e($body)))->onQueue('emails')), null);
    }
}
