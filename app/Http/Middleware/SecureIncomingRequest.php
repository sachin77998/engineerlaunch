<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SecureIncomingRequest
{
    private const MAX_BYTES = 12_582_912; // 12 MiB, enough for validated resume uploads.

    public function handle(Request $request, Closure $next): Response
    {
        if (config('app.force_https') && !$request->isSecure()) {
            return redirect()->secure($request->getRequestUri(), 308);
        }

        if (!in_array(strtoupper($request->method()), ['GET','HEAD','POST','PUT','PATCH','DELETE','OPTIONS'], true)) {
            return $this->reject($request, 405, 'Unsupported request method.');
        }

        if ((int) $request->server('CONTENT_LENGTH', 0) > self::MAX_BYTES) {
            return $this->reject($request, 413, 'Request payload is too large.');
        }

        $path = strtolower(rawurldecode('/'.$request->path()));
        $blockedPaths = ['/.env','/.git','/vendor/','/storage/logs','/phpmyadmin','/wp-admin','/wp-login','/server-status'];
        foreach ($blockedPaths as $blockedPath) {
            if (str_contains($path, $blockedPath)) {
                return $this->reject($request, 404, 'Not found.');
            }
        }

        $target = strtolower(rawurldecode($request->getRequestUri()));
        $signatures = ["\0", '../', '..\\', 'php://', 'data:text/html', '<script', '%0d%0a', "\r\n", 'union select', 'sleep(', 'benchmark('];
        foreach ($signatures as $signature) {
            if (str_contains($target, $signature)) {
                return $this->reject($request, 400, 'Malformed request.');
            }
        }

        // Laravel 9's "email" rule lets CR/LF through and Symfony Mime 6.0 would put it in mail headers (header / SMTP
        // injection); reject line breaks in any address field until the framework can be upgraded.
        foreach (\Illuminate\Support\Arr::dot($request->except(['password', 'password_confirmation'])) as $key => $value) {
            if (is_string($value) && preg_match('/(?:^|[._])(?:e-?mail|to|cc|bcc|reply_to)(?:$|[._])/i', (string) $key) && preg_match('/[\r\n]/', $value)) {
                return $this->reject($request, 422, 'Invalid email address.');
            }
        }

        $response = $next($request);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(self)');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $response->headers->set('Content-Security-Policy', "default-src 'self'; base-uri 'self'; form-action 'self' https://securestage.paytmpayments.com https://secure.paytmpayments.com; frame-ancestors 'self'; object-src 'none'; img-src 'self' data: https:; font-src 'self' data: https://fonts.gstatic.com https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://checkout.razorpay.com; frame-src 'self' https://api.razorpay.com https://checkout.razorpay.com; connect-src 'self' https://api.razorpay.com https://lumberjack.razorpay.com");
        if (config('app.force_https') && $request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        return $response;
    }

    private function reject(Request $request, int $status, string $message): Response
    {
        Log::warning('Rejected suspicious HTTP request', [
            'status' => $status,
            'method' => $request->method(),
            'path' => $request->path(),
            'ip' => $request->ip(),
            'user_id' => optional($request->user())->id,
        ]);

        return $request->expectsJson()
            ? response()->json(['message' => $message], $status)
            : response($message, $status);
    }
}
