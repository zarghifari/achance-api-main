<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserActivityCreateRequest;
use App\Http\Resources\UserActivityResource;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\UserActivity;
use Illuminate\Http\Request;

use function PHPUnit\Framework\isEmpty;

class UserActivityController extends Controller
{
    public function addUserActivity(UserActivityCreateRequest $request): UserActivityResource
    {
        if($request->user()->can('doing own tasks')){
            $data = $request->validated();
            $data['user_id'] = $request->user()->id;
            $data['last_seen_at'] = now();

            $user_activity = UserActivity::create($data);
            return new UserActivityResource($user_activity);
        }
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    public function getUserActivity(Request $request)
    {
        if($request->user()->can('doing own tasks')){
            $user_activities = UserActivity::where('user_id', $request->user()->id)
                ->orderBy('last_seen_at', 'desc')
                ->take(3)
                ->get();

            if ($user_activities->isEmpty()) {
                return response()->json(['message' => 'No activity found'], 404);
            }

            // Separate lesson and quiz activity IDs
            $lessonIds = $user_activities->where('activity_type', 'lesson')->pluck('activity_id')->toArray();
            $quizIds = $user_activities->where('activity_type', 'quiz')->pluck('activity_id')->toArray();

            // Eager load all lessons with their relationships in one query
            $lessons = collect();
            if (!empty($lessonIds)) {
                $lessons = Lesson::with(['module.course'])
                    ->whereIn('id', $lessonIds)
                    ->get()
                    ->keyBy('id');
            }

            // Eager load all quizzes in one query
            $quizzes = collect();
            if (!empty($quizIds)) {
                $quizzes = Quiz::whereIn('id', $quizIds)
                    ->get()
                    ->keyBy('id');
            }

            $recent_activities = [];
            foreach ($user_activities as $activity) {
                if ($activity->activity_type == 'lesson' && $lessons->has($activity->activity_id)) {
                    $lesson = $lessons->get($activity->activity_id);
                    $activity->details = [
                        'image_url' => $lesson->module->course->cover_image ?? null,
                        'sub_title' => $lesson->module->course->title ?? null,
                        'title' => $lesson->title,
                        'module_id' => $lesson->module->id ?? null,
                        'course_id' => $lesson->module->course->id ?? null
                    ];
                } elseif ($activity->activity_type == 'quiz' && $quizzes->has($activity->activity_id)) {
                    $quiz = $quizzes->get($activity->activity_id);
                    $activity->details = [
                        'title' => $quiz->title,
                        'sub_title' => $quiz->duration ?? null,
                        'quiz_data' => $quiz
                    ];
                }
                $recent_activities[] = $activity;
            }

            return response()->json(['data' => $recent_activities]);
        }
        return response()->json(['message' => 'Unauthorized'], 403);
    }
}
