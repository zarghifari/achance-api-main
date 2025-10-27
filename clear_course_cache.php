<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

// Clear all course-related caches
function clearAllCourseCaches() {
    echo "Clearing all course-related caches...\n";
    
    try {
        // Method 1: Clear tagged caches
        Cache::tags(['courses'])->flush();
        echo "✅ Tagged caches cleared\n";
        
        // Method 2: Clear by pattern using Redis directly
        $redis = Redis::connection();
        
        // Get all keys that match course patterns
        $patterns = [
            '*course*',
            '*courses*',
            '*module*',
            '*lesson*',
            '*epub*'
        ];
        
        foreach ($patterns as $pattern) {
            $keys = $redis->keys($pattern);
            if (!empty($keys)) {
                $redis->del($keys);
                echo "✅ Cleared " . count($keys) . " keys matching '$pattern'\n";
            }
        }
        
        echo "🎉 All course caches cleared successfully!\n";
        
    } catch (Exception $e) {
        echo "❌ Error clearing caches: " . $e->getMessage() . "\n";
    }
}

clearAllCourseCaches();