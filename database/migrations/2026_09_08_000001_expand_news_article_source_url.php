<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE news_articles DROP INDEX news_articles_source_url_unique');
            DB::statement('ALTER TABLE news_articles MODIFY source_url TEXT NOT NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE news_articles DROP CONSTRAINT IF EXISTS news_articles_source_url_unique');
            DB::statement('ALTER TABLE news_articles ALTER COLUMN source_url TYPE TEXT');
        }
        // SQLite TEXT/VARCHAR columns do not enforce the declared character length.
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE news_articles MODIFY source_url VARCHAR(255) NOT NULL');
            DB::statement('ALTER TABLE news_articles ADD UNIQUE news_articles_source_url_unique (source_url)');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE news_articles ALTER COLUMN source_url TYPE VARCHAR(255)');
            DB::statement('ALTER TABLE news_articles ADD CONSTRAINT news_articles_source_url_unique UNIQUE (source_url)');
        }
    }
};
