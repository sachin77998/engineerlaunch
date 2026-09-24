<?php

namespace App\Http\Controllers;

use App\Models\InterviewExperience;
use Illuminate\Http\Request;

class AdminCompanyExperienceController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->input('status', 'pending');
        $items = InterviewExperience::with(['company', 'role', 'user', 'rounds', 'package'])->when($status !== 'all', fn($q) => $q->where('moderation_status', $status))->latest()->paginate(20)->withQueryString();
        return view('admin.company-experiences', compact('items', 'status'));
    }
    public function moderate(Request $request, InterviewExperience $experience)
    {
        $data = $request->validate(['decision' => 'required|in:approve,reject']);
        $approved = $data['decision'] === 'approve';
        $experience->update(['moderation_status' => $approved ? 'approved' : 'rejected', 'is_verified' => $approved, 'is_published' => $approved, 'moderated_by' => $request->user()->id, 'moderated_at' => now()]);
        if ($experience->package) $experience->package->update(['is_verified' => $approved]);
        $experience->company->update(['experience_count' => $experience->company->experiences()->where('is_published', true)->count()]);
        return back()->with('success', $approved ? 'Experience published.' : 'Experience rejected.');
    }
}
