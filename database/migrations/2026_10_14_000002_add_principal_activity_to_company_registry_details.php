<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Principal business activity as recorded in the MCA register (e.g. "Manufacturing (Machinery & Equipments)"). */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('company_registry_details', 'principal_activity')) return;
        Schema::table('company_registry_details', function (Blueprint $table) {
            $table->string('principal_activity')->nullable()->after('nic_code');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('company_registry_details', 'principal_activity')) return;
        Schema::table('company_registry_details', function (Blueprint $table) {
            $table->dropColumn('principal_activity');
        });
    }
};
