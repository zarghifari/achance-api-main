<?php

namespace App\Http\Controllers;

use App\Http\Requests\CourseCreateRequest;
use App\Http\Requests\CourseUpdateRequest;
use App\Http\Resources\CourseResource;
use App\Http\Resources\CourseDetailResource;
use App\Http\Resources\CourseCollection;
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

            Cache::forget('course_' . $course_id);
            Cache::put('course_' . $course->id, $course, 3600);
            Cache::forget('courses');

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

            Cache::forget('course_' . $course_id);
            Cache::forget('courses');

            return response()->json(['message' => 'Course deleted'], 200);
        }

        return response()->json(['message' => 'Unauthorized'], 403);
    }
}