<?php
// Local CLI export only. Never place this script in public/.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Company;
use App\Models\Job;
use App\Models\Technology;

$directory = base_path('release/job-discovery');
if (!is_dir($directory)) mkdir($directory, 0775, true);
$path = $directory.'/jobs-and-skills.sql';
$out = fopen($path, 'wb');
function literal($value): string {
    if ($value === null) return 'NULL';
    if (is_bool($value)) return $value ? '1' : '0';
    if (is_int($value) || is_float($value)) return (string) $value;
    if ($value === '') return "''";
    return "CONVERT(0x".bin2hex((string) $value)." USING utf8mb4)";
}
function emitSql(string $statement): void { global $out; fwrite($out, $statement."\n"); }
function columns(array $values): string { return implode(',', array_keys($values)); }
function values(array $values): string { return implode(',', array_map('literal', array_values($values))); }

emitSql("-- Verified official-feed snapshot. Import into the live application's database with phpMyAdmin.");
emitSql("-- Re-runnable: matches companies by name/slug and jobs by company + official URL; no unrelated rows are deleted.");
emitSql("SET NAMES utf8mb4;");
emitSql("START TRANSACTION;");
foreach (Technology::orderBy('id')->get() as $technology) {
    $data = $technology->only(['name','slug','category']);
    emitSql("INSERT INTO technologies (".columns($data).",created_at,updated_at) VALUES (".values($data).",NOW(),NOW()) ON DUPLICATE KEY UPDATE category=VALUES(category);");
}
$boards = ['mongodb','grafanalabs','canonical','datadog','cloudflare','discord'];
$names = array_unique(array_merge(array_column(Database\Seeders\OfficialCareerSourceSeeder::sources(), 'name'), array_column(config('cyber_city.companies'), 'name')));
$counts = [];
foreach (Company::whereIn('name', $names)->orderBy('id')->get() as $company) {
    $data = $company->only(['name','slug','website','careers_url','country','industry','sector','ats_provider','ats_identifier','jobs_feed_url','sync_enabled','is_active']);
    $updates = implode(',', array_map(fn ($name) => $name.'=VALUES('.$name.')', array_diff(array_keys($data), ['name','slug'])));
    emitSql("INSERT INTO companies (".columns($data).",created_at,updated_at) VALUES (".values($data).",NOW(),NOW()) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id),".$updates.";");
    emitSql("SET @discovery_company_id=LAST_INSERT_ID();");
    if (!in_array($company->ats_identifier, $boards, true)) continue;
    $jobs = $company->activeJobs()->with('technologies')->orderBy('id')->get();
    $counts[$company->name] = $jobs->count();
    foreach ($jobs as $job) {
        $data = $job->only(['title','description','location','source_payload','country','job_type','posting_source','work_mode','experience_level','experience_min','experience_max','role_family','category','role','external_url','application_method','posted_at','expires_at','scraped_at','status','job_visibility','is_active','department','engineering_discipline','classification_version','classified_at']);
        foreach ($data as $key => $value) $data[$key] = $job->getRawOriginal($key);
        emitSql("INSERT INTO jobs (company_id,".columns($data).",created_at,updated_at) SELECT @discovery_company_id,".values($data).",NOW(),NOW() WHERE NOT EXISTS (SELECT 1 FROM jobs WHERE company_id=@discovery_company_id AND BINARY external_url=BINARY ".literal($job->external_url).");");
        emitSql("SET @discovery_job_id=(SELECT id FROM jobs WHERE company_id=@discovery_company_id AND BINARY external_url=BINARY ".literal($job->external_url)." ORDER BY id LIMIT 1);");
        $sets = implode(',', array_map(fn ($key) => $key.'='.literal($data[$key]), array_keys($data)));
        emitSql("UPDATE jobs SET ".$sets.",updated_at=NOW() WHERE id=@discovery_job_id AND (source IS NULL OR source<>'employer');");
        foreach ($job->technologies as $technology) {
            emitSql("INSERT INTO job_technology (job_id,technology_id,created_at,updated_at) SELECT @discovery_job_id,id,NOW(),NOW() FROM technologies WHERE BINARY slug=BINARY ".literal($technology->slug)." ON DUPLICATE KEY UPDATE updated_at=VALUES(updated_at);");
        }
    }
    emitSql("COMMIT;\nSTART TRANSACTION;");
}
emitSql("COMMIT;");
fclose($out);
$input = fopen($path, 'rb'); $gzip = gzopen($path.'.gz','wb9');
while (!feof($input)) gzwrite($gzip, fread($input, 1048576));
fclose($input); gzclose($gzip);

$stats = ['total_jobs'=>Job::active()->count(), 'total_companies'=>Company::active()->count(), 'hiring_companies'=>Company::active()->whereHas('activeJobs')->count(), 'total_technologies'=>Technology::count()];
file_put_contents($directory.'/homepage-preview.html', view('portal-v2', ['homeStats'=>$stats, 'dsaTracks'=>[]])->render());
$skills = [];
foreach (['c','c++','java','python','php','ruby','swift','django','laravel','springboot','kubernetes','kafka','docker','html','css','javascript','react native','react bootstrap','node js','type script'] as $term) {
    $skills[$term] = Job::active()->search($term)->count();
    echo $term.': '.$skills[$term].PHP_EOL;
}
$report = ['generated_at'=>now()->toIso8601String(),'import_jobs'=>$counts,'import_total'=>array_sum($counts),'local_active_jobs'=>Job::active()->count(),'technologies'=>Technology::count(),'local_search_results'=>$skills,'location_sources'=>config('cyber_city.companies')];
file_put_contents($directory.'/verification.json', json_encode($report, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
echo json_encode($report, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;
