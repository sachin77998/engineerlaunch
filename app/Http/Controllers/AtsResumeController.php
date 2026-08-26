<?php

namespace App\Http\Controllers;

use App\Models\AtsResume;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AtsResumeController extends Controller
{
    public function edit(Request $request)
    {
        $profile = $request->user()->candidateProfile;
        $resume = AtsResume::firstOrNew(['user_id'=>$request->user()->id]);
        if (!$resume->exists) {
            $resume->fill(['full_name'=>$request->user()->name,'email'=>$request->user()->email,'phone'=>$profile?->phone,'location'=>$profile?->location,'headline'=>$profile?->headline,'summary'=>$profile?->bio,'skills'=>[],'experience'=>[],'education'=>[],'links'=>[]]);
        }
        return view('resume.builder', compact('resume'));
    }

    public function save(Request $request): JsonResponse
    {
        $data = $request->validate($this->rules());
        $resume = AtsResume::updateOrCreate(['user_id'=>$request->user()->id], $data);
        $resume->update(['completeness'=>$resume->calculateCompleteness()]);
        return response()->json(['ok'=>true,'message'=>'Resume saved successfully.','completeness'=>$resume->completeness,'updated_at'=>$resume->updated_at->diffForHumans()]);
    }

    public function download(Request $request)
    {
        $resume = AtsResume::where('user_id',$request->user()->id)->firstOrFail();
        return Pdf::loadView('resume.pdf',compact('resume'))->setPaper('a4')->download(Str::slug($resume->full_name).'-ats-resume.pdf');
    }

    private function rules(): array
    {
        return [
            'full_name'=>['required','string','max:120'],'headline'=>['nullable','string','max:150'],'email'=>['required','email','max:190'],'phone'=>['nullable','string','max:30'],'location'=>['nullable','string','max:150'],'summary'=>['nullable','string','max:1200'],
            'skills'=>['nullable','array','max:50'],'skills.*'=>['string','max:60'],
            'experience'=>['nullable','array','max:20'],'experience.*.company'=>['required','string','max:150'],'experience.*.role'=>['required','string','max:150'],'experience.*.location'=>['nullable','string','max:150'],'experience.*.start_date'=>['nullable','string','max:20'],'experience.*.end_date'=>['nullable','string','max:20'],'experience.*.bullets'=>['nullable','array','max:12'],'experience.*.bullets.*'=>['string','max:300'],
            'education'=>['nullable','array','max:20'],'education.*.degree'=>['required','string','max:150'],'education.*.institution'=>['required','string','max:150'],'education.*.location'=>['nullable','string','max:150'],'education.*.end_date'=>['nullable','string','max:20'],'education.*.gpa'=>['nullable','string','max:20'],
            'links'=>['nullable','array','max:15'],'links.*.label'=>['required','string','max:60'],'links.*.url'=>['required','url','max:255'],
        ];
    }
}
