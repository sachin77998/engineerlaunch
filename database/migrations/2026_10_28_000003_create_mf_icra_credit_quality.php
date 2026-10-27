<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Fund credit quality per ICRA's Mutual Funds methodology, kept next to the base (mf_credit_quality) result each month.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mf_icra_credit_quality', function (Blueprint $table) {
            $table->id();
            $table->string('amc');
            $table->string('scheme_key')->index();
            $table->string('scheme_name');
            $table->string('category', 80)->nullable();
            $table->date('as_of');
            $table->decimal('credit_score', 12, 4)->nullable();
            $table->decimal('wam_years', 8, 3)->nullable();
            $table->decimal('category_maturity', 8, 3)->nullable();
            $table->decimal('maturity_considered', 8, 3)->nullable();
            $table->decimal('benchmark_score', 12, 4)->nullable();
            $table->string('implied', 8)->nullable();
            $table->string('rating', 8)->nullable();
            $table->string('rating_short', 8)->nullable();
            $table->string('status', 20);   // provisional, final, curing, downgraded, upgraded, upgrade_pending
            $table->json('flags')->nullable();
            $table->timestamps();
            $table->unique(['scheme_key', 'as_of']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mf_icra_credit_quality');
    }
};
