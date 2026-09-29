<?php
// Run with php -n tests/portable-geography-smoke.php to exclude PDO SQLite.
function resource_path($path) { return dirname(__DIR__).'/resources/'.$path; }
require dirname(__DIR__).'/app/Services/GeographyCatalog.php';
$catalog = new App\Services\GeographyCatalog;
if (count($catalog->table('countries')) !== 250) throw new RuntimeException('Countries missing.');
if (!$catalog->cities('IN', null, 'bangalore')) throw new RuntimeException('City alias missing.');
foreach (range(0, 100) as $i) $catalog->places(['unmatched '.$i]);
if (!$catalog->places(['bangalore'], 'city')) throw new RuntimeException('Shard eviction broke lookups.');
echo 'Portable geography works without php.ini extensions.'.PHP_EOL;
