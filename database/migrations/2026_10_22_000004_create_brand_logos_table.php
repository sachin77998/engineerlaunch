<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One stored logo per website domain, shared by companies, listed stocks and fund houses.
        if (!Schema::hasTable('brand_logos')) Schema::create('brand_logos', function (Blueprint $table) {
            $table->string('domain', 190)->primary();
            $table->string('path', 255)->nullable();
            $table->string('status', 10)->index();
            $table->timestamp('checked_at')->nullable();
        });
        if (!Schema::hasColumn('market_instruments', 'logo_domain')) Schema::table('market_instruments', function (Blueprint $table) {
            $table->string('logo_domain', 190)->nullable()->after('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brand_logos');
        if (Schema::hasColumn('market_instruments', 'logo_domain')) Schema::table('market_instruments', fn (Blueprint $t) => $t->dropColumn('logo_domain'));
    }
};
