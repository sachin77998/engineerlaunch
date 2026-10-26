<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Rating agencies' sector methodologies other than the base criteria (ICRA first): factors with the agency's strongest /
// weakest indicative levels and five-step cut points, adjustment factors, notes. The company assessment stores the
// resulting factor grid alongside its own result.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_agency_criteria', function (Blueprint $table) {
            $table->id();
            $table->string('key', 60)->unique();
            $table->string('agency', 40);
            $table->string('sector_key', 60)->index();
            $table->string('name');
            $table->string('criteria', 300);
            $table->date('published_on')->nullable();
            $table->json('factors');
            $table->json('adjustments')->nullable();
            $table->json('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::table('company_credit_assessments', function (Blueprint $table) {
            $table->json('agency_criteria')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('company_credit_assessments', fn (Blueprint $table) => $table->dropColumn('agency_criteria'));
        Schema::dropIfExists('credit_agency_criteria');
    }
};
