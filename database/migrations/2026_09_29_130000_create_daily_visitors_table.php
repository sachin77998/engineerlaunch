<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('daily_visitors', function (Blueprint $table) {
            $table->id();
            $table->date('visit_date')->index();
            $table->string('visitor_key', 64);
            $table->unsignedInteger('page_views')->default(0);
            $table->string('country_code', 2)->nullable();
            $table->string('state', 150)->nullable();
            $table->dateTime('first_seen_at');
            $table->dateTime('last_seen_at');
            $table->unique(['visit_date', 'visitor_key']);
        });
    }
    public function down(): void { Schema::dropIfExists('daily_visitors'); }
};
