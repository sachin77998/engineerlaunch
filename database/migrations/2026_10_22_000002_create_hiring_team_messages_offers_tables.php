<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A hiring team belongs to the employer who owns it; members join only through an emailed invite.
        if (!Schema::hasTable('hiring_team_members')) Schema::create('hiring_team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('member_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->foreignId('invited_by')->constrained('users')->cascadeOnDelete();
            $table->string('email', 190);
            $table->string('role', 12);
            $table->string('status', 10)->default('pending')->index();
            $table->string('token_hash', 64)->nullable()->unique();
            $table->timestamp('invite_expires_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
            $table->unique(['owner_id', 'email']);
            $table->index('member_id');
        });

        if (!Schema::hasTable('hiring_email_templates')) Schema::create('hiring_email_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('name', 80);
            $table->string('subject', 190);
            $table->text('body');
            $table->timestamps();
        });

        if (!Schema::hasTable('hiring_messages')) Schema::create('hiring_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_application_id')->constrained('job_applications')->cascadeOnDelete();
            $table->foreignId('sent_by')->constrained('users')->cascadeOnDelete();
            $table->string('to_email', 190);
            $table->string('subject', 190);
            $table->text('body');
            $table->string('status', 10)->default('queued')->index();
            $table->string('error', 255)->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });

        if (!Schema::hasTable('hiring_offers')) Schema::create('hiring_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_application_id')->constrained('job_applications')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('designation', 150);
            $table->decimal('annual_ctc', 14, 2);
            $table->string('currency', 3)->default('INR');
            $table->decimal('fixed_pay', 14, 2)->nullable();
            $table->decimal('variable_pay', 14, 2)->nullable();
            $table->decimal('joining_bonus', 14, 2)->nullable();
            $table->date('joining_date');
            $table->date('expires_on');
            $table->string('work_location', 190)->nullable();
            $table->unsignedSmallInteger('probation_months')->nullable();
            $table->text('terms')->nullable();
            $table->string('signatory_name', 120);
            $table->string('signatory_title', 120)->nullable();
            $table->string('status', 10)->default('draft')->index();
            $table->string('token_hash', 64)->nullable()->unique();
            $table->string('pdf_path', 255)->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->string('response_note', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['hiring_offers', 'hiring_messages', 'hiring_email_templates', 'hiring_team_members'] as $t) Schema::dropIfExists($t);
    }
};
