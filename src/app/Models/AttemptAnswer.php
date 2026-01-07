<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttemptAnswer extends Model
{
    protected $table = 'attempt_answers';
    protected $primaryKey = 'id';
    protected $keytype = 'int';
    public $incrementing = true;
    public $timestamps = true;

    protected $fillable = [
        'attempt_id',
        'quiz_question_id',
        'selected_answer_id',
        'answer_text',
        'is_correct',
    ];

    public function attemptQuizzes(): BelongsTo
    {
        return $this->belongsTo(AttemptQuiz::class, 'attempt_id', 'id');
    }

    public function quizAnswers(): BelongsTo
    {
        return $this->belongsTo(QuizAnswer::class, 'selected_answer_id', 'id');
    }

    // Alias for quizAnswers for compatibility
    public function answer(): BelongsTo
    {
        return $this->quizAnswers();
    }

    public function quizQuestions(): BelongsTo
    {
        return $this->belongsTo(QuizQuestion::class, 'quiz_question_id', 'id');
    }
}
