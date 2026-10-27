<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// An agency's sector paper can be narrower than the base sector (ICRA IT services within the broad services profile):
// a pattern on the company's industry or name decides whether the grid applies.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('credit_agency_criteria', function (Blueprint $table) {
            $table->string('applies_if', 400)->nullable()->after('sector_key');
        });
    }

    public function down(): void
    {
        Schema::table('credit_agency_criteria', fn (Blueprint $table) => $table->dropColumn('applies_if'));
    }
};
