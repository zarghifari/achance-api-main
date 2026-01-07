<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "═══════════════════════════════════════════════════════════════\n";
echo "🔍 Testing HTML Content Integration & Relationships\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Test 1: Lesson → Content relationship
echo "1️⃣  Testing Lesson → Content (hasOne)...\n";
echo "───────────────────────────────────────────────────────────────\n";
try {
    $lesson = App\Models\Lesson::with('content')->first();
    if ($lesson) {
        echo "✅ Lesson: {$lesson->title}\n";
        echo "   ID: {$lesson->id}\n";
        
        if ($lesson->content) {
            echo "   ✅ Has Content: YES\n";
            echo "      • Content Title: {$lesson->content->title}\n";
            echo "      • Type: {$lesson->content->type}\n";
            echo "      • Total Pages: {$lesson->content->total_pages}\n";
            echo "      • Is Processed: " . ($lesson->content->is_processed ? 'YES' : 'NO') . "\n";
            if ($lesson->content->zip_file_path) {
                echo "      • ZIP File: {$lesson->content->zip_file_path}\n";
                echo "      • ZIP Size: " . number_format($lesson->content->zip_file_size / 1024, 2) . " KB\n";
            }
        } else {
            echo "   ⚠️  Has Content: NO\n";
        }
    } else {
        echo "❌ No lessons found\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 2: Content → Lesson relationship
echo "2️⃣  Testing Content → Lesson (belongsTo)...\n";
echo "───────────────────────────────────────────────────────────────\n";
try {
    $content = App\Models\Content::with('lesson')->first();
    if ($content) {
        echo "✅ Content: {$content->title}\n";
        echo "   ID: {$content->id}\n";
        echo "   Lesson ID: {$content->lesson_id}\n";
        
        if ($content->lesson) {
            echo "   ✅ Belongs to Lesson: {$content->lesson->title}\n";
            echo "      • Lesson ID: {$content->lesson->id}\n";
        } else {
            echo "   ❌ No lesson found\n";
        }
    } else {
        echo "❌ No content found\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 3: Course with nested Content
echo "3️⃣  Testing Course → Modules → Lessons → Content (nested eager loading)...\n";
echo "───────────────────────────────────────────────────────────────\n";
try {
    $course = App\Models\Course::with('modules.lessons.content')->first();
    if ($course) {
        echo "✅ Course: {$course->title}\n";
        echo "   Total Modules: {$course->modules->count()}\n";
        
        $totalLessons = 0;
        $lessonsWithContent = 0;
        
        foreach ($course->modules as $module) {
            $totalLessons += $module->lessons->count();
            foreach ($module->lessons as $lesson) {
                if ($lesson->content) {
                    $lessonsWithContent++;
                }
            }
        }
        
        echo "   Total Lessons: {$totalLessons}\n";
        echo "   Lessons with Content: {$lessonsWithContent}\n";
        echo "   Content Coverage: " . ($totalLessons > 0 ? round(($lessonsWithContent / $totalLessons) * 100, 2) : 0) . "%\n";
    } else {
        echo "❌ No courses found\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 4: Foreign Key Constraint
echo "4️⃣  Testing Foreign Key Integrity...\n";
echo "───────────────────────────────────────────────────────────────\n";
try {
    $allContents = App\Models\Content::all();
    $validForeignKeys = 0;
    $invalidForeignKeys = 0;
    
    foreach ($allContents as $content) {
        $lesson = App\Models\Lesson::find($content->lesson_id);
        if ($lesson) {
            $validForeignKeys++;
        } else {
            $invalidForeignKeys++;
        }
    }
    
    echo "✅ Total Contents: {$allContents->count()}\n";
    echo "   Valid Foreign Keys: {$validForeignKeys} ✅\n";
    echo "   Invalid Foreign Keys: {$invalidForeignKeys} " . ($invalidForeignKeys > 0 ? '❌' : '✅') . "\n";
    
    if ($invalidForeignKeys === 0 && $allContents->count() > 0) {
        echo "   ✅ All foreign keys are valid!\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 5: Database Statistics
echo "5️⃣  Database Statistics...\n";
echo "───────────────────────────────────────────────────────────────\n";
try {
    $coursesCount = App\Models\Course::count();
    $modulesCount = App\Models\Module::count();
    $lessonsCount = App\Models\Lesson::count();
    $contentsCount = App\Models\Content::count();
    
    echo "✅ Courses: {$coursesCount}\n";
    echo "   Modules: {$modulesCount}\n";
    echo "   Lessons: {$lessonsCount}\n";
    echo "   Contents: {$contentsCount}\n";
    
    if ($lessonsCount > 0) {
        $contentPercentage = round(($contentsCount / $lessonsCount) * 100, 2);
        echo "   Content Coverage: {$contentPercentage}%\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 6: Sample Content Details
echo "6️⃣  Sample Content Details...\n";
echo "───────────────────────────────────────────────────────────────\n";
try {
    $sampleContent = App\Models\Content::with('lesson')->first();
    if ($sampleContent) {
        echo "✅ Sample Content:\n";
        echo "   • ID: {$sampleContent->id}\n";
        echo "   • Title: {$sampleContent->title}\n";
        echo "   • Type: {$sampleContent->type}\n";
        echo "   • Total Pages: {$sampleContent->total_pages}\n";
        echo "   • Is Processed: " . ($sampleContent->is_processed ? 'YES' : 'NO') . "\n";
        echo "   • Lesson: " . ($sampleContent->lesson ? $sampleContent->lesson->title : 'N/A') . "\n";
        
        if ($sampleContent->metadata) {
            $metadata = is_string($sampleContent->metadata) 
                ? json_decode($sampleContent->metadata, true) 
                : $sampleContent->metadata;
            echo "   • Metadata Keys: " . implode(', ', array_keys($metadata)) . "\n";
        }
        
        if ($sampleContent->html_content) {
            $htmlLength = strlen($sampleContent->html_content);
            echo "   • HTML Content Length: " . number_format($htmlLength) . " bytes\n";
        }
        
        if ($sampleContent->paginated_content) {
            $pages = is_string($sampleContent->paginated_content)
                ? json_decode($sampleContent->paginated_content, true)
                : $sampleContent->paginated_content;
            echo "   • Paginated Pages: " . count($pages) . "\n";
        }
    } else {
        echo "⚠️  No content found to display\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "✅ Relationship Test Complete!\n";
echo "═══════════════════════════════════════════════════════════════\n";
