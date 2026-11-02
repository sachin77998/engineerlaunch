<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Home Building: construction and interior materials read from a public store catalogue (materials:sync), one row per
// product, and one price row per product per day so price lists can show what changed.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materials', function (Blueprint $t) {
            $t->id();
            $t->string('source', 30)->default('homerun');
            $t->string('handle', 191)->unique();
            $t->string('title');
            $t->string('brand', 100)->nullable()->index();
            $t->string('brand_slug', 120)->nullable()->index();
            $t->string('product_type', 120)->nullable()->index();
            $t->string('category', 40)->nullable()->index();   // first matching group in config('materials.categories')
            $t->json('collections')->nullable();                // every collection handle the store lists it under
            $t->json('tags')->nullable();
            $t->decimal('price', 12, 2)->nullable();            // cheapest available variant
            $t->decimal('mrp', 12, 2)->nullable();
            $t->decimal('unit_size', 10, 3)->nullable();        // pack size of that variant, e.g. 50 (kg) or 20 (L)
            $t->string('unit', 8)->nullable();                  // kg | L | m
            $t->boolean('in_stock')->default(false);
            $t->unsignedInteger('quantity_available')->nullable();
            $t->json('variants')->nullable();                   // [{title, price, mrp, size, unit, qty, available}]
            $t->json('bulk_tiers')->nullable();                 // [{qty, price}]
            $t->string('image_url', 500)->nullable();
            $t->string('summary', 500)->nullable();
            $t->boolean('is_listed')->default(true);
            $t->timestamp('source_created_at')->nullable();
            $t->timestamp('source_updated_at')->nullable();
            $t->timestamp('sitemap_lastmod')->nullable();
            $t->timestamps();
            $t->index(['is_listed', 'category']);
        });

        Schema::create('material_prices', function (Blueprint $t) {
            $t->id();
            $t->foreignId('material_id')->constrained()->cascadeOnDelete();
            $t->date('recorded_on');
            $t->decimal('price', 12, 2);
            $t->decimal('mrp', 12, 2)->nullable();
            $t->unique(['material_id', 'recorded_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_prices');
        Schema::dropIfExists('materials');
    }
};
