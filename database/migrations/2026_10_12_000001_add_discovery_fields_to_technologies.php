<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Technologies are discovered from Wikidata and the skill taxonomy; jobs are matched to them daily.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('technologies', function (Blueprint $table) {
            if (!Schema::hasColumn('technologies', 'aliases')) $table->json('aliases')->nullable()->after('category');
            if (!Schema::hasColumn('technologies', 'wikidata_id')) $table->string('wikidata_id', 20)->nullable()->after('aliases');
            if (!Schema::hasColumn('technologies', 'popularity')) $table->unsignedInteger('popularity')->default(0)->after('wikidata_id');
            if (!Schema::hasColumn('technologies', 'jobs_count')) $table->unsignedInteger('jobs_count')->default(0)->after('popularity');
        });
    }

    public function down(): void
    {
        Schema::table('technologies', function (Blueprint $table) {
            $table->dropColumn(['aliases', 'wikidata_id', 'popularity', 'jobs_count']);
        });
    }
};
