<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplySiteChrome
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Local UI development changes frequently. Prevent the browser from
        // reusing an older rendered Blade page while the portal is being tested.
        if (app()->environment('local')) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
        }

        if (!$response->isSuccessful() || !str_contains((string) $response->headers->get('Content-Type'), 'text/html')) {
            return $response;
        }
        $html = $response->getContent();
        if (!is_string($html) || !str_contains($html, '<body')) return $response;
        $footer = view('partials.site-footer-v2')->render();
        if ($request->path() === '/') {
            if (!str_contains($html, 'class="site-footer"')) {
                $html = preg_replace('/<\/body>/i', $footer.'</body>', $html, 1);
                $response->setContent($html);
            }
            return $response;
        }
        if (str_contains($html, 'class="sitebar"')) return $response;
        $header = view('partials.site-header')->render();
        if ($request->is('login', 'register', 'verify-otp')) {
            $html = str_replace('</head>', '<style>body{background:linear-gradient(135deg,#eef4ff,#f8fafc)!important}.box{margin:7vh auto!important;padding:38px!important;border:1px solid #dbe4f2!important;border-radius:20px!important;box-shadow:0 24px 70px rgba(16,33,62,.14)!important}.box h1{font-size:32px;margin:0 0 10px}.box input{border:1px solid #cbd5e1;border-radius:9px}.box .btn{font-size:16px;cursor:pointer;box-shadow:0 8px 20px rgba(37,99,235,.25)}</style></head>', $html);
        }
        $html = preg_replace('/(<body[^>]*>)/i', '$1'.$header, $html, 1);
        $html = preg_replace('/<\/body>/i', $footer.'</body>', $html, 1);
        $response->setContent($html);
        return $response;
    }
}
