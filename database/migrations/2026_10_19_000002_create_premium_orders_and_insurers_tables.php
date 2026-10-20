<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Premium plan purchases, and insurers with the public metrics used to rank them in the insurance advisor. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('premium_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_no', 32)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('plan', 30);
            $table->unsignedSmallInteger('months');
            $table->decimal('base_amount', 12, 2);
            $table->decimal('gst_rate', 5, 2);
            $table->decimal('gst_amount', 12, 2);
            $table->decimal('total_amount', 12, 2);
            $table->string('currency', 3)->default('INR');
            $table->string('status', 20)->default('created')->index(); // created, paid, activated, failed, cancelled
            $table->string('gateway', 20)->nullable();
            $table->string('gateway_order_id', 64)->nullable()->index();
            $table->string('gateway_payment_id', 64)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('premium_until')->nullable();
            $table->string('notes', 500)->nullable();
            $table->timestamps();
        });

        Schema::create('insurers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->string('sector', 20)->index(); // life, health (standalone health insurer), general
            $table->string('products', 120);        // comma list: term, health, car, bike
            $table->string('website')->nullable();
            $table->boolean('is_public_sector')->default(false);
            // Public figures from the IRDAI annual report / insurer public disclosures, entered by the owner.
            $table->decimal('claim_settlement_ratio', 5, 2)->nullable();
            $table->decimal('incurred_claim_ratio', 6, 2)->nullable();
            $table->decimal('complaints_per_10k', 8, 2)->nullable();
            $table->unsignedInteger('network_hospitals')->nullable();
            $table->decimal('solvency_ratio', 5, 2)->nullable();
            $table->string('metrics_year', 10)->nullable();
            $table->string('metrics_source', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insurers');
        Schema::dropIfExists('premium_orders');
    }
};
