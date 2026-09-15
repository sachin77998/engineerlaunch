<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('industrial_role_profiles')) {
            return;
        }

        Schema::create('industrial_role_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_role_id')->constrained('industrial_job_roles')->cascadeOnDelete();
            $table->longText('skills')->nullable();
            $table->longText('qualifications')->nullable();
            $table->text('summary')->nullable();
            $table->string('source_name')->nullable();
            $table->timestamps();
            $table->unique('job_role_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('industrial_role_profiles');
    }
};
