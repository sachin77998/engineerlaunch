<?php

namespace App\Http\Controllers;

use App\Models\HiringOnboarding;
use App\Models\HiringOnboardingItem;
use App\Models\HiringOnboardingTemplate;
use App\Models\JobApplication;
use App\Services\Hiring\Onboarding;
use App\Services\Hiring\TalentSearch;
use App\Services\Hiring\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;

// Online onboarding: employer list / review screens, and the new hire's private upload page.
class HiringOnboardingController extends Controller
{
    public function __construct(private Team $team, private Onboarding $onboarding, private TalentSearch $search) {}

    public function index(Request $r)
    {
        $this->team->authorize($r->user(), 'view');
        $status = in_array($r->query('status'), ['in_progress', 'completed', 'cancelled'], true) ? $r->query('status') : 'in_progress';
        $list = HiringOnboarding::with(['items', 'application.candidate:id,name,email', 'application.job:id,title'])
            ->where('owner_id', $this->team->ownerId($r->user()))->where('status', $status)->latest('id')->paginate(25)->withQueryString();
        $counts = HiringOnboarding::where('owner_id', $this->team->ownerId($r->user()))->selectRaw('status, count(*) n')->groupBy('status')->pluck('n', 'status');
        $templates = HiringOnboardingTemplate::where('owner_id', $this->team->ownerId($r->user()))->orderBy('name')->get();
        return view('employer.hiring.onboarding', ['list' => $list, 'status' => $status, 'counts' => $counts, 'templates' => $templates,
            'defaultItems' => Onboarding::DEFAULT_ITEMS, 'canEdit' => $this->team->can($r->user(), 'edit')]);
    }

    public function show(Request $r, HiringOnboarding $onboarding)
    {
        $this->own($r, $onboarding, 'view');
        $onboarding->load(['items', 'application.candidate', 'application.job.company']);
        return view('employer.hiring.onboarding-show', ['o' => $onboarding, 'canEdit' => $this->team->can($r->user(), 'edit')]);
    }

    public function start(Request $r, JobApplication $application)
    {
        abort_unless($this->search->owns($r->user(), $application->loadMissing('job')), 403);
        $this->team->authorize($r->user(), 'edit');
        $ownerId = $this->team->ownerId($r->user());
        $d = $r->validate(['template_id' => ['nullable', 'integer', \Illuminate\Validation\Rule::exists('hiring_onboarding_templates', 'id')->where('owner_id', $ownerId)], 'due_on' => 'nullable|date|after_or_equal:today']);
        $o = $this->onboarding->start($application, $r->user(), $d['template_id'] ?? null, $d['due_on'] ?? null);
        return redirect()->route('employer.hiring.onboarding.show', $o)->with('success', 'Onboarding started; the joining-documents link was emailed to ' . $application->candidate?->email . '.');
    }

    public function resend(Request $r, HiringOnboarding $onboarding)
    {
        $this->own($r, $onboarding, 'edit');
        abort_unless($onboarding->status === 'in_progress', 422, 'This onboarding is not in progress.');
        $this->onboarding->sendLink($onboarding, $r->user());
        return back()->with('success', 'A new link was emailed; the previous link no longer works.');
    }

    public function cancel(Request $r, HiringOnboarding $onboarding)
    {
        $this->own($r, $onboarding, 'edit');
        $onboarding->update(['status' => 'cancelled', 'token_hash' => null]);
        return back()->with('success', 'Onboarding cancelled; the candidate\'s link no longer works.');
    }

    public function review(Request $r, HiringOnboardingItem $item)
    {
        $this->own($r, $item->onboarding, 'edit');
        $d = $r->validate(['decision' => 'required|in:approved,rejected,done,pending', 'note' => 'nullable|string|max:300']);
        if ($d['decision'] === 'rejected' && blank($d['note'] ?? null)) return back()->withErrors(['note' => 'Tell the candidate what to fix.']);
        $this->onboarding->review($item, $d['decision'], $d['note'] ?? null, $r->user());
        $label = ['approved' => 'approved', 'rejected' => 'sent back to the candidate', 'done' => 'marked done', 'pending' => 'reopened'][$d['decision']];
        return back()->with('success', '"' . $item->label . '" ' . $label . '.');
    }

    public function file(Request $r, HiringOnboardingItem $item)
    {
        $this->own($r, $item->onboarding, 'view');
        abort_unless($item->file_path && Storage::disk('local')->exists($item->file_path), 404);
        return Storage::disk('local')->response($item->file_path, $item->file_name ?: basename($item->file_path), ['X-Content-Type-Options' => 'nosniff']);
    }

    public function saveTemplate(Request $r)
    {
        $this->team->authorize($r->user(), 'edit');
        $d = $r->validate(['name' => 'required|string|max:120', 'items' => 'required|array|min:1|max:40', 'items.*.label' => 'required|string|max:150',
            'items.*.kind' => 'required|in:document,task', 'items.*.help' => 'nullable|string|max:300', 'items.*.required' => 'nullable|boolean']);
        $items = collect($d['items'])->map(fn ($i) => ['kind' => $i['kind'], 'label' => $i['label'], 'help' => $i['help'] ?? null, 'required' => (bool) ($i['required'] ?? false)])->values()->all();
        HiringOnboardingTemplate::create(['owner_id' => $this->team->ownerId($r->user()), 'name' => $d['name'], 'items' => $items]);
        return back()->with('success', 'Checklist template saved.');
    }

    public function deleteTemplate(Request $r, HiringOnboardingTemplate $template)
    {
        $this->team->authorize($r->user(), 'edit');
        abort_unless($template->owner_id === $this->team->ownerId($r->user()), 403);
        $template->delete();
        return back()->with('success', 'Checklist template deleted.');
    }

    // The new hire's page: no login, private token; uploads limited per link.
    public function publicShow(string $token)
    {
        $o = $this->onboarding->byToken($token);
        abort_unless($o, 404, 'This onboarding link is no longer active. Please ask your recruiter for a new one.');
        return view('employer.hiring.onboarding-public', ['o' => $o, 'token' => $token]);
    }

    public function publicUpload(Request $r, string $token, HiringOnboardingItem $item)
    {
        $o = $this->onboarding->byToken($token);
        abort_unless($o && $item->onboarding_id === $o->id, 404, 'This onboarding link is no longer active.');
        $key = 'onboarding-upload:' . $o->id;
        abort_if(RateLimiter::tooManyAttempts($key, 60), 429, 'Too many uploads; please try again in an hour.');
        RateLimiter::hit($key, 3600);
        $r->validate(['file' => 'required|file|max:' . Onboarding::MAX_KB . '|mimes:' . implode(',', Onboarding::MIMES)]);
        $this->onboarding->upload($item, $r->file('file'));
        return redirect()->route('hiring.onboarding.public', $token)->with('success', '"' . $item->label . '" uploaded. Your recruiter will review it.');
    }

    private function own(Request $r, ?HiringOnboarding $o, string $ability): void
    {
        abort_unless($o && ($o->owner_id === $this->team->ownerId($r->user()) || $r->user()->role === 'admin'), 403);
        $this->team->authorize($r->user(), $ability);
    }
}
