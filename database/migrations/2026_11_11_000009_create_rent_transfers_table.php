<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Credit-card rent / tuition / maintenance payments: card charge (Paytm) then payout to the payee (Cashfree Payouts).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rent_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('reference', 40)->unique();
            $table->string('purpose', 20);
            $table->string('method', 10); // upi | bank (contact resolves to one of these)
            $table->string('payee_name', 120);
            $table->string('payee_phone', 15);
            $table->string('payee_vpa', 100)->nullable();
            $table->text('payee_account')->nullable(); // encrypted
            $table->string('payee_account_last4', 4)->nullable();
            $table->string('payee_ifsc', 11)->nullable();
            $table->json('details')->nullable();
            $table->unsignedInteger('amount');
            $table->unsignedInteger('fee')->default(0);
            $table->unsignedInteger('total');
            $table->string('status', 20)->default('draft');
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->string('payout_ref', 60)->nullable();
            $table->json('payout_response')->nullable();
            $table->string('failure_reason', 255)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
            $table->index(['status', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rent_transfers');
    }
};
