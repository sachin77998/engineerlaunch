<?php
namespace App\Http\Middleware;

use App\Services\VisitorAnalytics;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TrackActivity
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        if (!$request->isMethod('get') || $request->is('admin*', 'api/*', 'login', 'forgot-password', 'reset-password/*', 'register', 'verify-otp', 'employers/register') ||
            $response->getStatusCode() < 200 || $response->getStatusCode() >= 300 ||
            !str_contains((string)$response->headers->get('Content-Type'), 'text/html') ||
            $response->headers->has('Content-Disposition') ||
            preg_match('/bot|crawler|spider|slurp|headless|preview|uptime|curl|wget/i', (string)$request->userAgent())) return $response;

        $visitorId = (string)$request->cookie('portal_visitor');
        if (!Str::isUuid($visitorId)) $visitorId = (string)Str::uuid();
        $response->headers->setCookie(cookie('portal_visitor', $visitorId, 525600, '/', null, $request->isSecure(), true, false, 'lax'));
        // Use the server peer, not untrusted client-supplied forwarding/location headers.
        $ip = (string)$request->server('REMOTE_ADDR', '');
        try {
            DB::table('activity_logs')->insert([
                'user_id'=>$request->user()?->id, 'session_id'=>$request->session()->getId(),
                'method'=>'GET', 'path'=>'/'.ltrim($request->path(), '/'), 'action'=>'page_view',
                'ip_hash'=>hash_hmac('sha256', $ip, config('app.key')),
                'user_agent'=>substr((string)$request->userAgent(), 0, 500), 'created_at'=>now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Page activity could not be recorded.', ['type'=>get_class($e)]);
        }
        try {
            $analytics = app(VisitorAnalytics::class);
            $match = $analytics->record($visitorId);
            // Runs after sending the response; no cPanel queue worker is required.
            app()->terminating(function () use ($analytics, $match, $ip) {
                try { $analytics->locate($match, $ip); }
                catch (\Throwable $e) { Log::warning('Visitor location lookup unavailable.', ['type'=>get_class($e)]); }
            });
        } catch (\Throwable $e) {
            Log::warning('Daily visitor tracking unavailable.', ['type'=>get_class($e)]);
        }
        return $response;
    }
}
