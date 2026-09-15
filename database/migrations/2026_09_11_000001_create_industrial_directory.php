<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('industrial_states', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('code', 10)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
        Schema::create('industrial_areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('state_id')->constrained('industrial_states')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('district')->nullable();
            $table->string('city')->nullable();
            $table->string('pincode', 10)->nullable();
            $table->string('area_type')->nullable();
            $table->json('sectors')->nullable();
            $table->text('description')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('source_name')->nullable();
            $table->text('source_url')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->unique(['state_id', 'slug']);
            $table->index(['state_id', 'district', 'city'], 'industrial_areas_location_index');
        });
        Schema::create('industrial_companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('industrial_area_id')->constrained('industrial_areas')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('industry')->nullable();
            $table->string('sector')->nullable();
            $table->string('plant_name')->nullable();
            $table->string('facility_type')->nullable();
            $table->string('website')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['industrial_area_id', 'slug'], 'industrial_companies_area_slug_unique');
            $table->index(['industrial_area_id', 'is_active'], 'industrial_companies_active_index');
        });
        Schema::create('industrial_departments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('category')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
        Schema::create('industrial_job_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained('industrial_departments')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
        Schema::create('industrial_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('industrial_area_id')->constrained('industrial_areas')->cascadeOnDelete();
            $table->foreignId('industrial_company_id')->nullable()->constrained('industrial_companies')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('industrial_departments')->nullOnDelete();
            $table->foreignId('job_role_id')->nullable()->constrained('industrial_job_roles')->nullOnDelete();
            $table->string('external_id')->unique();
            $table->string('job_title');
            $table->string('employment_type')->nullable();
            $table->decimal('experience_min', 4, 1)->nullable();
            $table->decimal('experience_max', 4, 1)->nullable();
            $table->decimal('salary_min', 12, 2)->nullable();
            $table->decimal('salary_max', 12, 2)->nullable();
            $table->string('salary_period')->default('monthly');
            $table->string('qualification')->nullable();
            $table->json('skills')->nullable();
            $table->text('description')->nullable();
            $table->text('source_url')->nullable();
            $table->timestamp('application_deadline')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['industrial_area_id', 'industrial_company_id'], 'industrial_jobs_location_index');
            $table->index(['department_id', 'is_active'], 'industrial_jobs_department_index');
            $table->index(['is_active', 'application_deadline'], 'industrial_jobs_live_index');
        });

        foreach (['industrial_states', 'industrial_areas', 'industrial_companies', 'industrial_departments', 'industrial_job_roles', 'industrial_jobs'] as $name) {
            Schema::table($name, function (Blueprint $table) use ($name) {
                if ($name !== 'industrial_areas') {
                    $table->string('source_name')->nullable();
                }
                if (! in_array($name, ['industrial_areas', 'industrial_jobs'], true)) {
                    $table->text('source_url')->nullable();
                }
                $table->timestamp('last_verified_at')->nullable();
                $table->string('verification_status')->default('unverified')->index();
            });
        }
    }

    public function down(): void
    {
        foreach (['industrial_jobs', 'industrial_job_roles', 'industrial_departments', 'industrial_companies', 'industrial_areas', 'industrial_states'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
