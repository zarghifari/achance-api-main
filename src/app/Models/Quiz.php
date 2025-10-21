<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Services\QuizCacheService;
use Carbon\Carbon;

class Quiz extends Model
{
    use HasFactory;

    protected $table = 'quizzes';
    protected $primaryKey = 'id';
    protected $keytype = 'int';
    public $incrementing = true;
    public $timestamps = true;

    protected $fillable = [
        'title',
        'slug',
        'type',
        'summary',
        'content',
        'published_at',
        'start_at',
        'ends_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'start_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    protected $dates = [
        'published_at',
        'start_at',
        'ends_at',
    ];

    public function quizQuestions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class, 'quiz_id', 'id');
    }

    public function quizAnswers(): HasMany
    {
        return $this->hasMany(QuizAnswer::class, 'quiz_id', 'id');
    }

    public function attemptQuizzes(): HasMany
    {
        return $this->hasMany(AttemptQuiz::class, 'quiz_id', 'id');
    }

    // Scope for eager loading quiz with questions and answers
    public function scopeWithQuestionsAndAnswers($query)
    {
        return $query->with(['quizQuestions' => function ($query) {
            $query->orderBy('question_number');
        }, 'quizQuestions.quizAnswers']);
    }

    // Scope for published quizzes
    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at')
                    ->where('published_at', '<=', now());
    }

    // Scope for active quizzes (published and within time range)
    public function scopeActive($query)
    {
        return $query->published()
                    ->where(function ($query) {
                        $query->whereNull('start_at')
                              ->orWhere('start_at', '<=', now());
                    })
                    ->where(function ($query) {
                        $query->whereNull('ends_at')
                              ->orWhere('ends_at', '>=', now());
                    });
    }

    // Scope for upcoming quizzes
    public function scopeUpcoming($query)
    {
        return $query->published()
                    ->where('start_at', '>', now());
    }

    // Scope for ended quizzes
    public function scopeEnded($query)
    {
        return $query->published()
                    ->where('ends_at', '<', now());
    }

    /**
     * Check if the quiz is currently active
     */
    public function isActive(): bool
    {
        if (!$this->published_at || $this->published_at > now()) {
            return false;
        }

        if ($this->start_at && $this->start_at > now()) {
            return false;
        }

        if ($this->ends_at && $this->ends_at < now()) {
            return false;
        }

        return true;
    }

    /**
     * Check if the quiz is upcoming
     */
    public function isUpcoming(): bool
    {
        return $this->published_at && 
               $this->published_at <= now() && 
               $this->start_at && 
               $this->start_at > now();
    }

    /**
     * Check if the quiz has ended
     */
    public function hasEnded(): bool
    {
        return $this->ends_at && $this->ends_at < now();
    }

    /**
     * Get quiz status
     */
    public function getStatusAttribute(): string
    {
        if (!$this->published_at || $this->published_at > now()) {
            return 'draft';
        }

        if ($this->isUpcoming()) {
            return 'upcoming';
        }

        if ($this->hasEnded()) {
            return 'ended';
        }

        if ($this->isActive()) {
            return 'active';
        }

        return 'inactive';
    }

    /**
     * Get time remaining for the quiz
     */
    public function getTimeRemainingAttribute(): ?int
    {
        if (!$this->ends_at) {
            return null;
        }

        $remaining = $this->ends_at->diffInSeconds(now(), false);
        return $remaining > 0 ? $remaining : 0;
    }

    /**
     * Get quiz statistics (cached)
     */
    public function getStatistics(): array
    {
        return QuizCacheService::getQuizStats($this->id);
    }

    /**
     * Get cached questions with answers
     */
    public function getCachedQuestions()
    {
        return QuizCacheService::getQuizQuestions($this->id);
    }

    /**
     * Invalidate quiz cache when model is updated
     */
    protected static function booted()
    {
        static::updated(function ($quiz) {
            QuizCacheService::invalidateQuizCache($quiz->id);
        });

        static::deleted(function ($quiz) {
            QuizCacheService::invalidateQuizCache($quiz->id);
        });
    }
}
