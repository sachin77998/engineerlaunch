<?php
namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\BatchRun;
use App\Models\BatchRunItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class AdminJobSourcesController extends Controller
{
    public function index()
    {
        $inventory=collect(config('requested_employers',[]));
        $companies=Company::whereIn('name',$inventory->pluck('name'))->withCount(['jobs'=>fn($q)=>$q->where('is_active',true)->where('status','published')->where(fn($q)=>$q->whereNull('expires_at')->orWhere('expires_at','>',now()))])->get()->keyBy('name');
        $ready=Schema::hasTable('batch_runs') && Schema::hasTable('batch_run_items') && Schema::hasTable('jobs_queue') && Schema::hasTable('job_batches');
        $run=$ready ? BatchRun::where('batch_type','official_job_ingestion')->latest('id')->first() : null;
        $items=$run ? BatchRunItem::where('batch_run_id',$run->id)->get()->keyBy('company_id') : collect();
        return view('admin.job-sources',compact('inventory','companies','ready','run','items'));
    }

    public function refresh(Request $request)
    {
        foreach (['batch_runs','batch_run_items','jobs_queue','job_batches'] as $table) {
            if (!Schema::hasTable($table)) return back()->with('source_status','Database deployment is incomplete: '.$table.' is missing.');
        }
        $lock=Cache::lock('requested-source-dispatch',300);
        if (!$lock->get()) return back()->with('source_status','A source refresh is already being prepared.');
        try {
            if (BatchRun::where('batch_type','official_job_ingestion')->whereIn('status',['queued','running','dispatching'])->where('pending_items','>',0)->exists()) {
                return back()->with('source_status','An ingestion batch is already pending. Check the batch below and the scheduled worker.');
            }
            if (Artisan::call('db:seed',['--class'=>'RequestedEmployerInventorySeeder','--force'=>true])!==0) {
                return back()->with('source_status','Employer preparation failed; check the server log.');
            }
            $code=Artisan::call('jobs:dispatch-daily',['--triggered-by'=>'owner','--user-id'=>$request->user()->id]);
            return back()->with('source_status',$code===0 ? 'Batch queued. The ingestion worker will process each source; reload this page to see progress.' : 'Batch could not be queued. Check the batch failure and server log.');
        } finally { $lock->release(); }
    }
}
