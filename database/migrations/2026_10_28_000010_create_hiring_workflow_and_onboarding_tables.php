<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Hiring automation: "when this happens, do these steps" rules per employer team, a log of every automated run (with the
// minutes of manual work it replaced), and online onboarding — a checklist of documents the new hire uploads through a
// private link and tasks the team ticks off.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hiring_workflows', function (Blueprint $t) {
            $t->id();
            $t->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->string('name', 120);
            $t->string('trigger', 40);
            $t->foreignId('job_id')->nullable()->constrained('jobs')->cascadeOnDelete();
            $t->json('steps');
            $t->boolean('is_active')->default(true);
            $t->unsignedInteger('runs_count')->default(0);
            $t->timestamp('last_run_at')->nullable();
            $t->timestamps();
            $t->index(['owner_id', 'trigger', 'is_active']);
        });

        Schema::create('hiring_workflow_runs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('workflow_id')->constrained('hiring_workflows')->cascadeOnDelete();
            $t->foreignId('job_application_id')->constrained('job_applications')->cascadeOnDelete();
            $t->string('trigger', 40);
            $t->string('status', 12);
            $t->json('log');
            $t->unsignedInteger('minutes_saved')->default(0);
            $t->timestamp('created_at')->nullable();
            $t->index(['workflow_id', 'created_at']);
        });

        Schema::create('hiring_onboarding_templates', function (Blueprint $t) {
            $t->id();
            $t->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $t->string('name', 120);
            $t->json('items');
            $t->timestamps();
        });

        Schema::create('hiring_onboardings', function (Blueprint $t) {
            $t->id();
            $t->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('job_application_id')->unique()->constrained('job_applications')->cascadeOnDelete();
            $t->foreignId('started_by')->nullable()->constrained('users')->nullOnDelete();
            $t->string('status', 16)->default('in_progress');
            $t->string('token_hash', 64)->nullable()->unique();
            $t->timestamp('token_expires_at')->nullable();
            $t->date('due_on')->nullable();
            $t->timestamp('completed_at')->nullable();
            $t->timestamps();
            $t->index(['owner_id', 'status']);
        });

        Schema::create('hiring_onboarding_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('onboarding_id')->constrained('hiring_onboardings')->cascadeOnDelete();
            $t->unsignedSmallInteger('position')->default(0);
            $t->string('kind', 10);
            $t->string('label', 150);
            $t->string('help', 300)->nullable();
            $t->boolean('required')->default(true);
            $t->string('status', 12)->default('pending');
            $t->string('file_path')->nullable();
            $t->string('file_name', 190)->nullable();
            $t->unsignedInteger('file_size')->nullable();
            $t->timestamp('submitted_at')->nullable();
            $t->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('reviewed_at')->nullable();
            $t->string('review_note', 300)->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hiring_onboarding_items');
        Schema::dropIfExists('hiring_onboardings');
        Schema::dropIfExists('hiring_onboarding_templates');
        Schema::dropIfExists('hiring_workflow_runs');
        Schema::dropIfExists('hiring_workflows');
    }
};
