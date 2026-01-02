<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LearningProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'visual_score',
        'auditory_score',
        'reading_score',
        'kinesthetic_score',
        'preferred_content_type',
        'preferred_lesson_length',
        'learning_pace',
        'preferred_study_time',
        'daily_study_goal_minutes',
        'likes_gamification',
        'likes_group_learning',
        'likes_challenges',
    ];

    protected $casts = [
        'likes_gamification' => 'boolean',
        'likes_group_learning' => 'boolean',
        'likes_challenges' => 'boolean',
    ];

    /**
     * Get the user that owns the profile.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the dominant learning style.
     */
    public function getDominantStyleAttribute()
    {
        $scores = [
            'Visual' => $this->visual_score,
            'Auditory' => $this->auditory_score,
            'Reading' => $this->reading_score,
            'Kinesthetic' => $this->kinesthetic_score,
        ];

        return array_keys($scores, max($scores))[0];
    }

    /**
     * Get recommendations based on learning style.
     */
    public function getRecommendations()
    {
        $recommendations = [];

        if ($this->visual_score >= 50) {
            $recommendations[] = "Use diagrams and mind maps";
            $recommendations[] = "Watch video tutorials";
            $recommendations[] = "Create visual summaries";
            $recommendations[] = "Use color coding in notes";
        }

        if ($this->auditory_score >= 50) {
            $recommendations[] = "Listen to podcasts and lectures";
            $recommendations[] = "Discuss concepts with others";
            $recommendations[] = "Explain topics out loud";
            $recommendations[] = "Use text-to-speech tools";
        }

        if ($this->reading_score >= 50) {
            $recommendations[] = "Read textbooks and articles";
            $recommendations[] = "Take detailed written notes";
            $recommendations[] = "Create written summaries";
            $recommendations[] = "Use lists and bullet points";
        }

        if ($this->kinesthetic_score >= 50) {
            $recommendations[] = "Practice hands-on exercises";
            $recommendations[] = "Build projects while learning";
            $recommendations[] = "Take breaks and move around";
            $recommendations[] = "Use interactive simulations";
        }

        return $recommendations;
    }
}
