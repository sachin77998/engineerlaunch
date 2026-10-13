<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Director shareholding and PAN (MGT-7), and the registry fields filled by automatic lookups (GLEIF / Wikidata).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_officers', function (Blueprint $table) {
            $table->string('pan', 10)->nullable()->after('din');
            $table->unsignedBigInteger('shares_held')->nullable()->after('ceased_on');
        });
        Schema::table('company_registry_details', function (Blueprint $table) {
            $table->string('lei', 20)->nullable()->index()->after('pan');
            $table->string('city')->nullable()->after('registered_address');
            $table->string('pin_code', 10)->nullable()->after('city');
            $table->unsignedInteger('employees')->nullable()->after('members_count');
            $table->string('wikidata_id', 20)->nullable()->after('lei');
            $table->timestamp('enriched_at')->nullable()->after('source');
        });
    }

    public function down(): void
    {
        Schema::table('company_officers', fn (Blueprint $table) => $table->dropColumn(['pan', 'shares_held']));
        Schema::table('company_registry_details', fn (Blueprint $table) => $table->dropColumn(['lei', 'city', 'pin_code', 'employees', 'wikidata_id', 'enriched_at']));
    }
};
