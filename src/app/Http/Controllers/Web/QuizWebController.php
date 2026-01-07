<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Quiz;
use App\Models\AttemptQuiz;
use App\Models\AttemptAnswer;

class QuizWebController extends Controller
{
    public function index(Request $request)
    {
        $query = Quiz::withCount('questions');
        
        if ($request->has('search') && $request->search) {
            $query->where(function($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }
        
        $quizzes = $query->latest()->paginate(10);
        
        return view('quizzes.index', compact('quizzes'));
    }

    public function show($quizId)
    {
        $quiz = Quiz::with('questions.answers')
            ->withCount('questions')
            ->findOrFail($quizId);
        
        // Get user's best attempt
        $bestAttempt = AttemptQuiz::where('quiz_id', $quizId)
            ->where('user_id', auth()->id())
            ->where('status', 'completed')
            ->orderBy('score', 'desc')
            ->first();
        
        // Get recent attempts
        $recentAttempts = AttemptQuiz::where('quiz_id', $quizId)
            ->where('user_id', auth()->id())
            ->latest()
            ->take(5)
            ->get();
        
        // Check if can attempt
        $attemptsCount = AttemptQuiz::where('quiz_id', $quizId)
            ->where('user_id', auth()->id())
            ->count();
        $canAttempt = !$quiz->max_attempts || $attemptsCount < $quiz->max_attempts;
        
        return view('quizzes.show', compact('quiz', 'bestAttempt', 'recentAttempts', 'canAttempt'));
    }

    public function startAttempt($quizId)
    {
        $quiz = Quiz::findOrFail($quizId);
        
        // Create new attempt
        $attempt = AttemptQuiz::create([
            'quiz_id' => $quiz->id,
            'user_id' => auth()->id(),
            'status' => 'in_progress',
            'started_at' => now(),
        ]);
        
        return redirect()->route('quiz-attempts.question', ['attempt' => $attempt->id, 'questionNumber' => 1]);
    }

    public function showQuestion($attemptId, $questionNumber = 1)
    {
        $attempt = AttemptQuiz::with('quiz.questions.answers')
            ->where('user_id', auth()->id())
            ->findOrFail($attemptId);
        
        if ($attempt->status === 'completed') {
            return redirect()->route('quiz-attempts.results', $attemptId);
        }
        
        $quiz = $attempt->quiz;
        $questions = $quiz->questions;
        $totalQuestions = $questions->count();
        
        // Get all answered questions with their selected answers
        $answeredQuestions = AttemptAnswer::where('attempt_id', $attemptId)
            ->pluck('selected_answer_id', 'quiz_question_id')
            ->toArray();
        
        // Show all questions at once (Google Forms style)
        return view('quizzes.take-all', compact(
            'quiz',
            'attempt',
            'questions',
            'totalQuestions',
            'answeredQuestions',
            'attemptId'
        ));
    }

    public function submitAnswer(Request $request, $attemptId, $questionId)
    {
        $request->validate([
            'answer_id' => 'required|exists:quiz_answers,id',
        ]);
        
        // Save or update answer
        AttemptAnswer::updateOrCreate(
            [
                'attempt_id' => $attemptId,
                'quiz_question_id' => $questionId,
            ],
            [
                'selected_answer_id' => $request->answer_id,
            ]
        );
        
        return response()->json(['success' => true]);
    }

    public function submitAllAnswers(Request $request, $attemptId)
    {
        $attempt = AttemptQuiz::with('quiz.questions.answers')
            ->where('user_id', auth()->id())
            ->findOrFail($attemptId);
        
        // Save all answers
        if ($request->has('answers')) {
            foreach ($request->answers as $questionId => $answerId) {
                if ($answerId) {
                    AttemptAnswer::updateOrCreate(
                        [
                            'attempt_id' => $attemptId,
                            'quiz_question_id' => $questionId,
                        ],
                        [
                            'selected_answer_id' => $answerId,
                        ]
                    );
                }
            }
        }
        
        // Calculate score
        $correctAnswers = 0;
        $totalQuestions = $attempt->quiz->questions->count();
        
        foreach ($attempt->quiz->questions as $question) {
            $userAnswer = AttemptAnswer::where('attempt_id', $attemptId)
                ->where('quiz_question_id', $question->id)
                ->first();
            
            if ($userAnswer) {
                $correctAnswer = $question->answers()->where('is_correct', true)->first();
                if ($correctAnswer && $userAnswer->selected_answer_id == $correctAnswer->id) {
                    $correctAnswers++;
                    // Update is_correct field
                    $userAnswer->update(['is_correct' => true]);
                }
            }
        }
        
        $score = $totalQuestions > 0 ? ($correctAnswers / $totalQuestions) * 100 : 0;
        
        // Update attempt
        $attempt->update([
            'status' => 'completed',
            'completed_at' => now(),
            'score' => round($score, 2),
        ]);
        
        return redirect()->route('quiz-attempts.results', $attemptId);
    }

    public function submitQuiz($attemptId)
    {
        $attempt = AttemptQuiz::with('quiz.questions.answers')
            ->where('user_id', auth()->id())
            ->findOrFail($attemptId);
        
        // Calculate score
        $correctAnswers = 0;
        $totalQuestions = $attempt->quiz->questions->count();
        
        foreach ($attempt->quiz->questions as $question) {
            $userAnswer = AttemptAnswer::where('attempt_id', $attemptId)
                ->where('quiz_question_id', $question->id)
                ->first();
            
            if ($userAnswer) {
                $correctAnswer = $question->answers()->where('is_correct', true)->first();
                if ($correctAnswer && $userAnswer->answer_id == $correctAnswer->id) {
                    $correctAnswers++;
                }
            }
        }
        
        $score = $totalQuestions > 0 ? ($correctAnswers / $totalQuestions) * 100 : 0;
        
        // Update attempt
        $attempt->update([
            'status' => 'completed',
            'completed_at' => now(),
            'score' => round($score, 2),
            'correct_answers' => $correctAnswers,
            'wrong_answers' => $totalQuestions - $correctAnswers,
        ]);
        
        return redirect()->route('quiz-attempts.results', $attemptId);
    }

    public function showResults($attemptId)
    {
        $attempt = AttemptQuiz::with('quiz')
            ->where('user_id', auth()->id())
            ->findOrFail($attemptId);
        
        $quiz = $attempt->quiz;
        
        // Get review questions
        $reviewQuestions = [];
        foreach ($quiz->questions as $question) {
            $userAnswer = AttemptAnswer::where('attempt_id', $attemptId)
                ->where('quiz_question_id', $question->id)
                ->with('answer')
                ->first();
            
            $correctAnswer = $question->answers()->where('is_correct', true)->first();
            
            $reviewQuestions[] = (object)[
                'question' => $question->question,
                'user_answer' => $userAnswer ? $userAnswer->answer->answer : 'Not answered',
                'correct_answer' => $correctAnswer ? $correctAnswer->answer : '',
                'is_correct' => $userAnswer && $correctAnswer && $userAnswer->answer_id == $correctAnswer->id,
                'explanation' => $question->explanation ?? null,
            ];
        }
        
        // Check if can retake
        $attemptsCount = AttemptQuiz::where('quiz_id', $quiz->id)
            ->where('user_id', auth()->id())
            ->count();
        $canRetake = !$quiz->max_attempts || $attemptsCount < $quiz->max_attempts;
        
        return view('quizzes.results', compact('attempt', 'quiz', 'reviewQuestions', 'canRetake'));
    }

    public function continueAttempt($attemptId)
    {
        $attempt = AttemptQuiz::where('user_id', auth()->id())
            ->where('status', 'in_progress')
            ->findOrFail($attemptId);
        
        // Find first unanswered question
        $answeredCount = AttemptAnswer::where('attempt_id', $attemptId)->count();
        $nextQuestion = $answeredCount + 1;
        
        return redirect()->route('quiz-attempts.question', [
            'attempt' => $attemptId,
            'questionNumber' => $nextQuestion
        ]);
    }
}
