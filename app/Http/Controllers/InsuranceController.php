<?php

namespace App\Http\Controllers;

use App\Models\Insurer;
use App\Services\InsuranceAdvisor;
use Illuminate\Http\Request;

/** Insurance advisor (health, term, car, bike) and the owner's editor for insurer figures. */
class InsuranceController extends Controller
{
    public function __construct(private InsuranceAdvisor $advisor) {}

    public function index(Request $request, ?string $product = null)
    {
        $product = $product ?? 'health';
        abort_unless(array_key_exists($product, InsuranceAdvisor::PRODUCTS), 404);
        $input = $request->validate([
            'adults' => 'nullable|integer|between:1,2', 'children' => 'nullable|integer|between:0,4', 'age' => 'nullable|integer|between:18,90',
            'city' => 'nullable|in:metro,tier2,tier3', 'cover' => 'nullable|integer|min:0', 'ped' => 'nullable|boolean',
            'income' => 'nullable|numeric|min:0|max:1000000000', 'existing' => 'nullable|numeric|min:0', 'till' => 'nullable|integer|in:60,65,70,75,85',
            'gender' => 'nullable|in:male,female', 'smoker' => 'nullable|boolean',
            'cc' => 'nullable|string|max:10', 'price' => 'nullable|numeric|min:10000|max:100000000', 'vehicle_age' => 'nullable|numeric|min:0|max:30',
            'ncb' => 'nullable|numeric|in:0,20,25,35,45,50', 'zero_dep' => 'nullable|boolean',
        ]);
        $result = $this->advisor->advise($product, $input);
        $gst = round($result['premium'] * $result['gst_rate'] / 100, 2);
        return view('insurance.index', ['product' => $product, 'products' => InsuranceAdvisor::PRODUCTS, 'in' => $input, 'result' => $result,
            'gst' => $gst, 'total' => $result['premium'] + $gst, 'ranked' => $result['insurers']->whereNotNull('claim_settlement_ratio')->isNotEmpty()]);
    }

    public function adminIndex()
    {
        return view('insurance.admin', ['insurers' => Insurer::orderBy('sector')->orderBy('name')->get()]);
    }

    public function adminUpdate(Request $request, Insurer $insurer)
    {
        $data = $request->validate([
            'claim_settlement_ratio' => 'nullable|numeric|between:0,100', 'incurred_claim_ratio' => 'nullable|numeric|between:0,500',
            'complaints_per_10k' => 'nullable|numeric|min:0', 'network_hospitals' => 'nullable|integer|min:0', 'solvency_ratio' => 'nullable|numeric|min:0|max:20',
            'metrics_year' => 'nullable|string|max:10', 'metrics_source' => 'nullable|string|max:255', 'is_active' => 'nullable|boolean',
        ]);
        $insurer->update($data + ['is_active' => $request->boolean('is_active')]);
        return back()->with('success', "Saved {$insurer->name}.");
    }
}
