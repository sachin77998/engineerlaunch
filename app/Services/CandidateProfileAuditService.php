<?php

namespace App\Services;

use App\Models\CandidateProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CandidateProfileAuditService
{
    public function record(CandidateProfile $profile, int $userId, array $oldData, array $newData): string
    {
        $changeId = (string) Str::uuid();
        $common = [
            'change_id' => $changeId,
            'candidate_profile_id' => $profile->id,
            'changed_by_user_id' => $userId,
            'created_at' => now(),
        ];

        DB::table('candidate_profile_old_data')->insert($common + [
            'profile_data' => json_encode($oldData, JSON_THROW_ON_ERROR),
        ]);
        DB::table('candidate_profile_new_data')->insert($common + [
            'profile_data' => json_encode($newData, JSON_THROW_ON_ERROR),
        ]);

        return $changeId;
    }
}
