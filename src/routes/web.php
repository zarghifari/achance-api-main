<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\CourseWebController;
use App\Http\Controllers\Web\ModuleWebController;
use App\Http\Controllers\Web\LessonWebController;
use App\Http\Controllers\Web\QuizWebController;
use App\Http\Controllers\Web\ProfileWebController;
use App\Http\Controllers\Web\GoalWebController;
use App\Http\Controllers\Web\BookmarkWebController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Guest Routes
Route::middleware('guest')->group(function () {
    Route::get('/', [AuthController::class, 'showLogin'])->name('login');
    Route::get('/login', [AuthController::class, 'showLogin']);
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.post');
});

// Authenticated Routes
Route::middleware('auth')->group(function () {
    // Home/Dashboard
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    
    // Courses
    Route::prefix('courses')->name('courses.')->group(function () {
        Route::get('/', [CourseWebController::class, 'index'])->name('index');
        Route::get('/{course}', [CourseWebController::class, 'show'])->name('show');
    });
    
    // Modules
    Route::prefix('courses/{course}/modules')->name('modules.')->group(function () {
        Route::get('/{module}', [ModuleWebController::class, 'show'])->name('show');
    });
    
    // Lessons
    Route::prefix('courses/{course}/modules/{module}/lessons')->name('lessons.')->group(function () {
        Route::get('/{lesson}', [LessonWebController::class, 'show'])->name('show');
        Route::post('/{lesson}/complete', [LessonWebController::class, 'markComplete'])->name('complete');
    });
    
    // Quizzes
    Route::prefix('quizzes')->name('quizzes.')->group(function () {
        Route::get('/', [QuizWebController::class, 'index'])->name('index');
        Route::get('/{quiz}', [QuizWebController::class, 'show'])->name('show');
    });
    
    // Quiz Attempts
    Route::prefix('quiz-attempts')->name('quiz-attempts.')->group(function () {
        Route::post('/{quiz}/start', [QuizWebController::class, 'startAttempt'])->name('start');
        Route::get('/{attempt}/question/{questionNumber?}', [QuizWebController::class, 'showQuestion'])->name('question');
        Route::post('/{attempt}/answer/{question}', [QuizWebController::class, 'submitAnswer'])->name('answer');
        Route::post('/{attempt}/save-answer', [QuizWebController::class, 'submitAnswer'])->name('save-answer');
        Route::post('/{attempt}/submit-all', [QuizWebController::class, 'submitAllAnswers'])->name('submit-all');
        Route::post('/{attempt}/submit', [QuizWebController::class, 'submitQuiz'])->name('submit');
        Route::get('/{attempt}/results', [QuizWebController::class, 'showResults'])->name('results');
        Route::get('/{attempt}/continue', [QuizWebController::class, 'continueAttempt'])->name('continue');
    });
    
    // Profile
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileWebController::class, 'index'])->name('index');
        Route::get('/edit', [ProfileWebController::class, 'edit'])->name('edit');
        Route::patch('/update', [ProfileWebController::class, 'update'])->name('update');
        Route::patch('/password', [ProfileWebController::class, 'updatePassword'])->name('password');
        Route::get('/learning', [ProfileWebController::class, 'learningProgress'])->name('learning');
        Route::get('/preferences', [ProfileWebController::class, 'preferences'])->name('preferences');
        Route::post('/preferences', [ProfileWebController::class, 'updatePreferences'])->name('preferences.update');
    });
    
    // Learning Goals
    Route::prefix('goals')->name('goals.')->group(function () {
        Route::get('/', [GoalWebController::class, 'index'])->name('index');
        Route::get('/create', [GoalWebController::class, 'create'])->name('create');
        Route::post('/', [GoalWebController::class, 'store'])->name('store');
        Route::get('/{goal}', [GoalWebController::class, 'show'])->name('show');
        Route::get('/{goal}/edit', [GoalWebController::class, 'edit'])->name('edit');
        Route::patch('/{goal}', [GoalWebController::class, 'update'])->name('update');
        Route::delete('/{goal}', [GoalWebController::class, 'destroy'])->name('destroy');
    });
    
    // Bookmarks
    Route::prefix('bookmarks')->name('bookmarks.')->group(function () {
        Route::get('/', [BookmarkWebController::class, 'index'])->name('index');
    });
});
