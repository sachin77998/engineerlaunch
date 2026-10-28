<?php

namespace App\Http\Controllers\Trading;

use App\Http\Controllers\Controller;
use App\Models\Trading\Loan;
use App\Services\Trading\LoanAmortization;
use App\Services\Trading\OrderExporter;
use App\Services\Trading\TradingAudit;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LoanController extends Controller
{
    public function __construct(private LoanAmortization $engine, private TradingAudit $audit) {}

    public function index(Request $request)
    {
        $loans = Loan::where('user_id', $request->user()->id)->latest('id')->paginate(20);
        return view('trading.loans.index', ['loans' => $loans, 'frequencies' => LoanAmortization::FREQUENCIES, 'dayCounts' => LoanAmortization::DAY_COUNTS]);
    }
    public function preview(Request $request)
    {
        $result = $this->engine->schedule($this->validated($request));
        return response()->json(['data' => $result]);
    }
    public function store(Request $request)
    {
        $loan = $this->engine->save($this->validated($request), $request->user()->id);
        $this->audit->log('loan.created', $loan, ['principal' => $loan->principal, 'frequency' => $loan->payment_frequency]);
        return redirect()->route('trading.loans.show', $loan)->with('success', 'Loan ' . $loan->loan_no . ' created with ' . $loan->installments . ' instalments.');
    }
    public function show(Request $request, Loan $loan)
    {
        abort_unless($loan->user_id === $request->user()->id || $request->user()->isOwner(), 404);
        return view('trading.loans.show', ['loan' => $loan->load('schedule'), 'frequencies' => LoanAmortization::FREQUENCIES, 'dayCounts' => LoanAmortization::DAY_COUNTS]);
    }
    public function destroy(Request $request, Loan $loan)
    {
        abort_unless($loan->user_id === $request->user()->id, 404);
        $this->audit->log('loan.deleted', $loan);
        $loan->delete();
        return redirect()->route('trading.loans')->with('success', 'Loan deleted.');
    }
    public function export(Request $request, Loan $loan, string $format, OrderExporter $exporter)
    {
        abort_unless($loan->user_id === $request->user()->id || $request->user()->isOwner(), 404);
        abort_unless(in_array($format, ['xlsx', 'pdf'], true), 404);
        $loan->load('schedule');
        if ($format === 'pdf') {
            return Pdf::loadView('trading.loans.pdf', ['loan' => $loan, 'frequencies' => LoanAmortization::FREQUENCIES])->setPaper('a4', 'landscape')->download($loan->loan_no . '-schedule.pdf');
        }
        $columns = [
            'installment_no' => 'No',
            'due_date' => 'Due date',
            'days' => 'Days',
            'opening_balance' => 'Opening balance',
            'scheduled_payment' => 'Scheduled payment',
            'principal' => 'Principal',
            'interest' => 'Interest',
            'total_payment' => 'Total payment',
            'closing_balance' => 'Closing balance',
            'is_moratorium' => 'Moratorium'
            ];
        $rows = $loan->schedule->map(fn ($r) => [
            'installment_no' => $r->installment_no,
            'due_date' => $r->due_date->format('d-m-Y'),
            'days' => $r->days,
            'opening_balance' => $r->opening_balance,
            'scheduled_payment' => $r->scheduled_payment,
            'principal' => $r->principal,
            'interest' => $r->interest,
            'total_payment' => $r->total_payment,
            'closing_balance' => $r->closing_balance,
            'is_moratorium' => $r->is_moratorium ? 'Yes' : ''])->all();
        return response()->download($exporter->xlsx($rows, $loan->loan_no, $columns), $loan->loan_no . '-schedule.xlsx')->deleteFileAfterSend();
    }
    private function validated(Request $request): array
    {
        return $request->validate(['borrower_name' => 'required|string|max:120', 'account_number' => 'nullable|string|max:40', 'loan_type' => 'nullable|string|max:30','principal' => 'required|numeric|min:1|max:100000000000', 'interest_rate' => 'required|numeric|min:0|max:60','issue_date' => 'required|date', 'first_due_date' => 'nullable|date|after:issue_date', 'maturity_date' => 'required|date|after:issue_date','payment_frequency' => ['required', Rule::in(array_keys(LoanAmortization::FREQUENCIES))], 'day_count' => ['required', Rule::in(array_keys(LoanAmortization::DAY_COUNTS))],'moratorium_months' => 'nullable|integer|min:0|max:120', 'moratorium_type' => ['nullable', Rule::in(['principal_only', 'principal_and_interest'])],]);
    }
}
