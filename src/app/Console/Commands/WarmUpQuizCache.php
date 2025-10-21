<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\QuizCacheService;
use App\Models\Quiz;
use Illuminate\Support\Facades\Log;

class WarmUpQuizCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quiz:cache-warmup 
                           {--quiz-id= : Specific quiz ID to warm up}
                           {--active-only : Only warm up active quizzes}
                           {--force : Force cache refresh}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Warm up quiz system caches for better performance';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting quiz cache warm-up...');

        $quizId = $this->option('quiz-id');
        $activeOnly = $this->option('active-only');
        $force = $this->option('force');

        try {
            if ($quizId) {
                $this->warmUpSingleQuiz($quizId, $force);
            } else {
                $this->warmUpMultipleQuizzes($activeOnly, $force);
            }

            $this->info('Quiz cache warm-up completed successfully!');
            
        } catch (\Exception $e) {
            $this->error('Failed to warm up quiz cache: ' . $e->getMessage());
            Log::error('Quiz cache warm-up failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return 1;
        }

        return 0;
    }

    /**
     * Warm up cache for a single quiz
     */
    private function warmUpSingleQuiz(int $quizId, bool $force = false): void
    {
        $quiz = Quiz::find($quizId);
        
        if (!$quiz) {
            throw new \Exception("Quiz with ID {$quizId} not found");
        }

        $this->info("Warming up cache for quiz: {$quiz->title} (ID: {$quizId})");

        if ($force) {
            QuizCacheService::invalidateQuizCache($quizId);
        }

        // Warm up all quiz-related caches
        QuizCacheService::warmUpQuizCache($quizId);

        $this->line("✓ Cache warmed up for quiz: {$quiz->title}");
    }

    /**
     * Warm up cache for multiple quizzes
     */
    private function warmUpMultipleQuizzes(bool $activeOnly = false, bool $force = false): void
    {
        $query = Quiz::query();
        
        if ($activeOnly) {
            $query->active();
            $this->info('Warming up cache for active quizzes only...');
        } else {
            $this->info('Warming up cache for all quizzes...');
        }

        $quizzes = $query->get(['id', 'title']);
        $totalQuizzes = $quizzes->count();

        if ($totalQuizzes === 0) {
            $this->warn('No quizzes found to warm up');
            return;
        }

        $this->info("Found {$totalQuizzes} quizzes to warm up");

        if ($force) {
            $this->info('Force refresh enabled - invalidating existing caches...');
            QuizCacheService::invalidateAllQuizCaches();
        }

        $progressBar = $this->output->createProgressBar($totalQuizzes);
        $progressBar->start();

        $successCount = 0;
        $failureCount = 0;

        foreach ($quizzes as $quiz) {
            try {
                if ($force) {
                    QuizCacheService::invalidateQuizCache($quiz->id);
                }

                QuizCacheService::warmUpQuizCache($quiz->id);
                $successCount++;
                
            } catch (\Exception $e) {
                $failureCount++;
                Log::warning('Failed to warm up cache for quiz', [
                    'quiz_id' => $quiz->id,
                    'quiz_title' => $quiz->title,
                    'error' => $e->getMessage()
                ]);
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        $this->info("Cache warm-up completed:");
        $this->line("  ✓ Successfully warmed up: {$successCount} quizzes");
        
        if ($failureCount > 0) {
            $this->warn("  ✗ Failed to warm up: {$failureCount} quizzes");
        }

        // Display cache statistics
        $this->displayCacheStatistics();
    }

    /**
     * Display cache statistics
     */
    private function displayCacheStatistics(): void
    {
        try {
            $stats = QuizCacheService::getQuizCacheStats();
            
            $this->newLine();
            $this->info('Quiz Cache Statistics:');
            $this->line("  Total quiz cache keys: {$stats['total_quiz_cache_keys']}");
            $this->line("  Cache memory usage: {$stats['cache_memory_usage']}");
            
        } catch (\Exception $e) {
            $this->warn('Could not retrieve cache statistics: ' . $e->getMessage());
        }
    }
}
