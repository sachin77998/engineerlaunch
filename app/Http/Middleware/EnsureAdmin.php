<?php
namespace App\Http\Middleware;use Closure;use Illuminate\Http\Request;
class EnsureAdmin {public function handle(Request $r,Closure $next){$user=$r->user();abort_unless($user&&($user->role==='admin'||$user->role_code===2)&&strcasecmp($user->email,(string)config('owner.email'))===0,403);return $next($r);}}
