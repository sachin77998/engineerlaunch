<?php
namespace App\Support;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class IndustrialSchema
{
    // Supports the older SQLite bundled with XAMPP, which lacks DROP COLUMN.
    public static function dropColumns(string $table, array $columns): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table($table, fn (Blueprint $t) => $t->dropColumn($columns));
            return;
        }
        $definition = DB::selectOne("SELECT sql FROM sqlite_master WHERE type='table' AND name=?", [$table])->sql;
        $body = substr($definition, strpos($definition, '(') + 1, strrpos($definition, ')') - strpos($definition, '(') - 1);
        $parts = preg_split('/,(?![^(]*\))/', $body);
        $parts = array_filter($parts, function ($part) use ($columns) {
            foreach ($columns as $column) {
                if (preg_match('/(?:^\s*|["`\[]|\()'.preg_quote($column, '/').'(?=["`\]\s,)])/i', $part)) return false;
            }
            return true;
        });
        $keep = array_values(array_diff(Schema::getColumnListing($table), $columns));
        $quoted = implode(', ', array_map(fn ($c) => '"'.$c.'"', $keep));
        $indexes = DB::select("SELECT sql FROM sqlite_master WHERE type='index' AND tbl_name=? AND sql IS NOT NULL", [$table]);
        Schema::disableForeignKeyConstraints();
        try {
            DB::transaction(function () use ($table, $parts, $quoted, $indexes, $columns) {
                DB::statement('CREATE TABLE "'.$table.'_rollback" ('.implode(',', $parts).')');
                DB::statement('INSERT INTO "'.$table.'_rollback" ('.$quoted.') SELECT '.$quoted.' FROM "'.$table.'"');
                DB::statement('DROP TABLE "'.$table.'"');
                DB::statement('ALTER TABLE "'.$table.'_rollback" RENAME TO "'.$table.'"');
                foreach ($indexes as $index) {
                    if (!collect($columns)->contains(fn ($c) => str_contains($index->sql, '"'.$c.'"'))) DB::statement($index->sql);
                }
            });
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }
}
