<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\QuizAnswer;
use App\Models\AttemptQuiz;
use App\Models\AttemptAnswer;
use App\Services\QuizCacheService;
use App\Events\QuizAttemptCompleted;
use Carbon\Carbon;

class EnhancedQuizDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * This seeder creates comprehensive test data to showcase all enhanced quiz features
     */
    public function run(): void
    {
        $this->command->info('Creating enhanced quiz demo data...');

        // Create special demo users with specific roles
        $users = $this->createDemoUsers();

        // Create special scenario quizzes
        $quizzes = $this->createScenarioQuizzes();

        // Create realistic attempt patterns
        $this->createRealisticAttempts($users, $quizzes);

        // Warm up all caches
        $this->warmUpSystemCaches($quizzes);

        $this->command->info('Enhanced quiz demo data created successfully!');
        $this->displaySummary($users, $quizzes);
    }

    /**
     * Create demo users with different characteristics
     */
    private function createDemoUsers(): array
    {
        $demoUsers = [
            [
                'name' => 'Demo Student - High Performer',
                'email' => 'demo.student.high@example.com',
                'password' => bcrypt('demo123'),
                'email_verified_at' => now(),
                'performance_level' => 'high',
            ],
            [
                'name' => 'Demo Student - Average Performer',
                'email' => 'demo.student.avg@example.com',
                'password' => bcrypt('demo123'),
                'email_verified_at' => now(),
                'performance_level' => 'average',
            ],
            [
                'name' => 'Demo Student - Struggling',
                'email' => 'demo.student.low@example.com',
                'password' => bcrypt('demo123'),
                'email_verified_at' => now(),
                'performance_level' => 'low',
            ],
            [
                'name' => 'Demo Admin User',
                'email' => 'demo.admin@example.com',
                'password' => bcrypt('demo123'),
                'email_verified_at' => now(),
                'performance_level' => 'admin',
            ],
        ];

        $createdUsers = [];
        foreach ($demoUsers as $userData) {
            $performanceLevel = $userData['performance_level'];
            unset($userData['performance_level']);
            
            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                $userData
            );
            
            $user->performance_level = $performanceLevel;
            $createdUsers[] = $user;
        }

        $this->command->info('Created ' . count($createdUsers) . ' demo users');
        return $createdUsers;
    }

    /**
     * Create quizzes that showcase different scenarios
     */
    private function createScenarioQuizzes(): array
    {
        $scenarios = [
            [
                'title' => 'Demo: Live Quiz - Open Now',
                'slug' => 'demo-live-quiz',
                'type' => QuizQuestion::TYPE_MULTIPLE_CHOICE,
                'summary' => 'A currently active quiz that students can take right now.',
                'content' => 'This quiz demonstrates real-time quiz taking with immediate feedback.',
                'published_at' => now()->subHour(),
                'start_at' => now()->subHour(),
                'ends_at' => now()->addDays(7),
                'difficulty' => 'beginner',
            ],
            [
                'title' => 'Demo: Upcoming Quiz - Registration Open',
                'slug' => 'demo-upcoming-quiz',
                'type' => 'mixed',
                'summary' => 'A quiz that will start tomorrow. Students can see it but not take it yet.',
                'content' => 'This quiz demonstrates the upcoming quiz state and access control.',
                'published_at' => now(),
                'start_at' => now()->addDay(),
                'ends_at' => now()->addDays(8),
                'difficulty' => 'intermediate',
            ],
            [
                'title' => 'Demo: Completed Quiz with Results',
                'slug' => 'demo-completed-quiz',
                'type' => QuizQuestion::TYPE_MULTIPLE_CHOICE,
                'summary' => 'A quiz that has ended, showing historical data and statistics.',
                'content' => 'This quiz demonstrates completed quiz statistics and leaderboards.',
                'published_at' => now()->subDays(10),
                'start_at' => now()->subDays(10),
                'ends_at' => now()->subDays(3),
                'difficulty' => 'advanced',
            ],
            [
                'title' => 'Demo: Performance Analytics Quiz',
                'slug' => 'demo-analytics-quiz',
                'type' => 'mixed',
                'summary' => 'A quiz designed to showcase performance analytics and insights.',
                'content' => 'This quiz has been taken by many students to demonstrate analytics features.',
                'published_at' => now()->subDays(5),
                'start_at' => now()->subDays(5),
                'ends_at' => now()->addDays(2),
                'difficulty' => 'intermediate',
            ],
        ];

        $createdQuizzes = [];
        foreach ($scenarios as $scenario) {
            $difficulty = $scenario['difficulty'];
            unset($scenario['difficulty']);
            
            $quiz = Quiz::create($scenario);
            $quiz->difficulty = $difficulty;
            
            // Create questions based on difficulty
            $this->createQuestionsForDifficulty($quiz, $difficulty);
            
            $createdQuizzes[] = $quiz;
        }

        $this->command->info('Created ' . count($createdQuizzes) . ' scenario quizzes');
        return $createdQuizzes;
    }

    /**
     * Create questions based on difficulty level
     */
    private function createQuestionsForDifficulty(Quiz $quiz, string $difficulty): void
    {
        $questionSets = [
            'beginner' => [
                [
                    'question_text' => 'What does HTML stand for?',
                    'question_type' => QuizQuestion::TYPE_MULTIPLE_CHOICE,
                    'answers' => [
                        ['answer' => 'HyperText Markup Language', 'is_correct' => true],
                        ['answer' => 'High Tech Modern Language', 'is_correct' => false],
                        ['answer' => 'Home Tool Markup Language', 'is_correct' => false],
                        ['answer' => 'Hyperlink and Text Markup Language', 'is_correct' => false],
                    ],
                ],
                [
                    'question_text' => 'CSS is used for styling web pages.',
                    'question_type' => QuizQuestion::TYPE_TRUE_FALSE,
                    'answers' => [
                        ['answer' => 'True', 'is_correct' => true],
                        ['answer' => 'False', 'is_correct' => false],
                    ],
                ],
            ],
            'intermediate' => [
                [
                    'question_text' => 'Which of the following are JavaScript frameworks? (Select all that apply)',
                    'question_type' => QuizQuestion::TYPE_MULTIPLE_CORRECT_CHOICE,
                    'answers' => [
                        ['answer' => 'React', 'is_correct' => true],
                        ['answer' => 'Angular', 'is_correct' => true],
                        ['answer' => 'Vue.js', 'is_correct' => true],
                        ['answer' => 'Bootstrap', 'is_correct' => false],
                        ['answer' => 'jQuery', 'is_correct' => false],
                    ],
                ],
                [
                    'question_text' => 'What is the purpose of the MVC pattern?',
                    'question_type' => QuizQuestion::TYPE_MULTIPLE_CHOICE,
                    'answers' => [
                        ['answer' => 'To separate concerns in application architecture', 'is_correct' => true],
                        ['answer' => 'To improve database performance', 'is_correct' => false],
                        ['answer' => 'To enhance user interface design', 'is_correct' => false],
                        ['answer' => 'To manage server configurations', 'is_correct' => false],
                    ],
                ],
            ],
            'advanced' => [
                [
                    'question_text' => 'What is the time complexity of inserting an element in a balanced binary search tree?',
                    'question_type' => QuizQuestion::TYPE_MULTIPLE_CHOICE,
                    'answers' => [
                        ['answer' => 'O(1)', 'is_correct' => false],
                        ['answer' => 'O(log n)', 'is_correct' => true],
                        ['answer' => 'O(n)', 'is_correct' => false],
                        ['answer' => 'O(n log n)', 'is_correct' => false],
                    ],
                ],
                [
                    'question_text' => 'Which SOLID principles focus on reducing dependencies? (Select all that apply)',
                    'question_type' => QuizQuestion::TYPE_MULTIPLE_CORRECT_CHOICE,
                    'answers' => [
                        ['answer' => 'Dependency Inversion Principle', 'is_correct' => true],
                        ['answer' => 'Interface Segregation Principle', 'is_correct' => true],
                        ['answer' => 'Single Responsibility Principle', 'is_correct' => false],
                        ['answer' => 'Open/Closed Principle', 'is_correct' => false],
                        ['answer' => 'Liskov Substitution Principle', 'is_correct' => false],
                    ],
                ],
            ],
        ];

        $questions = $questionSets[$difficulty] ?? $questionSets['beginner'];
        
        foreach ($questions as $index => $questionData) {
            $question = QuizQuestion::create([
                'quiz_id' => $quiz->id,
                'question_number' => $index + 1,
                'question_text' => $questionData['question_text'],
                'question_type' => $questionData['question_type'],
            ]);

            foreach ($questionData['answers'] as $answerData) {
                QuizAnswer::create([
                    'quiz_question_id' => $question->id,
                    'answer' => $answerData['answer'],
                    'is_correct' => $answerData['is_correct'],
                ]);
            }
        }
    }

    /**
     * Create realistic attempt patterns
     */
    private function createRealisticAttempts(array $users, array $quizzes): void
    {
        foreach ($quizzes as $quiz) {
            // Skip future quizzes
            if ($quiz->start_at && Carbon::parse($quiz->start_at)->isFuture()) {
                continue;
            }

            foreach ($users as $user) {
                if ($user->performance_level === 'admin') {
                    continue; // Admins don't take quizzes
                }

                // Create attempts based on quiz timing and user performance
                $this->createAttemptForUserAndQuiz($user, $quiz);
            }
        }

        $this->command->info('Created realistic attempt patterns');
    }

    /**
     * Create an attempt for a specific user and quiz
     */
    private function createAttemptForUserAndQuiz($user, Quiz $quiz): void
    {
        // Determine if user should have attempted this quiz
        $shouldAttempt = true;
        
        // Ended quizzes - all users should have attempted
        if ($quiz->ends_at && Carbon::parse($quiz->ends_at)->isPast()) {
            $shouldAttempt = true;
        }
        // Current quizzes - some users might not have attempted yet
        elseif ($quiz->isActive()) {
            $shouldAttempt = rand(1, 100) <= 80; // 80% chance
        }

        if (!$shouldAttempt) {
            return;
        }

        $questions = $quiz->quizQuestions()->with('quizAnswers')->get();
        if ($questions->isEmpty()) {
            return;
        }

        // Determine performance based on user level and quiz difficulty
        $performance = $this->calculateExpectedPerformance($user, $quiz);
        
        // Create attempt timing
        $startedAt = $this->getRealisticStartTime($quiz);
        $completedAt = $startedAt->copy()->addMinutes(rand(10, 45));

        $attempt = AttemptQuiz::create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'started_at' => $startedAt,
            'completed_at' => $completedAt,
            'status' => 'completed',
            'score' => 0,
        ]);

        // Create answers
        $correctAnswers = 0;
        foreach ($questions as $question) {
            $shouldBeCorrect = (rand(0, 100) / 100) < $performance['accuracy'];
            $selectedAnswer = $this->selectAnswerForPerformance($question, $shouldBeCorrect);
            
            if ($selectedAnswer) {
                AttemptAnswer::create([
                    'attempt_id' => $attempt->id,
                    'quiz_question_id' => $question->id,
                    'selected_answer_id' => $selectedAnswer->id,
                    'is_correct' => $selectedAnswer->is_correct,
                ]);

                if ($selectedAnswer->is_correct) {
                    $correctAnswers++;
                }
            }
        }

        // Update final score and status
        $score = round(($correctAnswers / $questions->count()) * 100, 2);
        $status = $score >= 50 ? 'completed' : 'failed';

        $attempt->update([
            'score' => $score,
            'status' => $status,
        ]);

        // Fire completion event
        try {
            event(new QuizAttemptCompleted($attempt));
        } catch (\Exception $e) {
            // Silently handle event errors in seeding
        }
    }

    /**
     * Calculate expected performance based on user and quiz characteristics
     */
    private function calculateExpectedPerformance($user, Quiz $quiz): array
    {
        $baseAccuracy = [
            'high' => 0.85,
            'average' => 0.65,
            'low' => 0.45,
        ];

        $difficultyModifier = [
            'beginner' => 0.1,
            'intermediate' => 0.0,
            'advanced' => -0.15,
        ];

        $accuracy = $baseAccuracy[$user->performance_level] + 
                   ($difficultyModifier[$quiz->difficulty] ?? 0);
        
        // Add some randomness
        $accuracy += (rand(-10, 10) / 100);
        $accuracy = max(0.1, min(0.95, $accuracy));

        return ['accuracy' => $accuracy];
    }

    /**
     * Get realistic start time for attempts
     */
    private function getRealisticStartTime(Quiz $quiz): Carbon
    {
        $startRange = Carbon::parse($quiz->start_at);
        $endRange = $quiz->ends_at ? Carbon::parse($quiz->ends_at) : now();
        
        if ($endRange->isPast()) {
            $endRange = Carbon::parse($quiz->ends_at);
        } else {
            $endRange = now()->subHours(2); // Don't create very recent attempts
        }

        $diffInHours = $startRange->diffInHours($endRange);
        $randomHours = rand(0, max(1, $diffInHours));
        
        return $startRange->copy()->addHours($randomHours);
    }

    /**
     * Select answer based on performance expectation
     */
    private function selectAnswerForPerformance(QuizQuestion $question, bool $shouldBeCorrect): ?QuizAnswer
    {
        $answers = $question->quizAnswers;
        
        if ($shouldBeCorrect) {
            $correctAnswers = $answers->where('is_correct', true);
            if ($correctAnswers->isNotEmpty()) {
                return $correctAnswers->random();
            }
        }
        
        $incorrectAnswers = $answers->where('is_correct', false);
        return $incorrectAnswers->isNotEmpty() ? $incorrectAnswers->random() : $answers->random();
    }

    /**
     * Warm up system caches
     */
    private function warmUpSystemCaches(array $quizzes): void
    {
        foreach ($quizzes as $quiz) {
            QuizCacheService::warmUpQuizCache($quiz->id);
        }
        
        $this->command->info('System caches warmed up');
    }

    /**
     * Display summary of created data
     */
    private function displaySummary(array $users, array $quizzes): void
    {
        $this->command->info('=== DEMO DATA SUMMARY ===');
        $this->command->info('Demo Users Created: ' . count($users));
        $this->command->info('Demo Quizzes Created: ' . count($quizzes));
        
        $totalAttempts = AttemptQuiz::whereIn('quiz_id', array_map(fn($q) => $q->id, $quizzes))->count();
        $this->command->info('Quiz Attempts Created: ' . $totalAttempts);
        
        $this->command->info('');
        $this->command->info('=== DEMO LOGIN CREDENTIALS ===');
        foreach ($users as $user) {
            $this->command->info("Email: {$user->email} | Password: demo123 | Level: {$user->performance_level}");
        }
        
        $this->command->info('');
        $this->command->info('=== QUIZ SCENARIOS ===');
        foreach ($quizzes as $quiz) {
            $status = $quiz->isActive() ? 'ACTIVE' : ($quiz->isUpcoming() ? 'UPCOMING' : 'ENDED');
            $this->command->info("{$quiz->title} - Status: {$status} - Difficulty: {$quiz->difficulty}");
        }
    }
}
