<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\PerformanceOptimizationService;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;

class PerformanceDashboardController extends Controller
{
    protected PerformanceOptimizationService $performanceService;

    public function __construct(PerformanceOptimizationService $performanceService)
    {
        $this->performanceService = $performanceService;
    }

    /**
     * Get performance metrics overview
     */
    public function getMetrics(): JsonResponse
    {
        $metrics = $this->performanceService->getPerformanceMetrics();
        
        // Add real-time system metrics
        $systemMetrics = [
            'system' => [
                'memory_usage' => memory_get_usage(true) / 1024 / 1024, // MB
                'memory_peak' => memory_get_peak_usage(true) / 1024 / 1024, // MB
                'memory_limit' => ini_get('memory_limit'),
                'execution_time' => microtime(true) - LARAVEL_START,
            ],
            'database' => $this->getDatabaseMetrics(),
            'cache' => $this->getCacheMetrics(),
            'queue' => $this->getQueueMetrics(),
        ];

        return response()->json([
            'performance_metrics' => $metrics,
            'system_metrics' => $systemMetrics,
            'timestamp' => now()
        ]);
    }

    /**
     * Get API response time statistics
     */
    public function getApiStats(): JsonResponse
    {
        $cacheKeys = cache()->getRedis()->keys('api_metrics:*');
        $metrics = [];

        foreach (array_slice($cacheKeys, -100) as $key) { // Last 100 requests
            $data = cache()->get($key);
            if ($data) {
                $metrics[] = $data;
            }
        }

        // Calculate statistics
        $responseTimes = array_column($metrics, 'execution_time');
        $memoryUsages = array_column($metrics, 'memory_usage');

        $stats = [
            'total_requests' => count($metrics),
            'average_response_time' => $responseTimes ? round(array_sum($responseTimes) / count($responseTimes), 2) : 0,
            'max_response_time' => $responseTimes ? max($responseTimes) : 0,
            'min_response_time' => $responseTimes ? min($responseTimes) : 0,
            'average_memory_usage' => $memoryUsages ? round(array_sum($memoryUsages) / count($memoryUsages), 2) : 0,
            'max_memory_usage' => $memoryUsages ? max($memoryUsages) : 0,
            'endpoints' => $this->getEndpointStats($metrics),
        ];

        return response()->json($stats);
    }

    /**
     * Clear all performance caches
     */
    public function clearCaches(): JsonResponse
    {
        try {
            $this->performanceService->clearAllCaches();
            
            return response()->json([
                'success' => true,
                'message' => 'All caches cleared successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear caches: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Warm up caches
     */
    public function warmUpCaches(): JsonResponse
    {
        try {
            $this->performanceService->warmUpCache();
            
            return response()->json([
                'success' => true,
                'message' => 'Caches warmed up successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to warm up caches: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get health status
     */
    public function getHealthStatus(): JsonResponse
    {
        $status = [
            'database' => $this->checkDatabaseHealth(),
            'redis' => $this->checkRedisHealth(),
            'storage' => $this->checkStorageHealth(),
            'queue' => $this->checkQueueHealth(),
        ];

        $overall = collect($status)->every(fn($check) => $check['status'] === 'healthy');

        return response()->json([
            'overall_status' => $overall ? 'healthy' : 'unhealthy',
            'checks' => $status,
            'timestamp' => now()
        ], $overall ? 200 : 503);
    }

    protected function getDatabaseMetrics(): array
    {
        try {
            $connectionInfo = DB::select('SHOW STATUS LIKE "Threads_connected"')[0] ?? null;
            $processListCount = count(DB::select('SHOW PROCESSLIST'));
            
            return [
                'connections' => $connectionInfo->Value ?? 0,
                'active_processes' => $processListCount,
                'status' => 'healthy'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }

    protected function getCacheMetrics(): array
    {
        try {
            $info = Redis::info();
            return [
                'connected_clients' => $info['connected_clients'] ?? 0,
                'memory_usage' => $info['used_memory_human'] ?? '0B',
                'keyspace_hits' => $info['keyspace_hits'] ?? 0,
                'keyspace_misses' => $info['keyspace_misses'] ?? 0,
                'hit_rate' => $this->calculateHitRate($info),
                'status' => 'healthy'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }

    protected function getQueueMetrics(): array
    {
        try {
            // Get queue sizes from Redis
            $queueSizes = [
                'default' => Redis::llen('queues:default'),
                'bulk-imports' => Redis::llen('queues:bulk-imports'),
                'failed' => Redis::llen('queues:failed'),
            ];

            return [
                'queue_sizes' => $queueSizes,
                'total_pending' => array_sum($queueSizes),
                'status' => 'healthy'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }

    protected function checkDatabaseHealth(): array
    {
        try {
            DB::select('SELECT 1');
            return ['status' => 'healthy'];
        } catch (\Exception $e) {
            return ['status' => 'unhealthy', 'error' => $e->getMessage()];
        }
    }

    protected function checkRedisHealth(): array
    {
        try {
            Redis::ping();
            return ['status' => 'healthy'];
        } catch (\Exception $e) {
            return ['status' => 'unhealthy', 'error' => $e->getMessage()];
        }
    }

    protected function checkStorageHealth(): array
    {
        try {
            $uploadPath = public_path('uploads/images');
            $writable = is_writable($uploadPath);
            
            return [
                'status' => $writable ? 'healthy' : 'unhealthy',
                'writable' => $writable,
                'path' => $uploadPath
            ];
        } catch (\Exception $e) {
            return ['status' => 'unhealthy', 'error' => $e->getMessage()];
        }
    }

    protected function checkQueueHealth(): array
    {
        try {
            // Check if queue workers are processing
            $failedJobs = Redis::llen('queues:failed');
            return [
                'status' => $failedJobs < 10 ? 'healthy' : 'degraded',
                'failed_jobs' => $failedJobs
            ];
        } catch (\Exception $e) {
            return ['status' => 'unhealthy', 'error' => $e->getMessage()];
        }
    }

    protected function calculateHitRate(array $info): float
    {
        $hits = $info['keyspace_hits'] ?? 0;
        $misses = $info['keyspace_misses'] ?? 0;
        $total = $hits + $misses;
        
        return $total > 0 ? round(($hits / $total) * 100, 2) : 0;
    }

    protected function getEndpointStats(array $metrics): array
    {
        $endpoints = [];
        
        foreach ($metrics as $metric) {
            $path = parse_url($metric['url'], PHP_URL_PATH);
            
            if (!isset($endpoints[$path])) {
                $endpoints[$path] = [
                    'count' => 0,
                    'total_time' => 0,
                    'max_time' => 0,
                    'methods' => []
                ];
            }
            
            $endpoints[$path]['count']++;
            $endpoints[$path]['total_time'] += $metric['execution_time'];
            $endpoints[$path]['max_time'] = max($endpoints[$path]['max_time'], $metric['execution_time']);
            $endpoints[$path]['methods'][$metric['method']] = ($endpoints[$path]['methods'][$metric['method']] ?? 0) + 1;
        }
        
        // Calculate averages
        foreach ($endpoints as &$endpoint) {
            $endpoint['avg_time'] = round($endpoint['total_time'] / $endpoint['count'], 2);
        }
        
        return $endpoints;
    }
}
