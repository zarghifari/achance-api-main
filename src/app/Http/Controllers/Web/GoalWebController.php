<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use App\Models\LearningGoal;

class GoalWebController extends Controller
{
    public function index()
    {
        $activeGoals = collect([]);
        $completedGoals = collect([]);
        
        try {
            $activeGoals = LearningGoal::where('user_id', auth()->id())
                ->latest()
                ->get();
        } catch (\Exception $e) {
            // Table might not exist
        }
        
        try {
            if (\Schema::hasColumn('learning_goals', 'status')) {
                $activeGoals = LearningGoal::where('user_id', auth()->id())
                    ->where('status', 'active')
                    ->latest()
                    ->get();
                    
                $completedGoals = LearningGoal::where('user_id', auth()->id())
                    ->where('status', 'completed')
                    ->latest()
                    ->take(5)
                    ->get();
            }
        } catch (\Exception $e) {
            // Keep empty collections
        }
        
        return view('goals.index', compact('activeGoals', 'completedGoals'));
    }

    public function create()
    {
        return view('goals.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'target_date' => 'nullable|date',
            'priority' => 'nullable|in:low,medium,high',
        ]);

        $validated['user_id'] = auth()->id();
        $validated['status'] = 'active';

        LearningGoal::create($validated);

        return redirect()->route('goals.index')->with('success', 'Goal created successfully!');
    }

    public function show($id)
    {
        $goal = LearningGoal::where('user_id', auth()->id())
            ->findOrFail($id);
        
        return view('goals.show', compact('goal'));
    }

    public function edit($id)
    {
        $goal = LearningGoal::where('user_id', auth()->id())
            ->findOrFail($id);
        
        return view('goals.edit', compact('goal'));
    }

    public function update(Request $request, $id)
    {
        $goal = LearningGoal::where('user_id', auth()->id())
            ->findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'target_date' => 'nullable|date',
            'priority' => 'nullable|in:low,medium,high',
            'status' => 'nullable|in:active,completed,abandoned',
        ]);

        $goal->update($validated);

        return redirect()->route('goals.show', $id)->with('success', 'Goal updated successfully!');
    }

    public function destroy($id)
    {
        $goal = LearningGoal::where('user_id', auth()->id())
            ->findOrFail($id);
        
        $goal->delete();

        return redirect()->route('goals.index')->with('success', 'Goal deleted successfully!');
    }
}
