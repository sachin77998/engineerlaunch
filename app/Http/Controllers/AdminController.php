<?php
namespace App\Http\Controllers;
use App\Models\User;use Illuminate\Support\Facades\DB;
class AdminController extends Controller {public function index(){return view('admin.dashboard',['users'=>User::count(),'visits'=>DB::table('activity_logs')->count(),'uniqueVisitors'=>DB::table('activity_logs')->distinct('session_id')->count('session_id'),'recent'=>DB::table('activity_logs')->leftJoin('users','users.id','=','activity_logs.user_id')->select('activity_logs.*','users.email')->latest('activity_logs.created_at')->limit(50)->get(),'popular'=>DB::table('activity_logs')->selectRaw('path,count(*) visits')->groupBy('path')->orderByDesc('visits')->limit(10)->get()]);}}
