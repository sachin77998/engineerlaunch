<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\RentTransfer;
use App\Services\Payments\PaytmGateway;
use App\Services\Payments\RentPayouts;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

// Pay rent / tuition / maintenance by credit card: purpose → method (saved contact, UPI or bank) → payee and amount → summary → card payment.
// The card is charged only when RentPayouts::live() (owner switch + Paytm + Cashfree keys); otherwise the request is saved as not processed.
class PayRentController extends Controller
{
    public function __construct(private RentPayouts $payouts, private PaytmGateway $paytm) {}

    public function index(Request $request)
    {
        return view('pay-rent.index', ['live' => $this->payouts->live(), 'recent' => $request->user() ? RentTransfer::where('user_id', $request->user()->id)->latest()->take(3)->get() : collect()]);
    }

    public function purpose(Request $request, string $purpose)
    {
        $this->purposeOr404($purpose);
        return view('pay-rent.purpose', ['purpose' => $purpose, 'p' => config('pay_rent.purposes')[$purpose], 'live' => $this->payouts->live()]);
    }

    public function form(Request $request, string $purpose, string $method)
    {
        $this->purposeOr404($purpose);
        abort_unless(isset(config('pay_rent.methods')[$method]), 404);
        $contacts = $method === 'contact' ? $this->contacts($request) : collect();
        $from = $request->filled('from') ? RentTransfer::where('user_id', $request->user()->id)->where('reference', $request->query('from'))->first() : null;
        if ($from) $method = $from->method;
        return view('pay-rent.form', ['purpose' => $purpose, 'p' => config('pay_rent.purposes')[$purpose], 'method' => $method, 'contacts' => $contacts, 'from' => $from, 'live' => $this->payouts->live()]);
    }

    public function store(Request $request, string $purpose)
    {
        $this->purposeOr404($purpose);
        $o = config('pay_rent');
        $method = $request->input('method') === 'upi' ? 'upi' : 'bank';
        $rules = [
            'payee_name' => 'required|string|max:120',
            'payee_phone' => ['required', 'regex:/^[6-9]\d{9}$/'],
            'amount' => 'required|integer|min:' . $o['min'] . '|max:' . $o['max'],
            'details' => 'nullable|array', 'details.*' => 'nullable|string|max:200',
            'confirm' => 'accepted',
        ];
        $rules += $method === 'upi'
            ? ['payee_vpa' => ['required', 'regex:/^[A-Za-z0-9._-]{2,256}@[A-Za-z][A-Za-z0-9]{1,64}$/']]
            : ['account' => 'required|digits_between:9,18|confirmed', 'payee_ifsc' => ['required', 'regex:/^[A-Za-z]{4}0[A-Za-z0-9]{6}$/']];
        $v = $request->validate($rules, [
            'payee_phone.regex' => 'Enter a 10-digit mobile number.', 'payee_vpa.regex' => 'Enter a UPI ID like name@bank.',
            'payee_ifsc.regex' => 'IFSC is 11 characters, like SBIN0001234.', 'account.confirmed' => 'The account numbers do not match.',
            'confirm.accepted' => 'Confirm the payee details are correct.',
        ]);
        $c = RentTransfer::charges((int) $v['amount']);
        $t = RentTransfer::create([
            'user_id' => $request->user()->id, 'reference' => 'PR' . now()->format('ymd') . strtoupper(Str::random(8)), 'purpose' => $purpose, 'method' => $method,
            'payee_name' => $v['payee_name'], 'payee_phone' => $v['payee_phone'],
            'payee_vpa' => $method === 'upi' ? strtolower($v['payee_vpa']) : null,
            'payee_account' => $method === 'bank' ? $v['account'] : null, 'payee_account_last4' => $method === 'bank' ? substr($v['account'], -4) : null,
            'payee_ifsc' => $method === 'bank' ? strtoupper($v['payee_ifsc']) : null,
            'details' => collect($v['details'] ?? [])->only(array_keys(config('pay_rent.purposes')[$purpose][3]))->filter()->all(),
            'amount' => $v['amount'], 'fee' => $c['fee'], 'total' => $c['total'],
        ]);
        return redirect()->route('pay-rent.show', $t);
    }

    public function show(Request $request, RentTransfer $transfer)
    {
        $this->own($request, $transfer);
        if ($transfer->status === 'awaiting_payment' && $transfer->payment) rescue(fn () => app(PaytmPaymentController::class)->reconcile($transfer->payment), null, false);
        return view('pay-rent.show', ['t' => $transfer->fresh(), 'live' => $this->payouts->live()]);
    }

    /** "Make payment": charge the card (live) or record the request as not processed. */
    public function pay(Request $request, RentTransfer $transfer)
    {
        $this->own($request, $transfer);
        if (!in_array($transfer->status, ['draft', 'payment_failed', 'not_processed'], true)) return redirect()->route('pay-rent.show', $transfer);
        if (!$this->payouts->live()) {
            $transfer->update(['status' => 'not_processed']);
            return redirect()->route('pay-rent.show', $transfer)->with('warning', 'Card payments to landlords and tutors are not switched on yet, so nothing was charged. Your request is saved.');
        }
        $o = config('pay_rent');
        $today = RentTransfer::where('user_id', $request->user()->id)->whereIn('status', ['paid', 'payout_pending', 'completed'])->where('paid_at', '>=', now()->startOfDay())->sum('amount');
        if ($today + $transfer->amount > $o['daily_max']) return back()->withErrors(['payment' => 'Daily limit of ₹' . number_format($o['daily_max']) . ' reached. Try again tomorrow.']);
        // Recompute charges from the stored amount: the browser never decides what is charged.
        $c = RentTransfer::charges($transfer->amount);
        $payment = Payment::create(['user_id' => $request->user()->id, 'order_id' => $transfer->reference . '_' . strtoupper(Str::random(5)), 'payable_type' => 'rent_transfer',
            'payable_id' => $transfer->id, 'provider' => 'paytm', 'amount_paise' => $c['total'] * 100]);
        $transfer->update(['status' => 'awaiting_payment', 'payment_id' => $payment->id] + $c);
        try {
            $token = $this->paytm->initiate($payment->order_id, $payment->amount_paise, 'CUST' . $request->user()->id, $request->user()->email, null, ['CREDIT_CARD']);
        } catch (\RuntimeException $e) {
            report($e);
            $payment->update(['status' => 'failed', 'gateway_response' => ['error' => $e->getMessage()]]);
            $transfer->update(['status' => 'payment_failed']);
            return back()->withErrors(['payment' => 'The card payment could not be started. Please try again shortly.']);
        }
        return view('payments.paytm-redirect', ['url' => $this->paytm->paymentPageUrl($payment->order_id), 'mid' => $this->paytm->mid(), 'orderId' => $payment->order_id, 'token' => $token]);
    }

    public function destroy(Request $request, RentTransfer $transfer)
    {
        $this->own($request, $transfer);
        abort_unless(in_array($transfer->status, ['draft', 'not_processed', 'payment_failed'], true), 422);
        $transfer->delete();
        return redirect()->route('pay-rent.mine')->with('success', 'Removed.');
    }

    public function mine(Request $request)
    {
        return view('pay-rent.mine', ['items' => RentTransfer::where('user_id', $request->user()->id)->latest()->paginate(20)]);
    }

    /** Distinct payees from earlier payments, newest first ("to Contact"). */
    private function contacts(Request $request)
    {
        return RentTransfer::where('user_id', $request->user()->id)->latest()->get()
            ->unique(fn ($t) => $t->method . '|' . ($t->payee_vpa ?? $t->payee_ifsc . $t->payee_account_last4))->take(20)->values();
    }

    private function own(Request $request, RentTransfer $t): void
    {
        abort_unless($t->user_id === $request->user()->id, 403);
    }

    private function purposeOr404(string $purpose): void
    {
        abort_unless(isset(config('pay_rent.purposes')[$purpose]), 404);
    }
}
