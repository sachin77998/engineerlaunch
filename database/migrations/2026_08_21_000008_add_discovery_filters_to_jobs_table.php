<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->string('posting_source', 30)->default('official_company')->after('job_type')->index();
            $table->string('work_mode', 30)->nullable()->after('posting_source')->index();
            $table->unsignedTinyInteger('experience_min')->nullable()->after('experience_level')->index();
            $table->unsignedTinyInteger('experience_max')->nullable()->after('experience_min');
            $table->string('role_family', 80)->nullable()->after('experience_max')->index();
        });
    }

    public function down(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropIndex(['posting_source']);
            $table->dropIndex(['work_mode']);
            $table->dropIndex(['experience_min']);
            $table->dropIndex(['role_family']);
            $table->dropColumn(['posting_source', 'work_mode', 'experience_min', 'experience_max', 'role_family']);
        });
    }
};
