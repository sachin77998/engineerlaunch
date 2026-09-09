<?php

namespace Tests\Feature;

use App\Models\CandidateProfile;
use App\Models\User;
use App\Services\CandidateProfileAuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CandidateProfileAuditServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_records_linked_old_and_new_profile_snapshots(): void
    {
        $user = User::factory()->create();
        $profile = CandidateProfile::create([
            'user_id' => $user->id,
            'first_name' => 'Old',
            'headline' => 'Junior Developer',
        ]);

        $oldData = $profile->toArray();
        $profile->update(['first_name' => 'New', 'headline' => 'Software Developer']);
        $changeId = app(CandidateProfileAuditService::class)
            ->record($profile, $user->id, $oldData, $profile->fresh()->toArray());

        $old = DB::table('candidate_profile_old_data')->where('change_id', $changeId)->first();
        $new = DB::table('candidate_profile_new_data')->where('change_id', $changeId)->first();

        $this->assertNotNull($old);
        $this->assertNotNull($new);
        $this->assertSame('Old', json_decode($old->profile_data, true)['first_name']);
        $this->assertSame('New', json_decode($new->profile_data, true)['first_name']);
        $this->assertSame($old->candidate_profile_id, $new->candidate_profile_id);
    }
}
