<?php

namespace App\Listeners;

use App\Events\QuizAttemptCompleted;
use App\Services\QuizCacheService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

class HandleQuizAttemptCompleted implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(QuizAttemptCompleted $event): void
    {
        $attempt = $event->attempt;
        $user = $event->user;
        $quiz = $event->quiz;

        // Log the completion
        Log::info('Quiz attempt completed', [
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'attempt_id' => $attempt->id,
            'score' => $attempt->score,
            'status' => $attempt->status,
        ]);

        // Invalidate user-specific caches
        QuizCacheService::invalidateUserAttemptCache($user->id, $quiz->id);

        // Update user activity (if you have an activity tracking system)
        $this->updateUserActivity($user, $quiz, $attempt);

        // Send notifications if needed
        $this->sendCompletionNotifications($user, $quiz, $attempt);

        // Update learning outcomes progress (if applicable)
        $this->updateLearningProgress($user, $quiz, $attempt);

        // Generate certificate if passed (if you have a certificate system)
        if ($attempt->status === 'completed' && $attempt->score >= 80) {
            $this->generateCertificate($user, $quiz, $attempt);
        }
    }

    /**
     * Update user activity
     */
    private function updateUserActivity($user, $quiz, $attempt): void
    {
        try {
            // Update user activities if the model exists
            if (class_exists(\App\Models\UserActivity::class)) {
                \App\Models\UserActivity::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'activity_type' => 'quiz_completion',
                        'activity_id' => $quiz->id,
                    ],
                    [
                        'last_seen_at' => now(),
                        'metadata' => json_encode([
                            'attempt_id' => $attempt->id,
                            'score' => $attempt->score,
                            'status' => $attempt->status,
                            'completed_at' => $attempt->completed_at,
                        ])
                    ]
                );
            }
        } catch (\Exception $e) {
            Log::warning('Failed to update user activity for quiz completion', [
                'user_id' => $user->id,
                'quiz_id' => $quiz->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send completion notifications
     */
    private function sendCompletionNotifications($user, $quiz, $attempt): void
    {
        try {
            // You can implement email notifications or push notifications here
            // For example:
            
            // Mail::to($user->email)->send(new QuizCompletedMail($user, $quiz, $attempt));
            
            // Or database notifications:
            // $user->notify(new QuizCompletedNotification($quiz, $attempt));
            
        } catch (\Exception $e) {
            Log::warning('Failed to send quiz completion notifications', [
                'user_id' => $user->id,
                'quiz_id' => $quiz->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Update learning progress
     */
    private function updateLearningProgress($user, $quiz, $attempt): void
    {
        try {
            // Update learning outcomes progress based on quiz performance
            $questions = $quiz->quizQuestions()->with('learningOutcomes')->get();
            
            foreach ($questions as $question) {
                $userAnswer = $attempt->attemptAnswers()
                    ->where('quiz_question_id', $question->id)
                    ->first();
                
                if ($userAnswer && $userAnswer->is_correct) {
                    foreach ($question->learningOutcomes as $outcome) {
                        // Update learning outcome progress
                        // This would depend on your learning outcome tracking system
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning('Failed to update learning progress', [
                'user_id' => $user->id,
                'quiz_id' => $quiz->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Generate certificate
     */
    private function generateCertificate($user, $quiz, $attempt): void
    {
        try {
            // Generate a certificate for high-scoring completed attempts
            // This would depend on your certificate system
            
            Log::info('Certificate eligible quiz completed', [
                'user_id' => $user->id,
                'quiz_id' => $quiz->id,
                'attempt_id' => $attempt->id,
                'score' => $attempt->score
            ]);
            
        } catch (\Exception $e) {
            Log::warning('Failed to generate certificate', [
                'user_id' => $user->id,
                'quiz_id' => $quiz->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(QuizAttemptCompleted $event, \Throwable $exception): void
    {
        Log::error('Failed to handle quiz attempt completed event', [
            'user_id' => $event->user->id,
            'quiz_id' => $event->quiz->id,
            'attempt_id' => $event->attempt->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
