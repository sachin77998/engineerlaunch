<?php
namespace App\Http\Controllers;
use App\Models\Job;use Illuminate\Http\Request;use Illuminate\Support\Facades\DB;
class CandidateJobController extends Controller
{
 public function show(Job $job){abort_unless($job->is_active&&$job->status==='published'&&$job->job_visibility==='public',404);$job->load(['company','skills','locations','screeningQuestions']);$job->incrementViews();return view('jobs.show',compact('job'));}
 public function apply(Request $r,Job $job){abort_unless($job->is_active&&$job->status==='published'&&$job->application_method==='portal',404);abort_if(in_array($r->user()->role,['employer','admin'],true)||in_array($r->user()->role_code,[0,2],true),403,'Employer accounts cannot apply.');$data=$r->validate(['cover_letter'=>'nullable|string|max:5000','answers'=>'nullable|array','answers.*'=>'nullable|string|max:3000']);DB::transaction(function()use($r,$job,$data){$application=$job->applications()->firstOrCreate(['user_id'=>$r->user()->id],['status'=>'new','cover_letter'=>$data['cover_letter']??null,'applied_at'=>now()]);foreach(($data['answers']??[]) as $question=>$answer)DB::table('application_answers')->updateOrInsert(['job_application_id'=>$application->id,'question_id'=>$question],['answer'=>$answer]);});return back()->with('success','Application submitted successfully.');}
}
