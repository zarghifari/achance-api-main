<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Lesson;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\LessonResource;
use App\Http\Resources\LessonCollection;
use App\Http\Requests\LessonCreateRequest;
use App\Http\Requests\LessonUpdateRequest;
use App\Http\Resources\LessonDetailResource;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class LessonController extends Controller
{
    public function create(int $course_id, int $module_id, LessonCreateRequest $request): JsonResponse
    {
        if ($request->user()->can('create courses')) {
            $data = $request->validated();
            $data['module_id'] = $module_id;
            if(Lesson::where('slug', $data['slug'])->exists()) {
                return response()->json(['message' => 'Lesson with this slug already exists'], 422);
            }

            $max_position = Lesson::where('module_id', $module_id)->max('position');
            if ($data['position'] == null) {
                $data['position'] = $max_position + 1;
            }

            if ($data['position'] > $max_position + 1) {
                $data['position'] = $max_position + 1;
            }

            if ($data['position'] < 1) {
                $data['position'] = 1;
            }

            if ($data['position'] < $max_position && $data['position'] >= 1) {
                $lessons = Lesson::where('module_id', $module_id)
                    ->where('position', '>=', $data['position'])
                    ->get();
                foreach ($lessons as $l) {
                    $l->position += 1;
                    $l->save();
                }
            }
            $lesson = Lesson::create($data);
            Cache::forget('course_' . $course_id);
            Cache::forget("module_" . $module_id);
            Cache::forget("lessons_" . $lesson->id);
            Cache::put("lesson_{$lesson->id}", $lesson, 3600); // Cache for 1 hour
            Cache::forget("lessons_list_{$module_id}");
            return (new LessonResource($lesson))->response()->setStatusCode(201);
        }
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    public function listByModule(int $course_id, int $module_id, Request $request)
    {
        if ($request->user()->can('view courses')) {
            $cacheKey = "lessons_list_{$module_id}";
            $lessons = Cache::remember($cacheKey, 3600, function() use ($module_id) {
                Log::info("Fetching lessons from database for module_id: {$module_id}");
                $lessons = Lesson::with('epub') // Eager load epub
                    ->where('module_id', $module_id)
                    ->orderByRaw('COALESCE(position, 0)')
                    ->get();
                Log::info("Lessons fetched from database: ", $lessons->toArray());
                return $lessons;
            });

            if ($lessons->isEmpty()) {
                Log::warning("No lessons found for module_id: {$module_id}");
            } else {
                Log::info("Lessons from cache or database: ", $lessons->toArray());
            }

            return new LessonCollection($lessons);
        }
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    public function get(int $course_id, int $module_id, int $lesson_id, Request $request): JsonResponse
    {
        if ($request->user()->can('view courses')) {
            $cacheKey = "lesson_{$lesson_id}";
            $lesson = Cache::remember($cacheKey, 3600, function() use ($module_id, $lesson_id) {
                Log::info("Fetching lesson from database for lesson_id: {$lesson_id}");
                $lesson = Lesson::with('epub') // Eager load epub
                    ->where('module_id', $module_id)
                    ->where('id', $lesson_id)
                    ->firstOrFail();
                Log::info("Lesson fetched from database with epub: ", $lesson->toArray());
                return $lesson;
            });

            if ($lesson === null) {
            Log::warning("No lesson found for lesson_id: {$lesson_id}");
            } else {
            Log::info("Lesson from cache or database: ", $lesson->toArray());
            }

            $next_lesson = Lesson::where('module_id', $module_id)
            ->where('position', '>', $lesson->position)
            ->orderBy('position')
            ->first();

            $prev_lesson = Lesson::where('module_id', $module_id)
            ->where('position', '<', $lesson->position)
            ->orderBy('position', 'desc')
            ->first();

            Cache::forget("lesson_{$lesson_id}_recent");
            return (new LessonDetailResource($lesson))->additional([
            'next_lesson' => $next_lesson ? new LessonResource($next_lesson) : null,
            'prev_lesson' => $prev_lesson ? new LessonResource($prev_lesson) : null,
            ])->response()->setStatusCode(200);
        }
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    public function getRecent(int $course_id, int $module_id, int $lesson_id, Request $request): JsonResponse
    {
        if ($request->user()->can('view courses')) {
            $cacheKey = "lesson_{$lesson_id}_recent";
            $lesson = Cache::remember($cacheKey, 3600, function() use ($module_id, $lesson_id) {
                Log::info("Fetching lesson from database for lesson_id: {$lesson_id}");
                $lesson = Lesson::where('module_id', $module_id)
                    ->where('id', $lesson_id)
                    ->firstOrFail();
                Log::info("Lesson fetched from database without epub: ", $lesson->toArray());
                return $lesson;
            });

            if ($lesson === null) {
                Log::warning("No lesson found for lesson_id: {$lesson_id}");
            } else {
                Log::info("Lesson from cache or database: ", $lesson->toArray());
            }

            return (new LessonResource($lesson))->response()->setStatusCode(200);
        }
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    public function update(int $course_id, int $module_id, int $lesson_id, LessonUpdateRequest $request): JsonResponse
    {
        if ($request->user()->can('edit courses')) {
            $data = $request->validated();
            $lesson = Lesson::where('module_id', $module_id)
                ->where('id', $lesson_id)
                ->firstOrFail();
            if(Lesson::where('slug', $data['slug'])->where('id', '!=', $lesson_id)->exists()) {
                return response()->json(['message' => 'Lesson with this slug already exists'], 422);
            }
            
            $max_position = Lesson::where('module_id', $module_id)->max('position');
            if ($data['position'] !== $lesson->position) {
                if ($data['position'] == null) {
                    $data['position'] = $lesson->position;
                }

                if ($data['position'] > $max_position) {
                    $data['position'] = $max_position;
                }

                if ($data['position'] < 1) {
                    $data['position'] = 1;
                }

                if ($data['position'] < $lesson->position) {
                    $lessons = Lesson::where('module_id', $module_id)
                        ->where('position', '>=', $data['position'])
                        ->where('position', '<', $lesson->position)
                        ->get();
                    foreach ($lessons as $l) {
                        $l->position += 1;
                        $l->save();
                    }
                }

                if ($data['position'] > $lesson->position) {
                    $lessons = Lesson::where('module_id', $module_id)
                        ->where('position', '>', $lesson->position)
                        ->where('position', '<=', $data['position'])
                        ->get();
                    foreach ($lessons as $l) {
                        $l->position -= 1;
                        $l->save();
                    }
                }
            } else {
                $data['position'] = $lesson->position;
            }
    
            $lesson->update($data);
            Cache::forget('course_' . $course_id);
            Cache::forget("module_" . $module_id);
            Cache::forget("lessons_" . $lesson->id);
            Cache::put("lesson_{$lesson->id}", $lesson, 3600); // Cache for 1 hour
            Cache::forget("lessons_list_{$module_id}");
            return (new LessonResource($lesson))->response()->setStatusCode(200);
        }
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    public function delete(int $course_id, int $module_id, int $lesson_id, Request $request): JsonResponse
    {
        if($request->user()->can('delete courses')) {
            $lesson = Lesson::where('module_id', $module_id)
                ->where('id', $lesson_id)
                ->firstOrFail();

            if($lesson->position !== null) {
                $lessons = Lesson::where('module_id', $module_id)
                    ->where('position', '>', $lesson->position)
                    ->orderBy('position')
                    ->get();
                $current_position = $lesson->position;
                foreach ($lessons as $l) {
                    $l->position = $current_position;
                    $l->save();
                    $current_position++;
                }
            }
            $lesson->delete();
            Cache::forget('course_' . $course_id);
            Cache::forget("module_" . $module_id);
            Cache::forget("lessons_" . $lesson->id);
            Cache::forget("lesson_{$lesson->id}"); // Remove from cache
            Cache::forget("lessons_list_{$module_id}");
            return response()->json(['message' => 'Lesson deleted'], 200);
        }
        return response()->json(['message' => 'Unauthorized'], 403);
    }
}