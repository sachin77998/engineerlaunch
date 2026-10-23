<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Rationale and liquidity text plus funding events read from each rating report (infusions, bonds/CCDs, repayments, tie-ups).
        Schema::table('credit_ratings', function (Blueprint $table) {
            if (!Schema::hasColumn('credit_ratings', 'rationale')) $table->text('rationale')->nullable()->after('about');
            if (!Schema::hasColumn('credit_ratings', 'liquidity_text')) $table->text('liquidity_text')->nullable()->after('rationale');
            if (!Schema::hasColumn('credit_ratings', 'funding_events')) $table->json('funding_events')->nullable()->after('liquidity_text');
        });
        // Same hashed key as daily_visitors, so a page view can be tied to the visitor's approximate location.
        if (!Schema::hasColumn('activity_logs', 'visitor_key')) Schema::table('activity_logs', function (Blueprint $table) {
            $table->string('visitor_key', 64)->nullable()->after('session_id');
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('credit_ratings', fn (Blueprint $t) => $t->dropColumn(['rationale', 'liquidity_text', 'funding_events']));
        Schema::table('activity_logs', function (Blueprint $t) {
            $t->dropIndex(['user_id', 'created_at']);
            $t->dropColumn('visitor_key');
        });
    }
};
