<?php
if (PHP_SAPI !== 'cli') exit;
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
$connection = config('database.connections.mysql');
if (!in_array($connection['host'], ['localhost','127.0.0.1'], true)) throw new RuntimeException('This check is local-only.');
$source = $connection['database'];
$test = 'discovery_import_test_'.bin2hex(random_bytes(4));
$pdo = DB::connection('mysql')->getPdo();
$quote = fn ($name) => chr(96).str_replace(chr(96), chr(96).chr(96), $name).chr(96);
$pdo->exec('CREATE DATABASE '.$quote($test).' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
try {
    foreach (['companies','jobs','technologies','job_technology'] as $table) {
        $pdo->exec('CREATE TABLE '.$quote($test).'.'.$quote($table).' LIKE '.$quote($source).'.'.$quote($table));
    }
    $pdo->exec('USE '.$quote($test));
    foreach ([1,2] as $run) {
        foreach (['jobs-and-skills.sql','career-explorer.sql'] as $file) {
        $stream = fopen(base_path('release/job-discovery/'.$file), 'rb');
        while (($statement = fgets($stream)) !== false) {
            if (trim($statement) !== '' && !str_starts_with(ltrim($statement), '--')) $pdo->exec($statement);
        }
        fclose($stream);
        }
        $counts = [];
        foreach (['companies','jobs','technologies','job_technology','career_tracks','career_roles','career_salary_benchmarks','company_facilities'] as $table) $counts[$table] = (int) $pdo->query('SELECT COUNT(*) FROM '.$quote($table))->fetchColumn();
        if ($run === 1) $initial = $counts;
        elseif ($initial !== $counts) throw new RuntimeException('Second import changed row counts.');
        echo 'Import '.$run.': '.json_encode($counts).PHP_EOL;
    }
    $orphans = $pdo->query('SELECT COUNT(*) FROM job_technology jt LEFT JOIN jobs j ON j.id=jt.job_id LEFT JOIN technologies t ON t.id=jt.technology_id WHERE j.id IS NULL OR t.id IS NULL')->fetchColumn();
    if ((int) $orphans !== 0) throw new RuntimeException('Orphaned skill links found.');
    echo "PASS: SQL imports twice without duplicates or orphaned skill links.\n";
} finally {
    $pdo->exec('USE '.$quote($source));
    if (!preg_match('/^discovery_import_test_[a-f0-9]{8}$/', $test)) throw new RuntimeException('Unexpected test database name.');
    $pdo->exec('DROP DATABASE '.$quote($test));
}
