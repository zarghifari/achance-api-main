<?php

namespace App\Http\Controllers;

use App\Http\Requests\CourseCreateRequest;
use App\Http\Requests\CourseUpdateRequest;
use App\Http\Resources\CourseResource;
use App\Http\Resources\CourseDetailResource;
use App\Http\Resources\CourseCollection;
use App\Http\Resources\EpubResource;
use App\Models\Course;
use App\Services\CacheService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CourseController extends Controller
{
    public function create(CourseCreateRequest $request): JsonResponse
    {
        $data = $request->validated();
        if ($request->user()->can('create courses')) {
            $course = new Course($data);
            if ($course->isOpen === null) {
                $course->isOpen = true;
            }

            if ($course->total_hours === null) {
                $course->total_hours = 0;
            }

            if (Course::where('slug', $course->slug)->exists()) {
                return response()->json(['message' => 'Course with this slug already exists'], 422);
            }

            $course->save();

            // Clear course caches
            CacheService::invalidateCourseCache();

            return (new CourseResource($course))->response()->setStatusCode(201);
        }

        return response()->json(['message' => 'Unauthorized'], 403);
    }

    public function getList(Request $request)
    {
        if ($request->user()->can('view courses')) {
            $courses = CacheService::cacheCourses(function () {
                return Course::select(['id', 'title', 'slug', 'description', 'cover_image', 'isOpen', 'total_hours', 'created_at', 'updated_at'])
                    ->orderBy('created_at', 'desc')
                    ->get();
            });

            return (new CourseCollection($courses))->response()->setStatusCode(200);
        }

        return response()->json(['message' => 'Unauthorized'], 403);
    }

    public function search(Request $request)
    {
        if ($request->user()->can('view courses')) {
            $page = request()->input('page', 1);
            $size = request()->input('size', 10);
            $search = $request->input('search');

            // Cache search results for short time
            $cacheKey = "course_search_" . md5($search . $page . $size);
            $courses = Cache::remember($cacheKey, 300, function () use ($search, $size, $page) {
                return Course::where('title', 'like', "%$search%")
                    ->orWhere('description', 'like', "%$search%")
                    ->select(['id', 'title', 'slug', 'description', 'cover_image', 'isOpen', 'total_hours'])
                    ->paginate($size, ['*'], 'page', $page);
            });

            if ($courses === null) {
                return response()->json(['message' => 'Course not found'], 404);
            }
            return new CourseCollection($courses);
        }

        return response()->json(['message' => 'Unauthorized'], 403);
    }

    public function get(Request $request, int $course_id)
    {
        if ($request->user()->can('view courses')) {
            $course = CacheService::cacheCourse($course_id, function () use ($course_id) {
                return Course::with([
                    'modules' => function ($query) {
                        $query->orderBy('position');
                    },
                    'modules.lessons' => function ($query) {
                        $query->orderBy('position');
                    },
                    'modules.lessons.epub',
                    'modules.tasks'
                ])->find($course_id);
            });

            if ($course === null) {
                throw new HttpResponseException(response()->json([
                    'error' => [
                        'message' => [
                            'Course not found'
                        ]
                    ]
                ], 404));
            }
            return (new CourseDetailResource($course))->response()->setStatusCode(200);
        }

        return response()->json(['message' => 'Unauthorized'], 403);
    }

    /**
     * Get course with full navigation info for lessons
     */
    public function getWithNavigation(Request $request, int $course_id)
    {
        if ($request->user()->can('view courses')) {
            $cacheKey = "course_with_navigation_{$course_id}";
            $courseData = Cache::remember($cacheKey, 3600, function () use ($course_id) {
                $course = Course::with([
                    'modules' => function ($query) {
                        $query->orderBy('position');
                    },
                    'modules.lessons' => function ($query) {
                        $query->orderBy('position');
                    },
                    'modules.lessons.epub',
                    'modules.tasks'
                ])->find($course_id);

                if (!$course) {
                    return null;
                }

                // Build flat array of all lessons for navigation
                $allLessons = collect();
                foreach ($course->modules as $module) {
                    foreach ($module->lessons as $lesson) {
                        $allLessons->push($lesson);
                    }
                }

                // Add navigation to each lesson
                foreach ($course->modules as $module) {
                    foreach ($module->lessons as $lesson) {
                        $currentIndex = $allLessons->search(function ($item) use ($lesson) {
                            return $item->id === $lesson->id;
                        });
                        
                        $lesson->next_lesson = $currentIndex !== false && $currentIndex < $allLessons->count() - 1 
                            ? $allLessons[$currentIndex + 1] : null;
                        $lesson->prev_lesson = $currentIndex > 0 
                            ? $allLessons[$currentIndex - 1] : null;
                    }
                }

                return $course;
            });

            if ($courseData === null) {
                throw new HttpResponseException(response()->json([
                    'error' => [
                        'message' => [
                            'Course not found'
                        ]
                    ]
                ], 404));
            }

            return response()->json([
                'data' => [
                    'id' => $courseData->id,
                    'title' => $courseData->title,
                    'slug' => $courseData->slug,
                    'description' => $courseData->description,
                    'course_img' => $courseData->course_img,
                    'video_url' => $courseData->video_url,
                    'isOpen' => $courseData->isOpen,
                    'total_hours' => $courseData->total_hours,
                    'created_at' => $courseData->created_at,
                    'updated_at' => $courseData->updated_at,
                    'modules' => $courseData->modules->map(function ($module) {
                        return [
                            'id' => $module->id,
                            'title' => $module->title,
                            'slug' => $module->slug,
                            'cover_image' => $module->cover_image,
                            'video_url' => $module->video_url,
                            'position' => $module->position,
                            'description' => $module->description,
                            'course_id' => $module->course_id,
                            'lessons' => $module->lessons->map(function ($lesson) {
                                return [
                                    'id' => $lesson->id,
                                    'module_id' => $lesson->module_id,
                                    'title' => $lesson->title,
                                    'slug' => $lesson->slug,
                                    'cover_image' => $lesson->cover_image,
                                    'video_url' => $lesson->video_url,
                                    'attachment' => $lesson->attachment,
                                    'position' => $lesson->position,
                                    'description' => $lesson->description,
                                    'epub' => $lesson->epub ? new EpubResource($lesson->epub) : null,
                                    'navigation' => [
                                        'next_lesson' => $lesson->next_lesson ? [
                                            'id' => $lesson->next_lesson->id,
                                            'title' => $lesson->next_lesson->title,
                                            'slug' => $lesson->next_lesson->slug,
                                            'module_id' => $lesson->next_lesson->module_id
                                        ] : null,
                                        'prev_lesson' => $lesson->prev_lesson ? [
                                            'id' => $lesson->prev_lesson->id,
                                            'title' => $lesson->prev_lesson->title,
                                            'slug' => $lesson->prev_lesson->slug,
                                            'module_id' => $lesson->prev_lesson->module_id
                                        ] : null
                                    ]
                                ];
                            })
                        ];
                    })
                ]
            ], 200);
        }

        return response()->json(['message' => 'Unauthorized'], 403);
    }

    public function update(CourseUpdateRequest $request, int $course_id): JsonResponse
    {
        $data = $request->validated();
        if ($request->user()->can('edit courses')) {
            $course = Course::find($course_id);
            if ($course === null) {
                return response()->json(['message' => 'Course not found'], 404);
            }

            if (Course::where('slug', $data['slug'])->where('id', '!=', $course_id)->exists()) {
                return response()->json(['message' => 'Course with this slug already exists'], 422);
            }

            $course->update($data);

            // Clear course caches using the service
            CacheService::invalidateCourseCache($course_id);
            
            // Also clear the navigation cache
            Cache::forget("course_with_navigation_{$course_id}");

            return (new CourseResource($course))->response()->setStatusCode(200);
        }

        return response()->json(['message' => 'Unauthorized'], 403);
    }

    public function delete(Request $request, int $course_id): JsonResponse
    {
        if ($request->user()->can('delete courses')) {
            $course = Course::find($course_id);
            if ($course === null) {
                return response()->json(['message' => 'Course not found'], 404);
            }
            $course->delete();

            // Clear course caches using the service
            CacheService::invalidateCourseCache($course_id);
            
            // Also clear the navigation cache
            Cache::forget("course_with_navigation_{$course_id}");

            return response()->json(['message' => 'Course deleted'], 200);
        }

        return response()->json(['message' => 'Unauthorized'], 403);
    }
}