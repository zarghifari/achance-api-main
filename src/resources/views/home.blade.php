@extends('layouts.app')

@section('title', 'Home')
@section('app-title', 'Dashboard')

@section('content')
<!-- Welcome Card -->
<div class="card">
    <div class="flex justify-between items-center">
        <div>
            <h2 style="font-size: 20px; font-weight: 700; margin-bottom: 4px;">Hello, {{ auth()->user()->name }}! 👋</h2>
            <p style="color: #6b7280;">Ready to learn something new today?</p>
        </div>
    </div>
</div>

<!-- Quick Stats -->
<div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin-bottom: 16px;">
    <div class="card text-center">
        <div style="font-size: 32px; font-weight: 700; color: var(--primary-color);">{{ $stats['courses'] ?? 0 }}</div>
        <div style="color: #6b7280; font-size: 14px;">Courses</div>
    </div>
    <div class="card text-center">
        <div style="font-size: 32px; font-weight: 700; color: var(--success-color);">{{ $stats['completed'] ?? 0 }}</div>
        <div style="color: #6b7280; font-size: 14px;">Completed</div>
    </div>
    <div class="card text-center">
        <div style="font-size: 32px; font-weight: 700; color: var(--warning-color);">{{ $stats['quizzes'] ?? 0 }}</div>
        <div style="color: #6b7280; font-size: 14px;">Quizzes Taken</div>
    </div>
    <div class="card text-center">
        <div style="font-size: 32px; font-weight: 700; color: var(--secondary-color);">{{ $stats['goals'] ?? 0 }}</div>
        <div style="color: #6b7280; font-size: 14px;">Active Goals</div>
    </div>
</div>

<!-- Continue Learning -->
@if(isset($recentLessons) && count($recentLessons) > 0)
<div class="card-header">Continue Learning</div>
@foreach($recentLessons as $lesson)
<a href="{{ route('lessons.show', $lesson->id) }}" class="list-item">
    <div class="list-item-icon">📖</div>
    <div class="list-item-content">
        <div class="list-item-title">{{ $lesson->title }}</div>
        <div class="list-item-subtitle">{{ $lesson->module->name ?? 'Module' }}</div>
        @if(isset($lesson->progress))
        <div class="progress-bar">
            <div class="progress-fill" style="width: {{ $lesson->progress }}%"></div>
        </div>
        @endif
    </div>
    <div class="list-item-arrow">›</div>
</a>
@endforeach
@endif

<!-- Featured Courses -->
<div class="card-header mt-2">Featured Courses</div>
@if(isset($featuredCourses) && count($featuredCourses) > 0)
@foreach($featuredCourses as $course)
<a href="{{ route('courses.show', $course->id) }}" class="list-item">
    <div class="list-item-icon">🎯</div>
    <div class="list-item-content">
        <div class="list-item-title">{{ $course->name }}</div>
        <div class="list-item-subtitle">{{ $course->description }}</div>
        <div style="margin-top: 8px;">
            <span class="badge badge-primary">{{ $course->modules_count ?? 0 }} Modules</span>
        </div>
    </div>
    <div class="list-item-arrow">›</div>
</a>
@endforeach
@else
<div class="card text-center" style="padding: 40px 20px;">
    <div style="font-size: 48px; margin-bottom: 16px;">📚</div>
    <p style="color: #6b7280; margin-bottom: 16px;">No courses available yet</p>
    <a href="{{ route('courses.index') }}" class="btn btn-primary">Browse Courses</a>
</div>
@endif

<!-- Learning Goals -->
@if(isset($activeGoals) && count($activeGoals) > 0)
<div class="card-header mt-2">Your Goals</div>
@foreach($activeGoals as $goal)
<div class="list-item">
    <div class="list-item-icon">🎯</div>
    <div class="list-item-content">
        <div class="list-item-title">{{ $goal->title }}</div>
        <div class="list-item-subtitle">Target: {{ $goal->target_date ? date('M d, Y', strtotime($goal->target_date)) : 'No deadline' }}</div>
        @if(isset($goal->progress_percentage))
        <div class="progress-bar">
            <div class="progress-fill" style="width: {{ $goal->progress_percentage }}%"></div>
        </div>
        <div style="font-size: 12px; color: #6b7280; margin-top: 4px;">{{ $goal->progress_percentage }}% Complete</div>
        @endif
    </div>
</div>
@endforeach
@endif

<!-- Quick Actions -->
<div class="card">
    <div class="card-header">Quick Actions</div>
    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px;">
        <a href="{{ route('bookmarks.index') }}" class="btn btn-secondary" style="padding: 20px; display: flex; flex-direction: column; align-items: center;">
            <div style="font-size: 32px; margin-bottom: 8px;">🔖</div>
            <div>Bookmarks</div>
        </a>
        <a href="{{ route('profile.learning') }}" class="btn btn-secondary" style="padding: 20px; display: flex; flex-direction: column; align-items: center;">
            <div style="font-size: 32px; margin-bottom: 8px;">📊</div>
            <div>Progress</div>
        </a>
    </div>
</div>
@endsection
