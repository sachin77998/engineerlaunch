<?php

namespace App\Services\Hiring;

use App\Mail\HiringMail;
use App\Models\HiringEmailTemplate;
use App\Models\HiringWorkflow;
use App\Models\HiringWorkflowRun;
use App\Models\JobApplication;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Throwable;

// Hiring automation. A workflow is "when <trigger> happens on an application, run <steps>"; each run is logged with the
// minutes of manual work its steps replace (the savings shown on the Workflows page).
class Workflows
{
    public const ACTIONS = [
        'send_email' => 'Email the candidate',
        'move_stage' => 'Move to stage',
        'start_onboarding' => 'Start online onboarding (document collection)',
        'notify_team' => 'Email the hiring team',
        'add_tag' => 'Tag the candidate',
    ];
    // Typical manual minutes each step replaces: writing and sending an email, updating the tracker, preparing the
    // joining checklist and chasing documents, informing colleagues, tagging.
    public const MINUTES = ['send_email' => 6, 'move_stage' => 2, 'start_onboarding' => 45, 'notify_team' => 4, 'add_tag' => 1];
    // Collecting, checking and filing one joining document on paper.
    public const MINUTES_PER_DOCUMENT = 12;

    private array $running = [];

    public function __construct(private Messenger $messenger, private Team $team) {}

    /** @return array<string,string> trigger key => label */
    public static function triggers(): array
    {
        $t = ['application.new' => 'A new application arrives'];
        foreach (JobApplication::STAGES as $k => $v) if ($k !== 'new') $t['stage.' . $k] = 'Candidate moves to ' . $v;
        return $t + ['offer.accepted' => 'Candidate accepts the offer', 'offer.declined' => 'Candidate declines the offer', 'onboarding.completed' => 'Onboarding checklist is completed'];
    }

    /** Runs every active workflow of the job's team for this trigger. Failures are logged on the run, never thrown. */
    public function fire(string $trigger, JobApplication $a): void
    {
        $a->loadMissing(['job', 'candidate']);
        $employer = User::find($a->job?->employer_id);
        if (!$employer) return;
        $ownerId = $this->team->ownerId($employer);
        $workflows = HiringWorkflow::where('owner_id', $ownerId)->where('trigger', $trigger)->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('job_id')->orWhere('job_id', $a->job_id))->orderBy('id')->get();
        foreach ($workflows as $w) {
            // A workflow never re-enters itself for the same application (a "move stage" step firing its own trigger).
            $key = $w->id . ':' . $a->id;
            if (isset($this->running[$key]) || count($this->running) >= 5) continue;
            $this->running[$key] = true;
            try { $this->run($w, $a, $trigger); } finally { unset($this->running[$key]); }
        }
    }

    public function run(HiringWorkflow $w, JobApplication $a, string $trigger): HiringWorkflowRun
    {
        $sender = User::find($w->created_by) ?? User::find($w->owner_id);
        $log = []; $minutes = 0; $failed = false;
        foreach ($w->steps as $step) {
            $action = $step['action'] ?? '';
            try {
                $note = $this->step($action, $step, $a->fresh(['job', 'candidate']), $sender);
                $log[] = ['action' => $action, 'ok' => true, 'note' => $note];
                $minutes += self::MINUTES[$action] ?? 0;
            } catch (Throwable $e) {
                report($e);
                $failed = true;
                $log[] = ['action' => $action, 'ok' => false, 'note' => mb_substr($e->getMessage(), 0, 200)];
            }
        }
        $w->forceFill(['runs_count' => $w->runs_count + 1, 'last_run_at' => now()])->save();
        return HiringWorkflowRun::create(['workflow_id' => $w->id, 'job_application_id' => $a->id, 'trigger' => $trigger,
            'status' => $failed ? ($minutes ? 'partial' : 'failed') : 'ok', 'log' => $log, 'minutes_saved' => $minutes]);
    }

    private function step(string $action, array $step, JobApplication $a, User $sender): string
    {
        $c = $step['config'] ?? [];
        switch ($action) {
            case 'send_email':
                $tpl = !empty($c['template_id']) ? HiringEmailTemplate::where('owner_id', $this->team->ownerId($sender))->find($c['template_id']) : null;
                $subject = $tpl?->subject ?? ($c['subject'] ?? ''); $body = $tpl?->body ?? ($c['body'] ?? '');
                if (trim($subject) === '' || trim($body) === '') throw new \RuntimeException('Email step has no subject or message.');
                if (!$this->messenger->send($a, $sender, $subject, $body)) throw new \RuntimeException('Candidate has no email address.');
                return 'Emailed ' . $a->candidate?->email;
            case 'move_stage':
                $stage = (string) ($c['stage'] ?? '');
                if (!isset(JobApplication::STAGES[$stage])) throw new \RuntimeException('Unknown stage.');
                if ($a->stage === $stage) return 'Already in ' . JobApplication::STAGES[$stage];
                app(Pipeline::class)->move($a, $stage, $sender->id, 'Automated workflow');
                return 'Moved to ' . JobApplication::STAGES[$stage];
            case 'start_onboarding':
                $o = app(Onboarding::class)->start($a, $sender, !empty($c['template_id']) ? (int) $c['template_id'] : null);
                return 'Onboarding #' . $o->id . ' started; link emailed';
            case 'notify_team':
                $ids = $this->team->userIds($sender);
                $emails = User::whereIn('id', $ids)->pluck('email')->filter()->unique()->values();
                $text = $this->messenger->render((string) ($c['message'] ?? '{candidate_name} — {job_title}: now {stage}.'), $a, $sender);
                foreach ($emails as $email) Mail::to($email)->queue((new HiringMail('Hiring update: ' . $a->candidate?->name . ' — ' . $a->job?->title,
                    $text . "\n\nOpen the candidate: " . route('employer.hiring.candidate', $a)))->onQueue('emails'));
                return 'Notified ' . $emails->count() . ' team member(s)';
            case 'add_tag':
                $tag = trim(mb_substr((string) ($c['tag'] ?? ''), 0, 40));
                if ($tag === '') throw new \RuntimeException('Tag step has no tag.');
                $tags = collect($a->tags ?? [])->push($tag)->unique()->values()->all();
                $a->forceFill(['tags' => $tags])->save();
                return 'Tagged "' . $tag . '"';
        }
        throw new \RuntimeException('Unknown step.');
    }

    /**
     * Manual work replaced this month and in total: automated steps plus joining documents collected online.
     * @return array{month: array, total: array}
     */
    public function savings(User $user): array
    {
        $ownerId = $this->team->ownerId($user);
        $cost = \App\Models\HiringCostSetting::where('user_id', $ownerId)->value('monthly_cost_per_recruiter') ?: 50000;
        $perMinute = (float) $cost / (22 * 8 * 60);
        $calc = function ($since) use ($ownerId, $perMinute) {
            $runs = HiringWorkflowRun::whereIn('workflow_id', HiringWorkflow::where('owner_id', $ownerId)->select('id'))->when($since, fn ($q) => $q->where('created_at', '>=', $since));
            $docs = \App\Models\HiringOnboardingItem::where('kind', 'document')->where('status', 'approved')
                ->whereIn('onboarding_id', \App\Models\HiringOnboarding::where('owner_id', $ownerId)->select('id'))->when($since, fn ($q) => $q->where('reviewed_at', '>=', $since))->count();
            $minutes = (int) (clone $runs)->sum('minutes_saved') + $docs * self::MINUTES_PER_DOCUMENT;
            return ['runs' => (clone $runs)->count(), 'documents' => $docs, 'minutes' => $minutes, 'hours' => round($minutes / 60, 1), 'rupees' => (int) round($minutes * $perMinute)];
        };
        return ['month' => $calc(now()->startOfMonth()), 'total' => $calc(null), 'cost_per_recruiter' => (int) $cost];
    }
}
