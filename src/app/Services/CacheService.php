<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class CacheService
{
    // Cache TTL constants (in seconds)
    const SHORT_TTL = 300;     // 5 minutes
    const MEDIUM_TTL = 1800;   // 30 minutes
    const LONG_TTL = 3600;     // 1 hour
    const VERY_LONG_TTL = 86400; // 24 hours

    /**
     * Cache courses with proper tagging
     */
    public static function cacheCourses($callback, $ttl = self::LONG_TTL)
    {
        return Cache::tags(['courses'])->remember('courses_list', $ttl, $callback);
    }

        /**
     * Cache individual course with proper tagging
     */
    public static function cacheCourse($courseId, $callback, $ttl = self::LONG_TTL)
    {
        return Cache::tags(['courses', "course_{$courseId}"])->remember(
            "course_detail_{$courseId}",
            $ttl,
            $callback
        );
    }

    /**
     * Cache user-specific data
     */
    public static function cacheUserData($userId, $key, $callback, $ttl = self::MEDIUM_TTL)
    {
        return Cache::tags(['users', "user_{$userId}"])->remember(
            "user_{$userId}_{$key}",
            $ttl,
            $callback
        );
    }

    /**
     * Cache lessons with modules and courses
     */
    public static function cacheLessons($callback, $ttl = self::LONG_TTL)
    {
        return Cache::tags(['lessons', 'modules', 'courses'])->remember('lessons_with_relations', $ttl, $callback);
    }

    /**
     * Invalidate course-related caches
     */
    public static function invalidateCourseCache($courseId = null)
    {
        Cache::tags(['courses'])->flush();
        
        if ($courseId) {
            Cache::tags(["course_{$courseId}"])->flush();
        }
    }

    /**
     * Invalidate user-specific caches
     */
    public static function invalidateUserCache($userId)
    {
        Cache::tags(["user_{$userId}"])->flush();
    }

    /**
     * Cache database query results with automatic key generation
     */
    public static function cacheQuery($queryKey, $callback, $ttl = self::MEDIUM_TTL, $tags = [])
    {
        $cacheKey = 'query_' . md5($queryKey);
        
        if (!empty($tags)) {
            return Cache::tags($tags)->remember($cacheKey, $ttl, $callback);
        }
        
        return Cache::remember($cacheKey, $ttl, $callback);
    }

    /**
     * Get cache statistics
     */
    public static function getCacheStats()
    {
        try {
            $redis = Redis::connection();
            $info = $redis->info('memory');
            
            return [
                'used_memory' => $info['used_memory_human'] ?? 'N/A',
                'used_memory_rss' => $info['used_memory_rss_human'] ?? 'N/A',
                'max_memory' => $info['maxmemory_human'] ?? 'N/A',
                'connected_clients' => $redis->info('clients')['connected_clients'] ?? 'N/A',
                'total_commands_processed' => $redis->info('stats')['total_commands_processed'] ?? 'N/A'
            ];
        } catch (\Exception $e) {
            return ['error' => 'Cannot connect to Redis'];
        }
    }

    /**
     * Warm up critical caches
     */
    public static function warmUpCache()
    {
        // This would be called during deployment or scheduled tasks
        \App\Models\Course::with(['modules.lessons.epub', 'modules.tasks'])->get();
        \App\Models\User::with('roles')->where('active', true)->get();
    }
}
