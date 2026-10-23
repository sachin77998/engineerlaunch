<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Credit methodology as data (sectors, ratio definitions, bands, weights, rating mappings) and stored results:
// per-company ratio values by year, assessments, funding events, borrowing capacity, sector medians and model runs.
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('credit_methodology_ratios')) Schema::create('credit_methodology_ratios', function (Blueprint $t) {
            $t->id();
            $t->string('key', 40)->unique();
            $t->string('label');
            $t->enum('direction', ['up', 'down']);
            $t->json('band');                       // [excellent, good, adequate, weak]
            $t->string('pillar', 20);               // business | financial | liquidity | management
            $t->unsignedSmallInteger('sort_order')->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        if (!Schema::hasTable('credit_methodology_sectors')) Schema::create('credit_methodology_sectors', function (Blueprint $t) {
            $t->id();
            $t->string('key', 40)->unique();
            $t->string('name');
            $t->string('criteria')->nullable();
            $t->string('match_industry')->nullable();  // regex on stated industry
            $t->string('match_name')->nullable();      // regex on company name
            $t->json('nic_prefixes')->nullable();
            $t->json('ratios')->nullable();            // ratio keys used (null = manufacturing set)
            $t->json('skip')->nullable();
            $t->json('overrides')->nullable();         // ratio key => band
            $t->json('weights')->nullable();           // pillar => weight
            $t->json('checks')->nullable();
            $t->json('notes')->nullable();
            $t->boolean('financial_ratios')->default(false);
            $t->unsignedSmallInteger('match_order')->default(100);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        if (!Schema::hasTable('credit_methodology_settings')) Schema::create('credit_methodology_settings', function (Blueprint $t) {
            $t->id();
            $t->string('key', 60)->unique();           // score_bands | short_term_map | default_weights | overlays
            $t->json('value');
            $t->timestamps();
        });
        if (!Schema::hasTable('company_ratio_values')) Schema::create('company_ratio_values', function (Blueprint $t) {
            $t->id();
            $t->foreignId('company_id')->constrained()->cascadeOnDelete();
            $t->string('financial_year', 12);
            $t->string('basis', 15);
            $t->string('ratio_key', 40);
            $t->decimal('value', 20, 4)->nullable();
            $t->timestamps();
            $t->unique(['company_id', 'financial_year', 'basis', 'ratio_key'], 'crv_unique');
            $t->index(['ratio_key', 'financial_year']);
        });
        if (!Schema::hasTable('company_credit_assessments')) Schema::create('company_credit_assessments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('company_id')->unique()->constrained()->cascadeOnDelete();
            $t->string('sector_key', 40)->index();
            $t->string('basis', 15)->nullable();
            $t->string('financial_year', 12)->nullable();
            $t->decimal('score', 6, 2)->nullable();
            $t->string('standalone', 6)->nullable();
            $t->string('indicative', 6)->nullable()->index();
            $t->string('short_term', 6)->nullable();
            $t->string('liquidity', 15)->nullable();
            $t->string('direction', 30)->nullable();
            $t->string('agency_rating', 40)->nullable();
            $t->smallInteger('agency_gap')->nullable();
            $t->unsignedTinyInteger('information_score')->nullable();
            $t->json('pillars')->nullable();
            $t->json('rows')->nullable();
            $t->json('checks')->nullable();
            $t->json('adjustments')->nullable();
            $t->json('policy')->nullable();
            $t->json('outlook')->nullable();
            $t->json('information')->nullable();
            $t->json('liquidity_detail')->nullable();
            $t->json('agency')->nullable();
            $t->timestamp('computed_at')->nullable()->index();
            $t->timestamps();
        });
        if (!Schema::hasTable('company_funding_events')) Schema::create('company_funding_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('company_id')->constrained()->cascadeOnDelete();
            $t->date('event_date')->nullable();
            $t->string('kind', 40)->index();
            $t->string('title', 500);
            $t->decimal('amount_crore', 16, 2)->nullable();
            $t->string('instrument', 40)->nullable();
            $t->string('counterparty')->nullable();
            $t->text('evidence')->nullable();
            $t->string('source')->nullable();
            $t->string('url', 1000)->nullable();
            $t->timestamps();
            $t->index(['company_id', 'event_date']);
        });
        if (!Schema::hasTable('company_borrowing_capacity')) Schema::create('company_borrowing_capacity', function (Blueprint $t) {
            $t->id();
            $t->foreignId('company_id')->unique()->constrained()->cascadeOnDelete();
            $t->string('verdict');
            $t->smallInteger('score')->nullable();
            $t->decimal('probability', 5, 3)->nullable();
            $t->decimal('room_crore', 16, 2)->nullable();
            $t->decimal('debt_cap_multiple', 4, 2)->nullable();
            $t->decimal('interest_coverage', 10, 2)->nullable();
            $t->decimal('utilisation', 6, 2)->nullable();
            $t->decimal('unallocated_crore', 16, 2)->nullable();
            $t->decimal('rated_total_crore', 16, 2)->nullable();
            $t->decimal('open_charges_crore', 16, 2)->nullable();
            $t->json('why')->nullable();
            $t->json('stories')->nullable();
            $t->json('facility_mix')->nullable();
            $t->timestamp('computed_at')->nullable();
            $t->timestamps();
        });
        if (!Schema::hasTable('sector_ratio_medians')) Schema::create('sector_ratio_medians', function (Blueprint $t) {
            $t->id();
            $t->string('sector_key', 40);
            $t->string('ratio_key', 40);
            $t->decimal('median', 20, 4);
            $t->unsignedInteger('sample');
            $t->timestamp('computed_at');
            $t->timestamps();
            $t->unique(['sector_key', 'ratio_key']);
        });
        if (!Schema::hasTable('credit_model_runs')) Schema::create('credit_model_runs', function (Blueprint $t) {
            $t->id();
            $t->string('model', 40)->default('credit_outlook');
            $t->unsignedInteger('pairs');
            $t->unsignedInteger('test_pairs')->nullable();
            $t->decimal('test_auc', 5, 3)->nullable();
            $t->decimal('test_accuracy', 5, 2)->nullable();
            $t->decimal('positive_rate', 5, 3)->nullable();
            $t->json('weights');
            $t->json('mean');
            $t->json('std');
            $t->boolean('is_active')->default(true);
            $t->timestamp('trained_at');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['credit_model_runs', 'sector_ratio_medians', 'company_borrowing_capacity', 'company_funding_events', 'company_credit_assessments', 'company_ratio_values', 'credit_methodology_settings', 'credit_methodology_sectors', 'credit_methodology_ratios'] as $t) Schema::dropIfExists($t);
    }
};
