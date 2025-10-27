<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\QuizQuestionRequest;
use App\Http\Requests\QuizQuestionWithAnswerRequest;
use App\Http\Resources\QuizQuestionResource;
use App\Http\Resources\QuizQuestionCollection;
use App\Models\QuizAnswer;
use App\Models\QuizQuestion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class QuizQuestionController extends Controller
{
    public function createQuestionWithAnswer(QuizQuestionWithAnswerRequest $request, int $quiz_id)
    {
        if ($request->user()->can('create quizzes')) {
            $data = $request->validated();
            $data['quiz_id'] = $quiz_id;
            $data['question_number'] = QuizQuestion::where('quiz_id', $quiz_id)->max('question_number') + 1;

            DB::beginTransaction();
            try {
                $quizQuestion = QuizQuestion::create($data);

                if ($request->has('quiz_answers')) {
                    $quizAnswers = [];
                    foreach ($data['quiz_answers'] as $answer) {
                        $answer['quiz_question_id'] = $quizQuestion->id;
                        $quizAnswers[] = $answer;
                    }
                    // Lakukan batch insert untuk quiz answers
                    QuizAnswer::insert($quizAnswers);
                }

                DB::commit();
                Cache::forget("questions_list_{$quiz_id}"); // Clear cache
                Cache::forget('qqa_' . $quiz_id); // Clear cache
                Cache::forget("question_" . $quizQuestion->id, $quizQuestion, 3600);

                return new QuizQuestionResource($quizQuestion);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['message' => 'Failed to create question and answers', 'error' => $e->getMessage()], 500);
            }
        } else {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
    }

    public function questionCreate(QuizQuestionRequest $request, int $quiz_id)
    {
        if ($request->user()->can('create quizzes')) {
            $data = $request->validated();
            $data['quiz_id'] = $quiz_id;
            $data['question_number'] = QuizQuestion::where('quiz_id', $quiz_id)->max('question_number') + 1;
            $quizQuestion = QuizQuestion::create($data);

            Cache::forget("questions_list_{$quiz_id}"); // Clear cache
            Cache::forget('qqa_' . $quiz_id); // Clear cache
            Cache::put("question_" . $quizQuestion->id, $quizQuestion, 3600);

            return new QuizQuestionResource($quizQuestion);
        } else {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
    }

    public function questionWithAnswerList(Request $request, int $quiz_id)
    {
        if ($request->user()->can('view quizzes')) {
            $cacheKey = "questions_list_{$quiz_id}";
            $quizQuestions = Cache::remember($cacheKey, 3600, function () use ($quiz_id) {
                return QuizQuestion::with('quizAnswers') // Eager load quizAnswers
                    ->where('quiz_id', $quiz_id)
                    ->orderBy('question_number', 'asc')
                    ->get();
            });
            return new QuizQuestionCollection($quizQuestions);
        } else {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
    }

    public function questionWithAnswerDetails(Request $request, int $quiz_id, int $quiz_question_id)
    {
        if ($request->user()->can('view quizzes')) {
            $cacheKey = "question_{$quiz_question_id}";
            $quizQuestion = Cache::remember($cacheKey, 3600, function () use ($quiz_id, $quiz_question_id) {
                return QuizQuestion::with('quizAnswers')->where('quiz_id', $quiz_id)->find($quiz_question_id);
            });

            if ($quizQuestion === null) {
                return response()->json([
                    'message' => 'Quiz question not found',
                ], 404);
            }

            return new QuizQuestionResource($quizQuestion);
        } else {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
    }
    
    public function questionWithAnswerUpdate(QuizQuestionWithAnswerRequest $request, int $quiz_id, int $quiz_question_id)
    {
        $quizQuestion = QuizQuestion::where('quiz_id', $quiz_id)->find($quiz_question_id);
        
        if ($quizQuestion === null) {
            return response()->json([
                'message' => 'Quiz question not found',
            ], 404);
        }
        
        if (!$request->user()->can('edit quizzes', $quizQuestion)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $data = $request->validated();
        $oldQuestionNumber = $quizQuestion->question_number;

            if ($request->has('quiz_answers')) {
                QuizAnswer::where('quiz_question_id', $quiz_question_id)->delete();
                $quizAnswers = [];
                foreach ($data['quiz_answers'] as $answer) {
                    $answer['quiz_question_id'] = $quiz_question_id;
                    $quizAnswers[] = $answer;
                }
                QuizAnswer::insert($quizAnswers);
            }

            // Reorder questions if question_number has changed
            if (isset($data['question_number']) && $data['question_number'] != $oldQuestionNumber) {
                if ($data['question_number'] < $oldQuestionNumber) {
                    QuizQuestion::where('quiz_id', $quiz_id)
                        ->where('question_number', '>=', $data['question_number'])
                        ->where('question_number', '<', $oldQuestionNumber)
                        ->increment('question_number');
                } else {
                    QuizQuestion::where('quiz_id', $quiz_id)
                        ->where('question_number', '<=', $data['question_number'])
                        ->where('question_number', '>', $oldQuestionNumber)
                        ->decrement('question_number');
                }
            }
            $quizQuestion->update($data);

            Cache::forget("questions_list_{$quiz_id}"); // Clear cache
            Cache::forget('qqa_' . $quiz_id); // Clear cache
            Cache::forget("question_" . $quiz_question_id);

            return new QuizQuestionResource($quizQuestion);
    }


    public function questionDelete(Request $request, int $quiz_id, int $quiz_question_id)
    {
        $quizQuestion = QuizQuestion::where('quiz_id', $quiz_id)->find($quiz_question_id);
        if ($request->user()->can('delete quizzes', $quizQuestion)) {
            if ($quizQuestion === null) {
                return response()->json([
                    'message' => 'Quiz question not found',
                ], 404);
            }

            $deletedQuestionNumber = $quizQuestion->question_number;
            $quizQuestion->delete();

            // Reorder question numbers
            QuizQuestion::where('quiz_id', $quiz_id)
                ->where('question_number', '>', $deletedQuestionNumber)
                ->decrement('question_number');

            Cache::forget("questions_list_{$quiz_id}"); // Clear cache
            Cache::forget('qqa_' . $quiz_id); // Clear cache
            Cache::forget("question_" . $quiz_question_id);

            return new QuizQuestionResource($quizQuestion);
        } else {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
    }


}
