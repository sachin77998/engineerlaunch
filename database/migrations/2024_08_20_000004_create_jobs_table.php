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
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('location');
            $table->string('country')->default('India');
            $table->decimal('salary_min', 12, 2)->nullable();
            $table->decimal('salary_max', 12, 2)->nullable();
            $table->string('salary_currency')->default('INR');
            $table->string('job_type')->default('Full-time'); // Full-time, Contract, Internship
            $table->text('requirements')->nullable(); // JSON or array of required skills
            $table->text('responsibilities')->nullable();
            $table->string('experience_level')->nullable(); // Entry, Mid, Senior, Lead
            $table->string('external_url')->nullable(); // Link to company's job posting
            $table->timestamp('posted_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('scraped_at')->nullable();
            $table->integer('views')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('company_id');
            $table->index('country');
            $table->index('location');
            $table->index('is_active');
            $table->index('posted_at');
            $table->fullText(['title', 'description']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jobs');
    }
};
