<?php
if(PHP_SAPI!=='cli')exit;
require __DIR__.'/../vendor/autoload.php';
$app=require __DIR__.'/../bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
$out=fopen(base_path('release/job-discovery/career-explorer.sql'),'wb');
function lineSql($sql){global $out;fwrite($out,$sql."\n");}
function lit($v){if($v===null)return 'NULL';if(is_bool($v))return $v?'1':'0';if($v==='')return "''";if(is_int($v)||is_float($v))return (string)$v;return 'CONVERT(0x'.bin2hex((string)$v).' USING utf8mb4)';}
function upsertRow($table,$data,$foreign=[]){
 unset($data['id']);$names=array_keys($data);$values=array_map(fn($k)=>$foreign[$k]??lit($data[$k]),$names);
 $updates=[];foreach($names as $k)if($k!=='created_at')$updates[]=$k.'=VALUES('.$k.')';
 lineSql('INSERT INTO '.$table.' ('.implode(',',$names).') VALUES ('.implode(',',$values).') ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id),'.implode(',',$updates).';');
}
lineSql('-- Career explorer schema, sourced profiles and user-supplied salary references. No account data.');
lineSql('SET NAMES utf8mb4;');
foreach(['career_tracks','career_roles','career_salary_benchmarks','company_facilities','password_resets'] as $table){
 $row=(array)DB::selectOne('SHOW CREATE TABLE '.$table);$sql=array_values($row)[1];
 $sql=preg_replace('/ AUTO_INCREMENT=\d+/','',$sql);$sql=preg_replace('/\s+/',' ',$sql);
 lineSql(str_replace('CREATE TABLE ','CREATE TABLE IF NOT EXISTS ',$sql).';');
}
lineSql('START TRANSACTION;');
foreach(App\Models\CareerTrack::with('roles')->get() as $track){
 upsertRow('career_tracks',$track->getAttributes());lineSql('SET @career_track=LAST_INSERT_ID();');
 foreach($track->roles as $role)upsertRow('career_roles',$role->getAttributes(),['career_track_id'=>'@career_track']);
}
foreach(DB::table('career_salary_benchmarks')->get() as $row)upsertRow('career_salary_benchmarks',(array)$row);
foreach(App\Models\CompanyFacility::with('company')->get() as $facility){
 $company=$facility->company;
 $data=$company->only(['name','slug','website','careers_url','country','industry','is_active','sync_enabled']);
 // Only insert missing companies. Preserve configured feeds and existing profiles.
 lineSql('INSERT INTO companies ('.implode(',',array_keys($data)).',created_at,updated_at) VALUES ('.implode(',',array_map('lit',$data)).',NOW(),NOW()) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id);');
 lineSql('SET @facility_company=LAST_INSERT_ID();');
 upsertRow('company_facilities',$facility->getAttributes(),['company_id'=>'@facility_company']);
}
lineSql('COMMIT;');fclose($out);
$path=base_path('release/job-discovery/career-explorer.sql');$gz=gzopen($path.'.gz','wb9');gzwrite($gz,file_get_contents($path));gzclose($gz);
echo 'Exported career schema and catalog.'.PHP_EOL;
