<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\LearningGoal;

class HomeController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        
        try {
            // Get statistics with defensive checks
            $stats = [
                'courses' => Course::count(),
                'completed' => 0,
                'quizzes' => Quiz::count(),
                'goals' => 0,
            ];
            
            // Try to get learning goals if table exists
            try {
                $stats['goals'] = LearningGoal::where('user_id', $user->id)->count();
            } catch (\Exception $e) {
                $stats['goals'] = 0;
            }
            
            // Get recent lessons
            $recentLessons = [];
            
            // Get featured courses
            $featuredCourses = Course::withCount('modules')
                ->latest()
                ->take(5)
                ->get();
            
            // Get active goals
            $activeGoals = [];
            try {
                $activeGoals = LearningGoal::where('user_id', $user->id)
                    ->orderBy('created_at', 'desc')
                    ->take(3)
                    ->get();
            } catch (\Exception $e) {
                // Table doesn't exist yet
            }
            
            return view('home', compact('stats', 'recentLessons', 'featuredCourses', 'activeGoals'));
        } catch (\Exception $e) {
            // Fallback if there are database issues
            return view('home', [
                'stats' => ['courses' => 0, 'completed' => 0, 'quizzes' => 0, 'goals' => 0],
                'recentLessons' => [],
                'featuredCourses' => [],
                'activeGoals' => []
            ]);
        }
    }
}
