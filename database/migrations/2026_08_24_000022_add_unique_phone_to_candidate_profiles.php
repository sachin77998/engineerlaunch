<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('candidate_profiles', function (Blueprint $table) {
            $table->unique('phone', 'candidate_profiles_phone_unique');
        });
    }

    public function down(): void
    {
        Schema::table('candidate_profiles', function (Blueprint $table) {
            $table->dropUnique('candidate_profiles_phone_unique');
        });
    }
};
