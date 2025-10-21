<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Quiz;
use App\Http\Requests\QuizRequest;
use App\Http\Resources\QuizCollection;
use App\Http\Resources\QuizDetailResource;
use App\Http\Resources\QuizQuestionAnswerResource;
use App\Http\Resources\QuizResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class QuizController extends Controller
{
    public function quizCreate(QuizRequest $request)
    {
        if ($request->user()->can('create quizzes')) {
            $data = $request->validated();
            if (Quiz::where('slug', $data['slug'])->exists()) {
                return response()->json(['message' => 'Slug already exists'], 409);
            }

            $data["published_at"] = now();
            if(!isset($data["start_at"])) {
                $data["start_at"] = now();
            }

            $quiz = Quiz::create($data);
            Cache::put('quiz_' . $quiz->id, $quiz, 3600); // Cache for 1 hour
            Cache::forget('quizzes'); // Clear cache
            return (new QuizResource($quiz))->response()->setStatusCode(201);
        } else {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
    }

    public function quizList(Request $request)
    {
        if ($request->user()->can('view quizzes')) {
            $quizzes = Cache::remember('quizzes', 3600, function () {
                return Quiz::all();
            });
            return new QuizCollection($quizzes);
        } else {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
    }

    public function quizDetailWithQuestionsAndAnswers(Request $request, $quiz_id)
    {
        $quiz = Cache::remember('qqa_' . $quiz_id, 3600, function () use ($quiz_id) {
            return Quiz::withQuestionsAndAnswers()->find($quiz_id);
        });

        if ($request->user()->can('view quizzes', $quiz)) {
            if ($quiz === null) {
                return response()->json([
                    'message' => 'Quiz not found',
                ], 404);
            }
            
            return new QuizDetailResource($quiz);
        } else {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
    }

    public function quizUpdate(QuizRequest $request, int $quiz_id): QuizResource
    {
        if($request->user()->can('edit quizzes')) {
            $quiz = Quiz::find($quiz_id);
            if ($quiz === null) {
                return response()->json([
                    'message' => 'Quiz not found',
                ], 404);
            }

            $data = $request->validated();
            if (Quiz::where('slug', $data['slug'])->where('id', '!=', $quiz_id)->exists()) {
                return response()->json(['message' => 'Slug already exists'], 409);
            }

            $quiz->update($data);
            Cache::put('quiz_' . $quiz->id, $quiz, 3600); // Update cache for 1 hour
            Cache::forget('quizzes'); // Clear cache
            Cache::forget('qqa_' . $quiz_id); // Clear cache

            return new QuizResource($quiz);
        } else {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
    }

    public function quizDelete(Request $request, int $quiz_id): JsonResponse
    {
        if($request->user()->can('delete quizzes')) {
            $quiz = Quiz::find($quiz_id);

            if ($quiz === null) {
                return response()->json([
                    'message' => 'Quiz not found',
                ], 404);
            }

            $quiz->delete();
            Cache::forget('quiz_' . $quiz_id); // Remove from cache
            Cache::forget('quizzes'); // Clear cache
            Cache::forget('qqa_' . $quiz_id); // Clear cache

            return response()->json([
                'message' => 'Quiz deleted successfully',
            ]);
        } else {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
    }

}
