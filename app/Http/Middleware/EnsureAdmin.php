<?php
namespace App\Http\Middleware;use Closure;use Illuminate\Http\Request;
class EnsureAdmin {public function handle(Request $r,Closure $next){abort_unless($r->user()?->role==='admin',403);return $next($r);}}
