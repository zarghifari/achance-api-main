<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\AttemptAnswerRequest;
use App\Http\Resources\AttemptAnswerResource;
use App\Http\Resources\AttemptAnswerCollection;
use App\Models\AttemptAnswer;
use App\Models\AttemptQuiz;
use App\Models\QuizAnswer;
use Illuminate\Http\JsonResponse;

class AttemptAnswerController extends Controller
{
    public function answerCreate(AttemptAnswerRequest $request, int $quiz_id, int $attempt_id): JsonResponse
    {
        // Verify the attempt exists and belongs to the user (or user has admin permissions)
        $attemptQuiz = AttemptQuiz::find($attempt_id);
        
        if (!$attemptQuiz) {
            return response()->json(['message' => 'Attempt not found'], 404);
        }
        
        // Check if user owns the attempt or has admin permissions
        if ($attemptQuiz->user_id !== $request->user()->id && !$request->user()->can('view all attempt quizzes')) {
            return response()->json(['message' => 'Unauthorized - attempt not owned by user'], 403);
        }

        if ($request->user()->can('doing own quizzes')) {
            $data = $request->validated();
            $data['attempt_id'] = $attempt_id;

            // Handle questionId from Postman (map to quiz_question_id)
            if ($request->has('questionId')) {
                $data['quiz_question_id'] = $request->input('questionId');
            }

            // Handle answer by index (Postman format)
            if (isset($data['answer']) && isset($data['quiz_question_id'])) {
                $questionId = $data['quiz_question_id'];
                $answerIndex = $data['answer'];
                
                // Get the quiz answers for this question
                $quizAnswers = QuizAnswer::where('quiz_question_id', $questionId)
                    ->orderBy('id')
                    ->get();
                
                if ($answerIndex < $quizAnswers->count()) {
                    $selectedAnswer = $quizAnswers[$answerIndex];
                    $data['selected_answer_id'] = $selectedAnswer->id;
                    $data['is_correct'] = $selectedAnswer->is_correct;
                } else {
                    return response()->json(['message' => 'Invalid answer index'], 400);
                }
            } 
            // Handle answer by selected_answer_id
            elseif (isset($data['selected_answer_id'])) {
                $quizAnswer = QuizAnswer::find($data['selected_answer_id']);
                if (!$quizAnswer) {
                    return response()->json(['message' => 'Invalid answer ID'], 400);
                }
                $data['is_correct'] = $quizAnswer->is_correct;
            } else {
                return response()->json(['message' => 'Either answer index or selected_answer_id is required'], 400);
            }

            $attemptAnswer = AttemptAnswer::create($data);
            
            return response()->json([
                'success' => true,
                'data' => new AttemptAnswerResource($attemptAnswer)
            ]);
        } else {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
    }


    public function answerDetail(Request $request, int $id): AttemptAnswerResource
    {
        $attemptAnswer = AttemptAnswer::find($id);
        if ($request->user()->can('view attempts', $attemptAnswer)) {
            if ($attemptAnswer === null) {
                return response()->json([
                    'message' => 'Attempt answer not found',
                ], 404);
            }
            return new AttemptAnswerResource($attemptAnswer);
        } else {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
    }

    public function answerUpdate(AttemptAnswerRequest $request, int $id): AttemptAnswerResource
    {
        $attemptAnswer = AttemptAnswer::find($id);
        if ($request->user()->can('edit attempts', $attemptAnswer)) {
            if ($attemptAnswer === null) {
                return response()->json([
                    'message' => 'Attempt answer not found',
                ], 404);
            }
            $data = $request->validated();
            $attemptAnswer->update($data);
            return new AttemptAnswerResource($attemptAnswer);
        } else {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
    }

    public function answerDelete(Request $request, int $id): JsonResponse
    {
        $attemptAnswer = AttemptAnswer::find($id);
        if ($request->user()->can('delete attempts', $attemptAnswer)) {
            if ($attemptAnswer === null) {
                return response()->json([
                    'message' => 'Attempt answer not found',
                ], 404);
            }
            $attemptAnswer->delete();
            return response()->json([
                'message' => 'Attempt answer deleted successfully',
            ]);
        } else {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
    }
}