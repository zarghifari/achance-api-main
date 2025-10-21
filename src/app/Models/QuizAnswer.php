<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuizAnswer extends Model
{
    protected $table = 'quiz_answers';
    protected $primaryKey = 'id';
    protected $keytype = 'int';
    public $incrementing = true;
    public $timestamps = true;

    protected $fillable = [
        'quiz_question_id',
        'answer',
        'is_correct',
    ];

    public function quizQuestions(): BelongsTo
    {
        return $this->belongsTo(QuizQuestion::class, 'quiz_question_id', 'id');
    }

    public function attemptAnswers(): HasMany
    {
        return $this->hasMany(AttemptAnswer::class, 'selected_answer_id', 'id');
    }
}
