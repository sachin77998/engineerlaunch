<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// What a signed-in candidate searched, viewed, applied to or told the assistant; feeds job recommendations.
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('interest_events')) return;
        Schema::create('interest_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 16);
            $table->string('value', 190);
            $table->unsignedBigInteger('job_id')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interest_events');
    }
};
