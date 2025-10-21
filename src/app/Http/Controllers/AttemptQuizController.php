<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\AttemptQuizRequest;
use App\Http\Requests\AttemptQuizWithAnswerRequest;
use App\Http\Resources\AttemptQuizResource;
use App\Http\Resources\AttemptQuizCollection;
use App\Models\AttemptQuiz;
use App\Models\Quiz;
use App\Services\QuizService;
use App\Services\QuizCacheService;
use App\Policies\AttemptQuizPolicy;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Gate;

class AttemptQuizController extends Controller
{
    protected QuizService $quizService;

    public function __construct(QuizService $quizService)
    {
        $this->quizService = $quizService;
    }
    /**
     * Create quiz attempt with answers
     */
    public function createAttemptWithAnswers(AttemptQuizWithAnswerRequest $request, int $quiz_id): JsonResponse
    {
        try {
            $quiz = Quiz::findOrFail($quiz_id);
            
            // Check authorization using policy
            Gate::authorize('create', [AttemptQuiz::class, $quiz]);
            
            $validatedData = $request->validated();
            
            // If no attempt_answers provided, create simple attempt
            if (empty($validatedData['attempt_answers'])) {
                $data = $validatedData;
                $data['user_id'] = $request->user()->id;
                $data['quiz_id'] = $quiz_id;
                $data['status'] = 'in_progress';
                $data['started_at'] = $data['started_at'] ?? now();
                
                $attemptQuiz = AttemptQuiz::create($data);
                
                // Invalidate cache
                QuizCacheService::invalidateUserAttemptCache($request->user()->id, $quiz_id);
                
                return response()->json([
                    'message' => 'Quiz attempt created successfully',
                    'data' => new AttemptQuizResource($attemptQuiz)
                ], 201);
            } else {
                // Create attempt with answers
                $attemptQuiz = $this->quizService->createAttemptWithAnswers(
                    $request->user(),
                    $quiz_id,
                    $validatedData
                );

                return response()->json([
                    'message' => 'Quiz attempt created successfully',
                    'data' => new AttemptQuizResource($attemptQuiz)
                ], 201);
            }

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'message' => 'Unauthorized to create quiz attempt',
                'error' => $e->getMessage()
            ], 403);
        } catch (\Exception $e) {
            Log::error('Failed to create quiz attempt', [
                'user_id' => $request->user()->id,
                'quiz_id' => $quiz_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Failed to create quiz attempt',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create simple quiz attempt
     */
    public function attemptCreate(AttemptQuizRequest $request, int $quiz_id): JsonResponse
    {
        try {
            $quiz = Quiz::findOrFail($quiz_id);
            
            // Check authorization using policy
            Gate::authorize('create', [AttemptQuiz::class, $quiz]);

            $data = $request->validated();
            $data['user_id'] = $request->user()->id;
            $data['quiz_id'] = $quiz_id;
            $data['status'] = 'in_progress';
            $data['started_at'] = $data['started_at'] ?? now();
            
            $attemptQuiz = AttemptQuiz::create($data);
            
            // Invalidate cache
            QuizCacheService::invalidateUserAttemptCache($request->user()->id, $quiz_id);

            return response()->json([
                'message' => 'Quiz attempt created successfully',
                'data' => new AttemptQuizResource($attemptQuiz)
            ], 201);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'message' => 'Unauthorized to create quiz attempt',
                'error' => $e->getMessage()
            ], 403);
        } catch (\Exception $e) {
            Log::error('Failed to create simple quiz attempt', [
                'user_id' => $request->user()->id,
                'quiz_id' => $quiz_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Failed to create quiz attempt',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's own attempts for a quiz
     */
    public function ownAttemptListWithoutAnswers(Request $request, int $quiz_id): JsonResponse
    {
        try {
            $quiz = Quiz::findOrFail($quiz_id);
            
            if ($request->user()->can('view all attempt quizzes')) {
                // Admin can see all attempts
                $attemptQuizzes = AttemptQuiz::where('quiz_id', $quiz_id)
                    ->with('user:id,name,email')
                    ->orderBy('created_at', 'desc')
                    ->get();
            } else if ($request->user()->can('doing own quizzes')) {
                // User can see only their own attempts
                $attemptQuizzes = $this->quizService->getUserQuizAttempts($request->user(), $quiz_id);
            } else {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            return response()->json([
                'message' => 'Quiz attempts retrieved successfully',
                'data' => new AttemptQuizCollection($attemptQuizzes)
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve quiz attempts', [
                'user_id' => $request->user()->id,
                'quiz_id' => $quiz_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Failed to retrieve quiz attempts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get detailed attempt with answers
     */
    public function detailAttemptWithAnswers(Request $request, int $quiz_id, int $id): JsonResponse
    {
        try {
            $attemptQuiz = $this->quizService->getAttemptWithAnswers(
                $request->user(), 
                $quiz_id, 
                $id
            );

            return response()->json([
                'message' => 'Quiz attempt details retrieved successfully',
                'data' => new AttemptQuizResource($attemptQuiz)
            ]);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'message' => 'Unauthorized to view this attempt',
                'error' => $e->getMessage()
            ], 403);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Quiz attempt not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve quiz attempt details', [
                'user_id' => $request->user()->id,
                'quiz_id' => $quiz_id,
                'attempt_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Failed to retrieve quiz attempt details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update quiz attempt with answers
     */
    public function updateAttemptWithAnswer(AttemptQuizWithAnswerRequest $request, int $quiz_id, int $id): JsonResponse
    {
        try {
            $attemptQuiz = $this->quizService->updateAttemptWithAnswers(
                $request->user(),
                $quiz_id,
                $id,
                $request->validated()
            );

            return response()->json([
                'message' => 'Quiz attempt updated successfully',
                'data' => new AttemptQuizResource($attemptQuiz)
            ]);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'message' => 'Unauthorized to update this attempt',
                'error' => $e->getMessage()
            ], 403);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Quiz attempt not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to update quiz attempt', [
                'user_id' => $request->user()->id,
                'quiz_id' => $quiz_id,
                'attempt_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Failed to update quiz attempt',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete quiz attempt with answers
     */
    public function deleteAttemptWithAnswers(Request $request, int $quiz_id, int $id): JsonResponse
    {
        try {
            $attemptQuiz = AttemptQuiz::where('quiz_id', $quiz_id)->findOrFail($id);
            
            // Check authorization using policy
            Gate::authorize('delete', $attemptQuiz);

            DB::transaction(function () use ($attemptQuiz, $request, $quiz_id) {
                // Delete related attempt answers (cascade should handle this, but let's be explicit)
                $attemptQuiz->attemptAnswers()->delete();
                
                // Delete the attempt
                $attemptQuiz->delete();
                
                // Invalidate caches
                QuizCacheService::invalidateUserAttemptCache($attemptQuiz->user_id, $quiz_id);
            });

            Log::info('Quiz attempt deleted', [
                'admin_user_id' => $request->user()->id,
                'deleted_attempt_id' => $id,
                'deleted_user_id' => $attemptQuiz->user_id,
                'quiz_id' => $quiz_id
            ]);

            return response()->json([
                'message' => 'Quiz attempt and related answers deleted successfully'
            ]);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'message' => 'Unauthorized to delete this attempt',
                'error' => $e->getMessage()
            ], 403);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Quiz attempt not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to delete quiz attempt', [
                'user_id' => $request->user()->id,
                'quiz_id' => $quiz_id,
                'attempt_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Failed to delete quiz attempt',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get quiz leaderboard
     */
    public function getQuizLeaderboard(Request $request, int $quiz_id): JsonResponse
    {
        try {
            $quiz = Quiz::findOrFail($quiz_id);
            
            // Check authorization
            Gate::authorize('viewLeaderboard', [AttemptQuiz::class, $quiz]);

            $leaderboard = $this->quizService->getQuizLeaderboard($quiz_id, 10);

            return response()->json([
                'message' => 'Quiz leaderboard retrieved successfully',
                'data' => $leaderboard->map(function ($attempt) {
                    return [
                        'id' => $attempt->id,
                        'user' => [
                            'id' => $attempt->user->id,
                            'name' => $attempt->user->name,
                            'email' => $attempt->user->email,
                        ],
                        'score' => $attempt->score,
                        'completed_at' => $attempt->completed_at,
                    ];
                })
            ]);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'message' => 'Unauthorized to view leaderboard',
                'error' => $e->getMessage()
            ], 403);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve quiz leaderboard', [
                'user_id' => $request->user()->id,
                'quiz_id' => $quiz_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Failed to retrieve quiz leaderboard',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get quiz statistics
     */
    public function getQuizStatistics(Request $request, int $quiz_id): JsonResponse
    {
        try {
            $quiz = Quiz::findOrFail($quiz_id);
            
            // Check authorization
            Gate::authorize('viewStatistics', [AttemptQuiz::class, $quiz]);

            $statistics = $this->quizService->getQuizStatistics($quiz_id);

            return response()->json([
                'message' => 'Quiz statistics retrieved successfully',
                'data' => $statistics
            ]);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'message' => 'Unauthorized to view statistics',
                'error' => $e->getMessage()
            ], 403);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve quiz statistics', [
                'user_id' => $request->user()->id,
                'quiz_id' => $quiz_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Failed to retrieve quiz statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if user can take quiz
     */
    public function checkQuizAccess(Request $request, int $quiz_id): JsonResponse
    {
        try {
            $quiz = Quiz::findOrFail($quiz_id);
            
            $quizService = new \App\Services\QuizService();
            $accessCheck = $quizService->canUserTakeQuiz($request->user(), $quiz);

            return response()->json([
                'message' => 'Quiz access check completed',
                'data' => $accessCheck
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to check quiz access', [
                'user_id' => $request->user()->id,
                'quiz_id' => $quiz_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Failed to check quiz access',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Complete quiz attempt and calculate score
     */
    public function completeAttempt(Request $request, int $quiz_id, int $attempt_id): JsonResponse
    {
        try {
            $quiz = Quiz::findOrFail($quiz_id);
            $attempt = AttemptQuiz::where('quiz_id', $quiz_id)
                ->where('id', $attempt_id)
                ->where('user_id', $request->user()->id)
                ->firstOrFail();

            // Check if attempt is already completed
            if ($attempt->status === 'completed') {
                return response()->json([
                    'message' => 'Quiz attempt already completed',
                    'data' => new AttemptQuizResource($attempt)
                ]);
            }

            // Calculate score using the quiz service
            $scoreResult = $this->quizService->calculateAttemptScore($attempt);

            // Update attempt status
            $attempt->update([
                'status' => 'completed',
                'completed_at' => now(),
                'score' => $scoreResult['score'],
                'total_questions' => $scoreResult['total_questions'],
                'correct_answers' => $scoreResult['correct_answers']
            ]);

            // Invalidate cache
            QuizCacheService::invalidateUserAttemptCache($request->user()->id, $quiz_id);

            return response()->json([
                'message' => 'Quiz attempt completed successfully',
                'data' => new AttemptQuizResource($attempt->fresh())
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Quiz attempt not found',
                'error' => $e->getMessage()
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to complete quiz attempt', [
                'user_id' => $request->user()->id,
                'quiz_id' => $quiz_id,
                'attempt_id' => $attempt_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Failed to complete quiz attempt',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get quiz attempt by ID (direct access)
     */
    public function getAttemptById(Request $request, int $attempt_id): JsonResponse
    {
        try {
            $attemptQuiz = AttemptQuiz::with(['quiz', 'user', 'attemptAnswers.quizAnswers.quizQuestions'])
                ->findOrFail($attempt_id);

            // Check authorization
            Gate::authorize('view', $attemptQuiz);

            return response()->json([
                'data' => new AttemptQuizResource($attemptQuiz)
            ]);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'error' => 'Unauthorized to view this quiz attempt'
            ], 403);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Quiz attempt not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve quiz attempt', [
                'attempt_id' => $attempt_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to retrieve quiz attempt'
            ], 500);
        }
    }

    /**
     * Update quiz attempt by ID (direct access)
     */
    public function updateAttemptById(AttemptQuizWithAnswerRequest $request, int $attempt_id): JsonResponse
    {
        try {
            $attemptQuiz = AttemptQuiz::findOrFail($attempt_id);

            // Check authorization
            Gate::authorize('update', $attemptQuiz);

            $validatedData = $request->validated();
            
            if (!empty($validatedData['attempt_answers'])) {
                $updatedAttempt = $this->quizService->updateAttemptWithAnswers(
                    $request->user(),
                    $attemptQuiz->quiz_id,
                    $attempt_id,
                    $validatedData
                );
            } else {
                // Simple update without answers
                unset($validatedData['attempt_answers']);
                $attemptQuiz->update($validatedData);
                $updatedAttempt = $attemptQuiz->fresh();
            }

            return response()->json([
                'message' => 'Quiz attempt updated successfully',
                'data' => new AttemptQuizResource($updatedAttempt)
            ]);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'error' => 'Unauthorized to update this quiz attempt'
            ], 403);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Quiz attempt not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to update quiz attempt', [
                'attempt_id' => $attempt_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to update quiz attempt'
            ], 500);
        }
    }

    /**
     * Delete quiz attempt by ID (direct access)
     */
    public function deleteAttemptById(Request $request, int $attempt_id): JsonResponse
    {
        try {
            $attemptQuiz = AttemptQuiz::findOrFail($attempt_id);

            // Check authorization
            Gate::authorize('delete', $attemptQuiz);

            $quizId = $attemptQuiz->quiz_id;
            $userId = $attemptQuiz->user_id;

            $attemptQuiz->delete();

            // Invalidate cache
            QuizCacheService::invalidateUserAttemptCache($userId, $quizId);

            return response()->json([
                'message' => 'Quiz attempt deleted successfully'
            ]);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'error' => 'Unauthorized to delete this quiz attempt'
            ], 403);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Quiz attempt not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to delete quiz attempt', [
                'attempt_id' => $attempt_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to delete quiz attempt'
            ], 500);
        }
    }

    /**
     * Complete quiz attempt by ID (direct access)
     */
    public function completeAttemptById(Request $request, int $attempt_id): JsonResponse
    {
        try {
            $attemptQuiz = AttemptQuiz::findOrFail($attempt_id);

            // Check authorization
            Gate::authorize('update', $attemptQuiz);

            // Calculate score and complete attempt
            $result = $this->quizService->calculateAttemptScore($attemptQuiz);
            
            $attemptQuiz->update([
                'status' => 'completed',
                'completed_at' => now(),
                'score' => $result['score']
            ]);

            // Invalidate cache
            QuizCacheService::invalidateUserAttemptCache($attemptQuiz->user_id, $attemptQuiz->quiz_id);

            return response()->json([
                'message' => 'Quiz attempt completed successfully',
                'data' => new AttemptQuizResource($attemptQuiz->fresh()),
                'result' => $result
            ]);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'error' => 'Unauthorized to complete this quiz attempt'
            ], 403);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Quiz attempt not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to complete quiz attempt', [
                'attempt_id' => $attempt_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to complete quiz attempt'
            ], 500);
        }
    }
}