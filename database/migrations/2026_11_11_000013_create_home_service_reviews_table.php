<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// One review per completed home-service booking; shown on the city page and averaged per category.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('home_service_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('home_service_booking_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('category', 20);
            $table->string('city', 80);
            $table->unsignedTinyInteger('rating');
            $table->text('body');
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
            $table->index(['is_visible', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('home_service_reviews');
    }
};
