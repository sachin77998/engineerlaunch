<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The full MCA company register (about 40 lakh companies) imported from data.gov.in "Company Master Data".
 * Kept apart from `companies` (which drives jobs and sectors); a company page is created from here on demand.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mca_companies', function (Blueprint $table) {
            $table->id();
            $table->char('cin', 21)->unique();
            $table->string('name');
            $table->string('status', 60)->nullable()->index();
            $table->string('company_class', 40)->nullable();
            $table->string('category', 80)->nullable();
            $table->string('sub_category', 80)->nullable();
            $table->decimal('authorised_capital', 20, 2)->nullable();
            $table->decimal('paid_up_capital', 20, 2)->nullable();
            $table->string('state', 60)->nullable()->index();
            $table->string('roc', 60)->nullable();
            $table->string('activity')->nullable();
            $table->text('address')->nullable();
            $table->date('registered_on')->nullable();
            $table->timestamps();
            $table->index('name');
            // The directory lists the largest companies first, overall or within a state.
            $table->index('paid_up_capital');
            $table->index(['state', 'paid_up_capital']);
            $table->fullText('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mca_companies');
    }
};
