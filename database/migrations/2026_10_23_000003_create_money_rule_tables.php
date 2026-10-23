<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Money planner rules as data: tax slabs, surcharges, capital-gains rules, deductions, instruments,
// retirement-income products, plan examples and settings. Seeded from config/money.php; edited at /admin/money-rules.
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('money_tax_slabs')) Schema::create('money_tax_slabs', function (Blueprint $t) {
            $t->id();
            $t->string('regime', 30)->index();          // new | old | old_senior | old_super_senior
            $t->decimal('upto', 16, 2)->nullable();     // null = no upper limit
            $t->decimal('rate', 6, 2);
            $t->unsignedSmallInteger('sort_order')->default(0);
            $t->timestamps();
        });
        if (!Schema::hasTable('money_tax_surcharges')) Schema::create('money_tax_surcharges', function (Blueprint $t) {
            $t->id();
            $t->string('regime', 10)->index();          // new | old
            $t->decimal('above', 16, 2);
            $t->decimal('rate', 6, 2);
            $t->timestamps();
        });
        if (!Schema::hasTable('money_capital_gains_rules')) Schema::create('money_capital_gains_rules', function (Blueprint $t) {
            $t->id();
            $t->string('asset', 30)->unique();
            $t->unsignedSmallInteger('ltcg_months')->nullable();
            $t->decimal('stcg_rate', 6, 2)->nullable();  // null = slab rate
            $t->decimal('ltcg_rate', 6, 2)->nullable();
            $t->decimal('ltcg_exemption', 16, 2)->default(0);
            $t->string('section_st', 20)->nullable();
            $t->string('section_lt', 20)->nullable();
            $t->timestamps();
        });
        if (!Schema::hasTable('money_deductions')) Schema::create('money_deductions', function (Blueprint $t) {
            $t->id();
            $t->string('section', 20)->unique();
            $t->string('label', 300);
            $t->decimal('limit', 16, 2)->nullable();
            $t->decimal('limit_senior', 16, 2)->nullable();
            $t->decimal('parents', 16, 2)->nullable();
            $t->decimal('parents_senior', 16, 2)->nullable();
            $t->decimal('limit_pct_basic', 6, 2)->nullable();
            $t->json('regimes');
            $t->unsignedSmallInteger('sort_order')->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        if (!Schema::hasTable('money_instruments')) Schema::create('money_instruments', function (Blueprint $t) {
            $t->id();
            $t->string('key', 30)->unique();
            $t->string('label', 120);
            $t->decimal('expected_return', 6, 2);
            $t->string('tax_class', 20);                // equity | debt | fd | exempt | nps | gold | none
            $t->boolean('liquid')->default(true);
            $t->unsignedSmallInteger('sort_order')->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        if (!Schema::hasTable('money_income_products')) Schema::create('money_income_products', function (Blueprint $t) {
            $t->id();
            $t->string('key', 30)->unique();
            $t->string('label', 160);
            $t->decimal('rate', 6, 2);
            $t->decimal('withdrawal', 6, 2)->nullable();
            $t->decimal('max_per_person', 16, 2)->nullable();
            $t->decimal('max_joint', 16, 2)->nullable();
            $t->unsignedTinyInteger('min_age')->nullable();
            $t->string('payout', 20);
            $t->string('tax_note', 200)->nullable();
            $t->string('risk', 120)->nullable();
            $t->unsignedSmallInteger('sort_order')->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        if (!Schema::hasTable('money_plan_examples')) Schema::create('money_plan_examples', function (Blueprint $t) {
            $t->id();
            $t->string('category', 40)->index();
            $t->string('name', 200);
            $t->unsignedSmallInteger('sort_order')->default(0);
            $t->timestamps();
        });
        if (!Schema::hasTable('money_settings')) Schema::create('money_settings', function (Blueprint $t) {
            $t->id();
            $t->string('key', 60)->unique();
            $t->json('value');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['money_settings', 'money_plan_examples', 'money_income_products', 'money_instruments', 'money_deductions', 'money_capital_gains_rules', 'money_tax_surcharges', 'money_tax_slabs'] as $t) Schema::dropIfExists($t);
    }
};
