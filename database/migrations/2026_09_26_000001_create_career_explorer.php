<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('career_tracks')) Schema::create('career_tracks', function (Blueprint $t) {
            $t->id(); $t->string('slug')->unique(); $t->string('sector'); $t->string('department'); $t->string('name'); $t->timestamps();
        });
        if (!Schema::hasTable('career_roles')) Schema::create('career_roles', function (Blueprint $t) {
            $t->id(); $t->foreignId('career_track_id')->constrained()->cascadeOnDelete(); $t->string('slug')->unique();
            $t->string('name'); $t->string('job_family'); $t->string('career_level'); $t->json('skills')->nullable();
            $t->json('next_roles')->nullable(); $t->unsignedInteger('sort_order')->default(0); $t->timestamps();
        });
        if (!Schema::hasTable('career_salary_benchmarks')) Schema::create('career_salary_benchmarks', function (Blueprint $t) {
            $t->id(); $t->string('sector'); $t->string('role_name'); $t->string('experience_label');
            $t->string('salary_label'); $t->string('currency',3)->default('INR'); $t->string('period')->default('annual');
            $t->string('source_name'); $t->string('verification_status')->default('user_reference'); $t->date('as_of'); $t->timestamps();
            $t->unique(['sector','role_name','source_name'], 'career_salary_reference_unique');
        });
        if (!Schema::hasTable('company_facilities')) Schema::create('company_facilities', function (Blueprint $t) {
            $t->id(); $t->foreignId('company_id')->constrained()->cascadeOnDelete(); $t->string('slug')->unique();
            $t->string('name'); $t->string('facility_type')->nullable(); $t->string('country')->default('India');
            $t->string('state')->nullable(); $t->string('city')->nullable(); $t->string('industrial_area')->nullable();
            $t->string('industry'); $t->text('products')->nullable(); $t->unsignedInteger('employee_min')->nullable();
            $t->unsignedInteger('employee_max')->nullable(); $t->string('employee_scope')->nullable();
            $t->text('source_url'); $t->date('verified_on'); $t->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('company_facilities'); Schema::dropIfExists('career_salary_benchmarks');
        Schema::dropIfExists('career_roles'); Schema::dropIfExists('career_tracks');
    }
};
