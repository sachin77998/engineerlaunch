<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
class CacheDiscoveryResponses
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        if ($request->isMethod('GET') && $response->isSuccessful()) {
            $response->setPublic();
            $response->setMaxAge((int) config('search.browser_cache_seconds', 300));
            $response->setSharedMaxAge((int) config('search.shared_cache_seconds', 3600));
            $response->headers->addCacheControlDirective('stale-while-revalidate', '60');
            $response->setEtag(sha1((string) $response->getContent()));
            $response->isNotModified($request);
        }
        return $response;
    }
}
