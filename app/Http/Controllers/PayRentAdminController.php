<?php

namespace App\Http\Controllers;

use App\Jobs\SendRentPayout;
use App\Models\RentTransfer;
use App\Services\Payments\RentPayouts;
use Illuminate\Http\Request;

// Owner's view of card-funded rent / tuition payments: retry a failed payout or mark it refunded.
class PayRentAdminController extends Controller
{
    public function index(Request $request, RentPayouts $payouts)
    {
        $status = array_key_exists($request->query('status'), config('pay_rent.statuses')) ? $request->query('status') : null;
        return view('pay-rent.admin', [
            'items' => RentTransfer::with('user:id,name,email')->when($status, fn ($q) => $q->where('status', $status))->latest()->paginate(40)->withQueryString(),
            'status' => $status, 'counts' => RentTransfer::selectRaw('status, count(*) n')->groupBy('status')->pluck('n', 'status'), 'live' => $payouts->live(),
        ]);
    }

    public function retry(RentTransfer $transfer)
    {
        abort_unless($transfer->status === 'payout_failed' && $transfer->paid_at, 422);
        $transfer->update(['status' => 'paid', 'failure_reason' => null]);
        SendRentPayout::dispatch($transfer->id);
        return back()->with('success', 'Payout queued again for ' . $transfer->reference . '.');
    }

    public function refunded(RentTransfer $transfer)
    {
        abort_unless($transfer->status === 'payout_failed', 422);
        $transfer->update(['status' => 'refunded']);
        return back()->with('success', $transfer->reference . ' marked refunded.');
    }
}
