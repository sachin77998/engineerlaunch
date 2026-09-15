<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('industrial_role_profiles', function (Blueprint $t) {
            $t->id();
            $t->foreignId('job_role_id')->unique()->constrained('industrial_job_roles')->cascadeOnDelete();
            $t->json('skills')->nullable();
            $t->json('qualifications')->nullable();
            $t->text('summary')->nullable();
            $t->string('source_name')->nullable();
            $t->timestamps();
        });
        Schema::create('industrial_role_sectors', function (Blueprint $t) {
            $t->foreignId('job_role_id')->constrained('industrial_job_roles')->cascadeOnDelete();
            $t->foreignId('sector_id')->constrained('industrial_sectors')->cascadeOnDelete();
            $t->unique(['job_role_id', 'sector_id']);
        });
        Schema::create('industrial_related_roles', function (Blueprint $t) {
            $t->foreignId('job_role_id')->constrained('industrial_job_roles')->cascadeOnDelete();
            $t->foreignId('related_role_id')->constrained('industrial_job_roles')->cascadeOnDelete();
            $t->string('reason');
            $t->unsignedSmallInteger('weight')->default(70);
            $t->unique(['job_role_id', 'related_role_id']);
        });
        Schema::create('industrial_company_sources', function (Blueprint $t) {
            $t->id();
            $t->foreignId('industrial_company_id')->constrained('industrial_companies')->cascadeOnDelete();
            $t->string('source_key');
            $t->string('title');
            $t->text('url');
            $t->string('source_period')->nullable();
            $t->text('evidence_note');
            $t->timestamp('checked_at');
            $t->timestamps();
            $t->unique(['industrial_company_id', 'source_key'], 'industrial_company_source_unique');
        });
        Schema::table('industrial_areas', function (Blueprint $t) {
            $t->string('hero_image')->nullable();
            $t->string('tagline')->nullable();
        });
    }
    public function down(): void
    {
        \App\Support\IndustrialSchema::dropColumns('industrial_areas', ['hero_image', 'tagline']);
        foreach (['industrial_company_sources', 'industrial_related_roles', 'industrial_role_sectors', 'industrial_role_profiles'] as $name) Schema::dropIfExists($name);
    }
};
