<?php

namespace App\Http\Controllers;

use App\Models\HomeLoan\HomeLoanApplication;
use App\Models\HomeLoan\HomeLoanBank;
use App\Models\HomeLoan\HomeLoanDocument;
use App\Models\Property\Property;
use App\Services\HomeLoan\LoanMath;
use App\Services\Property\PropertyFormat as PF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

// Home loans: eligibility form, lender comparison, EMI / eligibility calculators and the four-step application.
class HomeLoanController extends Controller
{
    public function index(Request $request)
    {
        $sort = array_key_exists($request->query('sort'), HomeLoanBank::SORTS) ? $request->query('sort') : 'rate';
        $property = $request->filled('property') ? Property::live()->with('saleTerms')->where('slug', $request->query('property'))->first() : null;
        $d = config('home_loans.defaults');
        $amount = $property?->saleTerms?->price ? round($property->saleTerms->price * config('properties.emi.loan_share')) : (int) $request->query('amount', $d['amount']);
        return view('home-loan.index', [
            'banks' => HomeLoanBank::listed($sort), 'sort' => $sort, 'property' => $property,
            'amount' => max(100000, min($amount, 100000000)), 'cities' => app(PropertyController::class)->cities(),
            'mine' => $request->user() ? HomeLoanApplication::where('user_id', $request->user()->id)->latest()->first() : null,
        ]);
    }

    /** Hero form: creates the application and opens step 1. */
    public function start(Request $request)
    {
        $o = config('home_loans');
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'phone' => ['required', 'regex:/^[6-9]\d{9}$/'],
            'email' => 'nullable|email|max:191',
            'city' => 'required|string|max:80',
            'loan_amount' => 'nullable|integer|min:100000|max:1000000000',
            'property_finalized' => 'required|boolean',
            'bank' => 'nullable|integer|exists:home_loan_banks,id',
            'tenure' => 'nullable|integer|in:' . implode(',', $o['tenures']),
            'income' => 'nullable|integer|min:0|max:100000000',
            'property' => 'nullable|string|max:191',
        ], ['phone.regex' => 'Enter a 10-digit mobile number.']);
        $property = !empty($data['property']) ? Property::live()->where('slug', $data['property'])->first() : null;
        $app = HomeLoanApplication::create([
            'user_id' => $request->user()?->id, 'name' => $data['name'], 'phone' => $data['phone'], 'email' => $data['email'] ?? $request->user()?->email,
            'city' => $data['city'], 'loan_amount' => $data['loan_amount'] ?? null, 'property_finalized' => (bool) $data['property_finalized'],
            'bank_id' => $data['bank'] ?? null, 'tenure' => $data['tenure'] ?? $o['defaults']['years'], 'monthly_income' => $data['income'] ?? null,
            'property_id' => $property?->id, 'property_city' => $property?->city, 'property_address' => $property?->address(),
        ]);
        $request->session()->push('home_loans', $app->id);
        $this->notifyOwner($app, 'New home loan lead');
        return redirect()->route('home-loan.step', [$app, 'income']);
    }

    public function step(Request $request, HomeLoanApplication $application, string $step)
    {
        $this->authorizeApp($request, $application);
        $o = config('home_loans');
        $data = ['app' => $application, 'step' => $step, 'o' => $o, 'steps' => HomeLoanApplication::STEPS];
        if ($step === 'offers') {
            if (!$application->monthly_income || !$application->loan_amount) return redirect()->route('home-loan.step', [$application, 'income']);
            $data['offers'] = $this->offers($application);
        }
        if ($step === 'property') $data['cities'] = app(PropertyController::class)->cities();
        if ($step === 'documents') $data['docs'] = $application->documents()->get()->groupBy('kind');
        return view('home-loan.step', $data);
    }

    public function save(Request $request, HomeLoanApplication $application, string $step)
    {
        $this->authorizeApp($request, $application);
        $o = config('home_loans');
        if ($step === 'income') {
            $application->fill($request->validate([
                'employment' => 'required|in:' . implode(',', array_keys($o['employment'])),
                'monthly_income' => 'required|integer|min:1000|max:100000000',
                'current_emis' => 'nullable|integer|min:0|max:100000000',
                'loan_amount' => 'required|integer|min:100000|max:1000000000',
                'tenure' => 'required|integer|in:' . implode(',', $o['tenures']),
            ]));
            $application->current_emis = (int) $request->input('current_emis', 0);
            $next = 'offers';
        } elseif ($step === 'offers') {
            $application->bank_id = $request->validate(['bank_id' => 'required|integer|exists:home_loan_banks,id'], ['bank_id.required' => 'Select a bank to continue.'])['bank_id'];
            $next = 'property';
        } elseif ($step === 'property') {
            $v = $request->validate([
                'property_finalized' => 'required|boolean',
                'property_type' => 'required|in:' . implode(',', array_keys($o['property_types'])),
                'property_stage' => 'nullable|in:' . implode(',', array_keys($o['property_stages'])),
                'property_city' => 'required|string|max:80',
                'property_address' => 'nullable|string|max:255',
                'property_value' => 'nullable|integer|min:100000|max:10000000000',
            ]);
            $application->fill($v);
            $next = 'documents';
        } else {
            return $this->submit($request, $application);
        }
        $application->markDone($step);
        $application->save();
        return redirect()->route('home-loan.step', [$application, $next]);
    }

    public function upload(Request $request, HomeLoanApplication $application)
    {
        $this->authorizeApp($request, $application);
        $o = config('home_loans');
        $v = $request->validate([
            'kind' => 'required|in:' . implode(',', array_keys($o['documents'])),
            'file' => 'required|file|mimes:' . $o['doc_mimes'] . '|max:' . $o['doc_max_kb'],
        ]);
        abort_if($application->documents()->count() >= 30, 422, 'Too many documents.');
        $file = $v['file'];
        $path = $file->storeAs('home-loans/' . $application->id, Str::random(32) . '.' . strtolower($file->getClientOriginalExtension() ?: $file->extension()), 'local');
        $application->documents()->create([
            'kind' => $v['kind'], 'path' => $path, 'original_name' => Str::limit($file->getClientOriginalName(), 180, ''),
            'mime' => $file->getMimeType() ?: 'application/octet-stream', 'size' => $file->getSize(),
        ]);
        return redirect()->route('home-loan.step', [$application, 'documents'])->with('success', 'Uploaded ' . config('home_loans.documents')[$v['kind']][0] . '.');
    }

    public function document(Request $request, HomeLoanDocument $document)
    {
        $this->authorizeApp($request, $document->application, true);
        abort_unless(Storage::disk('local')->exists($document->path), 404);
        return Storage::disk('local')->download($document->path, $document->original_name);
    }

    public function removeDocument(Request $request, HomeLoanDocument $document)
    {
        $app = $document->application;
        $this->authorizeApp($request, $app);
        Storage::disk('local')->delete($document->path);
        $document->delete();
        return redirect()->route('home-loan.step', [$app, 'documents'])->with('success', 'Document removed.');
    }

    public function mine(Request $request)
    {
        $ids = $request->session()->get('home_loans', []);
        $items = HomeLoanApplication::with('bank')->withCount('documents')
            ->where(fn ($q) => $q->where('user_id', $request->user()->id)->orWhereIn('id', $ids))->latest()->get();
        return view('home-loan.mine', ['items' => $items]);
    }

    private function submit(Request $request, HomeLoanApplication $application)
    {
        foreach (['income', 'offers', 'property'] as $s) {
            if (!$application->isDone($s)) return redirect()->route('home-loan.step', [$application, $s])->with('error', 'Complete ' . strtolower(HomeLoanApplication::STEPS[$s]) . ' first.');
        }
        $application->markDone('documents');
        $first = !$application->submitted_at;
        $application->forceFill(['status' => in_array($application->status, ['new', 'submitted'], true) ? 'submitted' : $application->status, 'submitted_at' => $application->submitted_at ?? now()])->save();
        if ($first) $this->notifyOwner($application, 'Home loan application submitted');
        return redirect()->route('home-loan.mine')->with('success', 'Application ' . $application->reference() . ' submitted. We will call you on ' . $application->phone . ' with the offer from ' . ($application->bank?->name ?? 'the bank') . '.');
    }

    /** Every active lender with EMI and eligibility at its own rate for this applicant. */
    private function offers(HomeLoanApplication $a)
    {
        return HomeLoanBank::listed()->map(function ($b) use ($a) {
            $years = min($a->tenure, $b->max_tenure);
            $max = LoanMath::maxLoan($a->monthly_income, $a->current_emis, $b->rate_from, $years);
            if ($a->property_value) $max = min($max, $a->property_value * $b->max_funding / 100);
            return (object) ['bank' => $b, 'years' => $years, 'emi' => LoanMath::emi($a->loan_amount, $b->rate_from, $years), 'max' => $max, 'eligible' => $max >= $a->loan_amount];
        })->sortBy([fn ($x, $y) => $y->eligible <=> $x->eligible, fn ($x, $y) => $x->bank->rate_from <=> $y->bank->rate_from])->values();
    }

    private function authorizeApp(Request $request, HomeLoanApplication $app, bool $allowAdmin = false): void
    {
        $user = $request->user();
        $own = ($user && $app->user_id === $user->id) || in_array($app->id, $request->session()->get('home_loans', []), true);
        $admin = $allowAdmin && $user && ($user->role === 'admin' || $user->role_code === 2) && strcasecmp($user->email, (string) config('owner.email')) === 0;
        abort_unless($own || $admin, 403);
        // A guest who signs in later takes the application with them.
        if ($own && $user && !$app->user_id) $app->forceFill(['user_id' => $user->id])->save();
    }

    private function notifyOwner(HomeLoanApplication $a, string $subject): void
    {
        $to = config('owner.email');
        if (!$to) return;
        $body = implode("\n", array_filter([
            $subject . ' — ' . $a->reference(),
            'Name: ' . $a->name, 'Phone: +91 ' . $a->phone, $a->email ? 'Email: ' . $a->email : null, 'City: ' . $a->city,
            $a->loan_amount ? 'Loan: ' . PF::full($a->loan_amount) . ' for ' . $a->tenure . ' years' : null,
            $a->monthly_income ? 'Monthly income: ' . PF::full($a->monthly_income) . ($a->employment ? ' (' . config('home_loans.employment')[$a->employment][0] . ')' : '') : null,
            $a->bank ? 'Bank chosen: ' . $a->bank->name : null,
            'Property finalised: ' . ($a->property_finalized ? 'yes' : 'still searching'),
            '', 'All applications: ' . route('admin.home-loans.index'),
        ], fn ($v) => $v !== null));
        rescue(fn () => Mail::to($to)->queue((new \App\Mail\HiringMail($subject . ': ' . $a->name, e($body), $a->email))->onQueue('emails')), null);
    }
}
