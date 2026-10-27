<?php

namespace App\Services\Hiring;

use App\Models\HiringOnboarding;
use App\Models\HiringOnboardingItem;
use App\Models\HiringOnboardingTemplate;
use App\Models\JobApplication;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

// Online joining: a checklist per new hire built from a template, a private link (emailed, valid 30 days) where the hire
// uploads documents, team review of each document, and completion (the application moves to Hired) once every required
// item is approved or done.
class Onboarding
{
    public const DEFAULT_ITEMS = [
        ['kind' => 'document', 'label' => 'PAN card', 'help' => 'Clear scan or photo of both sides', 'required' => true],
        ['kind' => 'document', 'label' => 'Aadhaar card (masked)', 'help' => 'Mask the first 8 digits; download a masked copy from UIDAI', 'required' => true],
        ['kind' => 'document', 'label' => 'Passport-size photograph', 'help' => 'Plain background', 'required' => true],
        ['kind' => 'document', 'label' => 'Highest qualification certificate', 'help' => 'Degree or diploma certificate', 'required' => true],
        ['kind' => 'document', 'label' => 'Relieving / experience letter', 'help' => 'From your last employer (freshers may skip)', 'required' => false],
        ['kind' => 'document', 'label' => 'Last 3 months\' salary slips', 'help' => 'One PDF is fine (freshers may skip)', 'required' => false],
        ['kind' => 'document', 'label' => 'Cancelled cheque or bank passbook page', 'help' => 'For salary credit', 'required' => true],
        ['kind' => 'document', 'label' => 'Signed offer letter', 'help' => 'Every page signed', 'required' => true],
        ['kind' => 'task', 'label' => 'Create employee ID and work email', 'help' => null, 'required' => true],
        ['kind' => 'task', 'label' => 'Issue laptop and access cards', 'help' => null, 'required' => true],
        ['kind' => 'task', 'label' => 'Schedule induction on day one', 'help' => null, 'required' => true],
    ];
    public const MIMES = ['pdf', 'jpg', 'jpeg', 'png'];
    public const MAX_KB = 5120;

    public function __construct(private Messenger $messenger, private Pipeline $pipeline, private Team $team) {}

    /** The owner's templates, with the built-in checklist first. */
    public function templates(User $user): array
    {
        $own = HiringOnboardingTemplate::where('owner_id', $this->team->ownerId($user))->orderBy('name')->get();
        return collect([['id' => null, 'name' => 'Standard joining checklist', 'items' => self::DEFAULT_ITEMS]])
            ->concat($own->map(fn ($t) => ['id' => $t->id, 'name' => $t->name, 'items' => $t->items]))->all();
    }

    /** Starts (or returns the running) onboarding for a hire and emails the private link. */
    public function start(JobApplication $a, User $by, ?int $templateId = null, ?string $dueOn = null): HiringOnboarding
    {
        $ownerId = $this->ownerFor($a);
        $existing = HiringOnboarding::where('job_application_id', $a->id)->first();
        if ($existing && $existing->status !== 'cancelled') return $existing;
        $template = $templateId ? HiringOnboardingTemplate::where('owner_id', $ownerId)->find($templateId) : null;
        $items = $template?->items ?: self::DEFAULT_ITEMS;
        $onboarding = DB::transaction(function () use ($a, $by, $ownerId, $items, $dueOn, $existing) {
            $existing?->delete();
            $o = HiringOnboarding::create(['owner_id' => $ownerId, 'job_application_id' => $a->id, 'started_by' => $by->id, 'status' => 'in_progress',
                'due_on' => $dueOn ?: optional($a->offers()->whereIn('status', ['accepted', 'sent'])->latest()->first())->joining_date]);
            foreach (array_values($items) as $i => $item) {
                $o->items()->create(['position' => $i, 'kind' => ($item['kind'] ?? 'document') === 'task' ? 'task' : 'document', 'label' => mb_substr((string) $item['label'], 0, 150),
                    'help' => isset($item['help']) ? mb_substr((string) $item['help'], 0, 300) : null, 'required' => (bool) ($item['required'] ?? true)]);
            }
            return $o;
        });
        $this->sendLink($onboarding, $by);
        return $onboarding;
    }

    /** New private link (the previous one stops working) emailed to the hire. */
    public function sendLink(HiringOnboarding $o, User $by): void
    {
        $token = Str::random(48);
        $o->forceFill(['token_hash' => hash('sha256', $token), 'token_expires_at' => now()->addDays(30)])->save();
        $a = $o->application()->with(['candidate', 'job.company'])->first();
        $docs = $o->items()->where('kind', 'document')->pluck('label')->map(fn ($l) => '• ' . $l)->implode("\n");
        $this->messenger->send($a, $by, 'Your joining documents for {job_title} at {company}',
            "Hi {first_name},\n\nWelcome aboard! Please upload your joining documents here (the link works for 30 days, no login needed):\n"
            . route('hiring.onboarding.public', $token) . "\n\nDocuments we need:\n{$docs}\n\nPDF, JPG or PNG, up to 5 MB each.\n\nRegards,\n{recruiter_name}");
    }

    public function byToken(string $token): ?HiringOnboarding
    {
        $o = HiringOnboarding::with(['items', 'application.candidate', 'application.job.company'])->where('token_hash', hash('sha256', $token))->first();
        return $o && $o->status === 'in_progress' && $o->token_expires_at?->isFuture() ? $o : null;
    }

    public function upload(HiringOnboardingItem $item, UploadedFile $file): void
    {
        abort_unless($item->kind === 'document' && in_array($item->status, ['pending', 'rejected', 'submitted'], true), 422, 'This document is already approved.');
        $ext = strtolower($file->getClientOriginalExtension());
        abort_unless(in_array($ext, self::MIMES, true) && in_array($file->getMimeType(), ['application/pdf', 'image/jpeg', 'image/png'], true), 422, 'Upload a PDF, JPG or PNG file.');
        if ($item->file_path) Storage::disk('local')->delete($item->file_path);
        $path = $file->storeAs('hiring/onboarding/' . $item->onboarding_id, $item->id . '-' . Str::random(16) . '.' . $ext, 'local');
        $item->forceFill(['file_path' => $path, 'file_name' => mb_substr(Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $ext, 0, 190),
            'file_size' => $file->getSize(), 'status' => 'submitted', 'submitted_at' => now(), 'review_note' => null])->save();
    }

    public function review(HiringOnboardingItem $item, string $decision, ?string $note, User $by): void
    {
        abort_unless(in_array($decision, ['approved', 'rejected', 'done', 'pending'], true), 422);
        abort_if($item->kind === 'document' && $decision === 'done', 422);
        abort_if($item->kind === 'task' && in_array($decision, ['approved', 'rejected'], true), 422);
        abort_if($decision === 'approved' && !$item->file_path, 422, 'Nothing uploaded yet.');
        $item->forceFill(['status' => $decision, 'reviewed_by' => $by->id, 'reviewed_at' => now(), 'review_note' => $note ? mb_substr($note, 0, 300) : null])->save();
        $o = $item->onboarding()->with(['items', 'application.candidate'])->first();
        if ($decision === 'rejected') {
            $this->messenger->send($o->application, $by, 'Please re-upload: ' . $item->label, "Hi {first_name},\n\nWe could not accept your \"{$item->label}\"" . ($note ? ": {$note}" : '.')
                . "\n\nPlease upload it again using the link in our earlier email." . "\n\nRegards,\n{recruiter_name}");
        }
        $this->checkComplete($o, $by);
    }

    /** Every required item approved or done: onboarding completes and the application moves to Hired. */
    public function checkComplete(HiringOnboarding $o, User $by): void
    {
        $o->loadMissing('items');
        if ($o->status !== 'in_progress' || $o->progress < 100) return;
        $o->forceFill(['status' => 'completed', 'completed_at' => now(), 'token_hash' => null])->save();
        $a = $o->application;
        if ($a && !in_array($a->stage, ['hired', 'rejected', 'withdrawn'], true)) $this->pipeline->move($a, 'hired', $by->id, 'Onboarding checklist completed');
        if ($a) rescue(fn () => app(Workflows::class)->fire('onboarding.completed', $a), null);
    }

    private function ownerFor(JobApplication $a): int
    {
        $a->loadMissing('job');
        $employer = User::find($a->job?->employer_id);
        return $employer ? $this->team->ownerId($employer) : (int) $a->job?->employer_id;
    }
}
