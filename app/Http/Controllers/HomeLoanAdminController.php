<?php

namespace App\Http\Controllers;

use App\Models\HomeLoan\HomeLoanApplication;
use App\Models\HomeLoan\HomeLoanBank;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

// Owner's desk: applications with their documents, and the lender table shown on /home-loan.
class HomeLoanAdminController extends Controller
{
    public function index(Request $request)
    {
        $status = array_key_exists($request->query('status'), config('home_loans.statuses')) ? $request->query('status') : null;
        $items = HomeLoanApplication::with(['bank', 'documents', 'property'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', '%' . $request->query('q') . '%')->orWhere('phone', 'like', '%' . $request->query('q') . '%')))
            ->latest()->paginate(30)->withQueryString();
        return view('home-loan.admin', [
            'items' => $items, 'status' => $status, 'banks' => HomeLoanBank::orderBy('sort')->orderBy('name')->get(),
            'counts' => HomeLoanApplication::selectRaw('status, count(*) n')->groupBy('status')->pluck('n', 'status'),
        ]);
    }

    public function update(Request $request, HomeLoanApplication $application)
    {
        $application->update($request->validate([
            'status' => 'required|in:' . implode(',', array_keys(config('home_loans.statuses'))),
            'admin_note' => 'nullable|string|max:2000',
        ]));
        return back()->with('success', $application->reference() . ' updated.');
    }

    public function destroy(HomeLoanApplication $application)
    {
        Storage::disk('local')->deleteDirectory('home-loans/' . $application->id);
        $application->delete();
        return back()->with('success', 'Application and its documents deleted.');
    }

    public function saveBank(Request $request, ?HomeLoanBank $bank = null)
    {
        $v = $request->validate([
            'name' => 'required|string|max:120',
            'kind' => 'required|in:bank,nbfc',
            'rate_from' => 'required|numeric|min:1|max:30',
            'processing_fee' => 'nullable|string|max:191',
            'max_tenure' => 'required|integer|min:1|max:40',
            'max_funding' => 'required|integer|min:10|max:100',
            'max_amount' => 'nullable|string|max:60',
            'color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'sort' => 'nullable|integer|min:0|max:999',
            'is_active' => 'nullable|boolean',
        ]);
        $v['is_active'] = $request->boolean('is_active');
        $v['color'] = $v['color'] ?? '#4338ca';
        $v['sort'] = $v['sort'] ?? 0;
        $bank && $bank->exists ? $bank->update($v) : HomeLoanBank::create($v);
        return back()->with('success', 'Lender saved.');
    }
}
