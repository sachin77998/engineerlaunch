<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['industrial_sectors', 'industrial_subsectors', 'industrial_processes'] as $name) {
            Schema::create($name, function (Blueprint $t) use ($name) {
                $t->id();
                $t->string('name');
                $t->string('slug')->unique();
                $t->text('description')->nullable();
                $t->boolean('is_active')->default(true);
                $t->timestamps();
                if ($name === 'industrial_sectors') {
                    $t->string('family')->nullable();
                    $t->string('hero_image')->nullable();
                }
                if ($name === 'industrial_subsectors') {
                    $t->foreignId('sector_id')->constrained('industrial_sectors')->cascadeOnDelete();
                    $t->foreignId('parent_id')->nullable()->constrained('industrial_subsectors')->cascadeOnDelete();
                }
            });
        }
        Schema::create('industrial_job_role_aliases', function (Blueprint $t) {
            $t->id();
            $t->foreignId('job_role_id')->constrained('industrial_job_roles')->cascadeOnDelete();
            $t->string('name');
            $t->string('slug')->unique();
            $t->timestamps();
        });
        Schema::create('industrial_assets', function (Blueprint $t) {
            $t->id();
            $t->foreignId('sector_id')->constrained('industrial_sectors')->cascadeOnDelete();
            $t->string('path')->unique();
            $t->string('alt');
            $t->string('source_name')->nullable();
            $t->unsignedInteger('sort_order')->default(0);
            $t->timestamps();
        });
        $pivots = [
            'industrial_area_sectors' => ['industrial_area_id' => 'industrial_areas', 'sector_id' => 'industrial_sectors'],
            'industrial_company_sectors' => ['industrial_company_id' => 'industrial_companies', 'sector_id' => 'industrial_sectors'],
            'industrial_company_processes' => ['industrial_company_id' => 'industrial_companies', 'process_id' => 'industrial_processes'],
            'industrial_subsector_processes' => ['subsector_id' => 'industrial_subsectors', 'process_id' => 'industrial_processes'],
            'industrial_company_subsectors' => ['industrial_company_id' => 'industrial_companies', 'subsector_id' => 'industrial_subsectors'],
            'industrial_process_job_roles' => ['process_id' => 'industrial_processes', 'job_role_id' => 'industrial_job_roles'],
        ];
        foreach ($pivots as $name => $keys) {
            Schema::create($name, function (Blueprint $t) use ($name, $keys) {
                foreach ($keys as $key => $target) {
                    $t->foreignId($key)->constrained($target)->cascadeOnDelete();
                }
                $t->unsignedInteger('sort_order')->default(0);
                $t->unique(array_keys($keys), substr($name, 0, 45) . '_unique');
            });
        }
    }

    public function down(): void
    {
        foreach (['industrial_process_job_roles', 'industrial_company_subsectors', 'industrial_subsector_processes', 'industrial_company_processes', 'industrial_company_sectors', 'industrial_area_sectors', 'industrial_assets', 'industrial_job_role_aliases', 'industrial_subsectors', 'industrial_processes', 'industrial_sectors'] as $name) {
            Schema::dropIfExists($name);
        }
    }
};
