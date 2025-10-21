<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Services\PerformanceOptimizationService;
use Carbon\Carbon;

class PerformanceMonitoring
{
    protected $performanceService;

    public function __construct(PerformanceOptimizationService $performanceService)
    {
        $this->performanceService = $performanceService;
    }

    public function handle(Request $request, Closure $next)
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        // Check for cached response
        if ($request->isMethod('GET') && $this->shouldCacheResponse($request)) {
            $cacheKey = $this->generateCacheKey($request);
            $cachedResponse = $this->performanceService->getCachedApiResponse($cacheKey);
            
            if ($cachedResponse) {
                Log::info('Cache hit', ['url' => $request->fullUrl(), 'key' => $cacheKey]);
                return response()->json($cachedResponse['data'])->header('X-Cache', 'HIT');
            }
        }

        $response = $next($request);

        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);
        
        $executionTime = ($endTime - $startTime) * 1000; // milliseconds
        $memoryUsage = ($endMemory - $startMemory) / 1024 / 1024; // MB

        // Log performance metrics
        $this->logPerformanceMetrics($request, $executionTime, $memoryUsage);

        // Cache successful GET responses
        if ($request->isMethod('GET') && $response->getStatusCode() === 200 && $this->shouldCacheResponse($request)) {
            $cacheKey = $this->generateCacheKey($request);
            $responseData = json_decode($response->getContent(), true);
            
            if ($responseData !== null) {
                $this->performanceService->cacheApiResponse($cacheKey, $responseData, $this->getCacheTtl($request));
                $response->header('X-Cache', 'MISS');
            }
        }

        // Add performance headers
        $response->headers->set('X-Response-Time', round($executionTime, 2) . 'ms');
        $response->headers->set('X-Memory-Usage', round($memoryUsage, 2) . 'MB');

        return $response;
    }

    protected function shouldCacheResponse(Request $request): bool
    {
        // Don't cache authenticated user-specific data
        if ($request->header('Authorization')) {
            return false;
        }

        // Don't cache POST, PUT, DELETE requests
        if (!$request->isMethod('GET')) {
            return false;
        }

        // Don't cache admin endpoints
        if (str_contains($request->path(), 'admin')) {
            return false;
        }

        // Cache public API endpoints
        $cachableEndpoints = [
            'api/courses',
            'api/quizzes',
            'api/quizzes/bulk-import/info',
            'api/quizzes/bulk-import/template',
        ];

        foreach ($cachableEndpoints as $endpoint) {
            if (str_starts_with($request->path(), $endpoint)) {
                return true;
            }
        }

        return false;
    }

    protected function generateCacheKey(Request $request): string
    {
        $url = $request->fullUrl();
        $params = $request->query();
        ksort($params);
        
        return 'api_response:' . md5($url . serialize($params));
    }

    protected function getCacheTtl(Request $request): int
    {
        // Different TTL for different endpoints
        $path = $request->path();
        
        if (str_contains($path, 'template')) {
            return 86400; // 24 hours for templates
        }
        
        if (str_contains($path, 'courses')) {
            return 3600; // 1 hour for courses
        }
        
        if (str_contains($path, 'info')) {
            return 1800; // 30 minutes for info
        }
        
        return 300; // 5 minutes default
    }

    protected function logPerformanceMetrics(Request $request, float $executionTime, float $memoryUsage): void
    {
        $data = [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'execution_time' => round($executionTime, 2),
            'memory_usage' => round($memoryUsage, 2),
            'timestamp' => Carbon::now()->toISOString(),
        ];

        // Log slow requests
        if ($executionTime > config('performance.monitoring.slow_query_threshold', 1000)) {
            Log::warning('Slow API request detected', $data);
        }

        // Log high memory usage
        if ($memoryUsage > config('performance.monitoring.memory_threshold', 256)) {
            Log::warning('High memory usage API request', $data);
        }

        // Store metrics for dashboard
        if (config('performance.monitoring.enabled', true)) {
            Cache::put(
                'api_metrics:' . time() . ':' . uniqid(),
                $data,
                300 // 5 minutes
            );
        }
    }
}
