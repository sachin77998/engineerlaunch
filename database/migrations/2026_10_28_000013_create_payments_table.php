<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// One row per payment attempt with a gateway (Paytm), separate from the order it pays for, so failed attempts, retries,
// refunds and reconciliation each keep their own record. Money in paise.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('order_id', 50)->unique();          // sent to the gateway (one per attempt)
            $t->string('payable_type', 60);               // what is being paid for, e.g. premium_order
            $t->unsignedBigInteger('payable_id');
            $t->string('provider', 20)->default('paytm');
            $t->unsignedBigInteger('amount_paise');
            $t->string('currency', 3)->default('INR');
            $t->string('status', 20)->default('pending'); // pending | success | failed | refund_pending | refunded | partially_refunded
            $t->string('gateway_txn_id', 64)->nullable()->index();
            $t->string('bank_txn_id', 64)->nullable();
            $t->string('payment_mode', 30)->nullable();
            $t->json('gateway_response')->nullable();
            $t->timestamp('verified_at')->nullable();
            $t->timestamps();
            $t->index(['payable_type', 'payable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
