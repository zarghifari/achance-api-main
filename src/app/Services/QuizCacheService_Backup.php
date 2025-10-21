<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\AttemptQuiz;

class QuizCacheService
{
    private const CACHE_TTL = 3600; // 1 hour
    private const SHORT_TTL = 300; // 5 minutes
    private const CACHE_PREFIX = 'quiz_system';

    /**
     * Cache a single quiz
     */
    public static function getQuiz(int $quizId): ?Quiz
    {
        $key = self::getCacheKey('quiz', $quizId);
        
        try {
            if (config('cache.default') === 'redis') {
                return Cache::tags(['quizzes', "quiz_{$quizId}"])->remember($key, self::CACHE_TTL, function () use ($quizId) {
                    return Quiz::find($quizId);
                });
            } else {
                return Cache::remember($key, self::CACHE_TTL, function () use ($quizId) {
                    return Quiz::find($quizId);
                });
            }
        } catch (\Exception $e) {
            \Log::warning('Quiz cache operation failed, executing query directly: ' . $e->getMessage());
            return Quiz::find($quizId);
        }
    }

    /**
     * Cache quiz with questions and answers
     */
    public static function getQuizWithQuestionsAndAnswers(int $quizId): ?Quiz
    {
        $key = self::getCacheKey('quiz_qqa', $quizId);
        
        try {
            if (config('cache.default') === 'redis') {
                return Cache::tags(['quizzes', 'quiz_questions', 'quiz_answers', "quiz_{$quizId}"])->remember(
                    $key, 
                    self::CACHE_TTL, 
                    function () use ($quizId) {
                        return Quiz::withQuestionsAndAnswers()->find($quizId);
                    }
                );
            } else {
                return Cache::remember($key, self::CACHE_TTL, function () use ($quizId) {
                    return Quiz::withQuestionsAndAnswers()->find($quizId);
                });
            }
        } catch (\Exception $e) {
            \Log::warning('Quiz cache operation failed, executing query directly: ' . $e->getMessage());
            return Quiz::withQuestionsAndAnswers()->find($quizId);
        }
    }

    /**
     * Cache quiz questions with answers
     */
    public static function getQuizQuestions(int $quizId): Collection
    {
        $key = self::getCacheKey('questions_list', $quizId);
        
        try {
            if (config('cache.default') === 'redis') {
                return Cache::tags(['quiz_questions', 'quiz_answers', "quiz_{$quizId}"])->remember(
                    $key, 
                    self::CACHE_TTL, 
                    function () use ($quizId) {
                        return QuizQuestion::with('quizAnswers')
                            ->where('quiz_id', $quizId)
                            ->orderBy('question_number', 'asc')
                            ->get();
                    }
                );
            } else {
                return Cache::remember($key, self::CACHE_TTL, function () use ($quizId) {
                    return QuizQuestion::with('quizAnswers')
                        ->where('quiz_id', $quizId)
                        ->orderBy('question_number', 'asc')
                        ->get();
                });
            }
        } catch (\Exception $e) {
            \Log::warning('Quiz cache operation failed, executing query directly: ' . $e->getMessage());
            return QuizQuestion::with('quizAnswers')
                ->where('quiz_id', $quizId)
                ->orderBy('question_number', 'asc')
                ->get();
        }
    }

    /**
     * Cache user's quiz attempts
     */
    public static function getUserQuizAttempts(int $userId, int $quizId): Collection
    {
        $key = self::getCacheKey('user_attempts', $userId, $quizId);
        
        try {
            if (config('cache.default') === 'redis') {
                return Cache::tags(['attempts', "user_{$userId}", "quiz_{$quizId}"])->remember(
                    $key, 
                    self::SHORT_TTL, 
                    function () use ($userId, $quizId) {
                        return AttemptQuiz::where('user_id', $userId)
                            ->where('quiz_id', $quizId)
                            ->orderBy('created_at', 'desc')
                            ->get();
                    }
                );
            } else {
                return Cache::remember($key, self::SHORT_TTL, function () use ($userId, $quizId) {
                    return AttemptQuiz::where('user_id', $userId)
                        ->where('quiz_id', $quizId)
                        ->orderBy('created_at', 'desc')
                        ->get();
                });
            }
        } catch (\Exception $e) {
            \Log::warning('Quiz cache operation failed, executing query directly: ' . $e->getMessage());
            return AttemptQuiz::where('user_id', $userId)
                ->where('quiz_id', $quizId)
                ->orderBy('created_at', 'desc')
                ->get();
        }
    }

    /**
     * Cache quiz statistics
     */
    public static function getQuizStats(int $quizId): array
    {
        $key = self::getCacheKey('quiz_stats', $quizId);
        
        try {
            if (config('cache.default') === 'redis') {
                return Cache::tags(['quiz_stats', "quiz_{$quizId}"])->remember(
                    $key, 
                    self::CACHE_TTL, 
                    function () use ($quizId) {
                        $attempts = AttemptQuiz::where('quiz_id', $quizId)->get();
                        
                        return [
                            'total_attempts' => $attempts->count(),
                            'completed_attempts' => $attempts->where('status', 'completed')->count(),
                            'failed_attempts' => $attempts->where('status', 'failed')->count(),
                            'in_progress_attempts' => $attempts->where('status', 'in_progress')->count(),
                            'average_score' => $attempts->where('status', 'completed')->avg('score') ?? 0,
                            'highest_score' => $attempts->max('score') ?? 0,
                            'lowest_score' => $attempts->where('score', '>', 0)->min('score') ?? 0,
                        ];
                    }
                );
            } else {
                return Cache::remember($key, self::CACHE_TTL, function () use ($quizId) {
                    $attempts = AttemptQuiz::where('quiz_id', $quizId)->get();
                    
                    return [
                        'total_attempts' => $attempts->count(),
                        'completed_attempts' => $attempts->where('status', 'completed')->count(),
                        'failed_attempts' => $attempts->where('status', 'failed')->count(),
                        'in_progress_attempts' => $attempts->where('status', 'in_progress')->count(),
                        'average_score' => $attempts->where('status', 'completed')->avg('score') ?? 0,
                        'highest_score' => $attempts->max('score') ?? 0,
                        'lowest_score' => $attempts->where('score', '>', 0)->min('score') ?? 0,
                    ];
                });
            }
        } catch (\Exception $e) {
            \Log::warning('Quiz cache operation failed, executing query directly: ' . $e->getMessage());
            $attempts = AttemptQuiz::where('quiz_id', $quizId)->get();
            
            return [
                'total_attempts' => $attempts->count(),
                'completed_attempts' => $attempts->where('status', 'completed')->count(),
                'failed_attempts' => $attempts->where('status', 'failed')->count(),
                'in_progress_attempts' => $attempts->where('status', 'in_progress')->count(),
                'average_score' => $attempts->where('status', 'completed')->avg('score') ?? 0,
                'highest_score' => $attempts->max('score') ?? 0,
                'lowest_score' => $attempts->where('score', '>', 0)->min('score') ?? 0,
            ];
        }
    }

    /**
     * Cache quiz answers for validation
     */
    public static function getQuizAnswers(array $answerIds): Collection
    {
        $key = self::getCacheKey('answers_batch', md5(implode(',', $answerIds)));
        
        try {
            if (config('cache.default') === 'redis') {
                return Cache::tags(['quiz_answers'])->remember(
                    $key, 
                    self::CACHE_TTL, 
                    function () use ($answerIds) {
                        return \App\Models\QuizAnswer::with('quizQuestions')
                            ->whereIn('id', $answerIds)
                            ->get()
                            ->keyBy('id');
                    }
                );
            } else {
                return Cache::remember($key, self::CACHE_TTL, function () use ($answerIds) {
                    return \App\Models\QuizAnswer::with('quizQuestions')
                        ->whereIn('id', $answerIds)
                        ->get()
                        ->keyBy('id');
                });
            }
        } catch (\Exception $e) {
            \Log::warning('Quiz cache operation failed, executing query directly: ' . $e->getMessage());
            return \App\Models\QuizAnswer::with('quizQuestions')
                ->whereIn('id', $answerIds)
                ->get()
                ->keyBy('id');
        }
    }

    /**
     * Invalidate all quiz-related caches
     */
    public static function invalidateQuizCache(int $quizId): void
    {
        try {
            if (config('cache.default') === 'redis') {
                Cache::tags(["quiz_{$quizId}"])->flush();
                Cache::tags(['quiz_stats'])->flush();
            } else {
                // For file cache, clear specific keys
                $keys = [
                    self::getCacheKey('quiz', $quizId),
                    self::getCacheKey('quiz_with_questions', $quizId),
                    self::getCacheKey('quiz_questions', $quizId),
                    self::getCacheKey('quiz_stats', $quizId),
                ];
                
                foreach ($keys as $key) {
                    Cache::forget($key);
                }
            }
        } catch (\Exception $e) {
            \Log::warning('Quiz cache invalidation failed: ' . $e->getMessage());
        }
    }

    /**
     * Invalidate question-related caches
     */
    public static function invalidateQuestionCache(int $quizId, int $questionId = null): void
    {
        try {
            if (config('cache.default') === 'redis') {
                Cache::tags(['quiz_questions'])->flush();
                Cache::tags(['quiz_answers'])->flush();
                self::invalidateQuizCache($quizId);
            } else {
                // For file cache, clear specific keys
                $keys = [
                    self::getCacheKey('quiz_questions', $quizId),
                    self::getCacheKey('quiz_with_questions', $quizId),
                ];
                
                if ($questionId) {
                    $keys[] = self::getCacheKey('quiz_question', $questionId);
                }
                
                foreach ($keys as $key) {
                    Cache::forget($key);
                }
                
                self::invalidateQuizCache($quizId);
            }
        } catch (\Exception $e) {
            \Log::warning('Question cache invalidation failed: ' . $e->getMessage());
        }
    }

    /**
     * Invalidate user attempt caches
     */
    public static function invalidateUserAttemptCache(int $userId, int $quizId = null): void
    {
        try {
            if (config('cache.default') === 'redis') {
                Cache::tags(["user_{$userId}"])->flush();
                Cache::tags(['attempts'])->flush();
                Cache::tags(['quiz_stats'])->flush();
                
                if ($quizId) {
                    Cache::tags(["quiz_{$quizId}"])->flush();
                }
            } else {
                // For file cache, clear specific keys
                $keys = [
                    self::getCacheKey('quiz_attempt_user', $userId),
                ];
                
                if ($quizId) {
                    $keys[] = self::getCacheKey('quiz_attempt_user', $userId . '_' . $quizId);
                    $keys[] = self::getCacheKey('quiz_stats', $quizId);
                    
                    // Also invalidate the specific quiz
                    self::invalidateQuizCache($quizId);
                }
                
                foreach ($keys as $key) {
                    Cache::forget($key);
                }
            }
        } catch (\Exception $e) {
            \Log::warning('User attempt cache invalidation failed: ' . $e->getMessage());
        }
    }

    /**
     * Invalidate all quiz system caches
     */
    public static function invalidateAllQuizCaches(): void
    {
        try {
            if (config('cache.default') === 'redis') {
                Cache::tags(['quizzes', 'quiz_questions', 'quiz_answers', 'attempts', 'quiz_stats'])->flush();
            } else {
                // For file cache, we can't flush by tags, so clear the entire cache
                // This is less efficient but ensures all quiz-related cache is cleared
                Cache::flush();
            }
        } catch (\Exception $e) {
            \Log::warning('All quiz caches invalidation failed: ' . $e->getMessage());
        }
    }

    /**
     * Generate cache key
     */
    private static function getCacheKey(string $type, ...$params): string
    {
        return sprintf('%s:%s:%s', self::CACHE_PREFIX, $type, implode('_', $params));
    }

    /**
     * Warm up critical quiz caches
     */
    public static function warmUpQuizCache(int $quizId): void
    {
        self::getQuiz($quizId);
        self::getQuizWithQuestionsAndAnswers($quizId);
        self::getQuizQuestions($quizId);
        self::getQuizStats($quizId);
    }

    /**
     * Get cache statistics for quiz system
     */
    public static function getQuizCacheStats(): array
    {
        try {
            $redis = \Illuminate\Support\Facades\Redis::connection();
            $keys = $redis->keys(self::CACHE_PREFIX . '*');
            
            return [
                'total_quiz_cache_keys' => count($keys),
                'cache_memory_usage' => $redis->info('memory')['used_memory_human'] ?? 'N/A',
                'quiz_cache_prefix' => self::CACHE_PREFIX,
            ];
        } catch (\Exception $e) {
            return ['error' => 'Cannot retrieve cache statistics'];
        }
    }
}
