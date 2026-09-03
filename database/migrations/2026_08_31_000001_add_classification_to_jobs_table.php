<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            if (! Schema::hasColumn('jobs', 'department')) {
                $table->string('department', 100)->nullable()->after('category')->index();
            }
            if (! Schema::hasColumn('jobs', 'engineering_discipline')) {
                $table->string('engineering_discipline', 100)->nullable()->after('department')->index();
            }
            if (! Schema::hasColumn('jobs', 'classification_version')) {
                $table->unsignedSmallInteger('classification_version')->default(1)->after('engineering_discipline');
            }
            if (! Schema::hasColumn('jobs', 'classified_at')) {
                $table->timestamp('classified_at')->nullable()->after('classification_version');
            }
        });
    }

    public function down(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            foreach (['classified_at', 'classification_version', 'engineering_discipline', 'department'] as $column) {
                if (Schema::hasColumn('jobs', $column)) $table->dropColumn($column);
            }
        });
    }
};
