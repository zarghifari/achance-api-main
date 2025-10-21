<?php

namespace App\Services;

use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\QuizAnswer;
use App\Models\AttemptQuiz;
use App\Models\AttemptAnswer;
use App\Models\User;
use App\Events\QuizAttemptStarted;
use App\Events\QuizAttemptCompleted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class QuizService
{
    /**
     * Create quiz attempt with answers
     */
    public function createAttemptWithAnswers(User $user, int $quizId, array $data): AttemptQuiz
    {
        return DB::transaction(function () use ($user, $quizId, $data) {
            // Validate quiz accessibility
            $quiz = $this->validateQuizAccess($user, $quizId);
            
            // Check if user can create new attempt
            $this->validateUserCanAttemptQuiz($user, $quiz);

            // Create attempt
            $attemptData = array_merge($data, [
                'quiz_id' => $quizId,
                'user_id' => $user->id,
                'started_at' => $data['started_at'] ?? now(),
                'status' => 'in_progress'
            ]);

            $attemptQuiz = AttemptQuiz::create($attemptData);

            // Process answers
            $this->processAttemptAnswers($attemptQuiz, $data['attempt_answers'], $quiz);

            // Fire event for attempt start
            event(new QuizAttemptStarted($attemptQuiz));

            // Fire completion event if quiz is completed
            if (in_array($attemptQuiz->status, ['completed', 'failed'])) {
                event(new QuizAttemptCompleted($attemptQuiz));
            }

            // Log attempt creation
            Log::info('Quiz attempt created', [
                'user_id' => $user->id,
                'quiz_id' => $quizId,
                'attempt_id' => $attemptQuiz->id,
                'score' => $attemptQuiz->score
            ]);

            // Invalidate relevant caches
            QuizCacheService::invalidateUserAttemptCache($user->id, $quizId);

            return $attemptQuiz->fresh();
        });
    }

    /**
     * Update quiz attempt with answers
     */
    public function updateAttemptWithAnswers(User $user, int $quizId, int $attemptId, array $data): AttemptQuiz
    {
        return DB::transaction(function () use ($user, $quizId, $attemptId, $data) {
            $attemptQuiz = AttemptQuiz::where('quiz_id', $quizId)
                ->where('id', $attemptId)
                ->where('user_id', $user->id)
                ->firstOrFail();

            // Check if attempt can be updated
            if ($attemptQuiz->status !== 'in_progress') {
                throw new \Exception('Cannot update completed or failed attempt');
            }

            $quiz = QuizCacheService::getQuiz($quizId);

            // Update attempt data
            $attemptQuiz->update(array_filter($data, function($key) {
                return in_array($key, ['started_at', 'completed_at']);
            }, ARRAY_FILTER_USE_KEY));

            // Delete existing answers
            AttemptAnswer::where('attempt_id', $attemptQuiz->id)->delete();

            // Process new answers
            $this->processAttemptAnswers($attemptQuiz, $data['attempt_answers'], $quiz);

            // Fire completion event if quiz is completed
            if (in_array($attemptQuiz->status, ['completed', 'failed'])) {
                event(new QuizAttemptCompleted($attemptQuiz));
            }

            // Log attempt update
            Log::info('Quiz attempt updated', [
                'user_id' => $user->id,
                'quiz_id' => $quizId,
                'attempt_id' => $attemptQuiz->id,
                'score' => $attemptQuiz->score
            ]);

            // Invalidate relevant caches
            QuizCacheService::invalidateUserAttemptCache($user->id, $quizId);

            return $attemptQuiz->fresh();
        });
    }

    /**
     * Get quiz with questions and answers (cached)
     */
    public function getQuizWithQuestionsAndAnswers(int $quizId): ?Quiz
    {
        return QuizCacheService::getQuizWithQuestionsAndAnswers($quizId);
    }

    /**
     * Get user's quiz attempts
     */
    public function getUserQuizAttempts(User $user, int $quizId): Collection
    {
        return QuizCacheService::getUserQuizAttempts($user->id, $quizId);
    }

    /**
     * Get quiz statistics
     */
    public function getQuizStatistics(int $quizId): array
    {
        return QuizCacheService::getQuizStats($quizId);
    }

    /**
     * Check if user can take quiz
     */
    public function canUserTakeQuiz(User $user, Quiz $quiz): array
    {
        $checks = [
            'can_access' => true,
            'reasons' => []
        ];

        // Check permissions
        if (!$user->can('doing own quizzes')) {
            $checks['can_access'] = false;
            $checks['reasons'][] = 'No permission to take quizzes';
        }

        // Check if quiz is published
        if (!$quiz->published_at || $quiz->published_at > now()) {
            $checks['can_access'] = false;
            $checks['reasons'][] = 'Quiz is not published yet';
        }

        // Check timing
        if ($quiz->start_at && Carbon::parse($quiz->start_at)->isFuture()) {
            $checks['can_access'] = false;
            $checks['reasons'][] = 'Quiz has not started yet';
        }

        if ($quiz->ends_at && Carbon::parse($quiz->ends_at)->isPast()) {
            $checks['can_access'] = false;
            $checks['reasons'][] = 'Quiz has ended';
        }

        // Check existing attempts
        $existingAttempts = AttemptQuiz::where('user_id', $user->id)
            ->where('quiz_id', $quiz->id)
            ->get();

        $completedAttempts = $existingAttempts->where('status', 'completed');
        
        if ($completedAttempts->count() > 0) {
            // For now, allow only one completed attempt
            $checks['can_access'] = false;
            $checks['reasons'][] = 'Already completed this quiz';
        }

        $inProgressAttempts = $existingAttempts->where('status', 'in_progress');
        if ($inProgressAttempts->count() > 0) {
            $checks['existing_attempt'] = $inProgressAttempts->first();
        }

        return $checks;
    }

    /**
     * Process attempt answers
     */
    private function processAttemptAnswers(AttemptQuiz $attemptQuiz, array $attemptAnswers, Quiz $quiz): void
    {
        // Validate and get quiz answers
        $answerIds = array_column($attemptAnswers, 'selected_answer_id');
        $quizAnswers = QuizCacheService::getQuizAnswers($answerIds);

        // Validate all answers belong to this quiz
        $this->validateAnswersBelongToQuiz($quizAnswers, $quiz->id);

        // Validate no duplicate questions
        $this->validateNoDuplicateQuestions($attemptAnswers, $quizAnswers);

        // Prepare batch insert data
        $attemptAnswersData = [];
        $correctCount = 0;
        $now = now();

        foreach ($attemptAnswers as $answer) {
            $quizAnswer = $quizAnswers[$answer['selected_answer_id']];
            $isCorrect = $quizAnswer->is_correct;
            
            $attemptAnswersData[] = [
                'attempt_id' => $attemptQuiz->id,
                'quiz_question_id' => $quizAnswer->quiz_question_id,
                'selected_answer_id' => $answer['selected_answer_id'],
                'answer_text' => $answer['answer_text'] ?? null,
                'is_correct' => $isCorrect,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if ($isCorrect) {
                $correctCount++;
            }
        }

        // Single bulk insert
        AttemptAnswer::insert($attemptAnswersData);

        // Calculate and update scores
        $this->calculateAndUpdateScore($attemptQuiz, $correctCount, count($attemptAnswers), $quiz);
    }

    /**
     * Calculate attempt score and return result data
     */
    public function calculateAttemptScore(AttemptQuiz $attemptQuiz): array
    {
        $attemptAnswers = $attemptQuiz->attemptAnswers()->get();
        $correctCount = $attemptAnswers->where('is_correct', true)->count();
        $totalAnswered = $attemptAnswers->count();
        $totalQuestions = $attemptQuiz->quiz->quizQuestions()->count();
        
        $score = $totalAnswered > 0 ? round(($correctCount / $totalAnswered) * 100, 2) : 0;
        
        return [
            'score' => $score,
            'correct_answers' => $correctCount,
            'total_questions' => $totalQuestions,
            'total_answered' => $totalAnswered
        ];
    }

    /**
     * Calculate and update attempt score
     */
    private function calculateAndUpdateScore(AttemptQuiz $attemptQuiz, int $correctCount, int $totalAnswered, Quiz $quiz): void
    {
        $score = $totalAnswered > 0 ? round(($correctCount / $totalAnswered) * 100, 2) : 0;
        
        $updates = ['score' => $score];
        
        // Get total quiz questions
        $totalQuizQuestions = $quiz->quizQuestions()->count();
        
        if ($totalAnswered == $totalQuizQuestions) {
            $status = ($score >= 50) ? 'completed' : 'failed';
            $updates['status'] = $status;
            $updates['completed_at'] = now();
        } else {
            $updates['status'] = 'in_progress';
        }
        
        $attemptQuiz->update($updates);
    }

    /**
     * Validate quiz access
     */
    private function validateQuizAccess(User $user, int $quizId): Quiz
    {
        $quiz = QuizCacheService::getQuiz($quizId);
        
        if (!$quiz) {
            throw new \Exception('Quiz not found');
        }

        $accessCheck = $this->canUserTakeQuiz($user, $quiz);
        
        if (!$accessCheck['can_access']) {
            throw new \Exception('Cannot access quiz: ' . implode(', ', $accessCheck['reasons']));
        }

        return $quiz;
    }

    /**
     * Validate user can attempt quiz
     */
    private function validateUserCanAttemptQuiz(User $user, Quiz $quiz): void
    {
        $accessCheck = $this->canUserTakeQuiz($user, $quiz);
        
        if (!$accessCheck['can_access']) {
            throw new \Exception('Cannot create quiz attempt: ' . implode(', ', $accessCheck['reasons']));
        }
    }

    /**
     * Validate answers belong to quiz
     */
    private function validateAnswersBelongToQuiz(Collection $quizAnswers, int $quizId): void
    {
        $invalidAnswers = $quizAnswers->filter(function ($answer) use ($quizId) {
            return $answer->quizQuestions->quiz_id !== $quizId;
        });

        if ($invalidAnswers->count() > 0) {
            throw new \Exception('Some answers do not belong to this quiz');
        }
    }

    /**
     * Validate no duplicate questions in answers
     */
    private function validateNoDuplicateQuestions(array $attemptAnswers, Collection $quizAnswers): void
    {
        $questionIds = [];
        
        foreach ($attemptAnswers as $answer) {
            $quizAnswer = $quizAnswers[$answer['selected_answer_id']];
            $questionId = $quizAnswer->quiz_question_id;
            
            if (in_array($questionId, $questionIds)) {
                throw new \Exception('Duplicate answers for the same question are not allowed');
            }
            
            $questionIds[] = $questionId;
        }
    }

    /**
     * Get quiz leaderboard
     */
    public function getQuizLeaderboard(int $quizId, int $limit = 10): Collection
    {
        return AttemptQuiz::with('user:id,name,email')
            ->where('quiz_id', $quizId)
            ->where('status', 'completed')
            ->orderBy('score', 'desc')
            ->orderBy('completed_at', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get quiz attempt details with answers
     */
    public function getAttemptWithAnswers(User $user, int $quizId, int $attemptId): AttemptQuiz
    {
        $attempt = AttemptQuiz::with(['attemptAnswers.quizQuestions', 'attemptAnswers.quizAnswers'])
            ->where('quiz_id', $quizId)
            ->where('id', $attemptId)
            ->firstOrFail();

        // Check if user can view this attempt
        if ($attempt->user_id !== $user->id && !$user->can('view all attempt quizzes')) {
            throw new \Exception('Unauthorized to view this attempt');
        }

        return $attempt;
    }
}
