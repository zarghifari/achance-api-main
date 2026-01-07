<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use App\Models\Course;
use App\Models\Module;
use App\Models\Lesson;

class LessonWebController extends Controller
{
    public function show($courseId, $moduleId, $lessonId)
    {
        $course = Course::findOrFail($courseId);
        $module = Module::findOrFail($moduleId);
        $lesson = Lesson::findOrFail($lessonId);
        
        // Try to get content if relationship exists
        $content = null;
        try {
            $content = $lesson->content;
            
            // Fix image paths in HTML content
            if ($content && $content->html_content) {
                $htmlContent = $content->html_content;
                
                // Replace image paths to use storage URL
                // Pattern: uploads/contents/images/... -> /storage/uploads/contents/images/...
                $htmlContent = preg_replace_callback(
                    '/<img([^>]*?)src=["\']([^"\']+)["\']([^>]*?)>/i',
                    function($matches) {
                        $fullTag = $matches[0];
                        $beforeSrc = $matches[1];
                        $src = $matches[2];
                        $afterSrc = $matches[3];
                        
                        // If path doesn't start with http or /storage, add /storage prefix
                        if (!preg_match('/^(https?:\/\/|\/storage\/)/', $src)) {
                            // Remove leading slashes if any
                            $src = ltrim($src, '/');
                            // Add /storage prefix
                            $src = '/storage/' . $src;
                        }
                        
                        return '<img' . $beforeSrc . 'src="' . $src . '"' . $afterSrc . '>';
                    },
                    $htmlContent
                );
                
                $content->html_content = $htmlContent;
            }
        } catch (\Exception $e) {
            // Content relationship might not exist
        }
        
        $currentPage = request('page', 1);
        
        // Check if bookmarked
        $isBookmarked = false;
        try {
            $isBookmarked = auth()->user()->bookmarks()
                ->where('bookmarkable_type', Lesson::class)
                ->where('bookmarkable_id', $lessonId)
                ->exists();
        } catch (\Exception $e) {
            // Bookmark might not exist
        }
        
        // Get previous and next lessons
        $previousLesson = null;
        $nextLesson = null;
        
        if (Schema::hasColumn('lessons', 'position')) {
            try {
                $previousLesson = Lesson::where('module_id', $moduleId)
                    ->where('position', '<', $lesson->position ?? 0)
                    ->orderBy('position', 'desc')
                    ->first();
                    
                $nextLesson = Lesson::where('module_id', $moduleId)
                    ->where('position', '>', $lesson->position ?? 0)
                    ->orderBy('position', 'asc')
                    ->first();
            } catch (\Exception $e) {
                // Position column might not work as expected
            }
        }
        
        return view('lessons.show', compact(
            'course', 
            'module', 
            'lesson', 
            'content', 
            'currentPage',
            'isBookmarked',
            'previousLesson',
            'nextLesson'
        ));
    }

    public function markComplete($courseId, $moduleId, $lessonId)
    {
        // TODO: Implement lesson completion tracking
        return back()->with('success', 'Lesson marked as complete!');
    }
}
