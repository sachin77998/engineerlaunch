<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Companies listed on NSE (equity list) and their link to the MCA register and company pages. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listed_companies', function (Blueprint $table) {
            $table->id();
            $table->string('symbol', 30)->unique();
            $table->string('isin', 12)->nullable()->index();
            $table->string('name');
            $table->string('series', 5)->nullable();
            $table->date('listed_on')->nullable();
            $table->decimal('face_value', 12, 2)->nullable();
            $table->char('cin', 21)->nullable()->index();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('last_price', 14, 2)->nullable();
            $table->unsignedSmallInteger('years_imported')->default(0);
            $table->timestamp('synced_at')->nullable();
            $table->string('sync_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listed_companies');
    }
};
