<?php

use App\Models\Company;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// name_key: company name without legal suffixes ("Titan Company Limited" == "TITAN COMPANY"), used to avoid duplicates
// when registries, catalogs and job feeds spell the same employer differently.
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('companies', 'name_key')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->string('name_key', 190)->nullable()->after('slug')->index();
            });
        }
        DB::table('companies')->whereNull('name_key')->select(['id', 'name'])->orderBy('id')->chunkById(1000, function ($rows) {
            foreach ($rows as $row) DB::table('companies')->where('id', $row->id)->update(['name_key' => Company::nameKey($row->name)]);
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('companies', 'name_key')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->dropIndex(['name_key']);
                $table->dropColumn('name_key');
            });
        }
    }
};
