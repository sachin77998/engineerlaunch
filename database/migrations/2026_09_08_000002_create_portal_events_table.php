<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portal_events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('event_type')->default('portal');
            $table->string('image_url', 2048)->nullable();
            $table->string('cta_text')->nullable();
            $table->string('cta_url', 2048)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('email_enabled')->default(true);
            $table->dateTime('scheduled_at')->nullable()->index();
            $table->dateTime('sent_at')->nullable();
            $table->unsignedInteger('target_users')->default(100);
            $table->unsignedInteger('emails_sent')->default(0);
            $table->unsignedInteger('emails_failed')->default(0);
            $table->timestamps();
            $table->index(['is_active', 'email_enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portal_events');
    }
};
