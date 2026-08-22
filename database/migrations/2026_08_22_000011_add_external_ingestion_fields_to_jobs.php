<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->string('source', 50)->default('official_career')->after('posting_source')->index();
            $table->string('external_job_id', 191)->nullable()->after('source');
            $table->string('deduplication_key', 64)->nullable()->after('external_job_id')->unique();
            $table->json('source_payload')->nullable()->after('deduplication_key');
            $table->unique(['source', 'external_job_id'], 'jobs_source_external_unique');
        });
    }

    public function down(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropUnique('jobs_source_external_unique');
            $table->dropUnique(['deduplication_key']);
            $table->dropIndex(['source']);
            $table->dropColumn(['source', 'external_job_id', 'deduplication_key', 'source_payload']);
        });
    }
};
