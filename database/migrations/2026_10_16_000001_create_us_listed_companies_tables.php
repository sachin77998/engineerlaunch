<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** US-listed companies (NYSE, Nasdaq, NYSE American) with their daily quote, and annual results from SEC EDGAR (USD). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('us_listed_companies', function (Blueprint $table) {
            $table->id();
            $table->string('ticker', 16)->unique();
            $table->unsignedBigInteger('cik')->nullable()->index();
            $table->string('name');
            $table->string('exchange', 32)->nullable();
            $table->string('sector', 80)->nullable();
            $table->string('industry', 160)->nullable();
            $table->string('country', 80)->nullable();
            $table->unsignedSmallInteger('ipo_year')->nullable();
            $table->decimal('last_price', 14, 4)->nullable();
            $table->decimal('change_pct', 9, 3)->nullable();
            $table->unsignedBigInteger('volume')->nullable();
            $table->decimal('market_cap', 22, 2)->nullable();
            $table->date('price_date')->nullable();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('us_company_financials', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cik');
            $table->unsignedSmallInteger('fiscal_year');
            $table->date('period_end')->nullable();
            foreach (['revenue', 'operating_income', 'net_income', 'operating_cash_flow', 'total_assets', 'total_liabilities', 'equity', 'cash', 'long_term_debt'] as $column) {
                $table->decimal($column, 22, 2)->nullable();
            }
            $table->decimal('eps_diluted', 14, 4)->nullable();
            $table->date('balance_sheet_date')->nullable();
            $table->timestamps();
            $table->unique(['cik', 'fiscal_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('us_company_financials');
        Schema::dropIfExists('us_listed_companies');
    }
};
