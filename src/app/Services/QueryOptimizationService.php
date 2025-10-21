<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QueryOptimizationService
{
    /**
     * Monitor and log N+1 queries in development
     */
    public static function enableQueryLogging(): void
    {
        if (config('app.debug')) {
            DB::listen(function ($query) {
                if ($query->time > 100) { // Log queries taking more than 100ms
                    Log::warning('Slow Query Detected', [
                        'sql' => $query->sql,
                        'bindings' => $query->bindings,
                        'time' => $query->time . 'ms'
                    ]);
                }
            });
        }
    }

    /**
     * Batch load relationships to prevent N+1 queries
     */
    public static function batchLoadRelationships(Collection $collection, array $relationships): Collection
    {
        foreach ($relationships as $relationship) {
            $collection->load($relationship);
        }
        
        return $collection;
    }

    /**
     * Count total queries executed
     */
    public static function countQueries(): int
    {
        return count(DB::getQueryLog());
    }

    /**
     * Get query statistics
     */
    public static function getQueryStats(): array
    {
        $queries = DB::getQueryLog();
        $totalTime = array_sum(array_column($queries, 'time'));
        
        return [
            'total_queries' => count($queries),
            'total_time' => $totalTime,
            'average_time' => count($queries) > 0 ? $totalTime / count($queries) : 0,
            'slow_queries' => array_filter($queries, fn($q) => $q['time'] > 100)
        ];
    }

    /**
     * Start query logging (for development/debugging)
     */
    public static function startQueryLogging(): void
    {
        if (config('app.debug')) {
            DB::enableQueryLog();
        }
    }

    /**
     * Stop query logging and return stats
     */
    public static function stopQueryLogging(): array
    {
        if (config('app.debug')) {
            $stats = self::getQueryStats();
            DB::disableQueryLog();
            return $stats;
        }
        
        return [];
    }
}
