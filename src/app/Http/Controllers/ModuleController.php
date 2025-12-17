<?php

namespace App\Http\Controllers;

use App\Http\Requests\ModuleCreateRequest;
use App\Http\Requests\ModuleUpdateRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Module;
use App\Http\Resources\ModuleResource;
use App\Http\Resources\ModuleCollection;
use App\Services\CacheService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ModuleController extends Controller
{
    public function create(int $course_id, ModuleCreateRequest $request): JsonResponse
    {
        Log::info('Request received on server: ' . $_SERVER['SERVER_NAME'] . ' using cache: ' . config('cache.default'));

        if ($request->user()->can('create courses')) {
            $data = $request->validated();
            $data['course_id'] = $course_id;
            if(Module::where('slug', $data['slug'])->exists()) {
                return response()->json(['message' => 'Module with this slug already exists'], 422);
            }

            $max_position = Module::where('course_id', $course_id)->max('position');
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
                $modules = Module::where('course_id', $course_id)
                    ->where('position', '>=', $data['position'])
                    ->get();
                foreach ($modules as $m) {
                    $m->position += 1;
                    $m->save();
                }
            }
            $module = Module::create($data);

            // Clear course caches and related caches
            CacheService::invalidateCourseCache($course_id);
            Cache::forget("module_" . $module->id);
            Cache::forget("module_json_{$module->id}");
            Cache::forget("modules_list_{$course_id}");
            
            return (new ModuleResource($module))->response()->setStatusCode(201);
        }

        return response()->json(['message' => 'Unauthorized'], 403);
    }

    public function listByCourse(int $course_id, Request $request): JsonResponse
    {
        if ($request->user()->can('view courses')) {
            $modules = Cache::remember("modules_list_{$course_id}", 3600, function() use ($course_id) {
                return Module::with([
                    'lessons' => function ($query) {
                        $query->orderBy('position');
                    },
                    'lessons.epub',
                    'tasks'
                ])
                ->where('course_id', $course_id)
                ->orderByRaw('COALESCE(position, 0)')
                ->get();
            });
            return (new ModuleCollection($modules))->response()->setStatusCode(200);
        }
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    public function get(int $course_id, int $module_id, Request $request): JsonResponse
    {
        if ($request->user()->can('view courses')) {
            $cacheKey = "module_json_{$module_id}";
            
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                return response()->json($cached, 200)
                    ->header('X-Cache-Status', 'HIT');
            }
            
            $module = Module::where('course_id', $course_id)
                ->where('id', $module_id)
                ->select(['id', 'course_id', 'title', 'slug', 'description', 'cover_image', 'video_url', 'position', 'created_at', 'updated_at'])
                ->firstOrFail();

            $data = [
                'data' => [
                    'id' => $module->id,
                    'course_id' => $module->course_id,
                    'title' => $module->title,
                    'slug' => $module->slug,
                    'description' => $module->description,
                    'cover_image' => $module->cover_image,
                    'video_url' => $module->video_url,
                    'position' => $module->position,
                    'created_at' => $module->created_at,
                    'updated_at' => $module->updated_at,
                ]
            ];
            
            Cache::put($cacheKey, $data, 3600); // 1 hour cache
            
            return response()->json($data, 200)
                ->header('X-Cache-Status', 'MISS');
        }
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    public function update(int $course_id, int $module_id, ModuleUpdateRequest $request): JsonResponse
    {
        if ($request->user()->can('edit courses')) {
            $data = $request->validated();
            $module = Module::where('course_id', $course_id)
                ->where('id', $module_id)
                ->firstOrFail();

            if (Module::where('slug', $data['slug'])->where('id', '!=', $module_id)->exists()) {
                return response()->json(['message' => 'Module with this slug already exists'], 422);
            }
            
            $max_position = Module::where('course_id', $course_id)->max('position');
            if($data['position'] !== $module->position) {
                if ($data['position'] == null) {
                    $data['position'] = $module->position;
                }
    
                if ($data['position'] > $max_position + 1) {
                    $data['position'] = $max_position + 1;
                }
    
                if ($data['position'] < 1) {
                    $data['position'] = 1;
                }
    
                if ($data['position'] < $max_position) {
                    $modules = Module::where('course_id', $course_id)
                        ->where('position', '>=', $data['position'])
                        ->where('position', '<', $module->position)
                        ->get();
                    foreach ($modules as $m) {
                        $m->position += 1;
                        $m->save();
                    }
                }

                if($data['position'] > $module->position) {
                    $modules = Module::where('course_id', $course_id)
                        ->where('position', '>', $module->position)
                        ->where('position', '<=', $data['position'])
                        ->get();
                    foreach ($modules as $m) {
                        $m->position -= 1;
                        $m->save();
                    }
                }
            }else {
                $data['position'] = $module->position;
            }
            $module->update($data);

            // Clear course caches and related caches
            CacheService::invalidateCourseCache($course_id);
            Cache::forget("module_" . $module->id);
            Cache::forget("module_json_{$module->id}");
            Cache::forget("modules_list_{$course_id}");
            
            return (new ModuleResource($module))->response()->setStatusCode(200);
        }
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    public function delete(int $course_id, int $module_id, Request $request): JsonResponse
    {
        if ($request->user()->can('delete courses')) {
            $module = Module::where('course_id', $course_id)
                ->where('id', $module_id)
                ->firstOrFail();
            $modules = Module::where('course_id', $course_id)
                ->where('position', '>', $module->position)
                ->get();

            foreach ($modules as $module) {
                $module->position -= 1;
                $module->save();
            }
            $module->delete();

            // Clear course caches and related caches
            CacheService::invalidateCourseCache($course_id);
            Cache::forget("module_" . $module->id);
            Cache::forget("module_{$module_id}");
            Cache::forget("module_json_{$module_id}");
            Cache::forget("modules_list_{$course_id}");
            
            return response()->json(['message' => 'Module deleted'], 200);
        }
        return response()->json(['message' => 'Unauthorized'], 403);
    }
}
