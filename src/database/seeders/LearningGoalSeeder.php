<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\LearningGoal;
use App\Models\GoalMilestone;
use App\Models\GoalProgressLog;
use App\Models\Course;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LearningGoalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get first user or create one
        $user = User::first();
        if (!$user) {
            $user = User::create([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => bcrypt('password123'),
            ]);
        }

        // Get some courses for related_courses
        $courses = Course::take(3)->pluck('id')->toArray();

        echo "Creating learning goals for user: {$user->name}...\n";

        // Goal 1: Skill-based goal (Active, on track)
        $goal1 = LearningGoal::create([
            'user_id' => $user->id,
            'goal_type' => 'skill',
            'title' => 'Master React Development',
            'description' => 'Become proficient in React to build modern web applications and advance my career as a frontend developer.',
            'target_date' => now()->addMonths(6)->format('Y-m-d'),
            'target_metric' => 'Complete 3 React courses and build 2 projects',
            'current_value' => 2,
            'target_value' => 5,
            'status' => 'active',
            'related_courses' => $courses,
            'related_skills' => ['React', 'JavaScript', 'Component Design', 'Hooks', 'State Management'],
        ]);

        // Add milestones for goal 1
        GoalMilestone::create([
            'learning_goal_id' => $goal1->id,
            'title' => 'Complete React Fundamentals course',
            'description' => 'Learn the basics of React including JSX, components, and props',
            'sequence_order' => 1,
            'is_achieved' => true,
            'achieved_at' => now()->subDays(20),
        ]);

        GoalMilestone::create([
            'learning_goal_id' => $goal1->id,
            'title' => 'Build first React app',
            'description' => 'Create a simple todo app using React',
            'sequence_order' => 2,
            'is_achieved' => true,
            'achieved_at' => now()->subDays(10),
        ]);

        GoalMilestone::create([
            'learning_goal_id' => $goal1->id,
            'title' => 'Learn React Hooks in depth',
            'description' => 'Master useState, useEffect, useContext, and custom hooks',
            'sequence_order' => 3,
            'is_achieved' => false,
        ]);

        GoalMilestone::create([
            'learning_goal_id' => $goal1->id,
            'title' => 'Build portfolio project',
            'description' => 'Create a full-featured e-commerce app with React',
            'sequence_order' => 4,
            'is_achieved' => false,
        ]);

        // Add progress logs
        GoalProgressLog::create([
            'learning_goal_id' => $goal1->id,
            'progress_value' => 1,
            'note' => 'Completed React Fundamentals course! Now I understand components and JSX.',
            'logged_at' => now()->subDays(20),
        ]);

        GoalProgressLog::create([
            'learning_goal_id' => $goal1->id,
            'progress_value' => 2,
            'note' => 'Built my first React todo app. It was challenging but rewarding!',
            'logged_at' => now()->subDays(10),
        ]);

        echo "✓ Created goal: {$goal1->title} (40% complete)\n";

        // Goal 2: Career goal (Active, slightly behind)
        $goal2 = LearningGoal::create([
            'user_id' => $user->id,
            'goal_type' => 'career',
            'title' => 'Become a Full-Stack Developer',
            'description' => 'Transition from frontend to full-stack by mastering backend technologies and databases.',
            'target_date' => now()->addYear()->format('Y-m-d'),
            'target_metric' => 'Complete backend courses, build 3 full-stack projects, get certified',
            'current_value' => 1,
            'target_value' => 10,
            'status' => 'active',
            'related_courses' => $courses,
            'related_skills' => ['Node.js', 'Express', 'MongoDB', 'PostgreSQL', 'REST APIs', 'Authentication'],
        ]);

        GoalMilestone::create([
            'learning_goal_id' => $goal2->id,
            'title' => 'Learn Node.js fundamentals',
            'sequence_order' => 1,
            'is_achieved' => true,
            'achieved_at' => now()->subDays(30),
        ]);

        GoalMilestone::create([
            'learning_goal_id' => $goal2->id,
            'title' => 'Master Express.js',
            'sequence_order' => 2,
            'is_achieved' => false,
        ]);

        GoalMilestone::create([
            'learning_goal_id' => $goal2->id,
            'title' => 'Database design and management',
            'sequence_order' => 3,
            'is_achieved' => false,
        ]);

        GoalProgressLog::create([
            'learning_goal_id' => $goal2->id,
            'progress_value' => 1,
            'note' => 'Finished Node.js basics. Ready to move to Express!',
            'logged_at' => now()->subDays(30),
        ]);

        echo "✓ Created goal: {$goal2->title} (10% complete)\n";

        // Goal 3: Project goal (Active, ahead of schedule)
        $goal3 = LearningGoal::create([
            'user_id' => $user->id,
            'goal_type' => 'project',
            'title' => 'Build Personal Portfolio Website',
            'description' => 'Create a stunning portfolio to showcase my projects and skills to potential employers.',
            'target_date' => now()->addMonths(2)->format('Y-m-d'),
            'target_metric' => 'Complete design, development, and deployment',
            'current_value' => 6,
            'target_value' => 8,
            'status' => 'active',
            'related_skills' => ['Web Design', 'React', 'Tailwind CSS', 'Deployment', 'SEO'],
        ]);

        GoalMilestone::create([
            'learning_goal_id' => $goal3->id,
            'title' => 'Design mockups',
            'sequence_order' => 1,
            'is_achieved' => true,
            'achieved_at' => now()->subDays(25),
        ]);

        GoalMilestone::create([
            'learning_goal_id' => $goal3->id,
            'title' => 'Develop homepage',
            'sequence_order' => 2,
            'is_achieved' => true,
            'achieved_at' => now()->subDays(15),
        ]);

        GoalMilestone::create([
            'learning_goal_id' => $goal3->id,
            'title' => 'Add projects section',
            'sequence_order' => 3,
            'is_achieved' => true,
            'achieved_at' => now()->subDays(10),
        ]);

        GoalMilestone::create([
            'learning_goal_id' => $goal3->id,
            'title' => 'Deploy to production',
            'sequence_order' => 4,
            'is_achieved' => false,
        ]);

        GoalProgressLog::create([
            'learning_goal_id' => $goal3->id,
            'progress_value' => 6,
            'note' => 'Made great progress! Portfolio is 75% complete. Just need final touches and deployment.',
            'logged_at' => now()->subDays(2),
        ]);

        echo "✓ Created goal: {$goal3->title} (75% complete)\n";

        // Goal 4: Certification goal (Paused)
        $goal4 = LearningGoal::create([
            'user_id' => $user->id,
            'goal_type' => 'certification',
            'title' => 'AWS Cloud Practitioner Certification',
            'description' => 'Get certified in AWS to expand my cloud computing knowledge.',
            'target_date' => now()->addMonths(8)->format('Y-m-d'),
            'target_metric' => 'Study materials, practice tests, and pass certification',
            'current_value' => 2,
            'target_value' => 10,
            'status' => 'paused',
            'related_skills' => ['AWS', 'Cloud Computing', 'DevOps'],
        ]);

        GoalMilestone::create([
            'learning_goal_id' => $goal4->id,
            'title' => 'Complete AWS fundamentals course',
            'sequence_order' => 1,
            'is_achieved' => true,
            'achieved_at' => now()->subMonths(2),
        ]);

        GoalMilestone::create([
            'learning_goal_id' => $goal4->id,
            'title' => 'Practice with AWS console',
            'sequence_order' => 2,
            'is_achieved' => true,
            'achieved_at' => now()->subMonths(1),
        ]);

        echo "✓ Created goal: {$goal4->title} (20% complete, paused)\n";

        // Goal 5: Time-based goal (Achieved!)
        $goal5 = LearningGoal::create([
            'user_id' => $user->id,
            'goal_type' => 'time_based',
            'title' => 'Study 30 Hours This Month',
            'description' => 'Dedicate consistent time to learning every day.',
            'target_date' => now()->subDays(5)->format('Y-m-d'),
            'target_metric' => 'Study hours',
            'current_value' => 32,
            'target_value' => 30,
            'status' => 'achieved',
            'achieved_at' => now()->subDays(5),
        ]);

        GoalMilestone::create([
            'learning_goal_id' => $goal5->id,
            'title' => 'Reach 10 hours',
            'sequence_order' => 1,
            'is_achieved' => true,
            'achieved_at' => now()->subDays(20),
        ]);

        GoalMilestone::create([
            'learning_goal_id' => $goal5->id,
            'title' => 'Reach 20 hours',
            'sequence_order' => 2,
            'is_achieved' => true,
            'achieved_at' => now()->subDays(12),
        ]);

        GoalMilestone::create([
            'learning_goal_id' => $goal5->id,
            'title' => 'Reach 30 hours',
            'sequence_order' => 3,
            'is_achieved' => true,
            'achieved_at' => now()->subDays(5),
        ]);

        echo "✓ Created goal: {$goal5->title} (ACHIEVED! 🎉)\n";

        // Goal 6: Custom goal (Just started)
        $goal6 = LearningGoal::create([
            'user_id' => $user->id,
            'goal_type' => 'custom',
            'title' => 'Learn Machine Learning Basics',
            'description' => 'Explore the fundamentals of ML and AI to stay current with technology trends.',
            'target_date' => now()->addMonths(10)->format('Y-m-d'),
            'target_metric' => 'Complete intro courses and build 1 ML project',
            'current_value' => 0,
            'target_value' => 5,
            'status' => 'active',
            'related_skills' => ['Python', 'Machine Learning', 'TensorFlow', 'Data Science'],
        ]);

        GoalMilestone::create([
            'learning_goal_id' => $goal6->id,
            'title' => 'Learn Python for ML',
            'sequence_order' => 1,
            'is_achieved' => false,
        ]);

        GoalMilestone::create([
            'learning_goal_id' => $goal6->id,
            'title' => 'Understand ML algorithms',
            'sequence_order' => 2,
            'is_achieved' => false,
        ]);

        echo "✓ Created goal: {$goal6->title} (0% complete, just started)\n";

        echo "\n✅ Successfully seeded 6 learning goals with milestones and progress logs!\n";
    }
}
