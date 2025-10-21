<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Services\QuizCacheService;

class QuizQuestion extends Model
{
    use HasFactory;

    protected $table = 'quiz_questions';
    protected $primaryKey = 'id';
    protected $keytype = 'int';
    public $incrementing = true;
    public $timestamps = true;

    protected $fillable = [
        'quiz_id',
        'question_number',
        'question_text',
        'question_img',
        'question_type',
        'attachment',
    ];

    protected $casts = [
        'question_number' => 'integer',
    ];

    // Constants for question types
    const TYPE_MULTIPLE_CHOICE = 'multiple_choice';
    const TYPE_TRUE_FALSE = 'true_false';
    const TYPE_SHORT_ANSWER = 'short_answer';
    const TYPE_MULTIPLE_CORRECT_CHOICE = 'multiple_correct_choice';

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class, 'quiz_id', 'id');
    }

    public function quizAnswers(): HasMany
    {
        return $this->hasMany(QuizAnswer::class, 'quiz_question_id', 'id');
    }

    public function attemptAnswers(): HasMany
    {
        return $this->hasMany(AttemptAnswer::class, 'quiz_question_id', 'id');
    }

    public function learningOutcomes(): BelongsToMany
    {
        return $this->belongsToMany(LearningOutcome::class, 'learning_outcome_quiz_question')
            ->withPivot('weight')
            ->withTimestamps();
    }

    /**
     * Get active learning outcomes
     */
    public function getActiveLearningOutcomes()
    {
        return $this->learningOutcomes()
            ->where('learning_outcomes.is_active', true)
            ->get();
    }

    /**
     * Get correct answers for this question
     */
    public function getCorrectAnswers()
    {
        return $this->quizAnswers()->where('is_correct', true)->get();
    }

    /**
     * Get correct answer IDs
     */
    public function getCorrectAnswerIds(): array
    {
        return $this->quizAnswers()
            ->where('is_correct', true)
            ->pluck('id')
            ->toArray();
    }

    /**
     * Check if this is a multiple choice question
     */
    public function isMultipleChoice(): bool
    {
        return $this->question_type === self::TYPE_MULTIPLE_CHOICE;
    }

    /**
     * Check if this is a true/false question
     */
    public function isTrueFalse(): bool
    {
        return $this->question_type === self::TYPE_TRUE_FALSE;
    }

    /**
     * Check if this is a short answer question
     */
    public function isShortAnswer(): bool
    {
        return $this->question_type === self::TYPE_SHORT_ANSWER;
    }

    /**
     * Check if this allows multiple correct choices
     */
    public function isMultipleCorrectChoice(): bool
    {
        return $this->question_type === self::TYPE_MULTIPLE_CORRECT_CHOICE;
    }

    /**
     * Validate answer for this question
     */
    public function validateAnswer($answerId): bool
    {
        return $this->quizAnswers()
            ->where('id', $answerId)
            ->where('is_correct', true)
            ->exists();
    }

    /**
     * Get question statistics
     */
    public function getStatistics(): array
    {
        $attempts = $this->attemptAnswers()->with('attemptQuizzes')->get();
        $totalAttempts = $attempts->count();
        $correctAttempts = $attempts->where('is_correct', true)->count();

        return [
            'total_attempts' => $totalAttempts,
            'correct_attempts' => $correctAttempts,
            'incorrect_attempts' => $totalAttempts - $correctAttempts,
            'success_rate' => $totalAttempts > 0 ? round(($correctAttempts / $totalAttempts) * 100, 2) : 0,
        ];
    }

    /**
     * Scope for questions by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('question_type', $type);
    }

    /**
     * Scope for ordering by question number
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('question_number');
    }

    /**
     * Get available question types
     */
    public static function getQuestionTypes(): array
    {
        return [
            self::TYPE_MULTIPLE_CHOICE => 'Multiple Choice',
            self::TYPE_TRUE_FALSE => 'True/False',
            self::TYPE_SHORT_ANSWER => 'Short Answer',
            self::TYPE_MULTIPLE_CORRECT_CHOICE => 'Multiple Correct Choice',
        ];
    }

    /**
     * Invalidate related caches when model is updated
     */
    protected static function booted()
    {
        static::saved(function ($question) {
            QuizCacheService::invalidateQuestionCache($question->quiz_id, $question->id);
        });

        static::deleted(function ($question) {
            QuizCacheService::invalidateQuestionCache($question->quiz_id, $question->id);
        });
    }
}
