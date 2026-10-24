<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Monthly scheme portfolios fetched from AMC disclosure pages, their holdings, and the fund credit quality (CQR)
// computed from them per the Fund Credit Quality rating methodology.
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('mf_portfolio_sources')) Schema::create('mf_portfolio_sources', function (Blueprint $t) {
            $t->id();
            $t->string('amc', 120)->unique();
            $t->string('page_url', 500);
            $t->string('file_pattern', 300)->nullable();   // regex the file link must match
            $t->boolean('is_active')->default(true);
            $t->date('last_month')->nullable();
            $t->string('last_status', 300)->nullable();
            $t->timestamp('last_checked_at')->nullable();
            $t->timestamps();
        });
        if (!Schema::hasTable('mf_portfolio_files')) Schema::create('mf_portfolio_files', function (Blueprint $t) {
            $t->id();
            $t->foreignId('source_id')->nullable()->index();
            $t->string('amc', 120)->index();
            $t->string('file_url', 1000);
            $t->char('url_hash', 40)->unique();
            $t->date('as_of')->nullable()->index();
            $t->string('status', 20)->default('pending');   // parsed | failed | skipped
            $t->unsignedInteger('schemes')->default(0);
            $t->unsignedInteger('holdings')->default(0);
            $t->string('error', 500)->nullable();
            $t->timestamp('fetched_at')->nullable();
            $t->timestamps();
        });
        if (!Schema::hasTable('mf_portfolio_holdings')) Schema::create('mf_portfolio_holdings', function (Blueprint $t) {
            $t->id();
            $t->foreignId('file_id')->index();
            $t->string('amc', 120);
            $t->date('as_of');
            $t->string('scheme_name', 200);
            $t->string('scheme_key', 160);
            $t->string('instrument', 300);
            $t->string('isin', 12)->nullable()->index();
            $t->string('issuer_key', 80)->nullable();
            $t->string('rating_raw', 120)->nullable();
            $t->string('rating', 10)->nullable();          // AAA … D, A1+ … A4, SOV
            $t->string('asset_type', 12);                  // sovereign, cash, long, short, unrated, equity, other
            $t->decimal('quantity', 20, 4)->nullable();
            $t->decimal('market_value_lakh', 20, 4)->nullable();
            $t->decimal('pct_nav', 9, 4)->nullable();
            $t->date('maturity_on')->nullable();
            $t->decimal('ytm', 8, 4)->nullable();
            $t->timestamps();
            $t->index(['scheme_key', 'as_of']);
        });
        if (!Schema::hasTable('mf_credit_quality')) Schema::create('mf_credit_quality', function (Blueprint $t) {
            $t->id();
            $t->string('amc', 120);
            $t->string('scheme_key', 160);
            $t->string('alias_key', 160)->nullable()->index();
            $t->string('scheme_name', 200);
            $t->date('as_of');
            $t->decimal('debt_share', 7, 2)->nullable();      // % of NAV in debt, money market, sovereign and cash
            $t->decimal('credit_score', 12, 4)->nullable();   // weighted expected-loss score (bps)
            $t->decimal('benchmark_score', 12, 4)->nullable();
            $t->decimal('wam_years', 8, 3)->nullable();
            $t->string('implied', 6)->nullable();             // this month's score band
            $t->string('cqr', 6)->nullable();                 // published band after curing rules
            $t->string('cqr_short', 6)->nullable();
            $t->boolean('curing')->default(false);
            $t->json('distribution')->nullable();
            $t->json('concentration')->nullable();
            $t->json('flags')->nullable();
            $t->unsignedInteger('holdings')->default(0);
            $t->foreignId('file_id')->nullable();
            $t->timestamps();
            $t->unique(['scheme_key', 'as_of']);
        });
    }

    public function down(): void
    {
        foreach (['mf_credit_quality', 'mf_portfolio_holdings', 'mf_portfolio_files', 'mf_portfolio_sources'] as $t) Schema::dropIfExists($t);
    }
};
