<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use App\Models\Course;
use App\Models\Bookmark;

class CourseWebController extends Controller
{
    public function index(Request $request)
    {
        $query = Course::where('is_published', true);
        
        if ($request->has('search') && $request->search) {
            $query->where(function($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }
        
        $courses = $query->with('modules')->latest()->paginate(10);
        
        return view('courses.index', compact('courses'));
    }

    public function show($courseId)
    {
        $course = Course::with(['modules.lessons' => function($query) {
            $query->orderBy('position');
        }])
        ->withCount('modules')
        ->findOrFail($courseId);
        
        $modules = $course->modules;
        
        // Count lessons for each module
        foreach ($modules as $module) {
            $module->lessons_count = $module->lessons->count();
        }
        
        // Check if bookmarked
        $isBookmarked = false;
        if (auth()->check()) {
            $isBookmarked = Bookmark::where('user_id', auth()->id())
                ->where('bookmarkable_type', Course::class)
                ->where('bookmarkable_id', $courseId)
                ->exists();
        }
        
        return view('courses.show', compact('course', 'modules', 'isBookmarked'));
    }
}
