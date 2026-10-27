<?php

namespace App\Http\Controllers;

use App\Models\HiringEmailTemplate;
use App\Models\HiringOnboardingTemplate;
use App\Models\HiringWorkflow;
use App\Models\HiringWorkflowRun;
use App\Models\Job;
use App\Models\JobApplication;
use App\Services\Hiring\Team;
use App\Services\Hiring\Workflows;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

// Employer automation rules: list with run history and time / cost saved, create, edit, pause, delete.
class HiringWorkflowController extends Controller
{
    public function __construct(private Team $team, private Workflows $workflows) {}

    public function index(Request $r)
    {
        $this->team->authorize($r->user(), 'view');
        $ownerId = $this->team->ownerId($r->user());
        $workflows = HiringWorkflow::with('job:id,title')->where('owner_id', $ownerId)->orderByDesc('is_active')->orderBy('name')->get();
        $runs = HiringWorkflowRun::with(['workflow:id,name', 'application.candidate:id,name', 'application.job:id,title'])
            ->whereIn('workflow_id', $workflows->pluck('id'))->latest('id')->limit(25)->get();
        $edit = $r->query('edit') ? $workflows->firstWhere('id', (int) $r->query('edit')) : null;
        return view('employer.hiring.workflows', [
            'workflows' => $workflows, 'runs' => $runs, 'edit' => $edit, 'savings' => $this->workflows->savings($r->user()),
            'triggers' => Workflows::triggers(), 'actions' => Workflows::ACTIONS, 'stages' => JobApplication::STAGES,
            'jobs' => Job::whereIn('employer_id', $this->team->userIds($r->user()))->orderByDesc('id')->limit(200)->get(['id', 'title']),
            'templates' => HiringEmailTemplate::where('owner_id', $ownerId)->orderBy('name')->get(['id', 'name']),
            'onboardingTemplates' => HiringOnboardingTemplate::where('owner_id', $ownerId)->orderBy('name')->get(['id', 'name']),
            'canEdit' => $this->team->can($r->user(), 'edit'),
        ]);
    }

    public function store(Request $r)
    {
        $this->team->authorize($r->user(), 'edit');
        HiringWorkflow::create($this->validated($r) + ['owner_id' => $this->team->ownerId($r->user()), 'created_by' => $r->user()->id, 'is_active' => true]);
        return redirect()->route('employer.hiring.workflows')->with('success', 'Workflow saved. It runs automatically from now on.');
    }

    public function update(Request $r, HiringWorkflow $workflow)
    {
        $this->own($r, $workflow);
        $workflow->update($this->validated($r));
        return redirect()->route('employer.hiring.workflows')->with('success', 'Workflow updated.');
    }

    public function toggle(Request $r, HiringWorkflow $workflow)
    {
        $this->own($r, $workflow);
        $workflow->update(['is_active' => !$workflow->is_active]);
        return back()->with('success', $workflow->is_active ? 'Workflow switched on.' : 'Workflow paused.');
    }

    public function destroy(Request $r, HiringWorkflow $workflow)
    {
        $this->own($r, $workflow);
        $workflow->delete();
        return back()->with('success', 'Workflow deleted.');
    }

    private function own(Request $r, HiringWorkflow $workflow): void
    {
        abort_unless($workflow->owner_id === $this->team->ownerId($r->user()) || $r->user()->role === 'admin', 403);
        $this->team->authorize($r->user(), 'edit');
    }

    private function validated(Request $r): array
    {
        $ownerId = $this->team->ownerId($r->user());
        $d = $r->validate([
            'name' => 'required|string|max:120',
            'trigger' => ['required', Rule::in(array_keys(Workflows::triggers()))],
            'job_id' => ['nullable', 'integer', Rule::exists('jobs', 'id')->whereIn('employer_id', $this->team->userIds($r->user()))],
            'steps' => 'required|array|min:1|max:8',
            'steps.*.action' => ['required', Rule::in(array_keys(Workflows::ACTIONS))],
            'steps.*.template_id' => ['nullable', 'integer', Rule::exists('hiring_email_templates', 'id')->where('owner_id', $ownerId)],
            'steps.*.onboarding_template_id' => ['nullable', 'integer', Rule::exists('hiring_onboarding_templates', 'id')->where('owner_id', $ownerId)],
            'steps.*.subject' => 'nullable|string|max:190',
            'steps.*.body' => 'nullable|string|max:5000',
            'steps.*.stage' => ['nullable', Rule::in(array_keys(JobApplication::STAGES))],
            'steps.*.message' => 'nullable|string|max:1000',
            'steps.*.tag' => 'nullable|string|max:40',
        ]);
        $steps = [];
        foreach (array_values($d['steps']) as $i => $s) {
            $config = match ($s['action']) {
                'send_email' => ['template_id' => $s['template_id'] ?? null, 'subject' => $s['subject'] ?? null, 'body' => $s['body'] ?? null],
                'move_stage' => ['stage' => $s['stage'] ?? null],
                'start_onboarding' => ['template_id' => $s['onboarding_template_id'] ?? null],
                'notify_team' => ['message' => $s['message'] ?? null],
                'add_tag' => ['tag' => $s['tag'] ?? null],
            };
            $n = $i + 1;
            if ($s['action'] === 'send_email' && empty($config['template_id']) && (blank($config['subject']) || blank($config['body'])))
                throw \Illuminate\Validation\ValidationException::withMessages(["steps.$i.subject" => "Step $n: choose an email template or write a subject and message."]);
            if ($s['action'] === 'move_stage' && empty($config['stage'])) throw \Illuminate\Validation\ValidationException::withMessages(["steps.$i.stage" => "Step $n: choose the stage."]);
            if ($s['action'] === 'add_tag' && blank($config['tag'])) throw \Illuminate\Validation\ValidationException::withMessages(["steps.$i.tag" => "Step $n: enter the tag."]);
            if ($s['action'] === 'move_stage' && 'stage.' . $config['stage'] === $d['trigger']) throw \Illuminate\Validation\ValidationException::withMessages(["steps.$i.stage" => "Step $n: the candidate is already in that stage when this workflow runs."]);
            $steps[] = ['action' => $s['action'], 'config' => array_filter($config, fn ($v) => $v !== null && $v !== '')];
        }
        return ['name' => $d['name'], 'trigger' => $d['trigger'], 'job_id' => $d['job_id'] ?? null, 'steps' => $steps];
    }
}
