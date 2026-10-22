<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('money_profiles')) Schema::create('money_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->json('data');
            $table->timestamps();
        });
        // Weekly NAV (last NAV of each week, 10 years) used to train the fund model; small compared with daily data.
        if (!Schema::hasTable('mf_nav_weekly')) Schema::create('mf_nav_weekly', function (Blueprint $table) {
            $table->unsignedInteger('scheme_code');
            $table->date('week_date');
            $table->decimal('nav', 14, 4);
            $table->primary(['scheme_code', 'week_date']);
        });
        if (!Schema::hasTable('mf_model_scores')) Schema::create('mf_model_scores', function (Blueprint $table) {
            $table->unsignedInteger('scheme_code')->primary();
            $table->string('category', 120)->index();
            $table->decimal('predicted_excess', 8, 4)->nullable();
            $table->unsignedTinyInteger('score')->nullable();
            $table->string('signal', 20)->index();
            $table->json('reasons')->nullable();
            $table->json('features')->nullable();
            $table->date('as_of');
            $table->timestamp('history_fetched_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['mf_model_scores', 'mf_nav_weekly', 'money_profiles'] as $t) Schema::dropIfExists($t);
    }
};
