<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('company_categories')) {
            Schema::create('company_categories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('parent_id')->nullable()->constrained('company_categories')->nullOnDelete();
                $table->string('name', 120);
                $table->string('slug', 140)->unique();
                $table->string('taxonomy', 30)->index();
                $table->string('symbol', 12)->nullable();
                $table->string('description', 255)->nullable();
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
                $table->index(['taxonomy', 'parent_id', 'sort_order']);
            });
        }

        if (! Schema::hasTable('company_category_company')) {
            Schema::create('company_category_company', function (Blueprint $table) {
                $table->foreignId('company_category_id')->constrained()->cascadeOnDelete();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->timestamps();
                $table->primary(['company_category_id', 'company_id']);
                $table->index(['company_id', 'company_category_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('company_category_company');
        Schema::dropIfExists('company_categories');
    }
};
