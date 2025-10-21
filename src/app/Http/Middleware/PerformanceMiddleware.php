<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class PerformanceMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();
        
        // Enable query logging for debugging
        if (config('app.debug')) {
            DB::enableQueryLog();
        }

        $response = $next($request);

        $endTime = microtime(true);
        $endMemory = memory_get_usage();
        
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        $memoryUsage = ($endMemory - $startMemory) / 1024 / 1024; // Convert to MB

        // Add performance headers
        $response->headers->set('X-Response-Time', round($executionTime, 2) . 'ms');
        $response->headers->set('X-Memory-Usage', round($memoryUsage, 2) . 'MB');
        
        if (config('app.debug')) {
            $queries = DB::getQueryLog();
            $response->headers->set('X-Query-Count', count($queries));
            
            // Log slow responses
            if ($executionTime > 1000) { // Log responses taking more than 1 second
                Log::warning('Slow API Response', [
                    'url' => $request->fullUrl(),
                    'method' => $request->method(),
                    'execution_time' => $executionTime . 'ms',
                    'memory_usage' => $memoryUsage . 'MB',
                    'query_count' => count($queries),
                    'user_id' => $request->user()?->id
                ]);
            }
        }

        return $response;
    }
}
