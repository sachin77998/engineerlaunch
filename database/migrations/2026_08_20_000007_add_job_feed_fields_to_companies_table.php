<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('ats_provider', 30)->nullable()->after('careers_url');
            $table->string('ats_identifier')->nullable()->after('ats_provider');
            $table->text('jobs_feed_url')->nullable()->after('ats_identifier');
            $table->boolean('sync_enabled')->default(false)->after('jobs_feed_url')->index();
            $table->timestamp('last_synced_at')->nullable()->after('sync_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropIndex(['sync_enabled']);
            $table->dropColumn(['ats_provider', 'ats_identifier', 'jobs_feed_url', 'sync_enabled', 'last_synced_at']);
        });
    }
};
