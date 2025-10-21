<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Quiz;
use App\Models\AttemptQuiz;
use App\Models\AttemptAnswer;
use App\Models\QuizQuestion;
use App\Models\QuizAnswer;
use App\Services\QuizCacheService;
use App\Events\QuizAttemptCompleted;
use Carbon\Carbon;

class QuizAttemptSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get users (create some if they don't exist)
        $users = $this->getOrCreateUsers();
        
        // Get quizzes
        $quizzes = Quiz::all();
        
        if ($quizzes->isEmpty()) {
            $this->command->error('No quizzes found. Please run QuizSeeder first.');
            return;
        }

        $this->command->info('Creating quiz attempts for ' . $users->count() . ' users across ' . $quizzes->count() . ' quizzes...');

        foreach ($quizzes as $quiz) {
            $this->createAttemptsForQuiz($quiz, $users);
        }

        // Invalidate caches after creating attempts
        QuizCacheService::invalidateAllQuizCaches();

        $this->command->info('Quiz attempts seeded successfully!');
    }

    /**
     * Get existing users or create some test users
     */
    private function getOrCreateUsers()
    {
        $users = User::limit(5)->get();
        
        if ($users->count() < 3) {
            // Create some test users if not enough exist
            $this->command->info('Creating test users...');
            
            $testUsers = [
                [
                    'name' => 'John Doe',
                    'email' => 'john.doe@example.com',
                    'password' => bcrypt('password123'),
                    'email_verified_at' => now(),
                ],
                [
                    'name' => 'Jane Smith',
                    'email' => 'jane.smith@example.com',
                    'password' => bcrypt('password123'),
                    'email_verified_at' => now(),
                ],
                [
                    'name' => 'Bob Johnson',
                    'email' => 'bob.johnson@example.com',
                    'password' => bcrypt('password123'),
                    'email_verified_at' => now(),
                ],
                [
                    'name' => 'Alice Brown',
                    'email' => 'alice.brown@example.com',
                    'password' => bcrypt('password123'),
                    'email_verified_at' => now(),
                ],
                [
                    'name' => 'Charlie Wilson',
                    'email' => 'charlie.wilson@example.com',
                    'password' => bcrypt('password123'),
                    'email_verified_at' => now(),
                ],
            ];

            foreach ($testUsers as $userData) {
                if (!User::where('email', $userData['email'])->exists()) {
                    User::create($userData);
                }
            }

            $users = User::whereIn('email', array_column($testUsers, 'email'))->get();
        }

        return $users;
    }

    /**
     * Create attempts for a specific quiz
     */
    private function createAttemptsForQuiz(Quiz $quiz, $users): void
    {
        // Skip if quiz is not yet available
        if ($quiz->start_at && Carbon::parse($quiz->start_at)->isFuture()) {
            $this->command->info("Skipping quiz '{$quiz->title}' - not yet started");
            return;
        }

        $questions = $quiz->quizQuestions()->with('quizAnswers')->get();
        
        if ($questions->isEmpty()) {
            $this->command->warn("Skipping quiz '{$quiz->title}' - no questions found");
            return;
        }

        // Create attempts for different users with varying performance
        foreach ($users as $index => $user) {
            $this->createAttemptForUser($quiz, $user, $questions, $index);
        }

        $this->command->info("Created attempts for quiz: {$quiz->title}");
    }

    /**
     * Create an attempt for a specific user
     */
    private function createAttemptForUser(Quiz $quiz, User $user, $questions, int $userIndex): void
    {
        // Create different scenarios for different users
        $scenarios = [
            ['correctRate' => 0.9, 'status' => 'completed'],  // High performer
            ['correctRate' => 0.7, 'status' => 'completed'],  // Good performer
            ['correctRate' => 0.5, 'status' => 'completed'],  // Average performer
            ['correctRate' => 0.3, 'status' => 'failed'],     // Poor performer
            ['correctRate' => 0.6, 'status' => 'in_progress'], // Incomplete
        ];

        $scenario = $scenarios[$userIndex % count($scenarios)];
        
        // Create the attempt
        $startedAt = now()->subDays(rand(1, 7))->subHours(rand(1, 12));
        $completedAt = $scenario['status'] === 'in_progress' ? null : $startedAt->copy()->addMinutes(rand(15, 60));

        $attempt = AttemptQuiz::create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'started_at' => $startedAt,
            'completed_at' => $completedAt,
            'status' => $scenario['status'],
            'score' => 0, // Will be calculated based on answers
        ]);

        // Create answers for questions
        $correctAnswers = 0;
        $totalQuestions = $questions->count();
        $questionsToAnswer = $scenario['status'] === 'in_progress' 
            ? rand(1, max(1, $totalQuestions - 1)) 
            : $totalQuestions;

        foreach ($questions->take($questionsToAnswer) as $question) {
            $shouldBeCorrect = (rand(0, 100) / 100) < $scenario['correctRate'];
            $selectedAnswer = $this->selectAnswer($question, $shouldBeCorrect);
            
            if ($selectedAnswer) {
                AttemptAnswer::create([
                    'attempt_id' => $attempt->id,
                    'quiz_question_id' => $question->id,
                    'selected_answer_id' => $selectedAnswer->id,
                    'answer_text' => $question->question_type === 'short_answer' ? 'Sample text answer' : null,
                    'is_correct' => $selectedAnswer->is_correct,
                ]);

                if ($selectedAnswer->is_correct) {
                    $correctAnswers++;
                }
            }
        }

        // Calculate and update score
        $score = $questionsToAnswer > 0 ? round(($correctAnswers / $questionsToAnswer) * 100, 2) : 0;
        
        // Determine final status based on score and completion
        $finalStatus = $scenario['status'];
        if ($scenario['status'] !== 'in_progress') {
            $finalStatus = $score >= 50 ? 'completed' : 'failed';
        }

        $attempt->update([
            'score' => $score,
            'status' => $finalStatus,
        ]);

        // Fire completion event if completed
        if (in_array($finalStatus, ['completed', 'failed'])) {
            try {
                event(new QuizAttemptCompleted($attempt));
            } catch (\Exception $e) {
                $this->command->warn("Could not fire completion event: " . $e->getMessage());
            }
        }
    }

    /**
     * Select an answer for a question
     */
    private function selectAnswer(QuizQuestion $question, bool $shouldBeCorrect): ?QuizAnswer
    {
        $answers = $question->quizAnswers;
        
        if ($answers->isEmpty()) {
            return null;
        }

        if ($shouldBeCorrect) {
            // Try to find a correct answer
            $correctAnswers = $answers->where('is_correct', true);
            if ($correctAnswers->isNotEmpty()) {
                return $correctAnswers->random();
            }
        }
        
        // If we want incorrect answer or no correct answers exist, pick any
        $incorrectAnswers = $answers->where('is_correct', false);
        if ($incorrectAnswers->isNotEmpty()) {
            return $incorrectAnswers->random();
        }

        // Fallback to any answer
        return $answers->random();
    }
}
