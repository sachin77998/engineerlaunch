<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (!Schema::hasColumn('companies', 'brands')) $table->json('brands')->nullable()->after('sector');
            if (!Schema::hasColumn('companies', 'headquarters')) $table->string('headquarters', 120)->nullable()->after('country');
            if (!Schema::hasColumn('companies', 'products')) $table->text('products')->nullable()->after('brands');
        });
        Schema::table('company_categories', function (Blueprint $table) {
            if (!Schema::hasColumn('company_categories', 'roles')) $table->json('roles')->nullable()->after('description');
        });
        if (Schema::hasTable('company_facilities')) {
            Schema::table('company_facilities', function (Blueprint $table) {
                $table->index(['state', 'city'], 'company_facilities_state_city_index');
                $table->index('industrial_area', 'company_facilities_area_index');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('company_facilities')) {
            Schema::table('company_facilities', function (Blueprint $table) {
                $table->dropIndex('company_facilities_state_city_index');
                $table->dropIndex('company_facilities_area_index');
            });
        }
        Schema::table('company_categories', fn (Blueprint $table) => $table->dropColumn('roles'));
        Schema::table('companies', fn (Blueprint $table) => $table->dropColumn(['brands', 'headquarters', 'products']));
    }
};
