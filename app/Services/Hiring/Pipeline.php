<?php

namespace App\Services\Hiring;

use App\Models\JobApplication;
use Illuminate\Support\Facades\DB;

class Pipeline
{
    // Moves an application to a recruiter stage, keeps the candidate-facing status in step and logs the change.
    public function move(JobApplication $application, string $stage, ?int $userId, ?string $note = null, ?string $reason = null): void
    {
        abort_unless(isset(JobApplication::STAGES[$stage]), 422, 'Unknown stage.');
        if (($application->stage ?? 'new') === $stage && !$reason) return;
        $from = $application->status;
        $status = JobApplication::STAGE_STATUS[$stage];
        // A brand-new application keeps "applied" until a recruiter has actually looked at it.
        if ($stage === 'new' && $from === 'applied') $status = 'applied';
        $application->forceFill(['stage' => $stage, 'status' => $status, 'stage_changed_at' => now(), 'rejection_reason' => $stage === 'rejected' ? ($reason ?? $application->rejection_reason) : null])->saveQuietly();
        DB::table('application_status_history')->insert(['job_application_id' => $application->id, 'changed_by' => $userId, 'from_status' => $from, 'to_status' => $status,
            'note' => mb_substr('Stage: ' . JobApplication::STAGES[$stage] . ($note ? ' — ' . $note : ''), 0, 1000), 'created_at' => now()]);
        if ($stage !== 'new') rescue(fn () => app(Workflows::class)->fire('stage.' . $stage, $application), null);
    }
}
