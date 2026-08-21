<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('website')->nullable();
            $table->string('careers_url')->nullable();
            $table->string('country')->default('India');
            $table->string('logo_url')->nullable();
            $table->string('industry')->nullable(); // Tech, Banking, IT Services, etc.
            $table->string('sector')->nullable(); // Software, Hardware, Cybersecurity, etc.
            $table->integer('employee_count')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index('is_active');
            $table->index('country');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
