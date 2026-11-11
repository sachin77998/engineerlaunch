<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Refer & Earn: owner referrals (contact or board photo), the reward ledger and UPI withdrawal requests.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 8); // contact | photo
            $table->string('city', 80);
            $table->string('owner_name', 120)->nullable();
            $table->string('owner_phone', 10)->nullable();
            $table->string('property_type', 20)->nullable();
            $table->string('listing', 6)->nullable();
            $table->string('notes', 500)->nullable();
            $table->string('photo_path', 255)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('status', 12)->default('pending');
            $table->string('reject_reason', 20)->nullable();
            $table->unsignedInteger('reward_total')->default(0);
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
            $table->index(['owner_phone', 'created_at']);
        });

        Schema::create('referral_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('referral_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('referral_withdrawal_id')->nullable();
            $table->integer('amount'); // + credit, − debit
            $table->string('note', 191);
            $table->timestamps();
            $table->index('user_id');
        });

        Schema::create('referral_withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('amount');
            $table->string('upi_id', 100);
            $table->string('status', 10)->default('requested');
            $table->string('paid_ref', 80)->nullable();
            $table->string('admin_note', 255)->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_withdrawals');
        Schema::dropIfExists('referral_ledger');
        Schema::dropIfExists('referrals');
    }
};
