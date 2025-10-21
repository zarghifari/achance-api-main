<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Http\Requests\TaskUpdateRequest;
use App\Http\Requests\TaskCreateRequest;
use App\Http\Resources\TaskResource;
use App\Http\Resources\TaskCollection;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class TaskController extends Controller
{
    public function create(int $course_id, int $module_id, TaskCreateRequest $request): JsonResponse   
    {
        // Create a new task
        $data = $request->validated();
        if($request->user()->can('create courses')) {
            $data['module_id'] = $module_id;
            if(Task::where('slug', $data['slug'])->exists()) {
                throw new HttpResponseException(response()->json([
                    'error' => [
                        'message' => [
                            'Task with this slug already exists'
                        ]
                    ]
                ], 422));
            }else if($data['slug'] === null) {
                $data['slug'] = Str::slug($data['title']);
            }

            $task_max_position = Task::where('module_id', $module_id)->max('position');
            if($data['position'] === null) {
                $data['position'] = $task_max_position + 1;
            } 
            if($data['position'] > $task_max_position + 1) {
                $data['position'] = $task_max_position + 1;
            }
            if($data['position'] < 1) {
                $data['position'] = 1;
            }
            if($data['position'] < $task_max_position && $data['position'] >= 1) {
                $tasks = Task::where('module_id', $module_id)
                    ->where('position', '>=', $data['position'])
                    ->get();
                foreach($tasks as $task) {
                    $task->position += 1;
                    $task->save();
                }
            }

            $task = Task::create($data);
            Cache::forget('course_' . $course_id);
            Cache::put("task_" . $task->id, $task, 3600);
            Cache::forget("tasks_list_{$module_id}");
            return (new TaskResource($task))->response()->setStatusCode(201);
        }

        return response()->json(['message' => 'Unauthorized'], 403);
    }

    
    public function listByModule(int $course_id, int $module_id, Request $request): TaskCollection
    {
        // Get all tasks for a module
        if($request->user()->can('view courses')) {
            $tasks = Cache::remember("tasks_list_{$module_id}", 3600, function() use ($module_id) {
                return Task::where('module_id', $module_id)
                    ->orderByRaw('COALESCE(position, 0)')
                    ->get();
            });
            return new TaskCollection($tasks);
        }
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    public function get(int $course_id, int $module_id, int $task_id, Request $request): JsonResponse
    {
        // Get a task
        if($request->user()->can('view courses')) {
            $task = Cache::remember("task_{$task_id}", 3600, function() use ($module_id, $task_id) {
                return Task::where('module_id', $module_id)
                    ->where('id', $task_id)
                    ->firstOrFail();
            });
            return (new TaskResource($task))->response()->setStatusCode(200);
        }
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    public function update(int $course_id, int $module_id, int $task_id, TaskUpdateRequest $request): JsonResponse
    {
        // Update a task
        if($request->user()->can('edit courses')) {
            $task = Task::where('module_id', $module_id)
                ->where('id', $task_id)
                ->firstOrFail();
            $data = $request->validated();
            if($data['slug'] === null) {
                $data['slug'] = Str::slug($data['title']);
            } else if($data['slug'] !== $task->slug) {
                if(Task::where('slug', $data['slug'])->exists()) {
                    throw new HttpResponseException(response()->json([
                        'error' => [
                            'message' => [
                                'Task with this slug already exists'
                            ]
                        ]
                    ], 422));
                }
            }
            
            $task_max_position = Task::where('module_id', $module_id)->max('position');
            if($data['position'] !== $task->position) {
                if($data['position'] === null) {
                    $data['position'] = $task->position;
                }
                if($data['position'] > $task_max_position) {
                    $data['position'] = $task_max_position;
                }
                if($data['position'] < 1) {
                    $data['position'] = 1;
                }
                if($data['position'] < $task->position) {
                    $tasks = Task::where('module_id', $module_id)
                        ->where('position', '>=', $data['position'])
                        ->where('position', '<', $task->position)
                        ->get();
                    foreach($tasks as $t) {
                        $t->position += 1;
                        $t->save();
                    }
                }
                if($data['position'] > $task->position) {
                    $tasks = Task::where('module_id', $module_id)
                        ->where('position', '>', $task->position)
                        ->where('position', '<=', $data['position'])
                        ->get();
                    foreach($tasks as $t) {
                        $t->position -= 1;
                        $t->save();
                    }
                }
            }
            $task->update($data);

            Cache::forget('course_' . $course_id);
            Cache::put("task_{$task_id}", $task, 3600);
            Cache::forget("tasks_list_{$module_id}");

            return (new TaskResource($task))->response()->setStatusCode(200);
        }
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    public function delete(int $course_id, int $module_id, int $task_id, Request $request): JsonResponse
    {
        // Delete a task
        if($request->user()->can('delete courses')) {
            $task = Task::where('module_id', $module_id)
                ->where('id', $task_id)
                ->firstOrFail();
            if($task->position !== null) {
                $tasks = Task::where('module_id', $module_id)
                    ->where('position', '>', $task->position)
                    ->orderBy('position')
                    ->get();
                $current_position = $task->position;
                foreach($tasks as $t) {
                    $t->position = $current_position;
                    $t->save();
                    $current_position++;
                }
            }
            $task->delete();

            Cache::forget('course_' . $course_id);
            Cache::forget("task_{$task_id}");
            Cache::forget("tasks_list_{$module_id}");

            return response()->json(['message' => 'Task deleted'], 200);
        }
        return response()->json(['message' => 'Unauthorized'], 403);
    }
}
