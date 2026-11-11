<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// "Property sold out? Incorrect data? Report": visitor reports the site owner reviews.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_reports', function (Blueprint $t) {
            $t->id();
            $t->foreignId('property_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('reason', 20);
            $t->string('details', 1000)->nullable();
            $t->string('status', 10)->default('open');     // open, resolved
            $t->timestamps();
            $t->index(['property_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_reports');
    }
};
