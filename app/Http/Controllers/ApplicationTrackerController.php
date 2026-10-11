<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** "Track my applications": every application with its status timeline and how the job's applicant pool is moving. */
class ApplicationTrackerController extends Controller
{
    public const STAGES = ['applied' => 'Applied', 'viewed_by_recruiter' => 'Viewed by recruiter', 'in_progress' => 'Shortlisted / interviews', 'joined' => 'Offer & joined'];

    public function index(Request $request)
    {
        $status = $request->query('status');
        $applications = $request->user()->applications()->with('job.company:id,name,slug,logo_url')
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latest('applied_at')->paginate(20)->withQueryString();

        $ids = $applications->getCollection()->pluck('id');
        $history = DB::table('application_status_history')->whereIn('job_application_id', $ids)->orderBy('created_at')->get()->groupBy('job_application_id');
        // Applicant pool per job: total, rejected, in progress, offers — the candidate sees where the job stands.
        $pools = DB::table('job_applications')->whereIn('job_id', $applications->getCollection()->pluck('job_id'))
            ->selectRaw("job_id, COUNT(*) as total,
                SUM(status = 'rejected') as rejected, SUM(status IN ('viewed_by_recruiter','in_progress')) as in_review,
                SUM(status = 'joined') as offers, SUM(status = 'withdrawn') as withdrawn")
            ->groupBy('job_id')->get()->keyBy('job_id');
        $counts = $request->user()->applications()->selectRaw('status, COUNT(*) as n')->groupBy('status')->pluck('n', 'status');

        return view('candidate.applications', [
            'applications' => $applications, 'history' => $history, 'pools' => $pools, 'counts' => $counts,
            'status' => $status, 'stages' => self::STAGES,
        ]);
    }
}
