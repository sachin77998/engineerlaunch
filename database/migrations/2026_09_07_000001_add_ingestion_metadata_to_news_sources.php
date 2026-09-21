<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('news_sources', function (Blueprint $t) {
            $t->string('api_url', 500)->nullable()->after('feed_url');
            $t->string('source_category', 80)->nullable()->after('source_type');
            $t->string('country', 80)->default('Global')->after('source_category');
            $t->unsignedTinyInteger('priority')->default(60)->index()->after('country');
            $t->string('trust_tier', 30)->default('industry')->after('priority');
            $t->boolean('attribution_required')->default(true)->after('trust_tier');
            $t->timestamp('last_fetched_at')->nullable()->after('is_active');
            $t->string('last_fetch_status', 20)->nullable()->after('last_fetched_at');
            $t->text('last_error')->nullable()->after('last_fetch_status');
        });
    }
    public function down(): void
    {
        Schema::table('news_sources', fn(Blueprint $t) => $t->dropColumn(['api_url', 'source_category', 'country', 'priority', 'trust_tier', 'attribution_required', 'last_fetched_at', 'last_fetch_status', 'last_error']));
    }
};
