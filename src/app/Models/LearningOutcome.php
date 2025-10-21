<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class LearningOutcome extends Model
{
    use HasFactory;

    protected $table = 'learning_outcomes';
    protected $primaryKey = 'id';
    protected $keytype = 'int';
    public $incrementing = true;
    public $timestamps = true;

    protected $fillable = [
        'course_id',
        'slug',
        'description',
        'cognitive_level',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(Module::class, 'learning_outcome_module');
    }

    public function quizQuestions(): BelongsToMany
    {
        return $this->belongsToMany(QuizQuestion::class, 'learning_outcome_quiz_question')
            ->withPivot('weight')
            ->withTimestamps();
    }

    public function getAssessmentQuestions()
    {
        return $this->quizQuestions()
            ->get();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCourse($query, $courseId)
    {
        return $query->where('course_id', $courseId);
    }
}
