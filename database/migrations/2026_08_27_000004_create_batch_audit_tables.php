<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('batch_runs', function (Blueprint $table) {
            $table->id();
            $table->uuid('queue_batch_id')->nullable()->unique();
            $table->string('batch_type', 80)->index();
            $table->string('name');
            $table->string('status', 30)->default('pending')->index();
            $table->string('triggered_by', 30)->default('console')->index();
            $table->string('command')->nullable();
            $table->string('cron_expression')->nullable();
            $table->string('scheduled_time')->nullable();
            $table->string('host')->nullable();
            $table->unsignedInteger('process_id')->nullable();
            $table->unsignedInteger('total_items')->default(0);
            $table->unsignedInteger('successful_items')->default(0);
            $table->unsignedInteger('failed_items')->default(0);
            $table->unsignedInteger('pending_items')->default(0);
            $table->unsignedBigInteger('records_found')->default(0);
            $table->unsignedBigInteger('records_created')->default(0);
            $table->unsignedBigInteger('records_updated')->default(0);
            $table->string('failure_stage')->nullable();
            $table->text('failure_reason')->nullable();
            $table->text('failed_query')->nullable();
            $table->string('sql_state', 20)->nullable();
            $table->json('context')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
        });

        Schema::create('batch_run_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->string('item_key')->nullable();
            $table->string('item_name')->nullable();
            $table->string('status', 30)->default('pending')->index();
            $table->unsignedTinyInteger('attempt')->default(0);
            $table->unsignedBigInteger('records_found')->default(0);
            $table->unsignedBigInteger('records_created')->default(0);
            $table->unsignedBigInteger('records_updated')->default(0);
            $table->string('failure_stage')->nullable();
            $table->text('failure_reason')->nullable();
            $table->text('failed_query')->nullable();
            $table->string('sql_state', 20)->nullable();
            $table->json('context')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unique(['batch_run_id', 'company_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batch_run_items');
        Schema::dropIfExists('batch_runs');
    }
};
