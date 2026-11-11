<?php

namespace App\Http\Controllers;

use App\Models\Property\PropertyOwner;
use App\Models\Referral\Referral;
use App\Models\Referral\ReferralWithdrawal;
use App\Services\ReferralRewards;
use Illuminate\Http\Request;

// Owner's desk for Refer & Earn: verify or reject referrals (rewards are credited automatically) and pay withdrawal requests.
class ReferralAdminController extends Controller
{
    public function __construct(private ReferralRewards $rewards) {}

    public function index(Request $request)
    {
        $status = array_key_exists($request->query('status'), config('referrals.statuses')) ? $request->query('status') : 'pending';
        $items = Referral::with('user:id,name,email')->where('status', $status)->latest()->paginate(30)->withQueryString();
        $phones = $items->pluck('owner_phone')->filter()->all();
        $listed = $phones ? PropertyOwner::whereIn('phone', $phones)->whereHas('properties', fn ($q) => $q->where('status', 'active'))->pluck('phone')->all() : [];
        return view('referrals.admin', [
            'items' => $items, 'status' => $status, 'listedPhones' => $listed,
            'counts' => Referral::selectRaw('status, count(*) n')->groupBy('status')->pluck('n', 'status'),
            'withdrawals' => ReferralWithdrawal::with('user:id,name,email')->where('status', 'requested')->oldest()->get(),
        ]);
    }

    public function review(Request $request, Referral $referral)
    {
        $v = $request->validate([
            'status' => 'required|in:verified,listed,rejected,duplicate',
            'reject_reason' => 'nullable|required_if:status,rejected|in:' . implode(',', array_keys(config('referrals.reject_reasons'))),
            'owner_phone' => ['nullable', 'regex:/^[6-9]\d{9}$/'],
        ]);
        if ($referral->kind === 'photo' && in_array($v['status'], ['verified', 'listed'], true) && !($v['owner_phone'] ?? $referral->owner_phone)) {
            return back()->withErrors(['owner_phone' => 'Type the number from the board photo before verifying it.']);
        }
        $this->rewards->review($referral, $v['status'], $v['reject_reason'] ?? null, $v['owner_phone'] ?? null);
        return back()->with('success', 'Referral #' . $referral->id . ' marked ' . strtolower(config('referrals.statuses')[$v['status']]) . '.');
    }

    public function settle(Request $request, ReferralWithdrawal $withdrawal)
    {
        $v = $request->validate(['status' => 'required|in:paid,rejected', 'paid_ref' => 'nullable|required_if:status,paid|string|max:80', 'admin_note' => 'nullable|string|max:255']);
        $this->rewards->settleWithdrawal($withdrawal, $v['status'], $v['paid_ref'] ?? null, $v['admin_note'] ?? null);
        return back()->with('success', 'Withdrawal ' . ($v['status'] === 'paid' ? 'marked paid.' : 'rejected and returned to the balance.'));
    }
}
