<?php

namespace App\Http\Controllers;

use App\Models\AtsResume;
use App\Http\Requests\SaveAtsResumeRequest;
use App\Models\Company;
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
        return view('resume.builder', [
            'resume' => $resume,
            'roles' => config('recruitment.roles', []),
            'skills' => config('recruitment.skills', []),
            'degrees' => config('resume.degrees', []),
            'linkTypes' => config('resume.link_types', []),
            'noticePeriods' => config('resume.notice_periods', []),
            'templates' => config('resume.templates', []),
        ]);
    }

    public function save(SaveAtsResumeRequest $request): JsonResponse
    {
        $data = $request->validated();
        $resume = AtsResume::updateOrCreate(['user_id'=>$request->user()->id], $data);
        $resume->update(['completeness'=>$resume->calculateCompleteness()]);
        return response()->json(['ok'=>true,'message'=>'Resume saved successfully.','completeness'=>$resume->completeness,'updated_at'=>$resume->updated_at->diffForHumans()]);
    }

    public function companies(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q'));
        return response()->json(Company::query()->when($q !== '', fn($query) => $query->where('name','like','%'.$q.'%'))->orderByRaw('CASE WHEN name LIKE ? THEN 0 ELSE 1 END', [$q.'%'])->orderBy('name')->limit(25)->pluck('name'));
    }

    public function locations(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q'));
        $values = collect();
        if (\Schema::hasTable('jobs')) $values = $values->merge(\DB::table('jobs')->whereNotNull('location')->when($q !== '', fn($x) => $x->where('location','like','%'.$q.'%'))->distinct()->limit(40)->pluck('location'));
        if (\Schema::hasTable('company_locations')) $values = $values->merge(\DB::table('company_locations')->whereNotNull('city')->when($q !== '', fn($x) => $x->where('city','like','%'.$q.'%'))->distinct()->limit(40)->pluck('city'));
        return response()->json($values->filter()->map(fn($v) => trim($v))->unique(fn($v) => mb_strtolower($v))->sort()->take(30)->values());
    }

    public function institutions(Request $request): JsonResponse
    {
        $q = mb_strtolower(trim((string) $request->query('q')));
        return response()->json(collect(config('resume.institutions', []))->when($q !== '', fn($items) => $items->filter(fn($name) => str_contains(mb_strtolower($name), $q)))->take(30)->values());
    }

    public function download(Request $request)
    {
        $resume = AtsResume::where('user_id',$request->user()->id)->firstOrFail();
        return Pdf::loadView('resume.pdf',compact('resume'))->setPaper('a4')->download(Str::slug($resume->full_name).'-ats-resume.pdf');
    }

}
