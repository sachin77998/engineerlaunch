<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Home interiors: the owner's design idea gallery, completed projects, and quote requests (with the visitor's estimate).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interior_ideas', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 140)->unique();
            $table->string('title', 120);
            $table->string('room', 30)->nullable();
            $table->string('style', 30)->nullable();
            $table->string('category', 30)->nullable();
            $table->string('image_path', 255);
            $table->string('description', 600)->nullable();
            $table->boolean('is_trending')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
            $table->index(['is_active', 'room']);
            $table->index(['is_active', 'style']);
        });

        Schema::create('interior_projects', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 140)->unique();
            $table->string('title', 120);
            $table->string('city', 80);
            $table->string('style', 30)->nullable();
            $table->unsignedTinyInteger('bhk')->nullable();
            $table->unsignedTinyInteger('rating')->nullable();
            $table->string('cover_path', 255);
            $table->json('photos')->nullable();
            $table->string('video_url', 255)->nullable();
            $table->text('review')->nullable();
            $table->string('reviewer', 120)->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('interior_leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 120);
            $table->string('phone', 15);
            $table->string('email', 191)->nullable();
            $table->string('city', 80);
            $table->unsignedTinyInteger('bhk')->nullable();
            $table->string('package', 12)->nullable();
            $table->string('possession', 20)->nullable();
            $table->string('style', 30)->nullable();
            $table->json('estimate')->nullable();
            $table->unsignedInteger('estimate_total')->nullable();
            $table->foreignId('interior_idea_id')->nullable()->constrained()->nullOnDelete();
            $table->text('notes')->nullable();
            $table->string('status', 15)->default('new');
            $table->text('admin_note')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interior_leads');
        Schema::dropIfExists('interior_projects');
        Schema::dropIfExists('interior_ideas');
    }
};
