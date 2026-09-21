<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ai_messages')) {
            return;
        }

        Schema::create('ai_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('ai_conversations')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('role', 20);
            $table->longText('message');
            $table->string('agent', 50)->nullable();
            $table->longText('payload')->nullable();
            $table->longText('sources')->nullable();
            $table->longText('metadata')->nullable();
            $table->timestamps();
            $table->index(['conversation_id', 'created_at'],'ai_messages_conversation_created_index');
            $table->index(['role', 'created_at'],'ai_messages_role_created_index');
            $table->index(['agent'],'ai_messages_agent_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_messages');
    }
};
