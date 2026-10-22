<?php

namespace App\Services\Hiring;

use App\Jobs\SendHiringMessage;
use App\Models\HiringMessage;
use App\Models\JobApplication;
use App\Models\User;

// Personalises recruiter emails with placeholders and queues them; every email is logged on the candidate.
class Messenger
{
    public const PLACEHOLDERS = ['{candidate_name}', '{first_name}', '{job_title}', '{company}', '{recruiter_name}', '{stage}'];

    public function render(string $text, JobApplication $a, User $sender): string
    {
        $a->loadMissing(['candidate', 'job.company']);
        $name = (string) $a->candidate?->name;
        return strtr($text, [
            '{candidate_name}' => $name, '{first_name}' => strtok($name, ' ') ?: $name, '{job_title}' => (string) $a->job?->title,
            '{company}' => (string) ($a->job?->company?->name ?? ''), '{recruiter_name}' => $sender->name, '{stage}' => JobApplication::STAGES[$a->stage] ?? (string) $a->stage,
        ]);
    }

    public function send(JobApplication $a, User $sender, string $subject, string $body, array $files = []): ?HiringMessage
    {
        $to = $a->candidate?->email;
        if (!$to) return null;
        $m = HiringMessage::create(['job_application_id' => $a->id, 'sent_by' => $sender->id, 'to_email' => $to,
            'subject' => mb_substr($this->render($subject, $a, $sender), 0, 190), 'body' => $this->render($body, $a, $sender), 'status' => 'queued']);
        SendHiringMessage::dispatch($m->id, $files)->onQueue('emails');
        return $m;
    }
}
