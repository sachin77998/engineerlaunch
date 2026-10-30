<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Search copy of the open jobs (App\Services\JobSearchIndex): the filter columns plus a short lower-case search text.
 * Searching the jobs table read ~1.3 GB of descriptions per keyword (45-100s); this table is ~120 MB.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('job_search')) return;
        Schema::create('job_search', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->string('title', 255);
            $table->string('location', 255)->nullable();
            $table->string('country', 120)->nullable();
            $table->string('state', 120)->nullable();
            $table->string('role_family', 80)->nullable();
            $table->string('department', 100)->nullable();
            $table->string('work_mode', 30)->nullable();
            $table->string('job_type', 60)->nullable();
            $table->string('experience_level', 60)->nullable();
            $table->unsignedTinyInteger('experience_min')->nullable();
            $table->unsignedTinyInteger('experience_max')->nullable();
            $table->string('posting_source', 30)->nullable();
            $table->string('source', 50)->nullable();
            $table->decimal('salary_min', 12, 2)->nullable();
            $table->decimal('salary_max', 12, 2)->nullable()->index();
            $table->string('salary_currency', 10)->nullable();
            $table->string('salary_period', 20)->nullable();
            $table->unsignedInteger('views')->default(0)->index();
            $table->timestamp('posted_at')->nullable()->index();
            $table->timestamp('expires_at')->nullable();
            // Always null: lets the Job model's soft-delete scope run against this table.
            $table->timestamp('deleted_at')->nullable();
            $table->text('search_text');
        });
        // Byte comparison: the text is already lower-case, and LIKE without collation rules is several times faster.
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE job_search MODIFY search_text TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('job_search');
    }
};
