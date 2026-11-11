<?php

namespace App\Http\Controllers;

use App\Models\Property\Property;
use Illuminate\Http\Request;

// Site owner: every listing, with take-down and restore.
class PropertyAdminController extends Controller
{
    public function index(Request $request)
    {
        $f = $request->validate(['q' => 'nullable|string|max:100', 'status' => 'nullable|in:draft,active,closed,inactive', 'reported' => 'nullable|boolean']);
        $items = Property::query()->with(['owner', 'rentTerms', 'saleTerms', 'pgTerms', 'reports' => fn ($r) => $r->where('status', 'open')])->withCount(['leads', 'reports as open_reports' => fn ($r) => $r->where('status', 'open')])
            ->when($f['reported'] ?? null, fn ($q) => $q->whereHas('reports', fn ($r) => $r->where('status', 'open')))
            ->when($f['q'] ?? null, fn ($q, $v) => $q->where(fn ($w) => $w->where('title', 'like', "%$v%")->orWhere('city', 'like', "%$v%")->orWhere('locality', 'like', "%$v%")
                ->orWhereHas('owner', fn ($o) => $o->where('name', 'like', "%$v%")->orWhere('phone', 'like', "%$v%"))))
            ->when($f['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->latest()->paginate(50)->withQueryString();
        return view('properties.admin', compact('items', 'f'));
    }

    /** Mark a dealer as featured (results filter "Featured dealer" and the badge on their listings). */
    public function feature(\App\Models\Property\PropertyOwner $owner)
    {
        abort_unless($owner->owner_type === 'agent', 422, 'Only dealers can be featured.');
        $owner->update(['is_featured' => !$owner->is_featured]);
        return back()->with('success', $owner->name . ($owner->is_featured ? ' is now a featured dealer.' : ' is no longer featured.'));
    }

    public function resolveReports(Property $property)
    {
        $property->reports()->where('status', 'open')->update(['status' => 'resolved']);
        return back()->with('success', 'Reports marked as resolved.');
    }

    /** Mark a listing as checked by the team ("Verified" badge and filter). */
    public function verify(Property $property)
    {
        $property->update(['is_verified' => !$property->is_verified, 'verified_at' => $property->is_verified ? null : now()]);
        return back()->with('success', $property->is_verified ? 'Listing verified.' : 'Verification removed.');
    }

    public function status(Request $request, Property $property)
    {
        $data = $request->validate(['status' => 'required|in:active,inactive']);
        $property->update(['status' => $data['status']] + ($data['status'] === 'active' && !$property->published_at ? ['published_at' => now()] : []));
        return back()->with('success', $data['status'] === 'inactive' ? 'Listing taken down.' : 'Listing restored.');
    }
}
