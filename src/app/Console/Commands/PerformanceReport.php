<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CacheService;
use Illuminate\Support\Facades\DB;

class PerformanceReport extends Command
{
    protected $signature = 'performance:report';
    protected $description = 'Generate a performance report for the API';

    public function handle()
    {
        $this->info('🚀 API Performance Report');
        $this->info('========================');

        // Database Stats
        $this->info('📊 Database Statistics:');
        $this->showDatabaseStats();

        // Cache Stats
        $this->info("\n💾 Cache Statistics:");
        $this->showCacheStats();

        // Slow Queries (if any)
        $this->info("\n🐌 Slow Query Analysis:");
        $this->showSlowQueries();

        return 0;
    }

    private function showDatabaseStats()
    {
        try {
            $connectionStatus = DB::connection()->getPdo() ? 'Connected' : 'Disconnected';
            $this->line("Status: {$connectionStatus}");

            // Get table sizes
            $tables = DB::select("
                SELECT 
                    TABLE_NAME as table_name,
                    ROUND(((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024), 2) AS size_mb,
                    TABLE_ROWS as table_rows
                FROM information_schema.tables 
                WHERE TABLE_SCHEMA = DATABASE()
                ORDER BY (DATA_LENGTH + INDEX_LENGTH) DESC
                LIMIT 10
            ");

            $this->table(['Table', 'Size (MB)', 'Rows'], array_map(function($table) {
                return [$table->table_name, $table->size_mb, $table->table_rows];
            }, $tables));

        } catch (\Exception $e) {
            $this->error("Database error: " . $e->getMessage());
        }
    }

    private function showCacheStats()
    {
        $stats = CacheService::getCacheStats();
        
        if (isset($stats['error'])) {
            $this->error($stats['error']);
            return;
        }

        $this->line("Memory Used: " . ($stats['used_memory'] ?? 'N/A'));
        $this->line("RSS Memory: " . ($stats['used_memory_rss'] ?? 'N/A'));
        $this->line("Max Memory: " . ($stats['max_memory'] ?? 'N/A'));
        $this->line("Connected Clients: " . ($stats['connected_clients'] ?? 'N/A'));
        $this->line("Commands Processed: " . ($stats['total_commands_processed'] ?? 'N/A'));
    }

    private function showSlowQueries()
    {
        // This would typically read from slow query log
        $this->line("Check your slow query log at: /var/log/mysql/mysql-slow.log");
        $this->line("You can also use: SHOW PROCESSLIST; to see current running queries");
        
        // Example of how to check for common performance issues
        $this->warn("Performance Tips:");
        $this->line("- Use eager loading with ->with() to prevent N+1 queries");
        $this->line("- Add database indexes for frequently queried columns");
        $this->line("- Use caching for expensive operations");
        $this->line("- Consider pagination for large datasets");
    }
}
