<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Company insights (like a company-check report): registry details from MGT-7 / master data, year-wise financial
 * statements from AOC-4, directors, shareholding, products, related-party transactions and the filed documents.
 * Amounts are stored in rupees.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_registry_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('cin', 21)->nullable()->unique();
            $table->string('pan', 10)->nullable()->index();
            $table->json('gstins')->nullable();
            $table->string('company_status')->nullable();
            $table->string('company_class')->nullable();
            $table->string('company_category')->nullable();
            $table->string('company_subcategory')->nullable();
            $table->string('listing_status')->nullable();
            $table->string('roc')->nullable();
            $table->string('nic_code', 10)->nullable();
            $table->date('incorporation_date')->nullable();
            $table->decimal('authorised_capital', 20, 2)->nullable();
            $table->decimal('paid_up_capital', 20, 2)->nullable();
            $table->unsignedInteger('members_count')->nullable();
            $table->text('registered_address')->nullable();
            $table->string('email')->nullable();
            $table->date('last_agm_date')->nullable();
            $table->date('last_balance_sheet_date')->nullable();
            $table->string('source')->nullable();
            $table->timestamps();
        });

        Schema::create('company_financial_statements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('financial_year', 9);          // 2024-25
            $table->date('period_end')->nullable();
            $table->boolean('is_consolidated')->default(false);
            foreach (['revenue', 'other_income', 'total_income', 'total_expenses', 'ebitda', 'depreciation', 'finance_cost', 'profit_before_tax',
                      'tax_expense', 'profit_after_tax', 'share_capital', 'reserves', 'net_worth', 'long_term_borrowings', 'short_term_borrowings',
                      'total_debt', 'trade_payables', 'current_liabilities', 'trade_receivables', 'inventories', 'cash_and_bank', 'current_assets',
                      'fixed_assets', 'total_assets', 'employee_cost', 'market_price'] as $column) {
                $table->decimal($column, 20, 2)->nullable();
            }
            $table->decimal('eps', 14, 4)->nullable();
            $table->unsignedBigInteger('shares_outstanding')->nullable();
            $table->string('source')->nullable();           // aoc4, xbrl, manual, instafinancials
            $table->foreignId('filing_id')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'financial_year', 'is_consolidated'], 'company_fs_year_unique');
        });

        Schema::create('company_filings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('form', 20);                     // AOC-4, AOC-4 XBRL, MGT-7, MGT-7A
            $table->string('financial_year', 9)->nullable();
            $table->string('original_name')->nullable();
            $table->string('file_path')->nullable();
            $table->string('text_path')->nullable();
            $table->string('status', 20)->default('uploaded');
            $table->json('parsed_fields')->nullable();
            $table->text('message')->nullable();
            $table->foreignId('uploaded_by')->nullable();
            $table->timestamps();
        });

        Schema::create('company_officers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('din', 8)->nullable();
            $table->string('name');
            $table->string('designation')->nullable();
            $table->date('appointed_on')->nullable();
            $table->date('ceased_on')->nullable();
            $table->string('source')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'name', 'designation'], 'company_officer_unique');
        });

        Schema::create('company_shareholdings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('financial_year', 9)->nullable();
            $table->string('category');
            $table->unsignedBigInteger('shares')->nullable();
            $table->decimal('percentage', 7, 4)->nullable();
            $table->string('source')->nullable();
            $table->timestamps();
        });

        Schema::create('company_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('financial_year', 9)->nullable();
            $table->string('product_code', 20)->nullable();
            $table->string('description');
            $table->decimal('turnover_percentage', 7, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('company_related_parties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('financial_year', 9)->nullable();
            $table->string('party_name');
            $table->string('relationship')->nullable();
            $table->text('transaction_description')->nullable();
            $table->decimal('amount', 20, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['company_related_parties', 'company_products', 'company_shareholdings', 'company_officers', 'company_filings', 'company_financial_statements', 'company_registry_details'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
