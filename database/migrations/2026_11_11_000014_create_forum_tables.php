<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Property forum: questions, answers, answer upvotes and reports (questions or answers).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forum_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('category', 30);
            $table->string('title', 200);
            $table->string('slug', 200)->nullable();
            $table->text('body')->nullable();
            $table->string('city', 80)->nullable();
            $table->unsignedInteger('views')->default(0);
            $table->unsignedInteger('answers_count')->default(0);
            $table->timestamp('last_answered_at')->nullable();
            $table->boolean('is_hidden')->default(false);
            $table->timestamps();
            $table->index(['category', 'is_hidden', 'last_answered_at']);
            $table->index(['is_hidden', 'answers_count', 'created_at']);
        });

        Schema::create('forum_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('forum_question_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->unsignedInteger('upvotes')->default(0);
            $table->boolean('is_best')->default(false);
            $table->boolean('is_hidden')->default(false);
            $table->timestamps();
            $table->index(['forum_question_id', 'is_hidden']);
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('forum_answer_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('forum_answer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['forum_answer_id', 'user_id']);
        });

        Schema::create('forum_reports', function (Blueprint $table) {
            $table->id();
            $table->string('target_type', 10); // question | answer
            $table->unsignedBigInteger('target_id');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('reason', 20);
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->unique(['target_type', 'target_id', 'user_id']);
            $table->index(['resolved_at', 'target_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forum_reports');
        Schema::dropIfExists('forum_answer_votes');
        Schema::dropIfExists('forum_answers');
        Schema::dropIfExists('forum_questions');
    }
};
