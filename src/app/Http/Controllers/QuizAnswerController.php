<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\QuizAnswerRequest;
use App\Http\Resources\QuizAnswerResource;
use App\Http\Resources\QuizAnswerCollection;
use App\Models\QuizAnswer;
use App\Models\QuizQuestion;
use Illuminate\Http\JsonResponse;

class QuizAnswerController extends Controller
{
    public function answerCreate(QuizAnswerRequest $request, int $quiz_id, int $quiz_question_id)
    {
        if ($request->user()->can('create quizzes')) {
            $data = $request->validated();
            $data['quiz_question_id'] = $quiz_question_id;
            $quizAnswer = QuizAnswer::create($data);
            return new QuizAnswerResource($quizAnswer);
        } else {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
    }

    public function answerQuestionList(Request $request, int $quiz_id, int $quiz_question_id)
    {
        if ($request->user()->can('view quizzes')) {
            $quizAnswers = QuizAnswer::where('quiz_question_id', $quiz_question_id)->get();
            return new QuizAnswerCollection($quizAnswers);
        } else {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
    }

    public function answerUpdate(QuizAnswerRequest $request,  int $quiz_id, int $quiz_question_id, int $quiz_answer_id)
    {
        $quizAnswer = QuizAnswer::where('quiz_question_id', $quiz_question_id)->find($quiz_answer_id);
        if ($request->user()->can('create quizzes')) {
            if ($quizAnswer === null) {
                return response()->json([
                    'message' => 'Quiz answer not found',
                ], 404);
            }
            $data = $request->validated();
            $quizAnswer->update($data);
            return new QuizAnswerResource($quizAnswer);
        } else {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
    }

    public function answerDelete(Request $request, int $quiz_id, int $quiz_question_id, int $quiz_answer_id)
    {
        $quizAnswer = QuizAnswer::where('quiz_question_id', $quiz_question_id)->find($quiz_answer_id);
        if ($request->user()->can('delete quizzes', $quizAnswer)) {
            if ($quizAnswer === null) {
                return response()->json([
                    'message' => 'Quiz answer not found',
                ], 404);
            }
            $quizAnswer->delete();
            return response()->json([
                'message' => 'Quiz answer deleted successfully',
            ]);
        } else {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
    }
}