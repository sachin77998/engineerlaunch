<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('industrial_companies', 'careers_url')) {
            Schema::table('industrial_companies', function (Blueprint $table) {
                $table->text('careers_url')->nullable()->after('website');
            });
        }
    }
    public function down(): void
    {
        if (Schema::hasColumn('industrial_companies', 'careers_url')) {
            Schema::table('industrial_companies', function (Blueprint $table) {
                $table->dropColumn('careers_url');
            });
        }
    }
};
