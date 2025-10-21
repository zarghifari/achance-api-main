<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Course;
use App\Models\Module;
use App\Models\LearningOutcome;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Services\QuizCacheService;

class LearningOutcomeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get existing courses and modules
        $course1 = Course::where('slug', 'sample-course-1')->first();
        $course2 = Course::where('slug', 'sample-course-2')->first();
        
        if (!$course1 || !$course2) {
            $this->command->warn('Some courses not found. Creating learning outcomes for available courses.');
            
            // If courses don't exist, create some basic ones for the learning outcomes
            if (!$course1) {
                $course1 = Course::create([
                    'title' => 'Programming Fundamentals',
                    'slug' => 'programming-fundamentals',
                    'summary' => 'Learn the basics of programming',
                    'content' => 'A comprehensive course on programming fundamentals',
                    'published_at' => now(),
                ]);
                $this->command->info('Created course: Programming Fundamentals');
            }
            
            if (!$course2) {
                $course2 = Course::create([
                    'title' => 'Advanced Software Engineering',
                    'slug' => 'advanced-software-engineering',
                    'summary' => 'Advanced concepts in software engineering',
                    'content' => 'Deep dive into advanced software engineering practices',
                    'published_at' => now(),
                ]);
                $this->command->info('Created course: Advanced Software Engineering');
            }
        }

        $module1 = Module::where('slug', 'sample-module-1')->first();
        $module2 = Module::where('slug', 'sample-module-2')->first();

        // Create comprehensive Learning Outcomes for Course 1 (Programming Fundamentals)
        $learningOutcomes1 = $this->createLearningOutcomesForCourse1($course1);
        
        // Create comprehensive Learning Outcomes for Course 2 (Advanced Software Engineering)
        $learningOutcomes2 = $this->createLearningOutcomesForCourse2($course2);

        // Create relationships between Learning Outcomes and Modules (if modules exist)
        $this->linkLearningOutcomesToModules($learningOutcomes1, $learningOutcomes2, $module1, $module2);

        // Create relationships with Quiz Questions
        $this->linkLearningOutcomesToQuizzes($learningOutcomes1, $learningOutcomes2);

        // Invalidate caches after creating relationships
        QuizCacheService::invalidateAllQuizCaches();

        $this->command->info('Learning Outcomes and relationships seeded successfully!');
        $this->command->info('Total Learning Outcomes created: ' . (count($learningOutcomes1) + count($learningOutcomes2)));
    }

    /**
     * Create learning outcomes for Programming Fundamentals course
     */
    private function createLearningOutcomesForCourse1($course1): array
    {
        $outcomes = [
            [
                'slug' => 'understand-programming-basics',
                'description' => 'Students will understand fundamental programming concepts including variables, data types, and control structures.',
                'cognitive_level' => 'Understand',
            ],
            [
                'slug' => 'apply-programming-concepts',
                'description' => 'Students will apply programming concepts to solve basic computational problems.',
                'cognitive_level' => 'Apply',
            ],
            [
                'slug' => 'analyze-code-structure',
                'description' => 'Students will analyze code structure and identify potential improvements.',
                'cognitive_level' => 'Analyze',
            ],
            [
                'slug' => 'implement-data-structures',
                'description' => 'Students will implement basic data structures like arrays, lists, and dictionaries.',
                'cognitive_level' => 'Apply',
            ],
            [
                'slug' => 'evaluate-algorithm-efficiency',
                'description' => 'Students will evaluate the efficiency and performance of different algorithms.',
                'cognitive_level' => 'Evaluate',
            ],
            [
                'slug' => 'create-software-solutions',
                'description' => 'Students will create comprehensive software solutions for real-world problems.',
                'cognitive_level' => 'Create',
            ],
        ];

        $createdOutcomes = [];
        foreach ($outcomes as $outcome) {
            $lo = LearningOutcome::create([
                'course_id' => $course1->id,
                'slug' => $outcome['slug'],
                'description' => $outcome['description'],
                'cognitive_level' => $outcome['cognitive_level'],
                'is_active' => true,
            ]);
            $createdOutcomes[] = $lo;
            $this->command->info("Created LO: {$outcome['slug']}");
        }

        return $createdOutcomes;
    }

    /**
     * Create learning outcomes for Advanced Software Engineering course
     */
    private function createLearningOutcomesForCourse2($course2): array
    {
        $outcomes = [
            [
                'slug' => 'understand-design-patterns',
                'description' => 'Students will understand common software design patterns and their applications.',
                'cognitive_level' => 'Understand',
            ],
            [
                'slug' => 'apply-best-practices',
                'description' => 'Students will apply industry best practices in software development including SOLID principles.',
                'cognitive_level' => 'Apply',
            ],
            [
                'slug' => 'analyze-system-architecture',
                'description' => 'Students will analyze complex system architectures and identify design trade-offs.',
                'cognitive_level' => 'Analyze',
            ],
            [
                'slug' => 'evaluate-software-quality',
                'description' => 'Students will evaluate software quality using various metrics and assessment tools.',
                'cognitive_level' => 'Evaluate',
            ],
            [
                'slug' => 'design-scalable-systems',
                'description' => 'Students will design scalable and maintainable software systems.',
                'cognitive_level' => 'Create',
            ],
            [
                'slug' => 'implement-testing-strategies',
                'description' => 'Students will implement comprehensive testing strategies including unit, integration, and end-to-end tests.',
                'cognitive_level' => 'Apply',
            ],
        ];

        $createdOutcomes = [];
        foreach ($outcomes as $outcome) {
            $lo = LearningOutcome::create([
                'course_id' => $course2->id,
                'slug' => $outcome['slug'],
                'description' => $outcome['description'],
                'cognitive_level' => $outcome['cognitive_level'],
                'is_active' => true,
            ]);
            $createdOutcomes[] = $lo;
            $this->command->info("Created LO: {$outcome['slug']}");
        }

        return $createdOutcomes;
    }

    /**
     * Link learning outcomes to modules
     */
    private function linkLearningOutcomesToModules($learningOutcomes1, $learningOutcomes2, $module1, $module2): void
    {
        if ($module1) {
            // Module 1 covers first 3 learning outcomes from course 1
            $module1LOs = array_slice($learningOutcomes1, 0, 3);
            $module1->learningOutcomes()->attach(array_map(fn($lo) => $lo->id, $module1LOs));
            $this->command->info("Linked " . count($module1LOs) . " learning outcomes to Module 1");
        }

        if ($module2) {
            // Module 2 covers last 3 learning outcomes from course 1
            $module2LOs = array_slice($learningOutcomes1, 3, 3);
            $module2->learningOutcomes()->attach(array_map(fn($lo) => $lo->id, $module2LOs));
            $this->command->info("Linked " . count($module2LOs) . " learning outcomes to Module 2");
        }
    }

    /**
     * Link learning outcomes to quiz questions
     */
    private function linkLearningOutcomesToQuizzes($learningOutcomes1, $learningOutcomes2): void
    {
        // Get existing quizzes and their questions
        $basicQuiz = Quiz::where('slug', 'basic-general-knowledge')->first();
        $advancedQuiz = Quiz::where('slug', 'advanced-programming-concepts')->first();
        $mathQuiz = Quiz::where('slug', 'quick-math-challenge')->first();
        $mixedQuiz = Quiz::where('slug', 'mixed-knowledge-assessment')->first();

        // Link basic quiz questions to fundamental learning outcomes
        if ($basicQuiz && count($learningOutcomes1) > 0) {
            $basicQuestions = $basicQuiz->quizQuestions()->get();
            foreach ($basicQuestions as $index => $question) {
                if (isset($learningOutcomes1[$index % count($learningOutcomes1)])) {
                    $learningOutcome = $learningOutcomes1[$index % count($learningOutcomes1)];
                    $learningOutcome->quizQuestions()->attach($question->id, [
                        'weight' => 1.0 + ($index * 0.5) // Increasing weight for later questions
                    ]);
                }
            }
            $this->command->info("Linked basic quiz questions to learning outcomes");
        }

        // Link advanced quiz questions to advanced learning outcomes
        if ($advancedQuiz && count($learningOutcomes2) > 0) {
            $advancedQuestions = $advancedQuiz->quizQuestions()->get();
            foreach ($advancedQuestions as $index => $question) {
                if (isset($learningOutcomes2[$index % count($learningOutcomes2)])) {
                    $learningOutcome = $learningOutcomes2[$index % count($learningOutcomes2)];
                    $learningOutcome->quizQuestions()->attach($question->id, [
                        'weight' => 2.0 + ($index * 0.5) // Higher weights for advanced concepts
                    ]);
                }
            }
            $this->command->info("Linked advanced quiz questions to learning outcomes");
        }

        // Link math quiz to analytical learning outcomes
        if ($mathQuiz && count($learningOutcomes1) >= 3) {
            $mathQuestions = $mathQuiz->quizQuestions()->get();
            $analyticalLO = $learningOutcomes1[2]; // "analyze-code-structure" - analytical thinking
            foreach ($mathQuestions as $question) {
                $analyticalLO->quizQuestions()->attach($question->id, ['weight' => 1.5]);
            }
            $this->command->info("Linked math quiz questions to analytical learning outcome");
        }

        // Link mixed quiz to multiple learning outcomes
        if ($mixedQuiz) {
            $mixedQuestions = $mixedQuiz->quizQuestions()->get();
            $allLearningOutcomes = array_merge($learningOutcomes1, $learningOutcomes2);
            
            foreach ($mixedQuestions as $index => $question) {
                // Distribute questions across different learning outcomes
                $loIndex = $index % count($allLearningOutcomes);
                $learningOutcome = $allLearningOutcomes[$loIndex];
                $learningOutcome->quizQuestions()->attach($question->id, [
                    'weight' => 1.0 + (rand(0, 10) / 10) // Random weight between 1.0 and 2.0
                ]);
            }
            $this->command->info("Linked mixed quiz questions to various learning outcomes");
        }

        // Create some additional relationships for comprehensive coverage
        $this->createAdditionalLearningOutcomeRelationships($learningOutcomes1, $learningOutcomes2);
    }

    /**
     * Create additional learning outcome relationships for better coverage
     */
    private function createAdditionalLearningOutcomeRelationships($learningOutcomes1, $learningOutcomes2): void
    {
        // Get all quiz questions
        $allQuestions = QuizQuestion::all();
        
        if ($allQuestions->count() > 0) {
            // Ensure each learning outcome has at least one question associated
            $allLearningOutcomes = array_merge($learningOutcomes1, $learningOutcomes2);
            
            foreach ($allLearningOutcomes as $index => $learningOutcome) {
                // If learning outcome has no questions, assign one
                if ($learningOutcome->quizQuestions()->count() === 0 && $allQuestions->count() > $index) {
                    $question = $allQuestions[$index % $allQuestions->count()];
                    $learningOutcome->quizQuestions()->attach($question->id, [
                        'weight' => 1.0
                    ]);
                }
            }
            
            $this->command->info("Ensured all learning outcomes have associated quiz questions");
        }
    }
}
