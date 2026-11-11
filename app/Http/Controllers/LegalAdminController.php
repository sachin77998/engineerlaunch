<?php

namespace App\Http\Controllers;

use App\Models\Legal\LegalBooking;
use App\Models\Legal\LegalBookingReview;
use App\Models\Legal\LegalPartner;
use App\Models\Legal\LegalService;
use Illuminate\Http\Request;

// Owner's desk for legal services: bookings and the catalogue's prices, turnaround and visibility.
class LegalAdminController extends Controller
{
    public function index(Request $request)
    {
        $status = array_key_exists($request->query('status'), config('legal.statuses')) ? $request->query('status') : null;
        return view('legal.admin', [
            'bookings' => LegalBooking::with(['items', 'rentAgreement'])->when($status, fn ($q) => $q->where('status', $status))->latest()->paginate(30)->withQueryString(),
            'status' => $status, 'counts' => LegalBooking::selectRaw('status, count(*) n')->groupBy('status')->pluck('n', 'status'),
            'services' => LegalService::orderBy('category')->orderBy('sort')->get(),
            'partners' => LegalPartner::orderBy('sort')->get(), 'reviews' => LegalBookingReview::with('user:id,name')->latest()->take(50)->get(),
        ]);
    }

    public function update(Request $request, LegalBooking $booking)
    {
        $booking->update($request->validate([
            'status' => 'required|in:' . implode(',', array_keys(config('legal.statuses'))),
            'admin_note' => 'nullable|string|max:2000',
        ]));
        return back()->with('success', $booking->reference() . ' updated.');
    }

    public function saveService(Request $request, LegalService $service)
    {
        $v = $request->validate([
            'price' => 'nullable|integer|min:0|max:10000000',
            'price_note' => 'nullable|string|max:120',
            'turnaround' => 'nullable|string|max:60',
            'sort' => 'nullable|integer|min:0|max:999',
        ]);
        $service->update($v + ['is_active' => $request->boolean('is_active'), 'sort' => $v['sort'] ?? $service->sort]);
        return back()->with('success', $service->name . ' saved.');
    }

    public function savePartner(Request $request, ?LegalPartner $partner = null)
    {
        $v = $request->validate([
            'name' => 'required|string|max:120',
            'focus' => 'required|string|max:300',
            'affiliation' => 'nullable|string|max:80',
            'rating' => 'nullable|numeric|min:1|max:5',
            'cases_resolved' => 'nullable|integer|min:0|max:1000000',
            'documents_reviewed' => 'nullable|integer|min:0|max:1000000',
            'years_experience' => 'nullable|integer|min:0|max:70',
            'cities' => 'nullable|string|max:500',
            'sort' => 'nullable|integer|min:0|max:999',
        ]);
        $v['cities'] = collect(explode(',', (string) ($v['cities'] ?? '')))->map(fn ($c) => trim($c))->filter()->values()->all() ?: null;
        $v['sort'] = $v['sort'] ?? 0;
        $v['is_active'] = $request->boolean('is_active');
        $partner && $partner->exists ? $partner->update($v) : LegalPartner::create($v);
        return back()->with('success', 'Partner saved.');
    }

    public function toggleReview(LegalBookingReview $review)
    {
        $review->update(['is_visible' => !$review->is_visible]);
        return back()->with('success', $review->is_visible ? 'Review shown.' : 'Review hidden.');
    }
}
