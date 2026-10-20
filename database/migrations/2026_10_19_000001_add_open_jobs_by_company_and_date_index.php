<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Latest open jobs of a set of companies (sector pages) and open jobs per location (industrial areas) without reading whole job rows:
 * every filter column plus posted_at is in the index, so MySQL answers from the index alone.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->index(['is_active', 'status', 'job_visibility', 'company_id', 'posted_at', 'expires_at', 'deleted_at'], 'jobs_open_company_posted_index');
            // Open jobs per location (industrial area cards) and companies per location (company filters).
            $table->index(['is_active', 'status', 'job_visibility', 'location', 'company_id', 'expires_at', 'deleted_at'], 'jobs_open_location_index');
        });
        // Company pages list subsidiaries by description = 'Part of <parent>.'; description is TEXT, so a prefix index.
        Schema::table('companies', function (Blueprint $table) {
            $table->rawIndex('description(120)', 'companies_description_prefix_index');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropIndex('companies_description_prefix_index');
        });
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropIndex('jobs_open_company_posted_index');
            $table->dropIndex('jobs_open_location_index');
        });
    }
};
