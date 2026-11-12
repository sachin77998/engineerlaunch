<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Projects (buildings) with photo galleries, commercial listing details and facilities, nearby places,
// the domain-event outbox, and visitor activity logs (views and searches) that power recommendations and demand.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('builders', function (Blueprint $t) {
            $t->id();
            $t->string('slug', 190)->unique();
            $t->string('name', 160);
            $t->string('logo_path')->nullable();
            $t->string('website', 191)->nullable();
            $t->unsignedSmallInteger('established_year')->nullable();
            $t->text('description')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('projects', function (Blueprint $t) {
            $t->id();
            $t->string('slug', 190)->unique();
            $t->string('name', 160);
            $t->foreignId('builder_id')->nullable()->constrained()->nullOnDelete();
            $t->string('segment', 12)->default('residential');
            $t->string('city', 80);
            $t->string('city_slug', 90);
            $t->string('locality', 150)->nullable();
            $t->string('address', 300)->nullable();
            $t->decimal('latitude', 10, 7)->nullable();
            $t->decimal('longitude', 10, 7)->nullable();
            $t->string('status', 20)->default('under_construction'); // popt_project_statuses
            $t->string('rera_number', 80)->nullable();
            $t->string('rera_website', 191)->nullable();
            $t->date('launched_on')->nullable();
            $t->date('possession_on')->nullable();
            $t->decimal('price_min', 16, 2)->nullable();
            $t->decimal('price_max', 16, 2)->nullable();
            $t->json('configurations')->nullable(); // ["2 BHK Apartment", "3 BHK Apartment"]
            $t->json('amenities')->nullable();      // popt_amenities keys
            $t->unsignedInteger('total_units')->nullable();
            $t->unsignedSmallInteger('towers')->nullable();
            $t->decimal('land_acres', 10, 2)->nullable();
            $t->text('description')->nullable();
            $t->boolean('is_featured')->default(false);
            $t->boolean('is_active')->default(true);
            $t->unsignedInteger('views')->default(0);
            $t->timestamps();
            $t->index(['city_slug', 'is_active', 'status']);
            $t->index(['is_featured', 'is_active']);
        });
        Schema::create('project_images', function (Blueprint $t) {
            $t->id();
            $t->foreignId('project_id')->constrained()->cascadeOnDelete();
            $t->string('path');
            $t->string('kind', 20)->default('exterior'); // popt_image_kinds
            $t->string('caption', 160)->nullable();
            $t->boolean('is_cover')->default(false);
            $t->unsignedSmallInteger('sort')->default(0);
            $t->timestamps();
            $t->index(['project_id', 'is_cover', 'sort']);
        });
        Schema::create('project_offers', function (Blueprint $t) {
            $t->id();
            $t->foreignId('project_id')->constrained()->cascadeOnDelete();
            $t->string('title', 160);
            $t->string('details', 500)->nullable();
            $t->date('valid_until')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->index(['project_id', 'is_active']);
        });
        Schema::create('project_view_days', function (Blueprint $t) {
            $t->id();
            $t->foreignId('project_id')->constrained()->cascadeOnDelete();
            $t->date('day');
            $t->unsignedInteger('views')->default(0);
            $t->unique(['project_id', 'day']);
        });
        Schema::table('properties', function (Blueprint $t) {
            $t->foreignId('project_id')->nullable()->after('owner_id')->constrained()->nullOnDelete();
        });

        Schema::create('property_commercial_details', function (Blueprint $t) {
            $t->id();
            $t->foreignId('property_id')->unique()->constrained()->cascadeOnDelete();
            $t->string('office_type', 20)->nullable();       // popt_office_types
            $t->string('situated_in', 30)->nullable();       // popt_situated_in
            $t->string('transaction_type', 10)->nullable();  // popt_transaction_types
            $t->unsignedInteger('seats_min')->nullable();
            $t->unsignedInteger('seats_max')->nullable();
            $t->unsignedSmallInteger('cabins')->nullable();
            $t->unsignedSmallInteger('meeting_rooms')->nullable();
            $t->string('washrooms', 120)->nullable();
            $t->string('car_parking', 160)->nullable();
            $t->boolean('is_pre_leased')->default(false);
            $t->decimal('lease_amount', 14, 2)->nullable();  // per month
            $t->decimal('lease_tenure_years', 5, 1)->nullable();
            $t->string('tenant_name', 160)->nullable();
            $t->string('rera_status', 20)->nullable();       // registered | not_available | not_required
            $t->string('rera_number', 80)->nullable();
            $t->timestamps();
            $t->index(['is_pre_leased', 'office_type']);
        });
        Schema::create('property_facilities', function (Blueprint $t) {
            $t->id();
            $t->foreignId('property_id')->constrained()->cascadeOnDelete();
            $t->string('facility', 40); // popt_facilities key
            $t->unique(['property_id', 'facility']);
            $t->index('facility');
        });
        Schema::create('nearby_places', function (Blueprint $t) {
            $t->id();
            $t->string('geo_key', 40); // lat,lng rounded to ~100 m — shared by listings and projects at the same spot
            $t->string('category', 30); // popt_nearby_categories key
            $t->string('name', 191);
            $t->decimal('latitude', 10, 7);
            $t->decimal('longitude', 10, 7);
            $t->unsignedInteger('distance_m');
            $t->string('source_id', 40)->nullable();
            $t->timestamp('fetched_at');
            $t->index(['geo_key', 'distance_m']);
        });

        Schema::create('domain_events', function (Blueprint $t) {
            $t->id();
            $t->uuid('uuid')->unique();
            $t->string('topic', 80);
            $t->string('key', 120)->nullable();
            $t->json('payload');
            $t->timestamp('occurred_at');
            $t->timestamp('published_at')->nullable();
            $t->string('transport', 20)->nullable();
            $t->unsignedTinyInteger('attempts')->default(0);
            $t->timestamp('retry_at')->nullable();
            $t->string('last_error', 500)->nullable();
            $t->timestamps();
            $t->index(['published_at', 'id']);
            $t->index(['topic', 'occurred_at']);
        });
        Schema::create('property_view_logs', function (Blueprint $t) {
            $t->id();
            $t->string('visitor', 80);
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('property_id')->constrained()->cascadeOnDelete();
            $t->string('city_slug', 90)->nullable();
            $t->string('listing', 4)->nullable();
            $t->string('home_type', 30)->nullable();
            $t->decimal('price', 16, 2)->nullable();
            $t->timestamp('created_at')->useCurrent();
            $t->index(['visitor', 'created_at']);
        });
        Schema::create('property_search_logs', function (Blueprint $t) {
            $t->id();
            $t->string('visitor', 80);
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('mode', 20);
            $t->string('city_slug', 90)->nullable();
            $t->string('locality', 150)->nullable();
            $t->string('home_type', 30)->nullable();
            $t->string('label', 191);
            $t->string('url', 600);
            $t->timestamp('created_at')->useCurrent();
            $t->index(['visitor', 'created_at']);
            $t->index(['city_slug', 'home_type', 'created_at']);
        });
    }

    public function down(): void
    {
        foreach (['property_search_logs', 'property_view_logs', 'domain_events', 'nearby_places', 'property_facilities', 'property_commercial_details'] as $t) Schema::dropIfExists($t);
        Schema::table('properties', function (Blueprint $t) { $t->dropConstrainedForeignId('project_id'); });
        foreach (['project_view_days', 'project_offers', 'project_images', 'projects', 'builders'] as $t) Schema::dropIfExists($t);
    }
};
