<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Foreign filers (20-F, IFRS) may report in euros: keep the reporting currency with each year. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('us_company_financials', function (Blueprint $table) {
            if (!Schema::hasColumn('us_company_financials', 'currency')) $table->string('currency', 3)->default('USD')->after('fiscal_year');
        });
    }

    public function down(): void
    {
        Schema::table('us_company_financials', fn (Blueprint $table) => $table->dropColumn('currency'));
    }
};
