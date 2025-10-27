<?php

namespace App\Services;

use App\Models\Quiz;
use App\Models\AttemptQuiz;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class QuizCacheService
{
    const CACHE_TTL = 3600; // 1 hour

    /**
     * Cache quiz by ID
     */
    public static function getQuiz(int $quizId): ?Quiz
    {
        $key = self::getCacheKey('quiz', $quizId);
        return Cache::tags(['quizzes'])->remember(
            $key, 
            self::CACHE_TTL, 
            function () use ($quizId) {
                return Quiz::find($quizId);
            }
        );
    }

    /**
     * Cache quiz with questions and answers
     */
    public static function getQuizWithQuestionsAndAnswers(int $quizId): ?Quiz
    {
        $key = self::getCacheKey('quiz_qqa', $quizId);
        return Cache::tags(['quizzes', 'quiz_questions', 'quiz_answers', "quiz_{$quizId}"])->remember(
            $key, 
            self::CACHE_TTL, 
            function () use ($quizId) {
                return Quiz::withQuestionsAndAnswers()->find($quizId);
            }
        );
    }

    /**
     * Cache quiz questions
     */
    public static function getQuizQuestions(int $quizId): Collection
    {
        $key = self::getCacheKey('quiz_questions', $quizId);
        return Cache::tags(['quiz_questions', "quiz_{$quizId}"])->remember(
            $key, 
            self::CACHE_TTL, 
            function () use ($quizId) {
                return \App\Models\QuizQuestion::where('quiz_id', $quizId)
                    ->with('quizAnswers')
                    ->orderBy('question_number')
                    ->get();
            }
        );
    }

    /**
     * Cache user quiz attempts
     */
    public static function getUserQuizAttempts(int $userId, int $quizId): Collection
    {
        $key = self::getCacheKey('quiz_attempt_user', $userId . '_' . $quizId);
        return Cache::tags(['attempts', "user_{$userId}", "quiz_{$quizId}"])->remember(
            $key, 
            self::CACHE_TTL, 
            function () use ($userId, $quizId) {
                return AttemptQuiz::where('user_id', $userId)
                    ->where('quiz_id', $quizId)
                    ->with(['attemptAnswers.quizAnswers.quizQuestions'])
                    ->orderBy('created_at', 'desc')
                    ->get();
            }
        );
    }

    /**
     * Cache quiz statistics
     */
    public static function getQuizStats(int $quizId): array
    {
        $key = self::getCacheKey('quiz_stats', $quizId);
        return Cache::tags(['quiz_stats', "quiz_{$quizId}"])->remember(
            $key, 
            self::CACHE_TTL, 
            function () use ($quizId) {
                $attempts = AttemptQuiz::where('quiz_id', $quizId);
                
                return [
                    'total_attempts' => $attempts->count(),
                    'completed_attempts' => $attempts->where('status', 'completed')->count(),
                    'average_score' => round($attempts->where('status', 'completed')->avg('score') ?: 0, 2),
                    'highest_score' => $attempts->where('status', 'completed')->max('score') ?: 0,
                    'lowest_score' => $attempts->where('status', 'completed')->min('score') ?: 0,
                ];
            }
        );
    }

    /**
     * Cache quiz answers for validation
     */
    public static function getQuizAnswers(array $answerIds): Collection
    {
        $key = self::getCacheKey('answers_batch', md5(implode(',', $answerIds)));
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
    }

    /**
     * Invalidate quiz-related cache
     */
    public static function invalidateQuizCache(int $quizId): void
    {
        Cache::tags(["quiz_{$quizId}"])->flush();
        Cache::tags(['quiz_stats'])->flush();
    }

    /**
     * Invalidate question-related caches
     */
    public static function invalidateQuestionCache(int $quizId, int $questionId = null): void
    {
        Cache::tags(['quiz_questions'])->flush();
        Cache::tags(['quiz_answers'])->flush();
        self::invalidateQuizCache($quizId);
    }

    /**
     * Invalidate user attempt caches
     */
    public static function invalidateUserAttemptCache(int $userId, int $quizId = null): void
    {
        Cache::tags(["user_{$userId}"])->flush();
        Cache::tags(['attempts'])->flush();
        Cache::tags(['quiz_stats'])->flush();
        
        if ($quizId) {
            Cache::tags(["quiz_{$quizId}"])->flush();
        }
    }

    /**
     * Warm up quiz cache by preloading data
     */
    public static function warmUpQuizCache(int $quizId): void
    {
        // Preload quiz with questions and answers
        self::getQuizWithQuestionsAndAnswers($quizId);
        
        // Preload quiz questions
        self::getQuizQuestions($quizId);
        
        // Preload quiz statistics
        self::getQuizStats($quizId);
    }

    /**
     * Invalidate all quiz system caches
     */
    public static function invalidateAllQuizCaches(): void
    {
        Cache::tags(['quizzes', 'quiz_questions', 'quiz_answers', 'attempts', 'quiz_stats'])->flush();
    }

    /**
     * Generate cache key
     */
    private static function getCacheKey(string $type, mixed $identifier): string
    {
        return "quiz_{$type}_{$identifier}";
    }
}
