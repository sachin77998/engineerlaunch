<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_profile_old_data', function (Blueprint $table) {
            $table->id();
            $table->uuid('change_id')->unique();
            $table->foreignId('candidate_profile_id')->constrained('candidate_profiles')->cascadeOnDelete();
            $table->foreignId('changed_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->json('profile_data');
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('candidate_profile_new_data', function (Blueprint $table) {
            $table->id();
            $table->uuid('change_id')->unique();
            $table->foreignId('candidate_profile_id')->constrained('candidate_profiles')->cascadeOnDelete();
            $table->foreignId('changed_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->json('profile_data');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_profile_new_data');
        Schema::dropIfExists('candidate_profile_old_data');
    }
};
