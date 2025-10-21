<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Course;
use App\Models\Module;
use App\Models\Lesson;
use App\Models\Epub;

class CourseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a course
        $course1 = Course::create([
            'title' => 'Sample Course',
            'slug' => 'sample-course-1',
            'description' => 'This is a sample course description.',
            'cover_image' => null,
            'video_url' => 'https://www.youtube.com/watch?v=9sR6ec0ekGk',
            'isOpen' => true,
            'total_hours' => 10,
        ]);

        $course2 = Course::create([
            'title' => 'Sample Course',
            'slug' => 'sample-course-2',
            'description' => 'This is a sample course description.',
            'cover_image' => null,
            'video_url' => null,
            'isOpen' => true,
            'total_hours' => 10,
        ]);

        // Create a module
        $module1 = Module::create([
            'course_id' => $course1->id,
            'title' => 'Sample Module 1',
            'slug' => 'sample-module-1',
            'description' => 'This is a sample module 1 description.',
            'cover_image' => null,
            'video_url' => null,
            'position' => 1,
        ]);

        $module2 = Module::create([
            'course_id' => $course1->id,
            'title' => 'Sample Module 2',
            'slug' => 'sample-module-2',
            'description' => 'This is a sample module 2 description.',
            'cover_image' => null,
            'video_url' => null,
            'position' => 2,
        ]);

        // Create lessons for module 1
        $lesson1 = Lesson::create([
            'module_id' => $module1->id,
            'title' => 'Sample Lesson 1',
            'slug' => 'sample-lesson-1',
            'description' => 'This is a sample lesson 1 description.',
            'cover_image' => null,
            'video_url' => null,
            'position' => 1,
        ]);

        $lesson2 = Lesson::create([
            'module_id' => $module1->id,
            'title' => 'Sample Lesson 2',
            'slug' => 'sample-lesson-2',
            'description' => 'This is a sample lesson 2 description.',
            'cover_image' => null,
            'video_url' => null,
            'position' => 2,
        ]);

        // Create lessons for module 2
        $lesson3 = Lesson::create([
            'module_id' => $module2->id,
            'title' => 'Sample Lesson 3',
            'slug' => 'sample-lesson-3',
            'description' => 'This is a sample lesson 3 description.',
            'cover_image' => null,
            'video_url' => null,
            'position' => 1,
        ]);

        $lesson4 = Lesson::create([
            'module_id' => $module2->id,
            'title' => 'Sample Lesson 4',
            'slug' => 'sample-lesson-4',
            'description' => 'This is a sample lesson 4 description.',
            'cover_image' => null,
            'video_url' => null,
            'position' => 2,
        ]);

        // Create epub for lesson 1
        Epub::create([
            'lesson_id' => $lesson1->id,
            'title' => 'loh',
            'file_path' => 'uploads/epubs/coreldraw_ddkv_sem2.epub', // dummy epub file path
        ]);

        // Create epubs for lesson 2
        Epub::create([
            'lesson_id' => $lesson2->id,
            'title' => 'Sample Epub 1',
            'file_path' => 'uploads/epubs/coreldraw-ddkv-sem2.epub', // dummy epub file path
        ]);
    }
}
