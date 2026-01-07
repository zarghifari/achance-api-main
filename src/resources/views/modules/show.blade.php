@extends('layouts.app')

@section('title', $module->name ?? 'Module')
@section('app-title', $module->name ?? 'Module')

@section('content')
<!-- Module Header -->
<div class="card">
    <div style="margin-bottom: 8px;">
        <a href="{{ route('courses.show', $course->id) }}" style="color: var(--primary-color); font-size: 14px; text-decoration: none;">
            ← Back to {{ $course->name }}
        </a>
    </div>
    
    <h1 style="font-size: 24px; font-weight: 700; margin-bottom: 8px;">{{ $module->name }}</h1>
    
    @if($module->description)
        <p style="color: #6b7280; margin-bottom: 16px;">{{ $module->description }}</p>
    @endif

    @if(isset($module->progress))
        <div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                <span style="font-weight: 600;">Progress</span>
                <span style="color: var(--primary-color); font-weight: 600;">{{ $module->progress }}%</span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill" style="width: {{ $module->progress }}%"></div>
            </div>
        </div>
    @endif
</div>

<!-- Lessons -->
<div class="card-header">Lessons</div>
@if(isset($lessons) && count($lessons) > 0)
    @foreach($lessons as $index => $lesson)
    <a href="{{ route('lessons.show', ['course' => $course->id, 'module' => $module->id, 'lesson' => $lesson->id]) }}" class="list-item">
        <div style="width: 32px; height: 32px; border-radius: 50%; background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); color: white; display: flex; align-items: center; justify-content: center; font-weight: 600; flex-shrink: 0; margin-right: 12px;">
            {{ $index + 1 }}
        </div>
        <div class="list-item-content">
            <div class="list-item-title">{{ $lesson->title }}</div>
            @if($lesson->description)
                <div class="list-item-subtitle">{{ Str::limit($lesson->description, 60) }}</div>
            @endif
            <div style="margin-top: 8px; display: flex; gap: 8px;">
                @if(isset($lesson->content_type))
                    <span class="badge badge-primary">{{ ucfirst($lesson->content_type) }}</span>
                @endif
                @if(isset($lesson->is_completed) && $lesson->is_completed)
                    <span class="badge badge-success">✓ Completed</span>
                @elseif(isset($lesson->is_in_progress) && $lesson->is_in_progress)
                    <span class="badge badge-warning">In Progress</span>
                @endif
            </div>
        </div>
        <div class="list-item-arrow">›</div>
    </a>
    @endforeach
@else
    <div class="card text-center" style="padding: 40px 20px;">
        <div style="font-size: 48px; margin-bottom: 16px;">📖</div>
        <p style="color: #6b7280;">No lessons available yet</p>
    </div>
@endif

<!-- Tasks -->
@if(isset($tasks) && count($tasks) > 0)
    <div class="card-header mt-2">Module Tasks</div>
    @foreach($tasks as $task)
    <a href="{{ route('tasks.show', ['course' => $course->id, 'module' => $module->id, 'task' => $task->id]) }}" class="list-item">
        <div class="list-item-icon">✏️</div>
        <div class="list-item-content">
            <div class="list-item-title">{{ $task->title }}</div>
            @if($task->description)
                <div class="list-item-subtitle">{{ Str::limit($task->description, 60) }}</div>
            @endif
            @if(isset($task->is_submitted) && $task->is_submitted)
                <span class="badge badge-success mt-1">✓ Submitted</span>
            @else
                <span class="badge badge-warning mt-1">Pending</span>
            @endif
        </div>
        <div class="list-item-arrow">›</div>
    </a>
    @endforeach
@endif
@endsection
