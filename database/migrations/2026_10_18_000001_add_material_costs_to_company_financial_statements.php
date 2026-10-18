<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Raw material consumption and traded goods purchased, for material trends and next-year consumption forecasts. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_financial_statements', function (Blueprint $table) {
            if (!Schema::hasColumn('company_financial_statements', 'cost_of_materials_consumed')) $table->decimal('cost_of_materials_consumed', 20, 2)->nullable()->after('total_expenses');
            if (!Schema::hasColumn('company_financial_statements', 'purchases_of_stock_in_trade')) $table->decimal('purchases_of_stock_in_trade', 20, 2)->nullable()->after('cost_of_materials_consumed');
            if (!Schema::hasColumn('company_financial_statements', 'other_expenses')) $table->decimal('other_expenses', 20, 2)->nullable()->after('purchases_of_stock_in_trade');
        });
    }

    public function down(): void
    {
        Schema::table('company_financial_statements', function (Blueprint $table) {
            $table->dropColumn(['cost_of_materials_consumed', 'purchases_of_stock_in_trade', 'other_expenses']);
        });
    }
};
