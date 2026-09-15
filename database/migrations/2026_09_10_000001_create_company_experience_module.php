<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('interview_companies', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('slug')->unique(); $table->string('logo')->nullable();
            $table->string('tier', 20)->default('tier_2'); $table->string('industry')->nullable(); $table->string('country')->default('India');
            $table->text('short_description')->nullable(); $table->boolean('is_featured')->default(false); $table->boolean('is_active')->default(true);
            $table->unsignedInteger('experience_count')->default(0); $table->timestamps();
            $table->index(['tier','is_active']); $table->index(['industry','is_active']);
        });
        Schema::create('interview_roles', function (Blueprint $table) {
            $table->id(); $table->foreignId('company_id')->constrained('interview_companies')->cascadeOnDelete();
            $table->string('role_name'); $table->string('experience_level')->nullable(); $table->string('department')->nullable();
            $table->boolean('is_active')->default(true); $table->timestamps(); $table->index(['company_id','role_name']);
        });
        Schema::create('interview_experiences', function (Blueprint $table) {
            $table->id(); $table->foreignId('company_id')->constrained('interview_companies')->cascadeOnDelete();
            $table->foreignId('role_id')->nullable()->constrained('interview_roles')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('candidate_name')->nullable(); $table->string('experience_level')->nullable(); $table->string('location')->nullable();
            $table->date('interview_date')->nullable(); $table->string('application_source')->nullable(); $table->string('result')->nullable();
            $table->string('difficulty')->nullable(); $table->unsignedInteger('total_rounds')->default(0); $table->unsignedInteger('duration_days')->nullable();
            $table->text('overall_experience')->nullable(); $table->text('preparation_advice')->nullable();
            $table->string('moderation_status',20)->default('pending'); $table->foreignId('moderated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('moderated_at')->nullable(); $table->boolean('is_verified')->default(false); $table->boolean('is_published')->default(false); $table->timestamps();
            $table->index(['company_id','role_id','is_published']); $table->index(['moderation_status','created_at']);
        });
        Schema::create('interview_rounds', function (Blueprint $table) {
            $table->id(); $table->foreignId('experience_id')->constrained('interview_experiences')->cascadeOnDelete();
            $table->unsignedInteger('round_number'); $table->string('round_name'); $table->string('round_type');
            $table->unsignedInteger('duration_minutes')->nullable(); $table->string('difficulty')->nullable(); $table->text('description')->nullable();
            $table->json('topics')->nullable(); $table->json('questions')->nullable(); $table->text('candidate_experience')->nullable();
            $table->boolean('is_elimination_round')->default(false); $table->timestamps(); $table->index(['experience_id','round_number']);
        });
        Schema::create('interview_packages', function (Blueprint $table) {
            $table->id(); $table->foreignId('company_id')->constrained('interview_companies')->cascadeOnDelete();
            $table->foreignId('role_id')->nullable()->constrained('interview_roles')->nullOnDelete();
            $table->foreignId('experience_id')->nullable()->constrained('interview_experiences')->nullOnDelete();
            $table->string('location')->nullable(); $table->decimal('experience_years',4,1)->nullable();
            $table->decimal('base_salary',12,2)->nullable(); $table->decimal('bonus',12,2)->nullable(); $table->decimal('stock_value',12,2)->nullable();
            $table->decimal('total_compensation',12,2)->nullable(); $table->string('currency',3)->default('INR'); $table->string('source_type')->nullable();
            $table->text('notes')->nullable(); $table->boolean('is_verified')->default(false); $table->timestamps(); $table->index(['company_id','role_id','location']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('interview_packages'); Schema::dropIfExists('interview_rounds'); Schema::dropIfExists('interview_experiences');
        Schema::dropIfExists('interview_roles'); Schema::dropIfExists('interview_companies');
    }
};
