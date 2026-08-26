<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $map = [
            'new'=>'applied', 'verified'=>'viewed_by_recruiter', 'processed'=>'in_progress',
            'in_process'=>'in_progress', 'approved'=>'in_progress', 'job_offer_received'=>'in_progress',
            'no_offer'=>'rejected', 'under_review'=>'viewed_by_recruiter', 'shortlisted'=>'in_progress',
            'interview_scheduled'=>'in_progress', 'selected'=>'in_progress', 'offer_sent'=>'in_progress', 'hired'=>'joined',
        ];
        foreach ($map as $from => $to) DB::table('job_applications')->where('status',$from)->update(['status'=>$to]);
    }

    public function down(): void {}
};
