<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Home Building store: the site sells the catalogue itself. Orders keep a copy of every line (title, pack, price) as it
// was when placed; payments go through the shared payments table (payable_type = material_order).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('materials', function (Blueprint $t) {
            $t->longText('description')->nullable()->after('summary');
            $t->json('images')->nullable()->after('image_url');
            $t->decimal('sell_price', 12, 2)->nullable()->after('mrp');  // owner's own price; empty = catalogue price
            $t->boolean('sellable')->default(true)->after('sell_price');
        });

        Schema::create('material_orders', function (Blueprint $t) {
            $t->id();
            $t->string('order_no', 24)->unique();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('status', 20)->default('created')->index(); // created | confirmed | packed | shipped | delivered | cancelled
            $t->string('payment_method', 10);                       // paytm | cod
            $t->string('payment_status', 12)->default('pending');   // pending | paid | refunded | failed
            $t->decimal('subtotal', 12, 2);
            $t->decimal('bulk_saving', 12, 2)->default(0);
            $t->decimal('discount', 12, 2)->default(0);
            $t->decimal('delivery_fee', 12, 2)->default(0);
            $t->decimal('total', 12, 2);
            $t->string('name', 120);
            $t->string('phone', 15);
            $t->string('address_line1', 200);
            $t->string('address_line2', 200)->nullable();
            $t->string('city', 80);
            $t->string('state', 60);
            $t->string('pincode', 6);
            $t->string('slot', 40)->nullable();
            $t->string('notes', 500)->nullable();
            $t->string('tracking', 200)->nullable();
            $t->timestamp('paid_at')->nullable();
            $t->timestamp('cancelled_at')->nullable();
            $t->timestamp('delivered_at')->nullable();
            $t->timestamps();
        });

        Schema::create('material_order_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('material_order_id')->constrained()->cascadeOnDelete();
            $t->foreignId('material_id')->nullable()->constrained()->nullOnDelete();
            $t->string('handle', 191);
            $t->string('title');
            $t->string('variant_title', 191)->nullable();
            $t->string('image_url', 500)->nullable();
            $t->unsignedInteger('qty');
            $t->decimal('unit_price', 12, 2);
            $t->decimal('mrp', 12, 2)->nullable();
            $t->decimal('total', 12, 2);
        });

        Schema::create('material_reviews', function (Blueprint $t) {
            $t->id();
            $t->foreignId('material_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->unsignedTinyInteger('rating');
            $t->string('title', 120)->nullable();
            $t->text('body')->nullable();
            $t->boolean('verified')->default(false);   // reviewer has a delivered order with this product
            $t->string('status', 12)->default('published'); // published | hidden
            $t->timestamps();
            $t->unique(['material_id', 'user_id']);
            $t->index(['material_id', 'status']);
        });

        Schema::create('material_feedback', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('material_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('material_order_id')->nullable()->constrained()->nullOnDelete();
            $t->string('kind', 20);            // question | issue | suggestion | delivery
            $t->string('name', 120)->nullable();
            $t->string('email', 191)->nullable();
            $t->text('message');
            $t->text('reply')->nullable();
            $t->string('status', 12)->default('open'); // open | answered | closed
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_feedback');
        Schema::dropIfExists('material_reviews');
        Schema::dropIfExists('material_order_items');
        Schema::dropIfExists('material_orders');
        Schema::table('materials', fn (Blueprint $t) => $t->dropColumn(['description', 'images', 'sell_price', 'sellable']));
    }
};
