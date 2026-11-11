<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Property marketplace: owners, listings, and separate terms tables for rent, sale and PG / flatmates.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_owners', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $t->string('name', 120);
            $t->string('phone', 20);
            $t->boolean('whatsapp')->default(true);
            $t->string('email')->nullable();
            $t->string('owner_type', 10)->default('owner');   // owner, agent, builder
            $t->string('company_name', 160)->nullable();
            $t->string('rera_number', 60)->nullable();
            $t->timestamps();
        });

        Schema::create('properties', function (Blueprint $t) {
            $t->id();
            $t->foreignId('owner_id')->constrained('property_owners')->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('slug', 190)->unique();
            $t->string('title', 200)->nullable();
            $t->string('listing', 4);                         // rent, sale
            $t->string('segment', 12);                        // residential, commercial
            $t->string('kind', 24);                           // full_house, land, pg, flatmate, office_space, shop …
            $t->string('bhk', 6)->nullable();
            $t->string('building_type', 30)->nullable();
            $t->string('age', 6)->nullable();
            $t->string('floor', 20)->nullable();
            $t->string('total_floors', 20)->nullable();
            $t->string('facing', 12)->nullable();
            $t->string('furnishing', 6)->nullable();
            $t->unsignedTinyInteger('bedrooms')->nullable();
            $t->unsignedTinyInteger('bathrooms')->nullable();
            $t->unsignedTinyInteger('balconies')->nullable();
            $t->decimal('area_value', 12, 2)->nullable();
            $t->string('area_unit', 10)->default('sqft');
            $t->decimal('area_sqft', 14, 2)->nullable()->index();
            $t->decimal('carpet_area_sqft', 14, 2)->nullable();
            $t->string('country', 60)->default('India');
            $t->string('currency', 3)->default('INR');
            $t->string('state', 80)->nullable();
            $t->string('city', 80)->nullable();
            $t->string('city_slug', 90)->nullable();
            $t->string('locality', 150)->nullable()->index();
            $t->string('society', 150)->nullable();
            $t->string('landmark', 150)->nullable();
            $t->string('street', 200)->nullable();
            $t->string('pincode', 12)->nullable();
            $t->decimal('latitude', 10, 7)->nullable();
            $t->decimal('longitude', 10, 7)->nullable();
            $t->text('description')->nullable();
            $t->string('directions', 500)->nullable();
            $t->json('features')->nullable();                 // commercial "other features"
            $t->json('highlights')->nullable();
            $t->json('furnishings')->nullable();              // {item: count}
            $t->json('amenities')->nullable();                // residential: [keys]; commercial: {field: value}
            $t->json('details')->nullable();                  // parking, power backup, open sides, flooring, road width …
            $t->string('availability', 10)->nullable();       // everyday, weekday, weekend
            $t->string('visit_start', 5)->nullable();
            $t->string('visit_end', 5)->nullable();
            $t->boolean('visit_all_day')->default(false);
            $t->string('status', 10)->default('draft');       // draft, active, closed, inactive
            $t->json('steps_done')->nullable();
            $t->decimal('price_sort', 16, 2)->nullable();      // rent per month or sale price, for sorting and budget filters
            $t->unsignedInteger('views')->default(0);
            $t->timestamp('published_at')->nullable();
            $t->timestamp('closed_at')->nullable();
            $t->timestamps();
            $t->softDeletes();
            $t->index(['status', 'listing', 'segment', 'city_slug']);
            $t->index(['kind', 'bhk']);
            $t->index('published_at');
        });

        Schema::create('property_rent_terms', function (Blueprint $t) {
            $t->id();
            $t->foreignId('property_id')->unique()->constrained()->cascadeOnDelete();
            $t->decimal('rent', 14, 2);
            $t->boolean('rent_negotiable')->default(false);
            $t->decimal('maintenance', 12, 2)->nullable();
            $t->boolean('maintenance_extra')->default(false);
            $t->decimal('deposit', 14, 2)->nullable();
            $t->boolean('deposit_negotiable')->default(false);
            $t->decimal('brokerage', 12, 2)->nullable();
            $t->string('lease_years', 6)->nullable();
            $t->string('lockin_years', 4)->nullable();
            $t->date('available_from')->nullable();
            $t->json('preferred_tenants')->nullable();
            $t->json('ideal_for')->nullable();
            $t->timestamps();
        });

        Schema::create('property_sale_terms', function (Blueprint $t) {
            $t->id();
            $t->foreignId('property_id')->unique()->constrained()->cascadeOnDelete();
            $t->decimal('price', 16, 2);
            $t->boolean('price_negotiable')->default(false);
            $t->decimal('maintenance', 12, 2)->nullable();
            $t->decimal('brokerage', 12, 2)->nullable();
            $t->string('possession', 20)->default('ready');
            $t->date('possession_by')->nullable();
            $t->boolean('is_new_project')->default(false);
            $t->string('project_name', 160)->nullable();
            $t->string('builder_name', 160)->nullable();
            $t->string('ownership', 12)->nullable();
            $t->json('ideal_for')->nullable();
            $t->timestamps();
            $t->index(['possession', 'is_new_project']);
        });

        Schema::create('property_pg_terms', function (Blueprint $t) {
            $t->id();
            $t->foreignId('property_id')->unique()->constrained()->cascadeOnDelete();
            $t->string('gender', 6)->default('any');
            $t->string('tenant_type', 14)->default('any');
            $t->json('rooms');                                // [{type, rent, deposit, beds_available}]
            $t->json('meals')->nullable();
            $t->json('rules')->nullable();
            $t->json('services')->nullable();
            $t->string('gate_closing', 5)->nullable();
            $t->date('available_from')->nullable();
            $t->timestamps();
        });

        Schema::create('property_media', function (Blueprint $t) {
            $t->id();
            $t->foreignId('property_id')->constrained()->cascadeOnDelete();
            $t->string('type', 6);                            // photo, video
            $t->string('path');
            $t->string('caption', 120)->nullable();
            $t->unsignedSmallInteger('sort')->default(0);
            $t->timestamps();
            $t->index(['property_id', 'type', 'sort']);
        });

        Schema::create('property_leads', function (Blueprint $t) {
            $t->id();
            $t->foreignId('property_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('kind', 10);                           // phone, message, visit
            $t->string('name', 120)->nullable();
            $t->string('phone', 20)->nullable();
            $t->string('email')->nullable();
            $t->text('message')->nullable();
            $t->dateTime('visit_at')->nullable();
            $t->timestamps();
            $t->index(['property_id', 'kind']);
            $t->index(['user_id', 'kind']);
        });

        Schema::create('property_shortlists', function (Blueprint $t) {
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('property_id')->constrained()->cascadeOnDelete();
            $t->timestamp('created_at')->useCurrent();
            $t->primary(['user_id', 'property_id']);
        });
    }

    public function down(): void
    {
        foreach (['property_shortlists', 'property_leads', 'property_media', 'property_pg_terms', 'property_sale_terms', 'property_rent_terms', 'properties', 'property_owners'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
