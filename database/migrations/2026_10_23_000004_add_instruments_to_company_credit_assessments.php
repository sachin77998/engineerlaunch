<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Instrument-level ratings against the criteria (Basel III Tier II / AT I, perpetual and subordinated debt, insurer hybrids, PP-MLD, PTC).
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('company_credit_assessments', 'instruments')) Schema::table('company_credit_assessments', function (Blueprint $t) {
            $t->json('instruments')->nullable()->after('checks');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('company_credit_assessments', 'instruments')) Schema::table('company_credit_assessments', fn (Blueprint $t) => $t->dropColumn('instruments'));
    }
};
