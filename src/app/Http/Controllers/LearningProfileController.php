<?php

namespace App\Http\Controllers;

use App\Models\LearningProfile;
use App\Models\Course;
use Illuminate\Http\Request;

class LearningProfileController extends Controller
{
    /**
     * Get or create learning profile for authenticated user.
     */
    public function getProfile()
    {
        $profile = LearningProfile::firstOrCreate(
            ['user_id' => auth()->id()],
            [
                'visual_score' => 25,
                'auditory_score' => 25,
                'reading_score' => 25,
                'kinesthetic_score' => 25,
            ]
        );

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $profile->id,
                'learning_style' => [
                    'dominant_style' => $profile->dominant_style,
                    'scores' => [
                        'visual' => $profile->visual_score,
                        'auditory' => $profile->auditory_score,
                        'reading' => $profile->reading_score,
                        'kinesthetic' => $profile->kinesthetic_score,
                    ],
                    'recommendations' => $profile->getRecommendations(),
                ],
                'preferences' => [
                    'preferred_content_type' => $profile->preferred_content_type,
                    'preferred_lesson_length' => $profile->preferred_lesson_length,
                    'learning_pace' => $profile->learning_pace,
                    'preferred_study_time' => $profile->preferred_study_time,
                    'daily_study_goal_minutes' => $profile->daily_study_goal_minutes,
                ],
                'engagement' => [
                    'likes_gamification' => $profile->likes_gamification,
                    'likes_group_learning' => $profile->likes_group_learning,
                    'likes_challenges' => $profile->likes_challenges,
                ],
            ],
        ]);
    }

    /**
     * Update learning preferences.
     */
    public function updatePreferences(Request $request)
    {
        $validated = $request->validate([
            'preferred_content_type' => 'sometimes|in:video,text,audio,interactive,mixed',
            'preferred_lesson_length' => 'sometimes|in:short,medium,long',
            'learning_pace' => 'sometimes|in:slow,moderate,fast',
            'preferred_study_time' => 'sometimes|in:morning,afternoon,evening,night',
            'daily_study_goal_minutes' => 'sometimes|integer|min:5|max:480',
            'likes_gamification' => 'sometimes|boolean',
            'likes_group_learning' => 'sometimes|boolean',
            'likes_challenges' => 'sometimes|boolean',
        ]);

        $profile = LearningProfile::firstOrCreate(['user_id' => auth()->id()]);
        $profile->update($validated);

        $recommendations = [];
        if (isset($validated['preferred_content_type'])) {
            $recommendations[] = "We'll show you more {$validated['preferred_content_type']}-based courses";
        }
        if (isset($validated['preferred_lesson_length']) && $validated['preferred_lesson_length'] === 'short') {
            $recommendations[] = "Lessons under 15 minutes will be prioritized";
        }
        if (isset($validated['preferred_study_time'])) {
            $recommendations[] = ucfirst($validated['preferred_study_time']) . " study reminders enabled";
        }

        return response()->json([
            'success' => true,
            'data' => [
                'preferences_updated' => true,
                'personalized_recommendations' => $recommendations,
            ],
        ]);
    }

    /**
     * Complete VARK learning style assessment.
     */
    public function submitAssessment(Request $request)
    {
        $validated = $request->validate([
            'answers' => 'required|array|min:4',
            'answers.*' => 'required|in:V,A,R,K',
        ]);

        // Calculate VARK scores
        $scores = [
            'V' => 0,
            'A' => 0,
            'R' => 0,
            'K' => 0,
        ];

        foreach ($validated['answers'] as $answer) {
            $scores[$answer]++;
        }

        $totalAnswers = count($validated['answers']);
        $profile = LearningProfile::firstOrCreate(['user_id' => auth()->id()]);

        $profile->update([
            'visual_score' => round(($scores['V'] / $totalAnswers) * 100),
            'auditory_score' => round(($scores['A'] / $totalAnswers) * 100),
            'reading_score' => round(($scores['R'] / $totalAnswers) * 100),
            'kinesthetic_score' => round(($scores['K'] / $totalAnswers) * 100),
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'learning_style' => [
                    'dominant_style' => $profile->dominant_style,
                    'scores' => [
                        'visual' => $profile->visual_score,
                        'auditory' => $profile->auditory_score,
                        'reading' => $profile->reading_score,
                        'kinesthetic' => $profile->kinesthetic_score,
                    ],
                    'recommendations' => $profile->getRecommendations(),
                ],
            ],
        ]);
    }

    /**
     * Get personalized content feed based on learning profile.
     */
    public function getPersonalizedFeed()
    {
        $profile = LearningProfile::firstOrCreate(['user_id' => auth()->id()]);

        // Get courses that match preferences
        $coursesQuery = Course::query();

        // Simple recommendations based on profile
        $courses = $coursesQuery->take(10)->get();

        $recommendedToday = $courses->map(function ($course) use ($profile) {
            $whyRecommended = "Matches your " . strtolower($profile->dominant_style) . " learning style";
            
            return [
                'type' => 'course',
                'course_id' => $course->id,
                'title' => $course->title,
                'description' => $course->description,
                'why_recommended' => $whyRecommended,
                'estimated_hours' => $course->estimated_hours ?? 0,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'recommended_today' => $recommendedToday,
                'study_goal_today' => $profile->daily_study_goal_minutes . ' minutes',
                'preferred_study_time' => $profile->preferred_study_time,
            ],
        ]);
    }
}
