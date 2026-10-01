<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        if (Schema::hasTable('jobs') && Schema::hasColumn('jobs', 'external_url') && DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE jobs MODIFY external_url TEXT NULL');
        }
    }
    public function down(): void {
        // Keep full application URLs; shrinking would destroy imported links.
    }
};
