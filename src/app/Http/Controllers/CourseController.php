<?php

namespace App\Http\Controllers;

use App\Http\Requests\CourseCreateRequest;
use App\Http\Requests\CourseUpdateRequest;
use App\Http\Resources\CourseResource;
use App\Http\Resources\CourseDetailResource;
use App\Http\Resources\CourseCollection;
use App\Http\Resources\ContentResource;
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
            $cacheKey = 'courses_list_json';
            
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                return response()->json($cached, 200)
                    ->header('X-Cache-Status', 'HIT');
            }
            
            $courses = Course::select(['id', 'title', 'slug', 'description', 'cover_image', 'isOpen', 'total_hours', 'created_at', 'updated_at'])
                ->orderBy('created_at', 'desc')
                ->get();

            $data = ['data' => $courses->map(function($course) {
                return [
                    'id' => $course->id,
                    'title' => $course->title,
                    'slug' => $course->slug,
                    'description' => $course->description,
                    'cover_image' => $course->cover_image,
                    'isOpen' => $course->isOpen,
                    'total_hours' => $course->total_hours,
                    'created_at' => $course->created_at,
                    'updated_at' => $course->updated_at,
                ];
            })->values()->all()];
            
            Cache::put($cacheKey, $data, 3600); // 1 hour cache
            
            return response()->json($data, 200)
                ->header('X-Cache-Status', 'MISS')
                ->header('Cache-Control', 'public, max-age=3600');
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
            $cacheKey = "course_search_json_" . md5($search . $page . $size);
            
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                return response()->json($cached, 200)
                    ->header('X-Cache-Status', 'HIT');
            }
            
            // Use FULLTEXT search for 50-100ms faster queries
            if (!empty($search)) {
                $courses = Course::whereRaw('MATCH(title, description) AGAINST(? IN NATURAL LANGUAGE MODE)', [$search])
                    ->select(['id', 'title', 'slug', 'description', 'cover_image', 'isOpen', 'total_hours'])
                    ->paginate($size, ['*'], 'page', $page);
            } else {
                // Empty search - return all
                $courses = Course::select(['id', 'title', 'slug', 'description', 'cover_image', 'isOpen', 'total_hours'])
                    ->paginate($size, ['*'], 'page', $page);
            }
            
            $data = [
                'data' => $courses->map(function($course) {
                    return [
                        'id' => $course->id,
                        'title' => $course->title,
                        'slug' => $course->slug,
                        'description' => $course->description,
                        'cover_image' => $course->cover_image,
                        'isOpen' => $course->isOpen,
                        'total_hours' => $course->total_hours,
                    ];
                })->values()->all(),
                'meta' => [
                    'current_page' => $courses->currentPage(),
                    'from' => $courses->firstItem(),
                    'last_page' => $courses->lastPage(),
                    'per_page' => $courses->perPage(),
                    'to' => $courses->lastItem(),
                    'total' => $courses->total(),
                ]
            ];
            
            Cache::put($cacheKey, $data, 1800); // 30 minutes cache
            
            return response()->json($data, 200)
                ->header('X-Cache-Status', 'MISS')
                ->header('Cache-Control', 'public, max-age=1800');
        }

        return response()->json(['message' => 'Unauthorized'], 403);
    }

    public function get(Request $request, int $course_id)
    {
        if ($request->user()->can('view courses')) {
            // Use response caching with fast serialization
            $cacheKey = "course_json_{$course_id}_v2";
            
            $cached = Cache::get($cacheKey);
            
            if ($cached !== null) {
                // Cache hit - return immediately without Resource overhead
                return response()->json($cached, 200)
                    ->header('Cache-Control', 'public, max-age=600')
                    ->header('X-Cache-Status', 'HIT');
            }
            
            // Cache miss - load and transform data
            $course = Course::with([
                'modules' => function ($query) {
                    $query->orderBy('position')->select(['id', 'course_id', 'title', 'slug', 'cover_image', 'video_url', 'position', 'description']);
                },
                'modules.lessons' => function ($query) {
                    $query->orderBy('position')->select(['id', 'module_id', 'title', 'slug', 'cover_image', 'video_url', 'attachment', 'position', 'description']);
                },
                'modules.lessons.content' => function ($query) {
                    $query->select(['id', 'lesson_id', 'title', 'type', 'total_pages', 'is_processed', 'metadata']);
                },
                'modules.tasks' => function ($query) {
                    $query->select(['id', 'module_id', 'title', 'slug', 'content', 'position']);
                }
            ])->select(['id', 'title', 'slug', 'description', 'cover_image', 'video_url', 'isOpen', 'total_hours', 'created_at', 'updated_at'])
            ->find($course_id);

            if ($course === null) {
                throw new HttpResponseException(response()->json([
                    'error' => [
                        'message' => [
                            'Course not found'
                        ]
                    ]
                ], 404));
            }
            
            // Transform to array directly (faster than Resource)
            $data = [
                'data' => [
                    'id' => $course->id,
                    'title' => $course->title,
                    'slug' => $course->slug,
                    'description' => $course->description,
                    'cover_image' => $course->cover_image,
                    'video_url' => $course->video_url,
                    'isOpen' => $course->isOpen,
                    'total_hours' => $course->total_hours,
                    'created_at' => $course->created_at,
                    'updated_at' => $course->updated_at,
                    'modules' => $course->modules->map(function ($module) {
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
                                    'content' => $lesson->content ? [
                                        'id' => $lesson->content->id,
                                        'title' => $lesson->content->title,
                                        'type' => $lesson->content->type,
                                        'total_pages' => $lesson->content->total_pages,
                                        'is_processed' => $lesson->content->is_processed,
                                        'metadata' => $lesson->content->metadata,
                                    ] : null,
                                ];
                            })->values()->all(),
                            'tasks' => $module->tasks->map(function ($task) {
                                return [
                                    'id' => $task->id,
                                    'module_id' => $task->module_id,
                                    'title' => $task->title,
                                    'slug' => $task->slug,
                                    'content' => $task->content,
                                    'position' => $task->position,
                                ];
                            })->values()->all(),
                        ];
                    })->values()->all(),
                ]
            ];
            
            // Cache for 1 hour
            Cache::put($cacheKey, $data, 3600);
            
            return response()->json($data, 200)
                ->header('Cache-Control', 'public, max-age=3600')
                ->header('X-Cache-Status', 'MISS');
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
                    'modules.lessons.content',
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
                                    'content' => $lesson->content ? new ContentResource($lesson->content) : null,
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