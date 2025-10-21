<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class SeedQuizSystem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quiz:seed 
                            {--fresh : Drop all tables and migrate fresh before seeding}
                            {--demo : Include enhanced demo data}
                            {--basic : Only run basic quiz seeding (no attempts)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed the quiz system with test data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting quiz system seeding...');

        if ($this->option('fresh')) {
            $this->warn('This will drop all tables and recreate them!');
            if (!$this->confirm('Are you sure you want to continue?')) {
                $this->info('Seeding cancelled.');
                return 0;
            }

            $this->info('Running fresh migration...');
            Artisan::call('migrate:fresh');
            $this->info('Migration completed.');
        }

        // Run basic seeders
        $this->info('Seeding roles and permissions...');
        Artisan::call('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);

        $this->info('Seeding courses...');
        Artisan::call('db:seed', ['--class' => 'CourseSeeder']);

        $this->info('Seeding quizzes...');
        Artisan::call('db:seed', ['--class' => 'QuizSeeder']);

        $this->info('Seeding learning outcomes...');
        Artisan::call('db:seed', ['--class' => 'LearningOutcomeSeeder']);

        if (!$this->option('basic')) {
            $this->info('Seeding quiz attempts...');
            Artisan::call('db:seed', ['--class' => 'QuizAttemptSeeder']);
        }

        if ($this->option('demo')) {
            $this->info('Seeding enhanced demo data...');
            Artisan::call('db:seed', ['--class' => 'EnhancedQuizDemoSeeder']);
        }

        // Warm up caches
        $this->info('Warming up quiz caches...');
        Artisan::call('quiz:cache-warmup');

        $this->info('Quiz system seeding completed successfully!');
        
        // Display summary
        $this->displaySummary();

        return 0;
    }

    /**
     * Display a summary of what was seeded
     */
    private function displaySummary(): void
    {
        $this->info('');
        $this->info('=== SEEDING SUMMARY ===');
        
        $quizCount = \App\Models\Quiz::count();
        $questionCount = \App\Models\QuizQuestion::count();
        $answerCount = \App\Models\QuizAnswer::count();
        $attemptCount = \App\Models\AttemptQuiz::count();
        $userCount = \App\Models\User::count();
        $courseCount = \App\Models\Course::count();
        $learningOutcomeCount = \App\Models\LearningOutcome::count();

        $this->table(['Item', 'Count'], [
            ['Courses', $courseCount],
            ['Quizzes', $quizCount],
            ['Questions', $questionCount],
            ['Answers', $answerCount],
            ['Quiz Attempts', $attemptCount],
            ['Users', $userCount],
            ['Learning Outcomes', $learningOutcomeCount],
        ]);

        if ($this->option('demo')) {
            $this->info('');
            $this->info('=== DEMO CREDENTIALS ===');
            $demoUsers = \App\Models\User::where('email', 'like', 'demo.%')->get();
            foreach ($demoUsers as $user) {
                $this->line("Email: {$user->email} | Password: demo123");
            }
        }

        $this->info('');
        $this->info('You can now test the enhanced quiz system!');
        $this->info('Use: php artisan quiz:cache-warmup to warm up caches');
        $this->info('Use: php artisan queue:work to process background jobs');
    }
}
