<?php
namespace App\Http\Middleware;
use Closure;use Illuminate\Http\Request;use Illuminate\Support\Facades\DB;
class TrackActivity {public function handle(Request $r,Closure $next){$response=$next($r);if($r->isMethod('get')&&!$r->is('admin*')&&!$r->is('api/*')&&!$r->is('login','register','verify-otp','employers/register')){DB::table('activity_logs')->insert(['user_id'=>$r->user()?->id,'session_id'=>$r->session()->getId(),'method'=>$r->method(),'path'=>'/'.ltrim($r->path(),'/'),'action'=>'page_view','ip_hash'=>hash_hmac('sha256',(string)$r->ip(),config('app.key')),'user_agent'=>substr((string)$r->userAgent(),0,500),'created_at'=>now()]);}return $response;}}
