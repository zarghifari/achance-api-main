<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\QuizAnswer;
use App\Services\QuizCacheService;

class QuizSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create multiple quizzes with different types and scenarios
        $this->createBasicQuiz();
        $this->createAdvancedQuiz();
        $this->createTimedQuiz();
        $this->createMixedTypeQuiz();

        // Warm up cache for all created quizzes
        $this->warmUpCaches();

        $this->command->info('Quiz seeder completed successfully!');
    }

    /**
     * Create a basic quiz for beginners
     */
    private function createBasicQuiz(): void
    {
        $quiz = Quiz::create([
            'title' => 'Basic General Knowledge Quiz',
            'slug' => 'basic-general-knowledge',
            'type' => QuizQuestion::TYPE_MULTIPLE_CHOICE,
            'summary' => 'Test your basic general knowledge with this simple quiz.',
            'content' => 'This quiz covers fundamental general knowledge topics including geography, mathematics, and science.',
            'published_at' => now()->subDays(1),
            'start_at' => now()->subDays(1),
            'ends_at' => now()->addDays(30),
        ]);

        $questions = [
            [
                'question_text' => 'What is the capital of France?',
                'question_type' => QuizQuestion::TYPE_MULTIPLE_CHOICE,
                'answers' => [
                    ['answer' => 'Paris', 'is_correct' => true],
                    ['answer' => 'London', 'is_correct' => false],
                    ['answer' => 'Berlin', 'is_correct' => false],
                    ['answer' => 'Madrid', 'is_correct' => false],
                ],
            ],
            [
                'question_text' => 'What is 2 + 2?',
                'question_type' => QuizQuestion::TYPE_MULTIPLE_CHOICE,
                'answers' => [
                    ['answer' => '3', 'is_correct' => false],
                    ['answer' => '4', 'is_correct' => true],
                    ['answer' => '5', 'is_correct' => false],
                    ['answer' => '6', 'is_correct' => false],
                ],
            ],
            [
                'question_text' => 'What is the largest planet in our solar system?',
                'question_type' => QuizQuestion::TYPE_MULTIPLE_CHOICE,
                'answers' => [
                    ['answer' => 'Earth', 'is_correct' => false],
                    ['answer' => 'Mars', 'is_correct' => false],
                    ['answer' => 'Jupiter', 'is_correct' => true],
                    ['answer' => 'Saturn', 'is_correct' => false],
                ],
            ],
            [
                'question_text' => 'The Earth is round.',
                'question_type' => QuizQuestion::TYPE_TRUE_FALSE,
                'answers' => [
                    ['answer' => 'True', 'is_correct' => true],
                    ['answer' => 'False', 'is_correct' => false],
                ],
            ],
        ];

        $this->createQuestionsForQuiz($quiz, $questions);
    }

    /**
     * Create an advanced quiz
     */
    private function createAdvancedQuiz(): void
    {
        $quiz = Quiz::create([
            'title' => 'Advanced Programming Concepts',
            'slug' => 'advanced-programming-concepts',
            'type' => QuizQuestion::TYPE_MULTIPLE_CHOICE,
            'summary' => 'Challenge yourself with advanced programming concepts and algorithms.',
            'content' => 'This quiz tests your understanding of complex programming concepts, data structures, and algorithms.',
            'published_at' => now(),
            'start_at' => now(),
            'ends_at' => now()->addDays(14),
        ]);

        $questions = [
            [
                'question_text' => 'What is the time complexity of quicksort in the average case?',
                'question_type' => QuizQuestion::TYPE_MULTIPLE_CHOICE,
                'answers' => [
                    ['answer' => 'O(n)', 'is_correct' => false],
                    ['answer' => 'O(n log n)', 'is_correct' => true],
                    ['answer' => 'O(n²)', 'is_correct' => false],
                    ['answer' => 'O(log n)', 'is_correct' => false],
                ],
            ],
            [
                'question_text' => 'Which design pattern ensures a class has only one instance?',
                'question_type' => QuizQuestion::TYPE_MULTIPLE_CHOICE,
                'answers' => [
                    ['answer' => 'Factory Pattern', 'is_correct' => false],
                    ['answer' => 'Observer Pattern', 'is_correct' => false],
                    ['answer' => 'Singleton Pattern', 'is_correct' => true],
                    ['answer' => 'Strategy Pattern', 'is_correct' => false],
                ],
            ],
            [
                'question_text' => 'In REST APIs, which HTTP method is idempotent?',
                'question_type' => QuizQuestion::TYPE_MULTIPLE_CORRECT_CHOICE,
                'answers' => [
                    ['answer' => 'GET', 'is_correct' => true],
                    ['answer' => 'POST', 'is_correct' => false],
                    ['answer' => 'PUT', 'is_correct' => true],
                    ['answer' => 'DELETE', 'is_correct' => true],
                ],
            ],
            [
                'question_text' => 'Recursion always uses more memory than iteration.',
                'question_type' => QuizQuestion::TYPE_TRUE_FALSE,
                'answers' => [
                    ['answer' => 'True', 'is_correct' => false],
                    ['answer' => 'False', 'is_correct' => true],
                ],
            ],
        ];

        $this->createQuestionsForQuiz($quiz, $questions);
    }

    /**
     * Create a timed quiz that's currently closed
     */
    private function createTimedQuiz(): void
    {
        $quiz = Quiz::create([
            'title' => 'Quick Math Challenge',
            'slug' => 'quick-math-challenge',
            'type' => QuizQuestion::TYPE_MULTIPLE_CHOICE,
            'summary' => 'A timed quiz to test your quick mathematical thinking.',
            'content' => 'Solve these mathematical problems as quickly as possible. This quiz has a limited time window.',
            'published_at' => now(),
            'start_at' => now()->addDays(2), // Starts in 2 days
            'ends_at' => now()->addDays(3),  // Ends in 3 days
        ]);

        $questions = [
            [
                'question_text' => 'What is 15 × 8?',
                'question_type' => QuizQuestion::TYPE_MULTIPLE_CHOICE,
                'answers' => [
                    ['answer' => '120', 'is_correct' => true],
                    ['answer' => '115', 'is_correct' => false],
                    ['answer' => '125', 'is_correct' => false],
                    ['answer' => '130', 'is_correct' => false],
                ],
            ],
            [
                'question_text' => 'If x + 5 = 12, what is x?',
                'question_type' => QuizQuestion::TYPE_MULTIPLE_CHOICE,
                'answers' => [
                    ['answer' => '5', 'is_correct' => false],
                    ['answer' => '6', 'is_correct' => false],
                    ['answer' => '7', 'is_correct' => true],
                    ['answer' => '8', 'is_correct' => false],
                ],
            ],
            [
                'question_text' => 'What is the square root of 144?',
                'question_type' => QuizQuestion::TYPE_MULTIPLE_CHOICE,
                'answers' => [
                    ['answer' => '11', 'is_correct' => false],
                    ['answer' => '12', 'is_correct' => true],
                    ['answer' => '13', 'is_correct' => false],
                    ['answer' => '14', 'is_correct' => false],
                ],
            ],
        ];

        $this->createQuestionsForQuiz($quiz, $questions);
    }

    /**
     * Create a quiz with mixed question types
     */
    private function createMixedTypeQuiz(): void
    {
        $quiz = Quiz::create([
            'title' => 'Mixed Knowledge Assessment',
            'slug' => 'mixed-knowledge-assessment',
            'type' => 'mixed',
            'summary' => 'A comprehensive quiz with various question types to test different skills.',
            'content' => 'This quiz includes multiple choice, true/false, and multiple correct choice questions.',
            'published_at' => now()->subHours(2),
            'start_at' => now()->subHours(2),
            'ends_at' => now()->addDays(7),
        ]);

        $questions = [
            [
                'question_text' => 'Which of the following are programming languages? (Select all that apply)',
                'question_type' => QuizQuestion::TYPE_MULTIPLE_CORRECT_CHOICE,
                'answers' => [
                    ['answer' => 'JavaScript', 'is_correct' => true],
                    ['answer' => 'HTML', 'is_correct' => false],
                    ['answer' => 'Python', 'is_correct' => true],
                    ['answer' => 'CSS', 'is_correct' => false],
                    ['answer' => 'Java', 'is_correct' => true],
                ],
            ],
            [
                'question_text' => 'Laravel is a PHP framework.',
                'question_type' => QuizQuestion::TYPE_TRUE_FALSE,
                'answers' => [
                    ['answer' => 'True', 'is_correct' => true],
                    ['answer' => 'False', 'is_correct' => false],
                ],
            ],
            [
                'question_text' => 'What does API stand for?',
                'question_type' => QuizQuestion::TYPE_MULTIPLE_CHOICE,
                'answers' => [
                    ['answer' => 'Application Programming Interface', 'is_correct' => true],
                    ['answer' => 'Advanced Programming Integration', 'is_correct' => false],
                    ['answer' => 'Automated Program Instruction', 'is_correct' => false],
                    ['answer' => 'Application Process Integration', 'is_correct' => false],
                ],
            ],
            [
                'question_text' => 'In database design, what does ACID stand for? (Select all that apply)',
                'question_type' => QuizQuestion::TYPE_MULTIPLE_CORRECT_CHOICE,
                'answers' => [
                    ['answer' => 'Atomicity', 'is_correct' => true],
                    ['answer' => 'Consistency', 'is_correct' => true],
                    ['answer' => 'Isolation', 'is_correct' => true],
                    ['answer' => 'Durability', 'is_correct' => true],
                    ['answer' => 'Accessibility', 'is_correct' => false],
                ],
            ],
        ];

        $this->createQuestionsForQuiz($quiz, $questions);
    }

    /**
     * Create questions for a given quiz
     */
    private function createQuestionsForQuiz(Quiz $quiz, array $questions): void
    {
        foreach ($questions as $index => $questionData) {
            $question = QuizQuestion::create([
                'quiz_id' => $quiz->id,
                'question_number' => $index + 1,
                'question_text' => $questionData['question_text'],
                'question_type' => $questionData['question_type'],
                'question_img' => null,
                'attachment' => null,
            ]);

            foreach ($questionData['answers'] as $answerData) {
                QuizAnswer::create([
                    'quiz_question_id' => $question->id,
                    'answer' => $answerData['answer'],
                    'is_correct' => $answerData['is_correct'],
                ]);
            }
        }

        $this->command->info("Created quiz: {$quiz->title} with " . count($questions) . " questions");
    }

    /**
     * Warm up caches for all created quizzes
     */
    private function warmUpCaches(): void
    {
        $quizzes = Quiz::all();
        
        foreach ($quizzes as $quiz) {
            QuizCacheService::warmUpQuizCache($quiz->id);
        }

        $this->command->info("Cache warmed up for " . $quizzes->count() . " quizzes");
    }
}
