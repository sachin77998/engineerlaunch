<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** "Is this section helpful?" answers on company pages. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insights_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('section', 40);
            $table->boolean('helpful');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('visitor', 64)->nullable();
            $table->timestamps();
            $table->index(['section', 'helpful']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insights_feedback');
    }
};
