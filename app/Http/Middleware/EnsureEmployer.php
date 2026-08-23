<?php
namespace App\Http\Middleware;use Closure;use Illuminate\Http\Request;class EnsureEmployer{public function handle(Request $r,Closure $next){abort_unless(in_array($r->user()?->role,['employer','admin'],true)||in_array($r->user()?->role_code,[0,2],true),403);return $next($r);}}
