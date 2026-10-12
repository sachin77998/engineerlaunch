<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Monthly vehicle sales by model (wholesale = SIAM/company dispatches, retail = FADA/registrations) and commodity prices.
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('vehicle_sales')) {
            Schema::create('vehicle_sales', function (Blueprint $t) {
                $t->id();
                $t->string('segment', 30);              // car, two-wheeler, tractor, commercial, ev
                $t->string('brand', 80);
                $t->string('model', 120);
                $t->date('month');                      // first day of the month
                $t->unsignedInteger('wholesale_units')->nullable();
                $t->unsignedInteger('retail_units')->nullable();
                $t->unsignedInteger('export_units')->nullable();
                $t->string('source', 120)->nullable();
                $t->string('source_url', 500)->nullable();
                $t->timestamps();
                $t->unique(['segment', 'brand', 'model', 'month']);
                $t->index(['brand', 'month']);
            });
        }
        if (!Schema::hasTable('commodity_prices')) {
            Schema::create('commodity_prices', function (Blueprint $t) {
                $t->id();
                $t->string('commodity', 40);            // gold, silver, copper, steel, sand
                $t->string('variant', 40)->default(''); // 24K, 22K, 999, TMT Fe500
                $t->string('unit', 20);                 // gram, 10 gram, kg, tonne, cft
                $t->decimal('price', 14, 2);
                $t->string('currency', 3)->default('INR');
                $t->string('city', 60)->default('');
                $t->date('priced_on');
                $t->string('source', 120)->nullable();
                $t->timestamps();
                $t->unique(['commodity', 'variant', 'unit', 'city', 'priced_on'], 'commodity_price_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_sales');
        Schema::dropIfExists('commodity_prices');
    }
};
