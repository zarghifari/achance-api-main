<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\LearningProfile;
use Illuminate\Database\Seeder;

class LearningProfileSeeder extends Seeder
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

        echo "Creating learning profile for user: {$user->name}...\n";

        // Create a Visual learner profile (most common)
        LearningProfile::updateOrCreate(
            ['user_id' => $user->id],
            [
                // VARK scores - Visual dominant
                'visual_score' => 65,
                'auditory_score' => 15,
                'reading_score' => 10,
                'kinesthetic_score' => 10,
                
                // Content preferences
                'preferred_content_type' => 'video',
                'preferred_lesson_length' => 'medium',
                'learning_pace' => 'moderate',
                
                // Study habits
                'preferred_study_time' => 'evening',
                'daily_study_goal_minutes' => 60,
                
                // Engagement preferences
                'likes_gamification' => true,
                'likes_group_learning' => false,
                'likes_challenges' => true,
            ]
        );

        echo "✓ Created Visual learner profile\n";
        echo "  - Visual: 65% (Dominant)\n";
        echo "  - Auditory: 15%\n";
        echo "  - Reading: 10%\n";
        echo "  - Kinesthetic: 10%\n";
        echo "  - Prefers: Video content, Medium lessons, Evening study\n";
        echo "  - Daily goal: 60 minutes\n";
        echo "  - Likes: Gamification ✓, Challenges ✓\n";

        // Create additional sample profiles for other users if they exist
        $otherUsers = User::where('id', '!=', $user->id)->take(3)->get();
        
        if ($otherUsers->count() > 0) {
            echo "\nCreating profiles for additional users...\n";
            
            $profiles = [
                [
                    'visual_score' => 20,
                    'auditory_score' => 60,
                    'reading_score' => 10,
                    'kinesthetic_score' => 10,
                    'preferred_content_type' => 'audio',
                    'preferred_lesson_length' => 'short',
                    'learning_pace' => 'fast',
                    'preferred_study_time' => 'morning',
                    'daily_study_goal_minutes' => 45,
                    'likes_gamification' => false,
                    'likes_group_learning' => true,
                    'likes_challenges' => false,
                    'type' => 'Auditory'
                ],
                [
                    'visual_score' => 15,
                    'auditory_score' => 15,
                    'reading_score' => 60,
                    'kinesthetic_score' => 10,
                    'preferred_content_type' => 'text',
                    'preferred_lesson_length' => 'long',
                    'learning_pace' => 'slow',
                    'preferred_study_time' => 'afternoon',
                    'daily_study_goal_minutes' => 90,
                    'likes_gamification' => true,
                    'likes_group_learning' => false,
                    'likes_challenges' => true,
                    'type' => 'Reading'
                ],
                [
                    'visual_score' => 20,
                    'auditory_score' => 10,
                    'reading_score' => 10,
                    'kinesthetic_score' => 60,
                    'preferred_content_type' => 'interactive',
                    'preferred_lesson_length' => 'short',
                    'learning_pace' => 'fast',
                    'preferred_study_time' => 'night',
                    'daily_study_goal_minutes' => 30,
                    'likes_gamification' => true,
                    'likes_group_learning' => true,
                    'likes_challenges' => true,
                    'type' => 'Kinesthetic'
                ],
            ];

            foreach ($otherUsers as $index => $otherUser) {
                if (isset($profiles[$index])) {
                    $profileData = $profiles[$index];
                    $type = $profileData['type'];
                    unset($profileData['type']);
                    
                    LearningProfile::updateOrCreate(
                        ['user_id' => $otherUser->id],
                        $profileData
                    );
                    
                    echo "✓ Created {$type} learner profile for {$otherUser->name}\n";
                }
            }
        }

        echo "\n✅ Successfully seeded learning profiles!\n";
    }
}
