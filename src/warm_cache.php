<?php
/**
 * Cache Warming Script
 * 
 * This script pre-populates the Redis cache with critical API responses
 * to eliminate cold starts after deployment.
 * 
 * Usage:
 *   php warm_cache.php
 * 
 * Or add to deployment:
 *   docker-compose exec app1 php warm_cache.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

echo "\n";
echo "═══════════════════════════════════════════════════\n";
echo "  CACHE WARMING SCRIPT - API Response Pre-loading\n";
echo "═══════════════════════════════════════════════════\n\n";

$startTime = microtime(true);
$baseUrl = env('APP_URL', 'http://localhost');
$warmedCount = 0;
$errorCount = 0;

// Helper function to make authenticated request
function warmEndpoint($url, $method = 'GET', $data = null) {
    global $warmedCount, $errorCount;
    
    try {
        echo "Warming: {$url} ... ";
        
        $response = Http::timeout(30)->withHeaders([
            'Accept' => 'application/json',
        ])->{strtolower($method)}($url, $data ?? []);
        
        if ($response->successful()) {
            echo "✓ [{$response->status()}]\n";
            $warmedCount++;
            return true;
        } else {
            echo "✗ [{$response->status()}]\n";
            $errorCount++;
            return false;
        }
    } catch (Exception $e) {
        echo "✗ Error: {$e->getMessage()}\n";
        $errorCount++;
        return false;
    }
}

try {
    // 1. Warm course list
    echo "\n[1/4] Warming course list...\n";
    warmEndpoint("{$baseUrl}/api/courses");
    
    // 2. Warm individual courses
    echo "\n[2/4] Warming individual courses...\n";
    $courses = DB::table('courses')->select('id', 'title')->get();
    
    if ($courses->isEmpty()) {
        echo "  ⚠ No courses found in database\n";
    } else {
        echo "  Found {$courses->count()} courses\n";
        foreach ($courses as $course) {
            warmEndpoint("{$baseUrl}/api/courses/{$course->id}");
            usleep(100000); // 100ms delay to avoid overwhelming the server
        }
    }
    
    // 3. Warm modules
    echo "\n[3/4] Warming modules...\n";
    $modules = DB::table('modules')
        ->select('id', 'title', 'course_id')
        ->limit(20) // Limit to first 20 for speed
        ->get();
    
    if ($modules->isEmpty()) {
        echo "  ⚠ No modules found in database\n";
    } else {
        echo "  Found {$modules->count()} modules (limited to 20)\n";
        foreach ($modules as $module) {
            warmEndpoint("{$baseUrl}/api/modules/{$module->id}");
            usleep(100000);
        }
    }
    
    // 4. Warm lessons
    echo "\n[4/4] Warming lessons...\n";
    $lessons = DB::table('lessons')
        ->select('id', 'title', 'module_id')
        ->limit(30) // Limit to first 30 for speed
        ->get();
    
    if ($lessons->isEmpty()) {
        echo "  ⚠ No lessons found in database\n";
    } else {
        echo "  Found {$lessons->count()} lessons (limited to 30)\n";
        foreach ($lessons as $lesson) {
            warmEndpoint("{$baseUrl}/api/lessons/{$lesson->id}");
            usleep(100000);
        }
    }
    
    // Summary
    $endTime = microtime(true);
    $duration = round($endTime - $startTime, 2);
    
    echo "\n";
    echo "═══════════════════════════════════════════════════\n";
    echo "  CACHE WARMING COMPLETE\n";
    echo "═══════════════════════════════════════════════════\n";
    echo "  ✓ Warmed: {$warmedCount} endpoints\n";
    echo "  ✗ Failed: {$errorCount} endpoints\n";
    echo "  ⏱ Duration: {$duration}s\n";
    echo "═══════════════════════════════════════════════════\n\n";
    
    // Check Redis cache stats
    try {
        $redis = app('redis')->connection();
        $info = $redis->info('stats');
        echo "Redis Stats:\n";
        echo "  Total keys: " . $redis->dbSize() . "\n";
        echo "  Keyspace hits: " . ($info['keyspace_hits'] ?? 'N/A') . "\n";
        echo "  Keyspace misses: " . ($info['keyspace_misses'] ?? 'N/A') . "\n\n";
    } catch (Exception $e) {
        echo "⚠ Could not retrieve Redis stats: {$e->getMessage()}\n\n";
    }
    
    exit($errorCount > 0 ? 1 : 0);
    
} catch (Exception $e) {
    echo "\n✗ FATAL ERROR: {$e->getMessage()}\n";
    echo "  File: {$e->getFile()}:{$e->getLine()}\n\n";
    exit(1);
}
