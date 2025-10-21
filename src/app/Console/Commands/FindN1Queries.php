<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\Finder;

class FindN1Queries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'n1:find {--fix : Attempt to suggest fixes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Find potential N+1 query patterns in controllers and suggest fixes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Scanning for potential N+1 query patterns...');
        
        $finder = new Finder();
        $controllerFiles = $finder->files()->in(app_path('Http/Controllers'))->name('*.php');
        
        $issues = [];
        
        foreach ($controllerFiles as $file) {
            $content = File::get($file->getRealPath());
            $fileName = $file->getRelativePathname();
            
            // Look for potential N+1 patterns
            $patterns = [
                'foreach.*->.*\(' => 'Potential N+1: Accessing relationships inside foreach loop',
                'foreach.*find\(' => 'Potential N+1: Using find() inside foreach loop',
                'foreach.*where\(' => 'Potential N+1: Using where() inside foreach loop',
                '->get\(\).*foreach' => 'Potential N+1: Looping through get() results without eager loading',
                'all\(\).*foreach.*->' => 'Potential N+1: Using all() then accessing relationships in loop'
            ];
            
            foreach ($patterns as $pattern => $description) {
                if (preg_match('/' . $pattern . '/i', $content, $matches)) {
                    $issues[] = [
                        'file' => $fileName,
                        'pattern' => $pattern,
                        'description' => $description,
                        'line_context' => trim($matches[0])
                    ];
                }
            }
        }
        
        if (empty($issues)) {
            $this->info('✅ No obvious N+1 query patterns found!');
            return;
        }
        
        $this->warn("⚠️  Found " . count($issues) . " potential N+1 query issues:");
        
        foreach ($issues as $issue) {
            $this->line('');
            $this->error("📁 File: {$issue['file']}");
            $this->line("🔴 Issue: {$issue['description']}");
            $this->line("💻 Code: {$issue['line_context']}");
            
            if ($this->option('fix')) {
                $this->line('');
                $this->info('💡 Suggested fixes:');
                $this->suggestFix($issue['pattern']);
            }
        }
        
        $this->line('');
        $this->info('🛠️  To get fix suggestions, run: php artisan n1:find --fix');
        $this->info('📖 For more help, check the N+1 query prevention guide in your project.');
    }
    
    private function suggestFix($pattern)
    {
        $suggestions = [
            'foreach.*->.*\(' => [
                '• Use eager loading: Model::with([\'relationship\'])->get()',
                '• Preload relationships before the loop',
                '• Consider using collections and pluck() method'
            ],
            'foreach.*find\(' => [
                '• Collect IDs first, then use whereIn() with single query',
                '• Use eager loading if accessing relationships',
                '• Consider caching frequently accessed models'
            ],
            'foreach.*where\(' => [
                '• Move where() clause outside the loop',
                '• Use whereIn() with collected values',
                '• Consider using database joins instead'
            ],
            '->get\(\).*foreach' => [
                '• Add with() method for eager loading relationships',
                '• Use specific select() to reduce data transfer',
                '• Consider pagination for large datasets'
            ],
            'all\(\).*foreach.*->' => [
                '• Replace all() with specific query using with()',
                '• Use select() to limit fields if not all are needed',
                '• Consider chunking for large datasets'
            ]
        ];
        
        foreach ($suggestions[$pattern] ?? ['• Consider using eager loading or restructuring the query'] as $suggestion) {
            $this->line("  $suggestion");
        }
    }
}
