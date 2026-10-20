<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\VendorObservation;
use App\Services\Insights\VendorPerformance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class VendorComparisonController extends Controller
{
    const COLUMNS = ['company_id', 'reference', 'kind', 'material', 'issued_on', 'due_on', 'completed_on', 'amount', 'currency', 'quality', 'evidence'];
    private function authorizeOwner(Request $r): void
    {
        abort_unless($r->user()?->isOwner(), 403);
    }
    private function rules(): array
    {
        return [
            'company_id' => 'required|integer|exists:companies,id',
            'reference' => 'required|string|max:100',
            'kind' => 'required|in:customer_payment,vendor_refund,supplier_delivery',
            'material' => 'required|string|max:100',
            'issued_on' => 'required|date_format:Y-m-d|before_or_equal:today',
            'due_on' => 'required|date_format:Y-m-d|after_or_equal:issued_on',
            'completed_on' => 'nullable|date_format:Y-m-d|after_or_equal:issued_on|before_or_equal:today',
            'amount' => 'required|numeric|min:0.01|max:99999999999999999.99',
            'currency' => 'required|regex:/^[A-Z]{3}$/',
            'quality' => 'nullable|in:accepted,rejected',
            'evidence' => 'required|string|max:500'
        ];
    }
    public function index(Request $r, VendorPerformance $performance)
    {
        $this->authorizeOwner($r);
        $data = $r->validate([
            'q' => 'nullable|string|max:100',
            'companies' => 'nullable|array|max:10',
            'companies.*' => 'integer|distinct|exists:companies,id',
            'kind' => 'nullable|in:customer_payment,vendor_refund,supplier_delivery',
            'material' => 'nullable|string|max:100',
            'currency' => 'nullable|regex:/^[A-Z]{3}$/'
        ]);
        $kind = $data['kind'] ?? 'vendor_refund';
        $material = $data['material'] ?? '';
        $currency = $data['currency'] ?? 'INR';
        $companies = Company::query()->when($data['q'] ?? null, fn($q, $v) => $q->where('name', 'like', '%' . $v . '%'))->orderBy('name')->limit(30)->get(['id', 'name']);
        $selected = Company::whereIn('id', $data['companies'] ?? [])->get(['id', 'name']);
        $ready = Schema::hasTable('vendor_observations');
        $comparisons = [];
        foreach ($selected as $company) {
            $rows = $ready ? VendorObservation::where('company_id', $company->id)->where('kind', $kind)->where('currency', $currency)->where('material', $material)->get() : collect();
            $comparisons[] = ['company' => $company, 'metrics' => $performance->summarize($rows)];
        }
        $history = $ready ? VendorObservation::where('kind', $kind)->where('currency', $currency)->where('material', $material)->orderByDesc('issued_on')->limit(2000)->get() : collect();
        $model = app(\App\Services\Insights\PaymentDelayModel::class)->evaluate($history);
        return view('insights.vendor-comparison', compact('companies', 'selected', 'comparisons', 'ready', 'kind', 'material', 'currency', 'model'));
    }
    public function template(Request $r)
    {
        $this->authorizeOwner($r);
        return response(implode(',', self::COLUMNS) . "\r\n", 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="vendor-history-template.csv"']);
    }
    public function store(Request $r)
    {
        $this->authorizeOwner($r);
        abort_unless(Schema::hasTable('vendor_observations'), 503, 'Vendor history migration is required.');
        $rows = [];
        if ($r->hasFile('csv')) {
            $r->validate(['csv' => 'required|file|max:2048']);
            $handle = fopen($r->file('csv')->getRealPath(), 'r');
            try {
                $header = fgetcsv($handle);
                if ($header) $header[0] = ltrim($header[0], "\xEF\xBB\xBF");
                if ($header !== self::COLUMNS) throw ValidationException::withMessages(['csv' => 'Use the downloaded template with its exact column order.']);
                while (($row = fgetcsv($handle)) !== false) {
                    if ($row === [null]) continue;
                    if (count($row) !== count(self::COLUMNS) || count($rows) >= 500) throw ValidationException::withMessages(['csv' => 'Maximum 500 rows; each row must match the template.']);
                    $rows[] = array_combine(self::COLUMNS, array_map(fn($v) => trim($v) === '' ? null : trim($v), $row));
                }
            } finally {
                fclose($handle);
            }
            if (!$rows) throw ValidationException::withMessages(['csv' => 'The CSV has no records.']);
        } else $rows[] = $r->only(self::COLUMNS);
        $validated = [];
        foreach ($rows as $i => $row) {
            $v = Validator::make($row, $this->rules());
            if ($v->fails()) throw ValidationException::withMessages(['history' => 'Row ' . ($i + 1) . ': ' . implode(' ', $v->errors()->all())]);
            $record = $v->validated();
            if (($record['quality'] ?? null) && ($record['kind'] !== 'supplier_delivery' || empty($record['completed_on']))) throw ValidationException::withMessages(['history' => 'Quality outcomes require a completed supplier delivery.']);
            $validated[] = $record;
        }
        DB::transaction(function () use ($validated, $r) {
            foreach ($validated as $record) {
                $key = array_intersect_key($record, array_flip(['company_id', 'kind', 'reference']));
                $existing = VendorObservation::where($key)->lockForUpdate()->first();
                if ($existing) throw ValidationException::withMessages(['history' => 'Duplicate reference ' . $record['reference'] . ': no rows were imported.']);
                VendorObservation::create($record + ['recorded_by' => $r->user()->id]);
            }
        });
        return back()->with('status', count($validated) . ' evidence records added.');
    }
}
