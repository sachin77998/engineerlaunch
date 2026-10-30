<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Career explorer lists plants and offices by name, 12 a page: without an index every page sorted ~100k rows (1-2s). */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('company_facilities') || $this->hasIndex()) return;
        Schema::table('company_facilities', fn (Blueprint $table) => $table->index('name', 'company_facilities_name_index'));
    }

    public function down(): void
    {
        if ($this->hasIndex()) Schema::table('company_facilities', fn (Blueprint $table) => $table->dropIndex('company_facilities_name_index'));
    }

    private function hasIndex(): bool
    {
        if (!in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) return false;
        return DB::table('information_schema.statistics')->where('table_schema', DB::getDatabaseName())->where('table_name', 'company_facilities')
            ->where('index_name', 'company_facilities_name_index')->exists();
    }
};
