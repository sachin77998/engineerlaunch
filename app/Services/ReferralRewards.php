<?php

namespace App\Services;

use App\Models\Property\PropertyOwner;
use App\Models\Referral\Referral;
use App\Models\Referral\ReferralLedger;
use App\Models\Referral\ReferralWithdrawal;
use Illuminate\Support\Facades\DB;

// Review outcomes for referrals and the reward ledger. Each reward type is credited at most once per referral.
class ReferralRewards
{
    /** Why a number cannot be referred: an existing owner on the site, or a recent referral by anyone. */
    public function duplicateOf(string $phone): ?string
    {
        if (PropertyOwner::where('phone', 'like', '%' . $phone)->exists()) return 'This owner already lists with us.';
        $recent = Referral::where('owner_phone', $phone)->whereNotIn('status', ['rejected', 'duplicate'])
            ->where('created_at', '>=', now()->subDays(config('referrals.duplicate_days')))->exists();
        return $recent ? 'This owner was already referred recently.' : null;
    }

    /** Admin decision: verified | listed | rejected | duplicate (+ optional reason and the phone read off a board photo). */
    public function review(Referral $r, string $status, ?string $reason = null, ?string $phone = null): void
    {
        DB::transaction(function () use ($r, $status, $reason, $phone) {
            $r = Referral::lockForUpdate()->find($r->id);
            if ($phone) $r->owner_phone = $phone;
            $r->fill(['status' => $status, 'reject_reason' => in_array($status, ['rejected'], true) ? $reason : null, 'reviewed_at' => now()]);
            $rw = config('referrals.rewards');
            if (in_array($status, ['verified', 'listed'], true)) $this->credit($r, 'verified', $r->kind === 'photo' ? $rw['verified_photo'] : $rw['verified_contact'], 'Referral verified');
            if ($status === 'listed') $this->credit($r, 'listed', $rw['listed'], 'Owner listed the property');
            $r->reward_total = (int) $r->ledger()->sum('amount');
            $r->save();
        });
    }

    private function credit(Referral $r, string $type, int $amount, string $note): void
    {
        if ($amount <= 0 || $r->ledger()->where('note', 'like', $note . '%')->exists()) return;
        ReferralLedger::create(['user_id' => $r->user_id, 'referral_id' => $r->id, 'amount' => $amount, 'note' => $note . ' (#' . $r->id . ')']);
    }

    /** Debits the balance immediately so the same money can't be requested twice; a rejected request is refunded. */
    public function requestWithdrawal(int $userId, int $amount, string $upi): ?ReferralWithdrawal
    {
        return DB::transaction(function () use ($userId, $amount, $upi) {
            ReferralLedger::where('user_id', $userId)->lockForUpdate()->get('id');
            if ($amount < config('referrals.min_withdrawal') || $amount > ReferralLedger::balance($userId)) return null;
            $w = ReferralWithdrawal::create(['user_id' => $userId, 'amount' => $amount, 'upi_id' => $upi]);
            ReferralLedger::create(['user_id' => $userId, 'referral_withdrawal_id' => $w->id, 'amount' => -$amount, 'note' => 'Withdrawal to ' . $upi]);
            return $w;
        });
    }

    public function settleWithdrawal(ReferralWithdrawal $w, string $status, ?string $ref = null, ?string $note = null): void
    {
        DB::transaction(function () use ($w, $status, $ref, $note) {
            $w = ReferralWithdrawal::lockForUpdate()->find($w->id);
            if ($w->status !== 'requested') return;
            $w->update(['status' => $status, 'paid_ref' => $ref, 'admin_note' => $note]);
            if ($status === 'rejected') ReferralLedger::create(['user_id' => $w->user_id, 'referral_withdrawal_id' => $w->id, 'amount' => $w->amount, 'note' => 'Withdrawal returned' . ($note ? ': ' . $note : '')]);
        });
    }
}
