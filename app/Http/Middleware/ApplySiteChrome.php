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
        $explanation = app(\App\Services\SiteExplanation::class)->forRequest($request);
        if ($explanation && !str_contains($html, 'data-visual-explainer=')) {
            $guide = view('partials.visual-explainer', compact('explanation'))->render();
            // Place explanations beside the main content, after the hero when present.
            $hero = '/(<section\b[^>]*class="[^"\n]*\bhero(?:-band)?\b[^"\n]*"[^>]*>.*?<\/section>)/si';
            if (preg_match($hero, $html)) $html = preg_replace_callback($hero, fn($m) => $m[1].$guide, $html, 1);
            elseif (preg_match('/<main\b[^>]*>/i', $html)) $html = preg_replace_callback('/<main\b[^>]*>/i', fn($m) => $m[0].$guide, $html, 1);
            elseif (preg_match('/<form\b/i', $html)) $html = preg_replace_callback('/<form\b/i', fn($m) => $guide.$m[0], $html, 1);
        }
        if (str_contains($html, 'data-visual-explainer=') || str_contains($html, 'data-industrial-examples')) {
            $assets='<link rel="stylesheet" href="'.asset('css/site-explanations.css').'">';
            $html=preg_replace('/<\/head>/i', $assets.'</head>', $html, 1);
            $bodyEnd=strripos($html,'</body>');
            if ($bodyEnd!==false) $html=substr_replace($html, '<script src="'.asset('js/site-explanations.js').'" defer></script>', $bodyEnd, 0);
        }
        $originalContent = $response->original ?? null;
        $response->setContent($html);
        if (property_exists($response, 'original')) $response->original = $originalContent;
        if ($request->is('companies', 'companies/category/*')) {
            $html = str_replace('50 per page', '10 per page', $html);
        }
        $footer = view('partials.site-footer-v2')->render();
        if ($request->path() === '/') {
            if (!str_contains($html, 'class="site-footer"')) {
                $html = preg_replace('/<\/body>/i', $footer.'</body>', $html, 1);
                $response->setContent($html);
            }
            return $response;
        }
        $header = view('partials.site-header')->render();
        if (str_contains($html, 'data-shared-header')) { return $response; }
        if (str_contains($html, 'class="sitebar"')) {
            $html = preg_replace('/<header class="sitebar">.*?<\/header>/s', $header, $html, 1);
            $response->setContent($html);
            return $response;
        }
        if ($request->is('owner/login')) {
            $html = str_replace('</head>', '<style>body{display:block!important}.box{margin:60px auto!important}</style></head>', $html);
        }
        if ($request->is('login', 'register', 'verify-otp')) {
            $html = str_replace('</head>', '<style>body{background:linear-gradient(135deg,#eef4ff,#f8fafc)!important}.box{margin:7vh auto!important;padding:38px!important;border:1px solid #dbe4f2!important;border-radius:20px!important;box-shadow:0 24px 70px rgba(16,33,62,.14)!important}.box h1{font-size:32px;margin:0 0 10px}.box input{border:1px solid #cbd5e1;border-radius:9px}.box .btn{font-size:16px;cursor:pointer;box-shadow:0 8px 20px rgba(37,99,235,.25)}</style></head>', $html);
        }
        $html = preg_replace('/(<body[^>]*>)/i', '$1'.$header, $html, 1);
        $html = preg_replace('/<\/body>/i', $footer.'</body>', $html, 1);
        $response->setContent($html);
        return $response;
    }
}
