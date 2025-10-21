<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class ApiResponseCache
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, $ttl = 300): Response
    {
        // Only cache GET requests
        if (!$request->isMethod('GET')) {
            return $next($request);
        }

        // Generate cache key based on URL, query parameters, and user
        $cacheKey = $this->generateCacheKey($request);

        // Try to get cached response
        $cachedResponse = Cache::get($cacheKey);
        
        if ($cachedResponse) {
            $response = response($cachedResponse['content'], $cachedResponse['status']);
            $response->headers->add($cachedResponse['headers']);
            $response->headers->set('X-Cache', 'HIT');
            return $response;
        }

        // Process request
        $response = $next($request);

        // Cache successful responses
        if ($response->getStatusCode() === 200) {
            $cacheData = [
                'content' => $response->getContent(),
                'status' => $response->getStatusCode(),
                'headers' => $response->headers->all()
            ];

            Cache::put($cacheKey, $cacheData, (int)$ttl);
            $response->headers->set('X-Cache', 'MISS');
        }

        return $response;
    }

    /**
     * Generate a unique cache key for the request
     */
    private function generateCacheKey(Request $request): string
    {
        $key = 'api_response_' . md5(
            $request->fullUrl() . 
            '|' . ($request->user()?->id ?? 'guest') .
            '|' . json_encode($request->headers->get('accept'))
        );

        return $key;
    }
}
