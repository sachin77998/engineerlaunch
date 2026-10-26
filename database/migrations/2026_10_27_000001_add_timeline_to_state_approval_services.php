<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Portals that publish the statutory timeline and procedure as text (not a PDF link).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('state_approval_services', function (Blueprint $table) {
            $table->string('timeline', 255)->nullable()->after('timeline_url');
            $table->text('procedure')->nullable()->after('timeline');
            $table->string('apply_url', 500)->nullable()->after('procedure');
        });
    }

    public function down(): void
    {
        Schema::table('state_approval_services', fn (Blueprint $table) => $table->dropColumn(['timeline', 'procedure', 'apply_url']));
    }
};
