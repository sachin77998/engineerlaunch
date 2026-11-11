<?php

namespace App\Http\Controllers;

use App\Models\Referral\Referral;
use App\Models\Referral\ReferralLedger;
use App\Models\Referral\ReferralWithdrawal;
use App\Services\ReferralRewards;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

// Refer & Earn: share an owner's contact or a TO-LET / FOR SALE board photo; track referrals, rewards and UPI withdrawals.
class ReferralController extends Controller
{
    public function __construct(private ReferralRewards $rewards) {}

    public function index(Request $request)
    {
        return view('referrals.index', [
            'cities' => collect(config('properties.cities.India'))->sort()->values(),
            'balance' => $request->user() ? ReferralLedger::balance($request->user()->id) : null,
        ]);
    }

    public function store(Request $request)
    {
        $o = config('referrals');
        $kind = $request->input('kind') === 'photo' ? 'photo' : 'contact';
        $rules = [
            'city' => 'required|string|in:' . implode(',', config('properties.cities.India')),
            'property_type' => 'nullable|in:' . implode(',', array_keys($o['property_types'])), 'listing' => 'nullable|in:rent,sale',
            'notes' => 'nullable|string|max:500',
        ];
        $rules += $kind === 'photo'
            ? ['photo' => 'required|image|mimes:jpg,jpeg,png,webp|max:' . $o['photo_max_kb'], 'latitude' => 'nullable|numeric|between:-90,90', 'longitude' => 'nullable|numeric|between:-180,180']
            : ['owner_phone' => ['required', 'regex:/^[6-9]\d{9}$/'], 'owner_name' => 'required|string|max:120', 'consent' => 'accepted'];
        $v = $request->validate($rules, ['owner_phone.regex' => 'Enter the owner\'s 10-digit mobile number.', 'consent.accepted' => 'Confirm the owner is happy to be contacted.']);
        $uid = $request->user()->id;
        if (Referral::where('user_id', $uid)->where('created_at', '>=', now()->startOfDay())->count() >= $o['daily_limit']) {
            return back()->withInput()->withErrors(['owner_phone' => 'Daily limit of ' . $o['daily_limit'] . ' referrals reached. Try again tomorrow.']);
        }
        if ($kind === 'contact' && ($dup = $this->rewards->duplicateOf($v['owner_phone']))) {
            return back()->withInput()->withErrors(['owner_phone' => $dup]);
        }
        Referral::create([
            'user_id' => $uid, 'kind' => $kind, 'city' => $v['city'], 'property_type' => $v['property_type'] ?? null, 'listing' => $v['listing'] ?? null, 'notes' => $v['notes'] ?? null,
            'owner_name' => $v['owner_name'] ?? null, 'owner_phone' => $v['owner_phone'] ?? null,
            'photo_path' => $kind === 'photo' ? $request->file('photo')->store('referrals/' . $uid, 'local') : null,
            'latitude' => $v['latitude'] ?? null, 'longitude' => $v['longitude'] ?? null,
        ]);
        return redirect()->route('referrals.mine')->with('success', 'Thanks! We will verify this ' . ($kind === 'photo' ? 'board photo' : 'owner') . ' and credit your reward once it checks out.');
    }

    public function mine(Request $request)
    {
        $uid = $request->user()->id;
        return view('referrals.mine', [
            'items' => Referral::where('user_id', $uid)->latest()->paginate(20),
            'balance' => ReferralLedger::balance($uid), 'earned' => (int) ReferralLedger::where('user_id', $uid)->where('amount', '>', 0)->whereNotNull('referral_id')->sum('amount'),
            'withdrawals' => ReferralWithdrawal::where('user_id', $uid)->latest()->take(10)->get(),
        ]);
    }

    public function withdraw(Request $request)
    {
        $v = $request->validate([
            'amount' => 'required|integer|min:' . config('referrals.min_withdrawal'),
            'upi_id' => ['required', 'regex:/^[A-Za-z0-9._-]{2,256}@[A-Za-z][A-Za-z0-9]{1,64}$/'],
        ], ['upi_id.regex' => 'Enter a UPI ID like name@bank.']);
        $w = $this->rewards->requestWithdrawal($request->user()->id, (int) $v['amount'], strtolower($v['upi_id']));
        if (!$w) return back()->withErrors(['amount' => 'The amount is more than your balance or below the ₹' . config('referrals.min_withdrawal') . ' minimum.']);
        return back()->with('success', 'Withdrawal of ₹' . number_format($w->amount) . ' to ' . $w->upi_id . ' requested.');
    }

    /** Board photos are private: only the referrer and the site owner can open them. */
    public function photo(Request $request, Referral $referral)
    {
        $u = $request->user();
        $admin = ($u->role === 'admin' || $u->role_code === 2) && strcasecmp($u->email, (string) config('owner.email')) === 0;
        abort_unless($referral->user_id === $u->id || $admin, 403);
        abort_unless($referral->photo_path && Storage::disk('local')->exists($referral->photo_path), 404);
        return response()->file(Storage::disk('local')->path($referral->photo_path), ['Cache-Control' => 'private, max-age=3600']);
    }
}
