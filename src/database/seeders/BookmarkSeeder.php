<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Bookmark;
use App\Models\Lesson;
use App\Models\Course;
use App\Models\Module;
use Illuminate\Database\Seeder;

class BookmarkSeeder extends Seeder
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

        echo "Creating bookmarks for user: {$user->name}...\n";

        $bookmarksCreated = 0;

        // Bookmark some lessons
        $lessons = Lesson::take(5)->get();
        if ($lessons->count() > 0) {
            foreach ($lessons as $index => $lesson) {
                $notes = [
                    'Important lesson to review before the final exam',
                    'Great explanation of complex concepts - review this again',
                    'Key points for the practical assignment',
                    'Excellent examples here, bookmark for future reference',
                    'Need to practice these exercises more',
                ];

                Bookmark::create([
                    'user_id' => $user->id,
                    'bookmarkable_type' => Lesson::class,
                    'bookmarkable_id' => $lesson->id,
                    'note' => $notes[$index] ?? 'Important lesson',
                ]);
                $bookmarksCreated++;
            }
            echo "✓ Bookmarked {$lessons->count()} lessons\n";
        }

        // Bookmark some courses
        $courses = Course::take(3)->get();
        if ($courses->count() > 0) {
            foreach ($courses as $index => $course) {
                $notes = [
                    'Essential course for career advancement',
                    'Recommended by colleague - must complete',
                    'Prerequisite for advanced certification',
                ];

                Bookmark::create([
                    'user_id' => $user->id,
                    'bookmarkable_type' => Course::class,
                    'bookmarkable_id' => $course->id,
                    'note' => $notes[$index] ?? 'Important course',
                ]);
                $bookmarksCreated++;
            }
            echo "✓ Bookmarked {$courses->count()} courses\n";
        }

        // Bookmark some modules
        $modules = Module::take(2)->get();
        if ($modules->count() > 0) {
            foreach ($modules as $index => $module) {
                $notes = [
                    'Core concepts module - review regularly',
                    'Advanced techniques worth revisiting',
                ];

                Bookmark::create([
                    'user_id' => $user->id,
                    'bookmarkable_type' => Module::class,
                    'bookmarkable_id' => $module->id,
                    'note' => $notes[$index] ?? 'Important module',
                ]);
                $bookmarksCreated++;
            }
            echo "✓ Bookmarked {$modules->count()} modules\n";
        }

        if ($bookmarksCreated === 0) {
            echo "⚠ No content available to bookmark. Please seed courses/modules/lessons first.\n";
        } else {
            echo "\n✅ Successfully seeded {$bookmarksCreated} bookmarks!\n";
        }
    }
}
