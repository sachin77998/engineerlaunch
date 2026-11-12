<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Saved property searches with new-listing alerts (instant, daily or weekly email), and a log of which listing was sent for which search.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_saved_searches', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('name', 160);
            $t->string('mode', 20);                     // buy | rent | commercial-buy | commercial-rent
            $t->string('city_slug', 90)->nullable();
            $t->json('filters')->nullable();            // PropertySearch query-string filters
            $t->char('fingerprint', 40);                // sha1 of mode + city + filters, one row per identical search
            $t->string('frequency', 10)->default('daily'); // instant | daily | weekly | off
            $t->timestamp('seen_at')->nullable();       // last time the user opened the results from "My searches"
            $t->timestamp('last_sent_at')->nullable();
            $t->timestamps();
            $t->unique(['user_id', 'fingerprint']);
            $t->index(['frequency', 'mode', 'city_slug']);
        });
        Schema::create('property_alert_sends', function (Blueprint $t) {
            $t->id();
            $t->foreignId('saved_search_id')->constrained('property_saved_searches')->cascadeOnDelete();
            $t->foreignId('property_id')->constrained()->cascadeOnDelete();
            $t->timestamp('created_at')->useCurrent();
            $t->unique(['saved_search_id', 'property_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_alert_sends');
        Schema::dropIfExists('property_saved_searches');
    }
};
