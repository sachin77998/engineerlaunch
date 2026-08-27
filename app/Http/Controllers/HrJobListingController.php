<?php
namespace App\Http\Controllers;
use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\View\View;
class HrJobListingController extends Controller { public function __invoke(Request $request):View { $filters=$request->validate(['q'=>'nullable|string|max:150','location'=>'nullable|string|max:150']);$jobs=Job::active()->whereNotNull('employer_id')->with('company')->when($filters['q']??null,fn($q,$term)=>$q->search($term))->when($filters['location']??null,fn($q,$location)=>$q->location($location))->latest('posted_at')->paginate(50)->withQueryString();return view('jobs.hr-listing',compact('jobs','filters')); } }
