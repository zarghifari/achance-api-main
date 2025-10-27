<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserActivityCreateRequest;
use App\Http\Resources\UserActivityResource;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\Epub;
use App\Models\UserActivity;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserActivityController extends Controller
{
    public function addUserActivity(UserActivityCreateRequest $request): JsonResponse
    {
        if ($request->user()->cannot('doing own tasks')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $data = $request->validated();
            $data['user_id'] = $request->user()->id;
            $data['last_seen_at'] = now();

            // Auto-detect device type if not provided
            if (!isset($data['device_type'])) {
                $data['device_type'] = $this->detectDeviceType($request);
            }

            // Auto-capture user agent if not provided
            if (!isset($data['user_agent'])) {
                $data['user_agent'] = $request->header('User-Agent');
            }

            // Handle specific activity types
            $this->enrichActivityData($data);

            // Check for existing activity to update or create new
            $userActivity = $this->findOrCreateActivity($data);

            Log::info('User activity tracked', [
                'user_id' => $data['user_id'],
                'activity_type' => $data['activity_type'],
                'activity_id' => $data['activity_id'],
                'action' => $data['action']
            ]);

            return response()->json([
                'data' => new UserActivityResource($userActivity),
                'message' => 'Activity tracked successfully'
            ], 201);

        } catch (\Exception $e) {
            Log::error('Failed to track user activity', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'message' => 'Failed to track activity',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    public function getUserActivity(Request $request): JsonResponse
    {
        if ($request->user()->cannot('doing own tasks')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $user_activities = UserActivity::where('user_id', $request->user()->id)
            ->orderBy('last_seen_at', 'desc')
            ->take(10)
            ->get();

        if ($user_activities->isEmpty()) {
            return response()->json(['message' => 'No activity found'], 404);
        }

        // Group activities by type for efficient querying
        $lessonIds = $user_activities->where('activity_type', UserActivity::TYPE_LESSON)->pluck('activity_id')->unique()->toArray();
        $epubIds = $user_activities->where('activity_type', UserActivity::TYPE_EPUB)->pluck('activity_id')->unique()->toArray();
        $quizIds = $user_activities->where('activity_type', UserActivity::TYPE_QUIZ)->pluck('activity_id')->unique()->toArray();

        // Eager load related data
        $lessons = collect();
        if (!empty($lessonIds)) {
            $lessons = Lesson::with(['module.course'])
                ->whereIn('id', $lessonIds)
                ->get()
                ->keyBy('id');
        }

        $epubs = collect();
        if (!empty($epubIds)) {
            $epubs = Epub::with(['lesson.module.course'])
                ->whereIn('id', $epubIds)
                ->get()
                ->keyBy('id');
        }

        $quizzes = collect();
        if (!empty($quizIds)) {
            $quizzes = Quiz::whereIn('id', $quizIds)
                ->get()
                ->keyBy('id');
        }

        $recent_activities = [];
        foreach ($user_activities as $activity) {
            $activityData = $activity->toArray();
            
            switch ($activity->activity_type) {
                case UserActivity::TYPE_LESSON:
                    if ($lessons->has($activity->activity_id)) {
                        $lesson = $lessons->get($activity->activity_id);
                        $activityData['details'] = [
                            'image_url' => $lesson->module->course->cover_image ?? null,
                            'sub_title' => $lesson->module->course->title ?? null,
                            'title' => $lesson->title,
                            'module_id' => $lesson->module->id ?? null,
                            'course_id' => $lesson->module->course->id ?? null
                        ];
                    }
                    break;

                case UserActivity::TYPE_EPUB:
                    if ($epubs->has($activity->activity_id)) {
                        $epub = $epubs->get($activity->activity_id);
                        $activityData['details'] = [
                            'title' => $epub->title,
                            'sub_title' => $epub->lesson->title ?? null,
                            'lesson_id' => $epub->lesson_id,
                            'module_id' => $epub->lesson->module->id ?? null,
                            'course_id' => $epub->lesson->module->course->id ?? null,
                            'file_size' => $epub->file_size,
                            'original_filename' => $epub->original_filename
                        ];
                    }
                    break;

                case UserActivity::TYPE_QUIZ:
                    if ($quizzes->has($activity->activity_id)) {
                        $quiz = $quizzes->get($activity->activity_id);
                        $activityData['details'] = [
                            'title' => $quiz->title,
                            'sub_title' => $quiz->duration ?? null,
                            'quiz_data' => $quiz
                        ];
                    }
                    break;
            }

            $recent_activities[] = $activityData;
        }

        return response()->json(['data' => $recent_activities]);
    }

    public function getAnalytics(Request $request): JsonResponse
    {
        if ($request->user()->cannot('view courses')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $userId = $request->input('user_id', $request->user()->id);
        $period = $request->input('period', '30'); // days
        $startDate = now()->subDays($period);

        $analytics = [
            'epub_analytics' => $this->getEpubAnalytics($userId, $startDate),
            'quiz_analytics' => $this->getQuizAnalytics($userId, $startDate),
            'overall_stats' => $this->getOverallStats($userId, $startDate),
        ];

        return response()->json(['data' => $analytics]);
    }

    private function detectDeviceType(Request $request): string
    {
        $userAgent = $request->header('User-Agent', '');
        
        if (preg_match('/Mobile|Android|iPhone|iPad/', $userAgent)) {
            return 'mobile';
        } elseif (preg_match('/Tablet/', $userAgent)) {
            return 'tablet';
        } else {
            return 'desktop';
        }
    }

    private function enrichActivityData(array &$data): void
    {
        // Add timestamps based on action
        if ($data['action'] === UserActivity::ACTION_START && !isset($data['started_at'])) {
            $data['started_at'] = now();
        }

        if ($data['action'] === UserActivity::ACTION_COMPLETE && !isset($data['completed_at'])) {
            $data['completed_at'] = now();
        }

        // Set default metadata based on activity type
        if (!isset($data['metadata'])) {
            $data['metadata'] = [];
        }

        // Add session information
        $data['metadata']['session_id'] = session()->getId();
        $data['metadata']['ip_address'] = request()->ip();
    }

    private function findOrCreateActivity(array $data): UserActivity
    {
        // For progress tracking, update existing record if found
        if (in_array($data['action'], [UserActivity::ACTION_PROGRESS, UserActivity::ACTION_COMPLETE])) {
            $existing = UserActivity::where('user_id', $data['user_id'])
                ->where('activity_type', $data['activity_type'])
                ->where('activity_id', $data['activity_id'])
                ->whereIn('action', [UserActivity::ACTION_START, UserActivity::ACTION_PROGRESS])
                ->first();

            if ($existing) {
                // Calculate duration if completing
                if ($data['action'] === UserActivity::ACTION_COMPLETE && $existing->started_at) {
                    $data['duration_seconds'] = now()->diffInSeconds($existing->started_at);
                }

                $existing->update($data);
                return $existing;
            }
        }

        // Create new activity record
        return UserActivity::create($data);
    }

    private function getEpubAnalytics(int $userId, $startDate): array
    {
        $epubStats = UserActivity::forUser($userId)
            ->epubActivities()
            ->where('created_at', '>=', $startDate)
            ->selectRaw('
                COUNT(*) as total_interactions,
                COUNT(DISTINCT activity_id) as unique_epubs,
                SUM(CASE WHEN action = "download" THEN 1 ELSE 0 END) as downloads,
                SUM(CASE WHEN action = "complete" THEN 1 ELSE 0 END) as completed_reads,
                AVG(progress_percentage) as avg_progress,
                AVG(duration_seconds) as avg_reading_time
            ')
            ->first();

        $topEpubs = UserActivity::forUser($userId)
            ->epubActivities()
            ->where('created_at', '>=', $startDate)
            ->select('activity_id', DB::raw('COUNT(*) as interaction_count'))
            ->groupBy('activity_id')
            ->orderBy('interaction_count', 'desc')
            ->limit(5)
            ->with(['epub'])
            ->get();

        return [
            'stats' => $epubStats,
            'top_epubs' => $topEpubs,
            'reading_patterns' => $this->getReadingPatterns($userId, $startDate)
        ];
    }

    private function getQuizAnalytics(int $userId, $startDate): array
    {
        $quizStats = UserActivity::forUser($userId)
            ->quizActivities()
            ->where('created_at', '>=', $startDate)
            ->selectRaw('
                COUNT(*) as total_attempts,
                COUNT(DISTINCT activity_id) as unique_quizzes,
                SUM(CASE WHEN action = "complete" THEN 1 ELSE 0 END) as completed_quizzes,
                AVG(duration_seconds) as avg_completion_time
            ')
            ->first();

        return [
            'stats' => $quizStats,
            'completion_rate' => $quizStats->total_attempts > 0 ? 
                ($quizStats->completed_quizzes / $quizStats->total_attempts) * 100 : 0
        ];
    }

    private function getOverallStats(int $userId, $startDate): array
    {
        return [
            'total_activities' => UserActivity::forUser($userId)->where('created_at', '>=', $startDate)->count(),
            'active_days' => UserActivity::forUser($userId)
                ->where('created_at', '>=', $startDate)
                ->selectRaw('COUNT(DISTINCT DATE(created_at)) as active_days')
                ->value('active_days'),
            'device_usage' => UserActivity::forUser($userId)
                ->where('created_at', '>=', $startDate)
                ->select('device_type', DB::raw('COUNT(*) as usage_count'))
                ->groupBy('device_type')
                ->get()
        ];
    }

    private function getReadingPatterns(int $userId, $startDate): array
    {
        return UserActivity::forUser($userId)
            ->epubActivities()
            ->where('created_at', '>=', $startDate)
            ->selectRaw('
                HOUR(created_at) as hour,
                COUNT(*) as activity_count
            ')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->toArray();
    }
}
