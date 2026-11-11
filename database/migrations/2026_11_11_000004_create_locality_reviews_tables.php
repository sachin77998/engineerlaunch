<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Locality reviews by residents: an overall rating, four feature ratings, positives and negatives, and helpful votes.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locality_reviews', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('city', 80);
            $t->string('city_slug', 90);
            $t->string('locality', 150);
            $t->string('locality_key', 160);
            $t->string('society', 150)->nullable();
            $t->string('reviewer_role', 16);
            $t->unsignedTinyInteger('rating');
            $t->unsignedTinyInteger('connectivity');
            $t->unsignedTinyInteger('lifestyle');
            $t->unsignedTinyInteger('safety');
            $t->unsignedTinyInteger('green_area');
            $t->text('positives')->nullable();
            $t->text('negatives')->nullable();
            $t->unsignedInteger('helpful_yes')->default(0);
            $t->unsignedInteger('helpful_no')->default(0);
            $t->string('status', 10)->default('published');   // published, hidden
            $t->timestamps();
            $t->unique(['user_id', 'city_slug', 'locality_key']);
            $t->index(['city_slug', 'locality_key', 'status']);
        });
        Schema::create('locality_review_votes', function (Blueprint $t) {
            $t->foreignId('locality_review_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->boolean('helpful');
            $t->timestamp('created_at')->useCurrent();
            $t->primary(['locality_review_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locality_review_votes');
        Schema::dropIfExists('locality_reviews');
    }
};
