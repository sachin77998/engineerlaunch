<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Recruiter pipeline lives beside the candidate-facing status, so the candidate tracker keeps working unchanged.
        Schema::table('job_applications', function (Blueprint $table) {
            if (!Schema::hasColumn('job_applications', 'stage')) $table->string('stage', 20)->default('new')->after('status');
            if (!Schema::hasColumn('job_applications', 'stage_changed_at')) $table->timestamp('stage_changed_at')->nullable()->after('stage');
            if (!Schema::hasColumn('job_applications', 'rating')) $table->unsignedTinyInteger('rating')->nullable()->after('stage_changed_at');
            if (!Schema::hasColumn('job_applications', 'tags')) $table->json('tags')->nullable()->after('rating');
            if (!Schema::hasColumn('job_applications', 'rejection_reason')) $table->string('rejection_reason', 120)->nullable()->after('tags');
            if (!Schema::hasColumn('job_applications', 'screening_result')) $table->json('screening_result')->nullable()->after('rejection_reason');
            if (!Schema::hasColumn('job_applications', 'screened_at')) $table->timestamp('screened_at')->nullable()->after('screening_result');
        });
        Schema::table('job_applications', function (Blueprint $table) {
            $table->index(['job_id', 'stage'], 'job_applications_job_stage_index');
            $table->index('applied_at', 'job_applications_applied_at_index');
        });
        DB::table('job_applications')->where('status', 'joined')->update(['stage' => 'hired']);
        DB::table('job_applications')->where('status', 'rejected')->update(['stage' => 'rejected']);
        DB::table('job_applications')->where('status', 'withdrawn')->update(['stage' => 'withdrawn']);
        DB::table('job_applications')->where('status', 'in_progress')->update(['stage' => 'screening']);

        if (!Schema::hasTable('hiring_screening_rules')) Schema::create('hiring_screening_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->unique()->constrained('jobs')->cascadeOnDelete();
            $table->decimal('min_experience', 4, 1)->nullable();
            $table->decimal('max_experience', 4, 1)->nullable();
            $table->unsignedSmallInteger('max_notice_days')->nullable();
            $table->decimal('max_expected_salary', 14, 2)->nullable();
            $table->unsignedTinyInteger('min_skill_match')->nullable();
            $table->json('locations')->nullable();
            $table->boolean('require_verified_phone')->default(false);
            $table->unsignedTinyInteger('auto_shortlist_score')->nullable();
            $table->boolean('auto_reject')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        if (!Schema::hasTable('hiring_notes')) Schema::create('hiring_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_application_id')->constrained('job_applications')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
        });

        if (!Schema::hasTable('hiring_interviews')) Schema::create('hiring_interviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_application_id')->constrained('job_applications')->cascadeOnDelete();
            $table->foreignId('scheduled_by')->constrained('users')->cascadeOnDelete();
            $table->string('round', 80);
            $table->string('mode', 12);
            $table->dateTime('scheduled_at')->index();
            $table->unsignedSmallInteger('duration_minutes')->default(45);
            $table->string('location', 255)->nullable();
            $table->string('interviewer_name', 120)->nullable();
            $table->string('interviewer_email', 190)->nullable();
            $table->string('status', 12)->default('scheduled')->index();
            $table->unsignedTinyInteger('rating')->nullable();
            $table->string('recommendation', 12)->nullable();
            $table->text('feedback')->nullable();
            $table->timestamps();
        });

        if (!Schema::hasTable('hiring_saved_searches')) Schema::create('hiring_saved_searches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name', 80);
            $table->json('filters');
            $table->timestamps();
        });

        if (!Schema::hasTable('candidate_verifications')) Schema::create('candidate_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_application_id')->nullable()->constrained('job_applications')->nullOnDelete();
            $table->foreignId('candidate_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->string('type', 20);
            $table->string('reference', 40)->nullable();
            $table->string('provider', 20);
            $table->string('status', 10)->index();
            $table->string('message', 255);
            $table->json('payload')->nullable();
            $table->timestamp('consent_at')->nullable();
            $table->timestamps();
            $table->index(['candidate_id', 'type']);
        });

        if (!Schema::hasTable('hiring_cost_settings')) Schema::create('hiring_cost_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->unsignedSmallInteger('recruiters')->default(1);
            $table->decimal('monthly_cost_per_recruiter', 12, 2)->default(0);
            $table->decimal('monthly_tools_spend', 12, 2)->default(0);
            $table->decimal('monthly_job_board_spend', 12, 2)->default(0);
            $table->decimal('agency_fee_per_hire', 12, 2)->default(0);
            $table->unsignedSmallInteger('minutes_per_manual_screen')->default(6);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['hiring_cost_settings', 'candidate_verifications', 'hiring_saved_searches', 'hiring_interviews', 'hiring_notes', 'hiring_screening_rules'] as $t) Schema::dropIfExists($t);
        Schema::table('job_applications', function (Blueprint $table) {
            $table->dropIndex('job_applications_job_stage_index');
            $table->dropIndex('job_applications_applied_at_index');
            $table->dropColumn(['stage', 'stage_changed_at', 'rating', 'tags', 'rejection_reason', 'screening_result', 'screened_at']);
        });
    }
};
