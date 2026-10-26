<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Fee type and the department's one-paragraph description of the approval (published by NSWS).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('state_approval_services', function (Blueprint $table) {
            $table->string('fee', 255)->nullable()->after('timeline');
            $table->text('description')->nullable()->after('fee');
        });
    }

    public function down(): void
    {
        Schema::table('state_approval_services', fn (Blueprint $table) => $table->dropColumn(['fee', 'description']));
    }
};
