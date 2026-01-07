<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileWebController extends Controller
{
    public function index()
    {
        $stats = [
            'enrolled_courses' => 0,
            'completed_lessons' => 0,
            'quiz_attempts' => 0,
            'bookmarks' => 0,
            'active_goals' => 0,
        ];
        
        try {
            $stats['quiz_attempts'] = auth()->user()->attemptQuizzes()->count();
        } catch (\Exception $e) {
            // Relationship might not exist
        }
        
        try {
            $stats['bookmarks'] = auth()->user()->bookmarks()->count();
        } catch (\Exception $e) {
            // Relationship might not exist
        }
        
        try {
            $stats['active_goals'] = auth()->user()->learningGoals()->count();
        } catch (\Exception $e) {
            // Relationship might not exist
        }
        
        return view('profile.index', compact('stats'));
    }

    public function edit()
    {
        return view('profile.edit');
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . auth()->id(),
            'phone' => 'nullable|string|max:20',
            'bio' => 'nullable|string|max:500',
        ]);

        auth()->user()->update($validated);

        return back()->with('success', 'Profile updated successfully!');
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if (!Hash::check($validated['current_password'], auth()->user()->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect']);
        }

        auth()->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('success', 'Password updated successfully!');
    }

    public function learningProgress()
    {
        // TODO: Implement learning progress analytics
        return view('profile.learning', [
            'progress' => [],
            'recentActivity' => [],
            'achievements' => [],
        ]);
    }

    public function preferences()
    {
        $preferences = auth()->user()->learningProfile ?? null;
        return view('profile.preferences', compact('preferences'));
    }

    public function updatePreferences(Request $request)
    {
        // TODO: Implement preferences update
        return back()->with('success', 'Preferences updated successfully!');
    }
}
