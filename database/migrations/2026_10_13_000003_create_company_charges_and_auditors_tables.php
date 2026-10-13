<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Company insights: registered charges (secured borrowings: lender, amount, open/satisfied) and statutory auditors.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('charge_id', 30)->nullable();
            $table->string('lender')->nullable();
            $table->decimal('amount', 20, 2)->nullable();
            $table->string('status', 30)->nullable();       // Open, Satisfied, Modified
            $table->date('created_on')->nullable();
            $table->date('modified_on')->nullable();
            $table->date('satisfied_on')->nullable();
            $table->text('assets_charged')->nullable();
            $table->string('source')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'charge_id'], 'company_charge_unique');
        });

        Schema::create('company_auditors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('financial_year', 9)->nullable();
            $table->string('name');
            $table->string('firm_registration_number', 20)->nullable();
            $table->string('membership_number', 20)->nullable();
            $table->string('pan', 10)->nullable();
            $table->string('city')->nullable();
            $table->string('source')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'financial_year', 'name'], 'company_auditor_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_auditors');
        Schema::dropIfExists('company_charges');
    }
};
