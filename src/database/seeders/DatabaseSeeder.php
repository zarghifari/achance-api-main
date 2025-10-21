<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\Course;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        $this->call(RolesAndPermissionsSeeder::class);
        $this->call(CourseSeeder::class);
        $this->call(QuizSeeder::class);
        $this->call(LearningOutcomeSeeder::class);
        $this->call(QuizAttemptSeeder::class);
        
        // Optional: Enhanced demo data (run with --class=EnhancedQuizDemoSeeder for comprehensive demo)
        if ($this->command->option('class') === 'EnhancedQuizDemoSeeder' || 
            $this->command->confirm('Would you like to create enhanced demo data?', false)) {
            $this->call(EnhancedQuizDemoSeeder::class);
        }
    }
}
