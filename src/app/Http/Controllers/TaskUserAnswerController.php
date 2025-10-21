<?php

namespace App\Http\Controllers;

use App\Http\Resources\TaskUserAnswerResource;
use App\Http\Requests\TaskUserAnswerCreateRequest;
use App\Http\Requests\TaskUserAnswerUpdateRequest;
use App\Http\Resources\TaskUserAnswerCollection;
use App\Models\Task;
use Illuminate\Http\Request;
use App\Models\TaskUserAnswer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Js;

class TaskUserAnswerController extends Controller
{
    public function create(TaskUserAnswerCreateRequest $request, int $courseId, int $moduleId, int $taskId)
    {
        if ($request->user()->can('create', TaskUserAnswer::class)) {
            $data = $request->validated();
            $data['user_id'] = $request->user()->id;
            $data['task_id'] = $taskId;
            $taskUserAnswer = TaskUserAnswer::create($data);
            return (new TaskUserAnswerResource($taskUserAnswer))->response()->setStatusCode(201);
        } else {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
    }

    public function update(TaskUserAnswerUpdateRequest $request, int $courseId, int $moduleId, int $taskId, int $id): JsonResponse
    {
        $taskUserAnswer = TaskUserAnswer::find($id);
        if ($taskUserAnswer === null) {
            return response()->json(['message' => 'Task user answer not found'], 404);
        }
        
        if ($request->user()->can('update', $taskUserAnswer)) {
            $data = $request->validated();
            $taskUserAnswer->update($data);
            return (new TaskUserAnswerResource($taskUserAnswer))->response()->setStatusCode(200);
        } else {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
    }

    public function detail(Request $request, int $courseId, int $moduleId, int $taskId, int $id)
    {
        $taskUserAnswer = TaskUserAnswer::find($id);
        if ($taskUserAnswer === null) {
            return response()->json(['message' => 'Task user answer not found'], 404);
        }
        
        if ($request->user()->can('view', $taskUserAnswer)) {
            return (new TaskUserAnswerResource($taskUserAnswer))->response()->setStatusCode(200);
        } else {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
    }

    public function delete(Request $request, int $courseId, int $moduleId, int $taskId, int $id): JsonResponse
    {
        $taskUserAnswer = TaskUserAnswer::find($id);
        if ($taskUserAnswer === null) {
            return response()->json(['message' => 'Task user answer not found'], 404);
        }
        
        if ($request->user()->can('delete', $taskUserAnswer)) {
            $taskUserAnswer->delete();
            return response()->json(['message' => 'Task user answer deleted successfully'], 200);
        } else {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
    }

    // public function list(Request $request, int $courseId, int $moduleId, int $taskId): TaskUserAnswerCollection
    // {
    //     if ($request->user()->can('view own tasks')) {
    //         $taskUserAnswers = TaskUserAnswer::where('user_id', $request->user()->id)
    //                                          ->where('course_id', $courseId)
    //                                          ->where('module_id', $moduleId)
    //                                          ->where('task_id', $taskId)
    //                                          ->get();
    //         return new TaskUserAnswerCollection($taskUserAnswers);
    //     } else {
    //         return response()->json(['message' => 'Unauthorized'], 403);
    //     }
    // }
}
