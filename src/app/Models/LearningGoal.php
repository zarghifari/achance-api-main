<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LearningGoal extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'goal_type',
        'title',
        'description',
        'target_date',
        'target_metric',
        'current_value',
        'target_value',
        'status',
        'achieved_at',
        'related_courses',
        'related_skills',
    ];

    protected $casts = [
        'target_date' => 'date',
        'current_value' => 'decimal:2',
        'target_value' => 'decimal:2',
        'achieved_at' => 'datetime',
        'related_courses' => 'array',
        'related_skills' => 'array',
    ];

    /**
     * Get the user that owns the goal.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the milestones for the goal.
     */
    public function milestones()
    {
        return $this->hasMany(GoalMilestone::class);
    }

    /**
     * Get the progress logs for the goal.
     */
    public function progressLogs()
    {
        return $this->hasMany(GoalProgressLog::class)->orderBy('logged_at', 'desc');
    }

    /**
     * Calculate progress percentage.
     */
    public function getProgressPercentageAttribute()
    {
        if (!$this->target_value || $this->target_value == 0) {
            return 0;
        }
        return round(($this->current_value / $this->target_value) * 100, 1);
    }

    /**
     * Get days remaining until target date.
     */
    public function getDaysRemainingAttribute()
    {
        if (!$this->target_date) {
            return null;
        }
        return now()->diffInDays($this->target_date, false);
    }

    /**
     * Check if goal is on track.
     */
    public function getOnTrackAttribute()
    {
        if (!$this->target_date || !$this->target_value) {
            return true;
        }

        $totalDays = $this->created_at->diffInDays($this->target_date);
        $daysPassed = $this->created_at->diffInDays(now());
        
        if ($totalDays == 0) {
            return true;
        }

        $expectedProgress = ($daysPassed / $totalDays) * 100;
        $actualProgress = $this->progress_percentage;

        return $actualProgress >= ($expectedProgress - 10); // 10% tolerance
    }
}
