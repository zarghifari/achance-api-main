<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PerformanceOptimizationService
{
    protected $redis;
    protected $config;

    public function __construct()
    {
        $this->redis = Redis::connection();
        $this->config = config('performance');
    }

    /**
     * Cache API response with intelligent TTL
     */
    public function cacheApiResponse(string $key, $data, int $customTtl = null): void
    {
        $ttl = $customTtl ?? $this->config['cache']['strategies']['api_responses']['ttl'];
        $prefix = $this->config['cache']['strategies']['api_responses']['prefix'];
        
        Cache::put($prefix . $key, [
            'data' => $data,
            'cached_at' => Carbon::now(),
            'ttl' => $ttl
        ], $ttl);
    }

    /**
     * Get cached API response
     */
    public function getCachedApiResponse(string $key)
    {
        $prefix = $this->config['cache']['strategies']['api_responses']['prefix'];
        return Cache::get($prefix . $key);
    }

    /**
     * Cache database query results
     */
    public function cacheQuery(string $query, array $bindings, $result, int $customTtl = null): void
    {
        if (!$this->config['cache']['strategies']['queries']['enabled']) {
            return;
        }

        $key = $this->generateQueryCacheKey($query, $bindings);
        $ttl = $customTtl ?? $this->config['cache']['strategies']['queries']['ttl'];
        $prefix = $this->config['cache']['strategies']['queries']['prefix'];

        Cache::put($prefix . $key, [
            'result' => $result,
            'cached_at' => Carbon::now(),
            'query' => $query,
        ], $ttl);
    }

    /**
     * Get cached query result
     */
    public function getCachedQuery(string $query, array $bindings)
    {
        if (!$this->config['cache']['strategies']['queries']['enabled']) {
            return null;
        }

        $key = $this->generateQueryCacheKey($query, $bindings);
        $prefix = $this->config['cache']['strategies']['queries']['prefix'];
        
        return Cache::get($prefix . $key);
    }

    /**
     * Invalidate cache by tags
     */
    public function invalidateCache(array $tags): void
    {
        if ($this->config['cache']['tags_enabled']) {
            Cache::tags($tags)->flush();
        }
    }

    /**
     * Monitor query performance
     */
    public function monitorQuery(string $query, float $executionTime, array $bindings = []): void
    {
        if (!$this->config['monitoring']['enabled']) {
            return;
        }

        $threshold = $this->config['monitoring']['slow_query_threshold'];
        
        if ($executionTime > $threshold) {
            Log::warning('Slow query detected', [
                'query' => $query,
                'execution_time' => $executionTime,
                'bindings' => $bindings,
                'timestamp' => Carbon::now()
            ]);

            // Store in Redis for monitoring dashboard
            $this->redis->lpush('slow_queries', json_encode([
                'query' => $query,
                'execution_time' => $executionTime,
                'bindings' => $bindings,
                'timestamp' => Carbon::now()->toISOString()
            ]));

            // Keep only last 100 slow queries
            $this->redis->ltrim('slow_queries', 0, 99);
        }
    }

    /**
     * Monitor memory usage
     */
    public function monitorMemory(): void
    {
        if (!$this->config['monitoring']['enabled']) {
            return;
        }

        $memoryUsage = memory_get_usage(true) / 1024 / 1024; // MB
        $threshold = $this->config['monitoring']['memory_threshold'];

        if ($memoryUsage > $threshold) {
            Log::warning('High memory usage detected', [
                'memory_usage' => $memoryUsage,
                'memory_limit' => ini_get('memory_limit'),
                'timestamp' => Carbon::now()
            ]);
        }

        // Store metrics for monitoring
        $this->redis->setex('memory_usage:' . time(), 300, $memoryUsage);
    }

    /**
     * Get performance metrics
     */
    public function getPerformanceMetrics(): array
    {
        $slowQueries = $this->redis->lrange('slow_queries', 0, 9); // Last 10
        $memoryKeys = $this->redis->keys('memory_usage:*');
        
        $memoryUsage = [];
        foreach ($memoryKeys as $key) {
            $timestamp = str_replace('memory_usage:', '', $key);
            $usage = $this->redis->get($key);
            $memoryUsage[] = [
                'timestamp' => $timestamp,
                'usage' => $usage
            ];
        }

        return [
            'slow_queries' => array_map('json_decode', $slowQueries),
            'memory_usage' => $memoryUsage,
            'cache_stats' => $this->getCacheStats(),
            'redis_info' => $this->redis->info('memory')
        ];
    }

    /**
     * Get cache statistics
     */
    public function getCacheStats(): array
    {
        return [
            'hits' => Cache::getRedis()->info('stats')['keyspace_hits'] ?? 0,
            'misses' => Cache::getRedis()->info('stats')['keyspace_misses'] ?? 0,
            'memory_usage' => Cache::getRedis()->info('memory')['used_memory_human'] ?? '0B',
        ];
    }

    /**
     * Warm up critical caches
     */
    public function warmUpCache(): void
    {
        Log::info('Starting cache warmup');

        // Warm up static data
        $this->warmUpStaticData();
        
        // Warm up frequently accessed queries
        $this->warmUpFrequentQueries();

        Log::info('Cache warmup completed');
    }

    /**
     * Clear all performance caches
     */
    public function clearAllCaches(): void
    {
        Cache::flush();
        $this->redis->flushdb();
        
        Log::info('All performance caches cleared');
    }

    /**
     * Generate cache key for query
     */
    protected function generateQueryCacheKey(string $query, array $bindings): string
    {
        return md5($query . serialize($bindings));
    }

    /**
     * Warm up static data cache
     */
    protected function warmUpStaticData(): void
    {
        // Cache courses
        $courses = \App\Models\Course::with(['modules'])->get();
        Cache::put('static_cache:courses', $courses, $this->config['cache']['strategies']['static_data']['ttl']);

        // Cache quiz types
        $quizTypes = ['assessment', 'practice', 'survey'];
        Cache::put('static_cache:quiz_types', $quizTypes, $this->config['cache']['strategies']['static_data']['ttl']);
    }

    /**
     * Warm up frequent queries
     */
    protected function warmUpFrequentQueries(): void
    {
        // Popular quizzes
        $popularQuizzes = \App\Models\Quiz::with(['questions.answers'])
            ->where('is_active', true)
            ->limit(10)
            ->get();
        
        Cache::put('query_cache:popular_quizzes', $popularQuizzes, $this->config['cache']['strategies']['queries']['ttl']);
    }
}
