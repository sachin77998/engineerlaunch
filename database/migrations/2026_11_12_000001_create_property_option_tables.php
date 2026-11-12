<?php

use App\Services\Property\PropertyOptions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

// Dynamic property data: one table per option list (popt_*), plus cities (with zones) and localities, all seeded from config/properties.php.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_cities', function (Blueprint $t) {
            $t->id();
            $t->string('name', 80);
            $t->string('slug', 90)->unique();
            $t->string('state', 80)->nullable();
            $t->string('country', 60)->default('India');
            $t->json('zones')->nullable(); // e.g. ["Bangalore East", "Bangalore North"]
            $t->string('image_path')->nullable(); // uploaded at /admin/property-cities
            $t->string('image_url', 500)->nullable(); // fetched by property-cities:images (Wikipedia) when nothing is uploaded
            $t->decimal('latitude', 10, 7)->nullable();
            $t->decimal('longitude', 10, 7)->nullable();
            $t->unsignedSmallInteger('sort')->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->index(['country', 'is_active']);
        });
        Schema::create('property_localities', function (Blueprint $t) {
            $t->id();
            $t->foreignId('city_id')->constrained('property_cities')->cascadeOnDelete();
            $t->string('name', 150);
            $t->string('slug', 160);
            $t->string('zone', 80)->nullable();
            $t->decimal('latitude', 10, 7)->nullable();
            $t->decimal('longitude', 10, 7)->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->unique(['city_id', 'slug']);
            $t->index(['city_id', 'zone']);
        });

        $now = now();
        $states = config('rent_agreement.cities', []);
        $sort = 0;
        foreach ((array) config('properties.cities') as $country => $names) {
            foreach ($names as $name) {
                DB::table('property_cities')->insertOrIgnore(['name' => $name, 'slug' => Str::slug($name), 'state' => $states[$name] ?? null, 'country' => $country,
                    'sort' => $sort++, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
            }
        }
        foreach ((array) config('properties.localities') as $city => $names) {
            $cityId = DB::table('property_cities')->where('slug', Str::slug($city))->value('id');
            if (!$cityId) continue;
            foreach ($names as $n) DB::table('property_localities')->insertOrIgnore(['city_id' => $cityId, 'name' => $n, 'slug' => Str::slug($n), 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
        }

        app(PropertyOptions::class)->sync();
    }

    public function down(): void
    {
        foreach (array_keys(config('property_options.groups')) as $group) Schema::dropIfExists(\App\Models\Property\PropertyOption::tableFor($group));
        Schema::dropIfExists('property_localities');
        Schema::dropIfExists('property_cities');
        app(PropertyOptions::class)->forget();
    }
};
