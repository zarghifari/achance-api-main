<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\EpubController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskUserAnswerController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\QuizQuestionController;
use App\Http\Controllers\QuizAnswerController;
use App\Http\Controllers\AttemptQuizController;
use App\Http\Controllers\PerformanceDashboardController;
use App\Http\Controllers\AttemptAnswerController;
use App\Http\Controllers\UserActivityController;
use App\Http\Controllers\QuizBulkImportController;
use App\Http\Controllers\LearningGoalController;
use App\Http\Controllers\LearningProfileController;
use App\Http\Controllers\BookmarkController;

Route::post('/register', [RegisterController::class, 'register']);
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LogoutController::class, 'logout'])->middleware('auth:sanctum');


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [UserController::class, 'get']);
    Route::patch('/user', [UserController::class, 'update']);
    Route::get('/users/search', [UserController::class, 'search']);

    Route::post('/courses', [CourseController::class, 'create']);
    Route::get('/courses/all', [CourseController::class, 'getList']);
    Route::get('/courses', [CourseController::class, 'search']);
    Route::get('/courses/{course_id}', [CourseController::class, 'get'])->where('id', '[0-9]+');
    Route::get('/courses/{course_id}/with-navigation', [CourseController::class, 'getWithNavigation'])->where('id', '[0-9]+');
    Route::put('/courses/{course_id}', [CourseController::class, 'update'])->where('id', '[0-9]+');
    Route::delete('/courses/{course_id}', [CourseController::class, 'delete'])->where('id', '[0-9]+');

    Route::prefix('courses/{course_id}')->where(['course_id' => '[0-9]+'])->group(function () {
        Route::post('/modules', [ModuleController::class, 'create']);
        Route::get('/modules', [ModuleController::class, 'listByCourse']);
        Route::get('/modules/{module_id}', [ModuleController::class, 'get']);
        Route::put('/modules/{module_id}', [ModuleController::class, 'update'])->where('module_id', '[0-9]+');
        Route::delete('/modules/{module_id}', [ModuleController::class, 'delete'])->where('module_id', '[0-9]+');

        Route::prefix('modules/{module_id}')->where(['module_id' => '[0-9]+'])->group(function () {
            Route::post('/lessons', [LessonController::class, 'create']);
            Route::get('/lessons', [LessonController::class, 'listByModule']);
            Route::get('/lessons/{lesson_id}', [LessonController::class, 'get']);
            Route::get('/lessons/{lesson_id}/recent', [LessonController::class, 'getRecent']);
            Route::get('/lessons/{lesson_id}/epub-info', [LessonController::class, 'getEpubInfo']);
            Route::post('/lessons/{lesson_id}/epub-version-check', [LessonController::class, 'checkEpubVersion']);
            Route::post('/lessons/{lesson_id}/epub-reading-progress', [LessonController::class, 'trackEpubProgress'])->where('lesson_id', '[0-9]+');
            Route::put('/lessons/{lesson_id}', [LessonController::class, 'update'])->where('lesson_id', '[0-9]+');
            Route::delete('/lessons/{lesson_id}', [LessonController::class, 'delete'])->where('lesson_id', '[0-9]+');

            Route::prefix('lessons/{lesson_id}')->where(['lesson_id' => '[0-9]+'])->group(function () {
                Route::post('/epubs', [EpubController::class, 'create']);
                Route::get('/epubs', [EpubController::class, 'get']);
                Route::get('/epubs/{epub_id}/download', [EpubController::class, 'download'])->where('epub_id', '[0-9]+');
                Route::put('/epubs/{epub_id}', [EpubController::class, 'update'])->where('epub_id', '[0-9]+');
                Route::delete('/epubs/{epub_id}', [EpubController::class, 'delete'])->where('epub_id', '[0-9]+');
            });

            Route::post('/tasks', [TaskController::class, 'create']);
            Route::get('/tasks/{task_id}', [TaskController::class, 'get']);
            Route::put('/tasks/{task_id}', [TaskController::class, 'update'])->where('task_id', '[0-9]+');
            Route::delete('/tasks/{task_id}', [TaskController::class, 'delete'])->where('task_id', '[0-9]+');
            Route::get('/tasks', [TaskController::class, 'listByModule']);

            Route::prefix('tasks/{task_id}')->where(['task_id' => '[0-9]+'])->group(function () {
                Route::post('/answers', [TaskUserAnswerController::class, 'create']);
                // Route::get('/answers', [TaskUserAnswerController::class, 'list']);
                Route::get('/answers/{answer_id}', [TaskUserAnswerController::class, 'detail']);
                Route::put('/answers/{answer_id}', [TaskUserAnswerController::class, 'update']);
                Route::delete('/answers/{answer_id}', [TaskUserAnswerController::class, 'delete']);
            });
        });
    });

    Route::prefix('quizzes')->group(function () {
        Route::post('/', [QuizController::class, 'quizCreate']);
        Route::get('/', [QuizController::class, 'quizList']);
        Route::get('/{quiz_id}', [QuizController::class, 'quizDetailWithQuestionsAndAnswers']);
        Route::put('/{quiz_id}', [QuizController::class, 'quizUpdate']);
        Route::delete('/{quiz_id}', [QuizController::class, 'quizDelete']);

        // Bulk import routes
        Route::post('/bulk-import', [QuizBulkImportController::class, 'import']);
        Route::get('/bulk-import/template/json', [QuizBulkImportController::class, 'downloadJsonTemplate']);
        Route::get('/bulk-import/template/csv', [QuizBulkImportController::class, 'downloadCsvTemplate']);
        Route::get('/bulk-import/info', [QuizBulkImportController::class, 'getImportInfo']);

        Route::prefix('{quiz_id}')->group(function () {
            Route::post('/questions', [QuizQuestionController::class, 'questionCreate']);
            Route::post('/questionswithanswers', [QuizQuestionController::class, 'createQuestionWithAnswer']);
            Route::get('/questions', [QuizQuestionController::class, 'questionWithAnswerList']);
            Route::get('/questions/{question_id}', [QuizQuestionController::class, 'questionWithAnswerDetails']);
            Route::put('/questions/{question_id}', [QuizQuestionController::class, 'questionWithAnswerUpdate']);
            Route::put('/questionswithanswers/{question_id}', [QuizQuestionController::class, 'questionWithAnswerUpdate']);
            Route::delete('/questions/{question_id}', [QuizQuestionController::class, 'questionDelete']);

            Route::prefix('questions/{question_id}')->group(function () {
                Route::post('/answers', [QuizAnswerController::class, 'answerCreate']);
                Route::get('/answers', [QuizAnswerController::class, 'answerQuestionList']);
                Route::put('/answers/{answer_id}', [QuizAnswerController::class, 'answerUpdate']);
                Route::delete('/answers/{answer_id}', [QuizAnswerController::class, 'answerDelete']);
            });

            Route::post('/attempts', [AttemptQuizController::class, 'createAttemptWithAnswers'])->middleware('quiz.rate.limit');
            Route::post('/simple-attempts', [AttemptQuizController::class, 'attemptCreate'])->middleware('quiz.rate.limit');
            Route::get('/attempts', [AttemptQuizController::class, 'ownAttemptListWithoutAnswers']);
            Route::get('/attempts/{id}', [AttemptQuizController::class, 'detailAttemptWithAnswers']);
            Route::put('/attempts/{id}', [AttemptQuizController::class, 'updateAttemptWithAnswer']);
            Route::delete('/attempts/{id}', [AttemptQuizController::class, 'deleteAttemptWithAnswers']);
            
            Route::prefix('attempts/{attempt_id}')->group(function () {
                Route::post('/answers', [AttemptAnswerController::class, 'answerCreate']);
                Route::post('/complete', [AttemptQuizController::class, 'completeAttempt']);
            });
            
            // New enhanced routes
            Route::get('/leaderboard', [AttemptQuizController::class, 'getQuizLeaderboard']);
            Route::get('/statistics', [AttemptQuizController::class, 'getQuizStatistics']);
            Route::get('/access-check', [AttemptQuizController::class, 'checkQuizAccess']);
        });
    });

    Route::post('/useractivities', [UserActivityController::class, 'addUserActivity']);
    Route::get('/useractivities', [UserActivityController::class, 'getUserActivity']);
    Route::get('/useractivities/analytics', [UserActivityController::class, 'getAnalytics']);

    // Performance Dashboard Routes
    Route::prefix('performance')->group(function () {
        Route::get('/metrics', [PerformanceDashboardController::class, 'getMetrics']);
        Route::get('/api-stats', [PerformanceDashboardController::class, 'getApiStats']);
        Route::get('/health', [PerformanceDashboardController::class, 'getHealthStatus']);
        Route::post('/cache/clear', [PerformanceDashboardController::class, 'clearCaches']);
        Route::post('/cache/warmup', [PerformanceDashboardController::class, 'warmUpCaches']);
    });

    // Quiz attempts routes (direct access by attempt ID)
    Route::prefix('quiz-attempts')->group(function () {
        Route::get('/{attempt_id}', [AttemptQuizController::class, 'getAttemptById']);
        Route::put('/{attempt_id}', [AttemptQuizController::class, 'updateAttemptById']);
        Route::delete('/{attempt_id}', [AttemptQuizController::class, 'deleteAttemptById']);
        Route::post('/{attempt_id}/complete', [AttemptQuizController::class, 'completeAttemptById']);
    });

    // Bulk Import Status Route
    Route::get('/quizzes/bulk-import/status/{jobId}', [QuizBulkImportController::class, 'getImportStatus'])
        ->name('quiz.bulk-import.status');

    // Self-Directed Learning Routes
    
    // Learning Goals
    Route::prefix('learning-goals')->group(function () {
        Route::post('/', [LearningGoalController::class, 'create']);
        Route::get('/', [LearningGoalController::class, 'getMyGoals']);
        Route::get('/{id}', [LearningGoalController::class, 'getGoal']);
        Route::put('/{id}', [LearningGoalController::class, 'update']);
        Route::delete('/{id}', [LearningGoalController::class, 'delete']);
        Route::post('/{id}/progress', [LearningGoalController::class, 'trackProgress']);
    });

    // Learning Profile & Preferences
    Route::prefix('learning-profile')->group(function () {
        Route::get('/', [LearningProfileController::class, 'getProfile']);
        Route::put('/preferences', [LearningProfileController::class, 'updatePreferences']);
        Route::post('/assessment', [LearningProfileController::class, 'submitAssessment']);
    });

    // Personalized Feed
    Route::get('/personalized-feed', [LearningProfileController::class, 'getPersonalizedFeed']);

    // Bookmarks
    Route::prefix('bookmarks')->group(function () {
        Route::post('/{type}/{id}', [BookmarkController::class, 'toggleBookmark'])->where(['type' => 'lesson|course|module', 'id' => '[0-9]+']);
        Route::get('/', [BookmarkController::class, 'getMyBookmarks']);
        Route::get('/check/{type}/{id}', [BookmarkController::class, 'checkBookmark'])->where(['type' => 'lesson|course|module', 'id' => '[0-9]+']);
        Route::put('/{id}', [BookmarkController::class, 'updateBookmark']);
        Route::delete('/{id}', [BookmarkController::class, 'deleteBookmark']);
    });
});