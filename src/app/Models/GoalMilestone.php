<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoalMilestone extends Model
{
    use HasFactory;

    protected $fillable = [
        'learning_goal_id',
        'title',
        'description',
        'sequence_order',
        'is_achieved',
        'achieved_at',
    ];

    protected $casts = [
        'is_achieved' => 'boolean',
        'achieved_at' => 'datetime',
    ];

    /**
     * Get the goal that owns the milestone.
     */
    public function learningGoal()
    {
        return $this->belongsTo(LearningGoal::class);
    }
}
