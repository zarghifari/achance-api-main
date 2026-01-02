<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoalProgressLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'learning_goal_id',
        'progress_value',
        'note',
        'logged_at',
    ];

    protected $casts = [
        'progress_value' => 'decimal:2',
        'logged_at' => 'datetime',
    ];

    /**
     * Get the goal that owns the progress log.
     */
    public function learningGoal()
    {
        return $this->belongsTo(LearningGoal::class);
    }
}
