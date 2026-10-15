<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Do Trading, part 2: fund houses, fund categories and collections (all data-driven), risk and rating per scheme,
 * the complete stock/ETF instrument master with prices, broker connections (each investor's own Zerodha account),
 * stock orders with their history, and the loan book with amortisation schedules.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mf_amcs', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120)->unique();
            $table->string('slug', 140)->unique();
            $table->string('short_name', 60);
            $table->string('color', 7)->default('#0b2545');
            $table->string('website')->nullable();
            $table->unsignedInteger('scheme_count')->default(0);
            $table->decimal('best_return_3y', 9, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('mf_categories', function (Blueprint $table) {
            $table->id();
            $table->string('asset_class', 40)->index();       // Equity, Debt, Hybrid, Commodities, Index Fund, Solution Oriented, Fund of Funds, ETF, Other
            $table->string('sub_category', 80);                // Small Cap, Liquid, Aggressive Hybrid, Gold…
            $table->string('slug', 140)->unique();
            $table->string('risk_level', 30)->nullable();     // SEBI riskometer for the category
            $table->unsignedInteger('scheme_count')->default(0);
            $table->decimal('avg_return_3y', 9, 2)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['asset_class', 'sub_category']);
        });

        Schema::create('mf_collections', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 60)->unique();
            $table->string('title', 80);
            $table->string('description')->nullable();
            $table->string('icon', 30)->default('chart');
            $table->json('rules');                 // filters and sort applied to mf_schemes
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('fund_count')->default(0);
            $table->timestamps();
        });

        Schema::table('mf_schemes', function (Blueprint $table) {
            $table->foreignId('amc_id')->nullable()->after('amc')->constrained('mf_amcs')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->after('category')->constrained('mf_categories')->nullOnDelete();
            $table->string('sub_category', 80)->nullable()->after('asset_class')->index();
            $table->string('risk_level', 30)->nullable()->after('sub_category')->index();
            $table->unsignedTinyInteger('rating')->nullable()->after('risk_level')->index();
            $table->boolean('is_index')->default(false)->after('rating')->index();
            $table->decimal('return_6m', 9, 2)->nullable()->after('nav_date');
        });

        Schema::create('market_instruments', function (Blueprint $table) {
            $table->id();
            $table->string('exchange', 5);              // NSE, BSE
            $table->string('symbol', 40);              // trading symbol
            $table->string('name')->nullable();
            $table->string('instrument_type', 10);     // STOCK, ETF
            $table->string('series', 5)->nullable();
            $table->string('isin', 12)->nullable()->index();
            $table->unsignedBigInteger('kite_token')->nullable()->index();
            $table->unsignedInteger('lot_size')->default(1);
            $table->decimal('tick_size', 8, 2)->default(0.05);
            $table->decimal('face_value', 12, 2)->nullable();
            $table->date('listed_on')->nullable();
            $table->string('underlying')->nullable();   // ETFs: Nifty 50, Gold…
            $table->string('etf_class', 20)->nullable(); // EQUITY, GOLD, SILVER, DEBT, INTERNATIONAL
            $table->decimal('last_price', 14, 2)->nullable();
            $table->decimal('prev_close', 14, 2)->nullable();
            $table->decimal('open', 14, 2)->nullable();
            $table->decimal('high', 14, 2)->nullable();
            $table->decimal('low', 14, 2)->nullable();
            $table->decimal('change_pct', 8, 2)->nullable();
            $table->unsignedBigInteger('volume')->nullable();
            $table->decimal('turnover_lakhs', 16, 2)->nullable();
            $table->date('price_date')->nullable();
            $table->decimal('margin_pct', 7, 2)->nullable();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['exchange', 'symbol']);
            $table->index(['instrument_type', 'exchange', 'is_active']);
            $table->fullText(['symbol', 'name']);
        });

        Schema::create('broker_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('broker', 20);                   // kite
            $table->string('broker_user_id', 40)->nullable();
            $table->text('access_token')->nullable();
            $table->text('public_token')->nullable();
            $table->timestamp('login_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->json('profile')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'broker']);
        });

        Schema::create('stock_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_no', 24)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('market_instrument_id')->constrained()->cascadeOnDelete();
            $table->string('exchange', 5);
            $table->string('symbol', 40);
            $table->string('side', 4);                  // BUY, SELL
            $table->string('product', 5);               // CNC, MIS
            $table->string('order_type', 6);            // MARKET, LIMIT, SL, SL-M
            $table->string('validity', 4)->default('DAY');
            $table->unsignedInteger('quantity');
            $table->decimal('price', 14, 2)->nullable();
            $table->decimal('trigger_price', 14, 2)->nullable();
            $table->string('status', 20)->index();      // created, sent, open, complete, rejected, cancelled, failed
            $table->string('status_message')->nullable();
            $table->string('broker', 20)->default('kite');
            $table->string('broker_order_id', 40)->nullable()->index();
            $table->unsignedInteger('filled_quantity')->default(0);
            $table->decimal('average_price', 14, 2)->nullable();
            $table->json('charges')->nullable();
            $table->string('idempotency_key', 64)->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'idempotency_key']);
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('stock_order_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_order_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20);
            $table->string('source', 20);               // client, broker, postback, system
            $table->string('message')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->string('loan_no', 24)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('borrower_name');
            $table->string('account_number', 40)->nullable();
            $table->string('loan_type', 30)->default('term');
            $table->decimal('principal', 16, 2);
            $table->decimal('interest_rate', 7, 4);
            $table->date('issue_date');
            $table->date('first_due_date')->nullable();
            $table->date('maturity_date');
            $table->string('payment_frequency', 60);
            $table->string('day_count', 12)->default('actual_365');
            $table->unsignedSmallInteger('moratorium_months')->default(0);
            $table->string('moratorium_type', 30)->nullable();   // principal_only, principal_and_interest
            $table->decimal('emi', 16, 2)->nullable();
            $table->decimal('total_interest', 16, 2)->nullable();
            $table->decimal('total_payment', 16, 2)->nullable();
            $table->unsignedSmallInteger('installments')->default(0);
            $table->string('status', 20)->default('active');
            $table->timestamps();
        });

        Schema::create('loan_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('installment_no');
            $table->date('due_date');
            $table->unsignedSmallInteger('days');
            $table->decimal('opening_balance', 16, 2);
            $table->decimal('scheduled_payment', 16, 2);
            $table->decimal('principal', 16, 2);
            $table->decimal('interest', 16, 2);
            $table->decimal('total_payment', 16, 2);
            $table->decimal('closing_balance', 16, 2);
            $table->decimal('daily_interest', 14, 4)->nullable();
            $table->boolean('is_moratorium')->default(false);
            $table->string('status', 12)->default('due');      // due, paid, overdue
            $table->unique(['loan_id', 'installment_no']);
        });
    }

    public function down(): void
    {
        foreach (['loan_schedules', 'loans', 'stock_order_events', 'stock_orders', 'broker_connections', 'market_instruments'] as $t) Schema::dropIfExists($t);
        Schema::table('mf_schemes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('amc_id');
            $table->dropConstrainedForeignId('category_id');
            $table->dropColumn(['sub_category', 'risk_level', 'rating', 'is_index', 'return_6m']);
        });
        foreach (['mf_collections', 'mf_categories', 'mf_amcs'] as $t) Schema::dropIfExists($t);
    }
};
