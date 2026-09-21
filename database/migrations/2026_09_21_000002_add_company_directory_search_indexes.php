<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->index(['is_active', 'industry'], 'companies_active_industry_index');
            $table->index(['is_active', 'sector'], 'companies_active_sector_index');
            $table->index(['is_active', 'name'], 'companies_active_name_index');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropIndex('companies_active_industry_index');
            $table->dropIndex('companies_active_sector_index');
            $table->dropIndex('companies_active_name_index');
        });
    }
};
