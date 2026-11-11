<?php

namespace App\Http\Controllers;

use App\Models\HomeService\HomeService;
use App\Models\HomeService\HomeServiceBooking;
use App\Models\HomeService\HomeServiceReview;
use Illuminate\Http\Request;

// Owner's desk for home services: bookings by day and status, and the catalogue's prices / units / visibility.
class HomeServiceAdminController extends Controller
{
    public function index(Request $request)
    {
        $status = array_key_exists($request->query('status'), config('home_services.statuses')) ? $request->query('status') : null;
        return view('home-services.admin', [
            'bookings' => HomeServiceBooking::with(['items', 'user:id,name,email'])->when($status, fn ($q) => $q->where('status', $status))
                ->orderByRaw("status in ('completed','cancelled')")->orderBy('slot_date')->orderBy('slot')->paginate(40)->withQueryString(),
            'status' => $status, 'counts' => HomeServiceBooking::selectRaw('status, count(*) n')->groupBy('status')->pluck('n', 'status'),
            'services' => HomeService::orderBy('category')->orderBy('sort')->get(),
            'reviews' => HomeServiceReview::with('user:id,name')->latest()->take(40)->get(),
        ]);
    }

    public function update(Request $request, HomeServiceBooking $booking)
    {
        $booking->update($request->validate(['status' => 'required|in:' . implode(',', array_keys(config('home_services.statuses'))), 'admin_note' => 'nullable|string|max:500']));
        return back()->with('success', $booking->reference() . ' updated.');
    }

    public function saveService(Request $request, HomeService $service)
    {
        $v = $request->validate(['price' => 'nullable|integer|min:0|max:10000000', 'unit' => 'required|string|max:30', 'sort' => 'nullable|integer|min:0|max:999']);
        $service->update($v + ['is_active' => $request->boolean('is_active'), 'sort' => $v['sort'] ?? $service->sort]);
        return back()->with('success', $service->name . ' saved.');
    }

    public function toggleReview(HomeServiceReview $review)
    {
        $review->update(['is_visible' => !$review->is_visible]);
        return back()->with('success', $review->is_visible ? 'Review shown.' : 'Review hidden.');
    }
}
