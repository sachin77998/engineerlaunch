<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Covering index for "open jobs per company" (homepage and sector cards): the count is answered from the index alone.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->index(['is_active', 'status', 'job_visibility', 'company_id', 'expires_at', 'deleted_at'], 'jobs_open_per_company_index');
        });
    }

    public function down(): void
    {
        Schema::table('jobs', fn (Blueprint $table) => $table->dropIndex('jobs_open_per_company_index'));
    }
};
