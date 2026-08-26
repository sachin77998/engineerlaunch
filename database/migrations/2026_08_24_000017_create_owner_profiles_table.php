<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('owner_profiles')) {
            Schema::create('owner_profiles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
                $table->string('phone', 30)->nullable();
                $table->string('designation')->default('Platform Owner');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('owner_profiles');
    }
};
