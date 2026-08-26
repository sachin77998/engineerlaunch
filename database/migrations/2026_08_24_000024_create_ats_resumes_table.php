<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('ats_resumes')) return;
        Schema::create('ats_resumes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('full_name',120);
            $table->string('headline',150)->nullable();
            $table->string('email',190);
            $table->string('phone',30)->nullable();
            $table->string('location',150)->nullable();
            $table->text('summary')->nullable();
            $table->json('skills')->nullable();
            $table->json('experience')->nullable();
            $table->json('education')->nullable();
            $table->json('links')->nullable();
            $table->string('template',30)->default('ats-classic');
            $table->unsignedTinyInteger('completeness')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('ats_resumes'); }
};
