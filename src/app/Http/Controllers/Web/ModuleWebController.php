<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Course;
use App\Models\Module;

class ModuleWebController extends Controller
{
    public function show($courseId, $moduleId)
    {
        $course = Course::findOrFail($courseId);
        $module = Module::where('course_id', $courseId)
            ->findOrFail($moduleId);
        
        $lessons = $module->lessons;
        $tasks = $module->tasks ?? [];
        
        return view('modules.show', compact('course', 'module', 'lessons', 'tasks'));
    }
}
