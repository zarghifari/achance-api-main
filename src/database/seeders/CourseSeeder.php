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
     * Generate a readable title from filename
     */
    private function generateTitleFromFilename(string $filename): string
    {
        // Remove extension
        $name = pathinfo($filename, PATHINFO_FILENAME);
        
        // Replace hyphens, underscores with spaces
        $name = str_replace(['-', '_'], ' ', $name);
        
        // Convert to title case
        $name = ucwords(strtolower($name));
        
        // Handle common abbreviations
        $name = str_replace(['Ddkv', 'Sem'], ['DDKV', 'Semester'], $name);
        
        return $name;
    }

    /**
     * Validate and get EPUB file information
     */
    private function getEpubFileInfo(string $filePath): array
    {
        $fullPath = public_path($filePath);
        
        if (!file_exists($fullPath)) {
            return [
                'exists' => false,
                'size' => 0,
                'mime_type' => 'application/epub+zip',
                'is_valid' => false,
                'error' => 'File does not exist'
            ];
        }

        $fileSize = filesize($fullPath);
        $mimeType = 'application/epub+zip';
        
        // Basic EPUB validation (check if it's a ZIP file)
        $isValid = false;
        $error = null;
        
        if ($fileSize > 0) {
            $fileHandle = fopen($fullPath, 'rb');
            if ($fileHandle) {
                $header = fread($fileHandle, 4);
                fclose($fileHandle);
                
                // Check for ZIP file signature (EPUB is a ZIP file)
                if ($header === "PK\x03\x04" || $header === "PK\x05\x06" || $header === "PK\x07\x08") {
                    $isValid = true;
                } else {
                    $error = 'File is not a valid ZIP/EPUB format';
                }
            } else {
                $error = 'Cannot read file';
            }
        } else {
            $error = 'File is empty';
        }

        return [
            'exists' => true,
            'size' => $fileSize,
            'mime_type' => $mimeType,
            'is_valid' => $isValid,
            'error' => $error
        ];
    }

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

        // Dynamically load all EPUB files from uploads/epubs directory
        $epubDirectory = public_path('uploads/epubs');
        $epubFiles = [];
        
        if (is_dir($epubDirectory)) {
            $files = scandir($epubDirectory);
            foreach ($files as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) === 'epub') {
                    $epubFiles[] = $file;
                }
            }
        }

        // Create EPUBs for lessons using available files
        $lessons = [$lesson1, $lesson2, $lesson3, $lesson4];
        
        foreach ($epubFiles as $index => $epubFile) {
            if (isset($lessons[$index])) {
                $filePath = 'uploads/epubs/' . $epubFile;
                
                // Generate title from filename
                $title = $this->generateTitleFromFilename($epubFile);
                
                // Get and validate file information
                $fileInfo = $this->getEpubFileInfo($filePath);
                
                if (!$fileInfo['exists']) {
                    $this->command->warn("Skipping {$epubFile}: File not found");
                    continue;
                }

                if (!$fileInfo['is_valid']) {
                    $this->command->warn("Warning for {$epubFile}: {$fileInfo['error']}");
                }
                
                Epub::create([
                    'lesson_id' => $lessons[$index]->id,
                    'title' => $title,
                    'file_path' => $filePath,
                    'original_filename' => $epubFile,
                    'file_size' => $fileInfo['size'],
                    'mime_type' => $fileInfo['mime_type'],
                    'position' => 0,
                    'is_active' => $fileInfo['is_valid'], // Only activate if valid
                ]);
                
                $status = $fileInfo['is_valid'] ? '✓ Valid' : '⚠ Invalid';
                $this->command->info("Created EPUB: {$title} for lesson {$lessons[$index]->title} (Size: " . number_format($fileInfo['size'] / 1024, 2) . " KB) [{$status}]");
            }
        }

        // If we have more lessons than EPUB files, create additional EPUBs using existing files
        if (count($lessons) > count($epubFiles) && !empty($epubFiles)) {
            for ($i = count($epubFiles); $i < count($lessons); $i++) {
                $epubFile = $epubFiles[$i % count($epubFiles)]; // Cycle through available files
                $filePath = 'uploads/epubs/' . $epubFile;
                
                $title = $this->generateTitleFromFilename($epubFile) . ' (Copy ' . ($i - count($epubFiles) + 2) . ')';
                
                // Get and validate file information
                $fileInfo = $this->getEpubFileInfo($filePath);
                
                if (!$fileInfo['exists']) {
                    $this->command->warn("Skipping {$epubFile} (copy): File not found");
                    continue;
                }
                
                Epub::create([
                    'lesson_id' => $lessons[$i]->id,
                    'title' => $title,
                    'file_path' => $filePath,
                    'original_filename' => $epubFile,
                    'file_size' => $fileInfo['size'],
                    'mime_type' => $fileInfo['mime_type'],
                    'position' => 0,
                    'is_active' => $fileInfo['is_valid'], // Only activate if valid
                ]);
                
                $status = $fileInfo['is_valid'] ? '✓ Valid' : '⚠ Invalid';
                $this->command->info("Created EPUB: {$title} for lesson {$lessons[$i]->title} (Size: " . number_format($fileInfo['size'] / 1024, 2) . " KB) [{$status}]");
            }
        }

        // Log summary
        $totalEpubsCreated = Epub::count();
        $activeEpubsCount = Epub::where('is_active', true)->count();
        $this->command->info("📚 Seeding Summary:");
        $this->command->info("   - Total EPUBs created: {$totalEpubsCreated}");
        $this->command->info("   - Active EPUBs: {$activeEpubsCount}");
        $this->command->info("   - Files found in uploads/epubs: " . count($epubFiles));
        
        if (!empty($epubFiles)) {
            $this->command->info("   - Available EPUB files:");
            foreach ($epubFiles as $file) {
                $this->command->info("     • {$file}");
            }
        } else {
            $this->command->warn("   - No EPUB files found in public/uploads/epubs directory");
            $this->command->info("   - Add .epub files to public/uploads/epubs/ and re-run the seeder");
        }
    }
}
