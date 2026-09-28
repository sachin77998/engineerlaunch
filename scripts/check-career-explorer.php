<?php
if(PHP_SAPI!=='cli')exit;
require __DIR__.'/../vendor/autoload.php';
$app=require __DIR__.'/../bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$search=app(App\Services\CareerOpportunitySearch::class);
foreach([[],['industry'=>'Sports','city'=>'Jalandhar'],['industry'=>'Software & IT','skills'=>'Laravel'],['company'=>'Ramkrishna','employee_min'=>3000],['role'=>'melter'],['industry'=>'Banking','department'=>'Finance'],['career_level'=>'manager'],['business_unit'=>'Passenger Vehicles'],['source_url'=>'https://www.google.com','state'=>'Punjab'],['salary_min'=>500000],['role'=>'sde2']] as $filters){
 $data=$search->search($filters);echo json_encode(['filters'=>$filters,'jobs'=>$data['jobs']->total(),'industrial'=>$data['industrialJobs']->total(),'facilities'=>$data['facilities']->total(),'tracks'=>$data['tracks']->count()]).PHP_EOL;
 if(!$filters){$html=view('career-explorer',$data)->render();file_put_contents(base_path('release/job-discovery/career-explorer-preview.html'),$html);}
}
echo "PASS: explorer filters and template rendered.\n";
