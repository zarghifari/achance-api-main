<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Bookmark;

class BookmarkWebController extends Controller
{
    public function index(Request $request)
    {
        $bookmarks = collect([]);
        
        try {
            $query = auth()->user()->bookmarks()->with('bookmarkable');
            
            if ($request->has('type') && $request->type) {
                $modelMap = [
                    'course' => \App\Models\Course::class,
                    'module' => \App\Models\Module::class,
                    'lesson' => \App\Models\Lesson::class,
                ];
                
                if (isset($modelMap[$request->type])) {
                    $query->where('bookmarkable_type', $modelMap[$request->type]);
                }
            }
            
            $bookmarks = $query->latest()->get()->map(function($bookmark) {
                return (object)[
                    'id' => $bookmark->id,
                    'type' => class_basename($bookmark->bookmarkable_type),
                    'title' => $bookmark->bookmarkable->name ?? $bookmark->bookmarkable->title ?? 'Unknown',
                    'note' => $bookmark->note ?? '',
                    'url' => $this->getBookmarkUrl($bookmark),
                    'created_at' => $bookmark->created_at,
                ];
            });
        } catch (\Exception $e) {
            // Bookmark table might not exist
        }
        
        return view('bookmarks.index', compact('bookmarks'));
    }

    private function getBookmarkUrl($bookmark)
    {
        $type = class_basename($bookmark->bookmarkable_type);
        $id = $bookmark->bookmarkable_id;
        
        switch($type) {
            case 'Course':
                return route('courses.show', $id);
            case 'Module':
                $module = $bookmark->bookmarkable;
                return route('modules.show', ['course' => $module->course_id, 'module' => $id]);
            case 'Lesson':
                $lesson = $bookmark->bookmarkable;
                return route('lessons.show', [
                    'course' => $lesson->module->course_id,
                    'module' => $lesson->module_id,
                    'lesson' => $id
                ]);
            default:
                return '#';
        }
    }
}
