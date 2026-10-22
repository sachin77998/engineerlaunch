<?php

namespace App\Http\Controllers;

use App\Models\CandidateVerification;
use App\Models\HiringCostSetting;
use App\Models\HiringInterview;
use App\Models\HiringSavedSearch;
use App\Models\HiringScreeningRule;
use App\Models\Job;
use App\Models\JobApplication;
use App\Services\Hiring\ApplicationScreening;
use App\Services\Hiring\CandidateVerifier;
use App\Services\Hiring\HiringAnalytics;
use App\Services\Hiring\Pipeline;
use App\Services\Hiring\TalentSearch;
use App\Services\Hiring\Team;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class HiringSuiteController extends Controller
{
    public function __construct(private TalentSearch $search, private Pipeline $pipeline, private Team $team) {}

    public function talent(Request $r)
    {
        $filters = $this->filters($r);
        $results = $this->search->query($r->user(), $filters)->paginate(50)->withQueryString();
        return view('employer.hiring.talent', [
            'results' => $results, 'f' => $filters, 'jobs' => $this->jobs($r), 'tags' => $this->tags($r),
            'saved' => HiringSavedSearch::where('user_id', $r->user()->id)->latest()->get(),
            'templates' => \App\Models\HiringEmailTemplate::where('owner_id', $this->team->ownerId($r->user()))->orderBy('name')->get(),
            'total' => $this->scoped($r)->count(),
        ]);
    }

    public function export(Request $r)
    {
        $this->team->authorize($r->user(), 'edit');
        $query = $this->search->query($r->user(), $this->filters($r));
        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Name', 'Email', 'Phone', 'Phone verified', 'Job', 'Stage', 'Applied on', 'Match score', 'Rating', 'Experience (yrs)', 'Current CTC', 'Expected CTC', 'Notice period', 'Current company', 'Designation', 'City', 'State', 'Education', 'Tags', 'Source', 'Rejection reason']);
            $query->lazy(500)->each(function (JobApplication $a) use ($out) {
                $p = $a->candidate?->candidateProfile;
                fputcsv($out, [$a->candidate?->name, $a->candidate?->email, $p?->phone, $p?->phone_confirmed_at ? 'Yes' : 'No', $a->job?->title, JobApplication::STAGES[$a->stage] ?? $a->stage,
                    $a->applied_at?->format('Y-m-d'), $a->match_score, $a->rating, $p?->experience_years, $p?->current_salary, $p?->expected_salary, $p?->notice_period, $p?->current_company,
                    $p?->current_designation, $p?->city, $p?->state, $p?->education, implode('; ', (array) $a->tags), $a->application_source ?: 'portal', $a->rejection_reason]);
            });
            fclose($out);
        }, 'talent-database-' . now()->format('Y-m-d') . '.csv', ['Content-Type' => 'text/csv']);
    }

    public function saveSearch(Request $r)
    {
        $d = $r->validate(['name' => 'required|string|max:80']);
        HiringSavedSearch::create(['user_id' => $r->user()->id, 'name' => $d['name'], 'filters' => array_filter($this->filters($r), fn ($v) => $v !== null && $v !== '' && $v !== [])]);
        return back()->with('success', 'Search saved.');
    }

    public function deleteSearch(Request $r, HiringSavedSearch $search)
    {
        abort_unless($search->user_id === $r->user()->id, 403);
        $search->delete();
        return back()->with('success', 'Saved search removed.');
    }

    public function bulk(Request $r)
    {
        $this->team->authorize($r->user(), $r->input('action') === 'email' ? 'email' : 'edit');
        $d = $r->validate(['ids' => 'required|array|max:500', 'ids.*' => 'integer', 'action' => 'required|in:stage,tag,untag,email', 'stage' => 'required_if:action,stage|nullable|in:' . implode(',', array_keys(JobApplication::STAGES)),
            'tag' => 'required_if:action,tag,untag|nullable|string|max:40', 'reason' => 'nullable|string|max:120',
            'subject' => 'required_if:action,email|nullable|string|max:190', 'body' => 'required_if:action,email|nullable|string|max:10000']);
        $apps = $this->scoped($r)->whereIn('job_applications.id', $d['ids'])->get();
        if ($d['action'] === 'email') {
            // Each candidate gets their own personalised copy; nobody sees the other recipients.
            $sent = $apps->filter(fn ($a) => app(\App\Services\Hiring\Messenger::class)->send($a, $r->user(), $d['subject'], $d['body']))->count();
            return back()->with('success', $sent . ' personalised email(s) queued.');
        }
        DB::transaction(function () use ($apps, $d, $r) {
            foreach ($apps as $a) {
                if ($d['action'] === 'stage') $this->pipeline->move($a, $d['stage'], $r->user()->id, 'Bulk update', $d['stage'] === 'rejected' ? ($d['reason'] ?: 'Not a fit') : null);
                else {
                    $tags = collect((array) $a->tags);
                    $tags = $d['action'] === 'tag' ? $tags->push(trim($d['tag']))->unique() : $tags->reject(fn ($t) => $t === trim($d['tag']));
                    $a->forceFill(['tags' => $tags->values()->all()])->saveQuietly();
                }
            }
        });
        return back()->with('success', $apps->count() . ' application(s) updated.');
    }

    public function pipeline(Request $r, Job $job)
    {
        $this->ownJob($r, $job);
        $job->load(['skills', 'screeningRule']);
        // Opening the board counts as the recruiter seeing new applications.
        $job->applications()->where('status', 'applied')->update(['status' => 'viewed_by_recruiter']);
        $apps = $job->applications()->with('candidate.candidateProfile')->orderByDesc('match_score')->orderByDesc('applied_at')->get()->groupBy(fn ($a) => $a->stage ?: 'new');
        return view('employer.hiring.pipeline', ['job' => $job, 'apps' => $apps, 'rule' => $job->screeningRule ?? new HiringScreeningRule(['is_active' => true])]);
    }

    public function saveRules(Request $r, Job $job, ApplicationScreening $screening)
    {
        $this->ownJob($r, $job, 'edit');
        $d = $r->validate(['min_experience' => 'nullable|numeric|min:0|max:50', 'max_experience' => 'nullable|numeric|min:0|max:60', 'max_notice_days' => 'nullable|integer|min:0|max:365',
            'max_expected_salary' => 'nullable|numeric|min:0', 'min_skill_match' => 'nullable|integer|min:0|max:100', 'locations' => 'nullable|string|max:500',
            'auto_shortlist_score' => 'nullable|integer|min:1|max:100']);
        $d['locations'] = collect(preg_split('/\s*,\s*/', (string) ($d['locations'] ?? '')))->filter()->values()->all() ?: null;
        foreach (['require_verified_phone', 'auto_reject', 'is_active'] as $flag) $d[$flag] = $r->boolean($flag);
        HiringScreeningRule::updateOrCreate(['job_id' => $job->id], $d);
        $count = $r->boolean('rescreen') ? $screening->rescreenJob($job->fresh()) : 0;
        return back()->with('success', 'Screening rules saved.' . ($count ? " $count waiting application(s) re-screened." : ''));
    }

    public function candidate(Request $r, JobApplication $application, CandidateVerifier $verifier)
    {
        $this->ownApp($r, $application);
        $application->load(['candidate.candidateProfile', 'job.skills', 'resume', 'notes.author:id,name', 'interviews', 'verifications', 'messages.sender:id,name', 'offers']);
        if ($application->status === 'applied') {
            $application->forceFill(['status' => 'viewed_by_recruiter'])->saveQuietly();
            DB::table('application_status_history')->insert(['job_application_id' => $application->id, 'changed_by' => $r->user()->id, 'from_status' => 'applied', 'to_status' => 'viewed_by_recruiter', 'note' => 'Application viewed by recruiter.', 'created_at' => now()]);
        }
        $answers = DB::table('application_answers as a')->join('job_screening_questions as q', 'q.id', '=', 'a.question_id')->where('a.job_application_id', $application->id)->orderBy('q.sort_order')->get(['q.question', 'a.answer']);
        $history = DB::table('application_status_history as h')->leftJoin('users as u', 'u.id', '=', 'h.changed_by')->where('h.job_application_id', $application->id)->orderByDesc('h.id')->get(['h.*', 'u.name as by_name']);
        $others = $this->scoped($r)->where('job_applications.user_id', $application->user_id)->where('job_applications.id', '!=', $application->id)->with('job:id,title')->get();
        $profile = $application->candidate?->candidateProfile;
        $skills = $profile ? DB::table('candidate_skills')->join('technologies', 'technologies.id', '=', 'candidate_skills.technology_id')->where('candidate_profile_id', $profile->id)->get(['technologies.name', 'candidate_skills.years']) : collect();
        $allVerifications = CandidateVerification::where('candidate_id', $application->user_id)->latest()->get();
        return view('employer.hiring.candidate', ['a' => $application, 'p' => $profile, 'answers' => $answers, 'history' => $history, 'others' => $others, 'skills' => $skills,
            'checks' => $verifier->basicChecks($application), 'verifications' => $allVerifications, 'provider' => $verifier->provider(),
            'templates' => \App\Models\HiringEmailTemplate::where('owner_id', $this->team->ownerId($r->user()))->orderBy('name')->get(),
            'companyName' => $application->job->company?->name, 'uanReady' => filled(config('services.surepass.token'))]);
    }

    public function stage(Request $r, JobApplication $application)
    {
        $this->ownApp($r, $application, 'edit');
        $d = $r->validate(['stage' => 'required|in:' . implode(',', array_keys(JobApplication::STAGES)), 'reason' => 'nullable|string|max:120', 'note' => 'nullable|string|max:500']);
        $this->pipeline->move($application, $d['stage'], $r->user()->id, $d['note'] ?? null, $d['stage'] === 'rejected' ? ($d['reason'] ?? 'Not a fit') : null);
        return back()->with('success', 'Moved to ' . JobApplication::STAGES[$d['stage']] . '.');
    }

    public function updateCandidate(Request $r, JobApplication $application)
    {
        $this->ownApp($r, $application, 'edit');
        $d = $r->validate(['rating' => 'nullable|integer|min:1|max:5', 'tags' => 'nullable|string|max:300']);
        $application->forceFill(['rating' => $d['rating'] ?? null, 'tags' => collect(preg_split('/\s*,\s*/', (string) ($d['tags'] ?? '')))->filter()->unique()->take(12)->values()->all()])->saveQuietly();
        return back()->with('success', 'Candidate updated.');
    }

    public function note(Request $r, JobApplication $application)
    {
        $this->ownApp($r, $application, 'feedback');
        $application->notes()->create(['user_id' => $r->user()->id, 'body' => $r->validate(['body' => 'required|string|max:3000'])['body']]);
        return back()->with('success', 'Note added.');
    }

    public function schedule(Request $r, JobApplication $application)
    {
        $this->ownApp($r, $application, 'edit');
        $d = $r->validate(['round' => 'required|string|max:80', 'mode' => 'required|in:' . implode(',', array_keys(HiringInterview::MODES)), 'scheduled_at' => 'required|date|after:now',
            'duration_minutes' => 'required|integer|min:10|max:480', 'location' => 'nullable|string|max:255', 'interviewer_name' => 'nullable|string|max:120', 'interviewer_email' => 'nullable|email|max:190', 'notify' => 'nullable|boolean']);
        $interview = $application->interviews()->create($d + ['scheduled_by' => $r->user()->id, 'status' => 'scheduled']);
        if (in_array($application->stage, ['new', 'screening', 'shortlisted'], true)) $this->pipeline->move($application, 'interview', $r->user()->id, $d['round'] . ' scheduled for ' . Carbon::parse($d['scheduled_at'])->format('d M Y, h:i A'));
        if ($r->boolean('notify')) $this->sendInvite($interview->load('application.candidate', 'application.job.company'), $r->user()->name);
        return back()->with('success', 'Interview scheduled' . ($r->boolean('notify') ? ' and calendar invite emailed.' : '.'));
    }

    public function feedback(Request $r, HiringInterview $interview)
    {
        $this->ownApp($r, $interview->application, 'feedback');
        $d = $r->validate(['status' => 'required|in:scheduled,completed,cancelled,no_show', 'rating' => 'nullable|integer|min:1|max:5', 'recommendation' => 'nullable|in:' . implode(',', array_keys(HiringInterview::RECOMMENDATIONS)), 'feedback' => 'nullable|string|max:5000']);
        $interview->update($d);
        return back()->with('success', 'Interview updated.');
    }

    public function interviews(Request $r)
    {
        $when = $r->query('when', 'upcoming');
        $q = HiringInterview::query()->with(['application.candidate', 'application.job:id,title'])
            ->whereHas('application.job', fn ($j) => $r->user()->role === 'admin' ? $j : $j->whereIn('employer_id', $this->team->userIds($r->user())))
            ->when($this->team->role($r->user()) === 'interviewer', fn ($q) => $q->where('interviewer_email', $r->user()->email));
        match ($when) {
            'today' => $q->whereDate('scheduled_at', today())->orderBy('scheduled_at'),
            'past' => $q->where('scheduled_at', '<', now())->orderByDesc('scheduled_at'),
            'pending_feedback' => $q->where('scheduled_at', '<', now())->where('status', 'scheduled')->orderByDesc('scheduled_at'),
            default => $q->where('scheduled_at', '>=', now()->startOfDay())->where('status', 'scheduled')->orderBy('scheduled_at'),
        };
        return view('employer.hiring.interviews', ['interviews' => $q->paginate(50)->withQueryString(), 'when' => $when]);
    }

    public function verifyPan(Request $r, JobApplication $application, CandidateVerifier $verifier)
    {
        $this->ownApp($r, $application, 'verify');
        $this->limitChecks($r);
        $d = $r->validate(['pan' => 'required|string|max:12', 'name' => 'required|string|max:120', 'consent' => 'accepted']);
        $v = $verifier->pan($application, $r->user()->id, $d['pan'], $d['name']);
        return back()->with('success', 'PAN check: ' . $v->message);
    }

    public function aadhaarStart(Request $r, JobApplication $application, CandidateVerifier $verifier)
    {
        $this->ownApp($r, $application, 'verify');
        $this->limitChecks($r);
        $d = $r->validate(['aadhaar' => 'required|string|max:16', 'consent' => 'accepted']);
        $v = $verifier->aadhaarStart($application, $r->user()->id, $d['aadhaar']);
        return back()->with('success', 'Aadhaar check: ' . $v->message);
    }

    public function aadhaarFinish(Request $r, CandidateVerification $verification, CandidateVerifier $verifier)
    {
        abort_unless($verification->type === 'aadhaar' && $verification->status === 'pending' && $verification->job_application_id, 422);
        $this->ownApp($r, JobApplication::findOrFail($verification->job_application_id), 'verify');
        $v = $verifier->aadhaarFinish($verification, $r->validate(['otp' => 'required|digits:6'])['otp']);
        return back()->with('success', 'Aadhaar check: ' . $v->message);
    }

    public function resume(Request $r, JobApplication $application)
    {
        $this->ownApp($r, $application);
        $resume = $application->resume ?: \App\Models\Resume::where('candidate_profile_id', $application->candidate?->candidateProfile?->id)->orderByDesc('is_primary')->latest()->first();
        abort_unless($resume && Storage::disk($resume->disk ?: 'local')->exists($resume->path), 404, 'No resume file on record.');
        return Storage::disk($resume->disk ?: 'local')->download($resume->path, $resume->original_name ?: 'resume.pdf');
    }

    public function analytics(Request $r, HiringAnalytics $analytics)
    {
        $period = $r->query('period', '90d');
        $from = $r->filled('from') ? Carbon::parse($r->query('from'))->startOfDay() : match ($period) {
            '30d' => now()->subDays(30), '6m' => now()->subMonths(6), '1y' => now()->subYear(), '3y' => now()->subYears(3), '5y' => now()->subYears(5), default => now()->subMonths(3),
        };
        $to = $r->filled('to') ? Carbon::parse($r->query('to'))->endOfDay() : now();
        return view('employer.hiring.analytics', ['m' => $analytics->build($r->user(), $from, $to), 'period' => $period, 'from' => $from, 'to' => $to]);
    }

    public function saveCosts(Request $r)
    {
        $this->team->authorize($r->user(), 'costs');
        $d = $r->validate(['recruiters' => 'required|integer|min:0|max:1000', 'monthly_cost_per_recruiter' => 'required|numeric|min:0', 'monthly_tools_spend' => 'required|numeric|min:0',
            'monthly_job_board_spend' => 'required|numeric|min:0', 'agency_fee_per_hire' => 'required|numeric|min:0', 'minutes_per_manual_screen' => 'required|integer|min:1|max:120']);
        HiringCostSetting::updateOrCreate(['user_id' => $this->team->ownerId($r->user())], $d);
        return back()->with('success', 'Hiring cost inputs saved.');
    }

    private function sendInvite(HiringInterview $i, string $organizer): void
    {
        $a = $i->application;
        $to = $a->candidate?->email;
        if (!$to) return;
        $start = $i->scheduled_at->copy()->utc();
        $title = $i->round . ' — ' . $a->job?->title . ' at ' . ($a->job?->company?->name ?? 'our company');
        $ics = implode("\r\n", ['BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//EngineerLaunch//Hiring//EN', 'METHOD:REQUEST', 'BEGIN:VEVENT', 'UID:interview-' . $i->id . '@' . parse_url(config('app.url'), PHP_URL_HOST),
            'DTSTAMP:' . now()->utc()->format('Ymd\THis\Z'), 'DTSTART:' . $start->format('Ymd\THis\Z'), 'DTEND:' . $start->copy()->addMinutes($i->duration_minutes)->format('Ymd\THis\Z'),
            'SUMMARY:' . addcslashes($title, ',;'), 'LOCATION:' . addcslashes((string) $i->location, ',;'), 'DESCRIPTION:' . addcslashes(HiringInterview::MODES[$i->mode] . ' interview. Organizer: ' . $organizer, ',;'), 'END:VEVENT', 'END:VCALENDAR']);
        $body = "Hello {$a->candidate->name},\n\nYour {$i->round} for {$a->job?->title} is scheduled on " . $i->scheduled_at->format('l, d M Y \a\t h:i A') . " ({$i->duration_minutes} minutes).\nMode: " . HiringInterview::MODES[$i->mode]
            . ($i->location ? "\nWhere / link: {$i->location}" : '') . ($i->interviewer_name ? "\nInterviewer: {$i->interviewer_name}" : '') . "\n\nThe calendar invite is attached.\n\nRegards,\n{$organizer}";
        $cc = $i->interviewer_email;
        dispatch(function () use ($to, $cc, $title, $body, $ics) {
            Mail::raw($body, function ($m) use ($to, $cc, $title, $ics) {
                $m->to($to)->subject('Interview: ' . $title)->attachData($ics, 'interview.ics', ['mime' => 'text/calendar; method=REQUEST']);
                if ($cc) $m->cc($cc);
            });
        })->afterResponse();
    }

    // Provider checks are billed per call: 40 per recruiter per hour (own key, not the shared route throttle).
    private function limitChecks(Request $r): void
    {
        $key = 'hiring-verify:' . $r->user()->id;
        abort_if(\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($key, 40), 429, 'Verification limit reached; try again in an hour.');
        \Illuminate\Support\Facades\RateLimiter::hit($key, 3600);
    }

    private function filters(Request $r): array
    {
        $f = $r->only(TalentSearch::FIELDS);
        if ($r->filled('saved') && $s = HiringSavedSearch::where('user_id', $r->user()->id)->find($r->integer('saved'))) $f = $s->filters + $f;
        $f['stage'] = array_values(array_filter((array) ($f['stage'] ?? [])));
        return $f;
    }

    private function scoped(Request $r)
    {
        return JobApplication::query()->select('job_applications.*')->join('jobs', 'jobs.id', '=', 'job_applications.job_id')->when($r->user()->role !== 'admin', fn ($q) => $q->whereIn('jobs.employer_id', $this->team->userIds($r->user())));
    }

    private function jobs(Request $r)
    {
        return Job::query()->when($r->user()->role !== 'admin', fn ($q) => $q->whereIn('employer_id', $this->team->userIds($r->user())))->orderByDesc('id')->limit(500)->get(['id', 'title', 'status']);
    }

    private function tags(Request $r)
    {
        return $this->scoped($r)->whereNotNull('job_applications.tags')->limit(2000)->toBase()->pluck('job_applications.tags')->flatMap(fn ($t) => (array) json_decode((string) $t, true))->filter()->countBy()->sortDesc()->keys()->take(30);
    }

    private function ownJob(Request $r, Job $job, string $ability = 'view'): void
    {
        abort_unless($r->user()->role === 'admin' || in_array((int) $job->employer_id, $this->team->userIds($r->user()), true), 403);
        $this->team->authorize($r->user(), $ability);
    }

    private function ownApp(Request $r, ?JobApplication $application, string $ability = 'view'): void
    {
        abort_unless($application && $this->search->owns($r->user(), $application->loadMissing('job')), 403);
        $this->team->authorize($r->user(), $ability);
    }
}
