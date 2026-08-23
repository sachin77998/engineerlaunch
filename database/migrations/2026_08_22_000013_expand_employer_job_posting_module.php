<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->string('category')->nullable()->after('title')->index();
            $table->string('role')->nullable()->after('category')->index();
            $table->string('job_level')->nullable()->after('work_mode')->index();
            $table->string('state')->nullable()->after('country');
            $table->boolean('relocation_allowed')->default(false);
            $table->string('hiring_urgency')->nullable();
            $table->string('specialization')->nullable();
            $table->string('primary_technology')->nullable()->index();
            $table->string('salary_type')->default('undisclosed');
            $table->string('salary_period')->default('year');
            $table->json('additional_compensation')->nullable();
            $table->string('application_method')->default('portal');
            $table->string('application_email')->nullable();
            $table->date('application_deadline')->nullable()->index();
            $table->string('job_visibility')->default('public')->index();
            $table->boolean('resume_required')->default(true);
            $table->boolean('cover_letter_required')->default(false);
            $table->boolean('portfolio_required')->default(false);
            $table->boolean('github_required')->default(false);
            $table->boolean('linkedin_required')->default(false);
        });

        Schema::create('skills', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('job_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->string('importance', 20)->default('required');
            $table->unique(['job_id', 'skill_id', 'importance']);
        });

        Schema::create('job_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained()->cascadeOnDelete();
            $table->string('country')->index();
            $table->string('state')->nullable()->index();
            $table->string('city')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('job_benefits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unique(['job_id', 'name']);
        });

        Schema::create('job_screening_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained()->cascadeOnDelete();
            $table->text('question');
            $table->string('type', 30)->default('text');
            $table->json('options')->nullable();
            $table->boolean('is_required')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('application_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('job_screening_questions')->cascadeOnDelete();
            $table->text('answer')->nullable();
            $table->unique(['job_application_id', 'question_id']);
        });

        Schema::create('saved_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('job_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'job_id']);
        });

        Schema::create('job_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained()->cascadeOnDelete();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_status_history');
        Schema::dropIfExists('saved_jobs');
        Schema::dropIfExists('application_answers');
        Schema::dropIfExists('job_screening_questions');
        Schema::dropIfExists('job_benefits');
        Schema::dropIfExists('job_locations');
        Schema::dropIfExists('job_skills');
        Schema::dropIfExists('skills');
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropColumn(['category','role','job_level','state','relocation_allowed','hiring_urgency','specialization','primary_technology','salary_type','salary_period','additional_compensation','application_method','application_email','application_deadline','job_visibility','resume_required','cover_letter_required','portfolio_required','github_required','linkedin_required']);
        });
    }
};
