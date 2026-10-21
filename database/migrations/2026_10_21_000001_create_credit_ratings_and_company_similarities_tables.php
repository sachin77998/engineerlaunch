<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Credit ratings read from rating-agency rationales (ICRA daily releases, CARE company press releases), the agencies'
 * company lookups, and the company recommendation model (similar companies, page views for "viewed together").
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('credit_ratings')) Schema::create('credit_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->string('agency', 20);
            $table->string('source_key', 64);
            $table->string('entity_name', 255);
            $table->string('name_key', 190)->index();
            $table->string('title', 500)->nullable();
            $table->date('published_on')->nullable()->index();
            $table->string('source_url', 500)->nullable();
            $table->string('action', 30)->nullable();
            $table->string('rating_long', 40)->nullable();
            $table->string('rating_short', 40)->nullable();
            $table->string('outlook', 40)->nullable();
            $table->string('previous_rating_long', 40)->nullable();
            $table->unsignedTinyInteger('score')->nullable();
            $table->decimal('total_rated_crore', 18, 2)->nullable();
            $table->string('liquidity', 20)->nullable();
            $table->boolean('withdrawn')->default(false);
            $table->boolean('non_cooperating')->default(false);
            $table->json('instruments')->nullable();
            $table->json('debt_instruments')->nullable();
            $table->json('financials')->nullable();
            $table->json('strengths')->nullable();
            $table->json('challenges')->nullable();
            $table->json('sensitivities')->nullable();
            $table->text('about')->nullable();
            $table->string('parse_status', 20)->default('parsed');
            $table->timestamps();
            $table->unique(['agency', 'source_key']);
            $table->index(['company_id', 'published_on']);
        });

        // One row per company and agency: the agency's own company id, its current ratings and when it was last checked.
        if (!Schema::hasTable('credit_rating_sources')) Schema::create('credit_rating_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('agency', 20);
            $table->string('agency_company_id', 64)->nullable();
            $table->string('agency_name', 255)->nullable();
            $table->json('current')->nullable();
            $table->json('lenders')->nullable();
            $table->timestamp('checked_at')->nullable()->index();
            $table->timestamps();
            $table->unique(['company_id', 'agency']);
        });

        if (!Schema::hasTable('company_similarities')) Schema::create('company_similarities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('similar_company_id')->constrained('companies')->cascadeOnDelete();
            $table->float('score');
            $table->json('reasons')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'similar_company_id']);
            $table->index(['company_id', 'score']);
        });

        // Company pages opened per visitor and day; feeds the "viewed together" (item-item collaborative) signal.
        if (!Schema::hasTable('company_page_views')) Schema::create('company_page_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('visitor', 64);
            $table->date('viewed_on');
            $table->unique(['visitor', 'company_id', 'viewed_on']);
            $table->index(['viewed_on', 'visitor']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_page_views');
        Schema::dropIfExists('company_similarities');
        Schema::dropIfExists('credit_rating_sources');
        Schema::dropIfExists('credit_ratings');
    }
};
