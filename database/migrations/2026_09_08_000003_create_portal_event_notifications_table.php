<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portal_event_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portal_event_id')->constrained('portal_events')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('email');
            $table->string('status', 20)->default('pending');
            $table->text('error_message')->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->timestamps();
            $table->unique(['portal_event_id', 'user_id']);
            $table->index(['portal_event_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portal_event_notifications');
    }
};
