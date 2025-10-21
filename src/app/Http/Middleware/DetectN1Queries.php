<?php

namespace App\Http\Middleware;

use App\Services\QueryOptimizationService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DetectN1Queries
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Only enable in development environment
        if (!config('app.debug')) {
            return $next($request);
        }

        QueryOptimizationService::startQueryLogging();
        
        $response = $next($request);
        
        $stats = QueryOptimizationService::stopQueryLogging();
        
        // Log potential N+1 queries
        if ($stats['total_queries'] > 10) {
            Log::warning('Potential N+1 Query Detected', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'total_queries' => $stats['total_queries'],
                'total_time' => $stats['total_time'] . 'ms',
                'slow_queries_count' => count($stats['slow_queries'])
            ]);
        }

        // Add query stats to response headers in development
        $response->headers->set('X-Query-Count', $stats['total_queries']);
        $response->headers->set('X-Query-Time', $stats['total_time'] . 'ms');
        
        return $response;
    }
}
