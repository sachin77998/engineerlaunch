<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ai_conversations')) {
            return;
        }

        Schema::create('ai_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 255)->nullable();
            $table->string('agent', 50)->nullable();
            $table->string('status', 30)->default('active');
            $table->longText('context')->nullable();
            $table->longText('metadata')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status'],'ai_conversations_user_status_index');
            $table->index(['agent', 'status'],'ai_conversations_agent_status_index');
            $table->index('created_at','ai_conversations_created_at_index');
        });
    }
    public function down()
    {
        Schema::dropIfExists('ai_conversations');
    }
};
