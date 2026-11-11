<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Equity funding rounds and the investors in them, read from funding news ("X raises $20 mn Series B led by Y").
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investors', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('name_key', 190)->unique();
            $t->string('slug')->unique();
            $t->string('type', 20)->default('firm');          // vc, angel_network, accelerator, corporate, financial, individual, government, firm
            $t->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $t->unsignedInteger('rounds_count')->default(0);
            $t->unsignedInteger('lead_count')->default(0);
            $t->date('last_invested_on')->nullable()->index();
            $t->timestamps();
            $t->index('rounds_count');
        });
        Schema::create('funding_rounds', function (Blueprint $t) {
            $t->id();
            $t->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $t->string('company_name');
            $t->string('company_key', 190)->index();
            $t->string('round_type', 20)->index();            // pre_seed, seed, series_a … series_h, bridge, growth, pre_ipo, debt, strategic, undisclosed
            $t->decimal('amount', 20, 2)->nullable();          // in `currency` units
            $t->string('currency', 3)->nullable();
            $t->decimal('amount_usd', 16, 2)->nullable()->index(); // approximate, for sorting and totals
            $t->date('announced_on')->index();
            $t->string('headline', 500);
            $t->string('source_url', 2048)->nullable();
            $t->unsignedSmallInteger('sources_count')->default(1);
            $t->timestamps();
            $t->index(['company_key', 'round_type', 'announced_on']);
        });
        Schema::create('funding_round_investor', function (Blueprint $t) {
            $t->foreignId('funding_round_id')->constrained()->cascadeOnDelete();
            $t->foreignId('investor_id')->constrained()->cascadeOnDelete();
            $t->boolean('is_lead')->default(false);
            $t->primary(['funding_round_id', 'investor_id']);
            $t->index('investor_id');
        });
        Schema::create('funding_round_sources', function (Blueprint $t) {
            $t->foreignId('funding_round_id')->constrained()->cascadeOnDelete();
            $t->foreignId('news_article_id')->constrained()->cascadeOnDelete();
            $t->primary(['funding_round_id', 'news_article_id']);
            $t->index('news_article_id');
        });
    }

    public function down(): void
    {
        foreach (['funding_round_sources', 'funding_round_investor', 'funding_rounds', 'investors'] as $table) Schema::dropIfExists($table);
    }
};
