<?php

namespace App\Policies;

use App\Models\AttemptQuiz;
use App\Models\Quiz;
use App\Models\User;
use Carbon\Carbon;

class AttemptQuizPolicy
{
    /**
     * Determine if the user can create quiz attempts
     */
    public function create(User $user, Quiz $quiz): bool
    {
        // Check basic permission
        if (!$user->can('doing own quizzes')) {
            return false;
        }

        // Check quiz timing
        if ($quiz->start_at && Carbon::parse($quiz->start_at)->isFuture()) {
            return false;
        }

        if ($quiz->ends_at && Carbon::parse($quiz->ends_at)->isPast()) {
            return false;
        }

        // Check if user already has a completed attempt
        $existingCompletedAttempt = AttemptQuiz::where('user_id', $user->id)
            ->where('quiz_id', $quiz->id)
            ->where('status', 'completed')
            ->exists();

        return !$existingCompletedAttempt;
    }

    /**
     * Determine if the user can view the quiz attempt
     */
    public function view(User $user, AttemptQuiz $attempt): bool
    {
        return $user->can('view all attempt quizzes') || 
               ($user->can('doing own quizzes') && $attempt->user_id === $user->id);
    }

    /**
     * Determine if the user can update the quiz attempt
     */
    public function update(User $user, AttemptQuiz $attempt): bool
    {
        // Only the owner can update their own attempt
        if ($attempt->user_id !== $user->id) {
            return false;
        }

        // Check basic permission
        if (!$user->can('doing own quizzes')) {
            return false;
        }

        // Only in-progress attempts can be updated
        if ($attempt->status !== 'in_progress') {
            return false;
        }

        // Check if quiz is still within time limits
        $quiz = $attempt->quiz;
        if ($quiz->ends_at && Carbon::parse($quiz->ends_at)->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Determine if the user can delete the quiz attempt
     */
    public function delete(User $user, AttemptQuiz $attempt): bool
    {
        // Only admins can delete attempts
        return $user->can('view all attempt quizzes');
    }

    /**
     * Determine if the user can view quiz attempt answers
     */
    public function viewAnswers(User $user, AttemptQuiz $attempt): bool
    {
        // Owner can view answers if attempt is completed or failed
        if ($attempt->user_id === $user->id && 
            $user->can('doing own quizzes') && 
            in_array($attempt->status, ['completed', 'failed'])) {
            return true;
        }

        // Admins can always view answers
        return $user->can('view all attempt quizzes');
    }

    /**
     * Determine if the user can view quiz leaderboard
     */
    public function viewLeaderboard(User $user, Quiz $quiz): bool
    {
        return $user->can('doing own quizzes') || $user->can('view all attempt quizzes');
    }

    /**
     * Determine if the user can view quiz statistics
     */
    public function viewStatistics(User $user, Quiz $quiz): bool
    {
        return $user->can('view all attempt quizzes');
    }

    /**
     * Check if user can start a new attempt (considering existing attempts)
     */
    public function startNewAttempt(User $user, Quiz $quiz): bool
    {
        if (!$this->create($user, $quiz)) {
            return false;
        }

        // Check for existing in-progress attempts
        $existingInProgressAttempt = AttemptQuiz::where('user_id', $user->id)
            ->where('quiz_id', $quiz->id)
            ->where('status', 'in_progress')
            ->exists();

        // Don't allow multiple in-progress attempts
        return !$existingInProgressAttempt;
    }

    /**
     * Check if user can resume an existing attempt
     */
    public function resumeAttempt(User $user, AttemptQuiz $attempt): bool
    {
        return $this->update($user, $attempt);
    }
}
