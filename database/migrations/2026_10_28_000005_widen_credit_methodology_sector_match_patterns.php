<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Sector name patterns with exclusions (construction contractors, construction equipment makers) run past 255 characters.
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE credit_methodology_sectors MODIFY match_industry VARCHAR(1000) NULL, MODIFY match_name VARCHAR(1000) NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE credit_methodology_sectors MODIFY match_industry VARCHAR(255) NULL, MODIFY match_name VARCHAR(255) NULL');
    }
};
