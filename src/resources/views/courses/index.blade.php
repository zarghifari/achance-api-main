@extends('layouts.app')

@section('title', 'Courses')
@section('app-title', 'My Courses')

@section('content')
<!-- Search Bar -->
<div class="card">
    <form action="{{ route('courses.index') }}" method="GET">
        <div class="form-group" style="margin-bottom: 0;">
            <input type="text" name="search" class="form-input" placeholder="🔍 Search courses..." value="{{ request('search') }}">
        </div>
    </form>
</div>

<!-- Courses List -->
@if(isset($courses) && count($courses) > 0)
    @foreach($courses as $course)
    <a href="{{ route('courses.show', $course->id) }}" class="list-item">
        <div class="list-item-icon">
            @if($course->is_published ?? true)
                📚
            @else
                🔒
            @endif
        </div>
        <div class="list-item-content">
            <div class="list-item-title">{{ $course->title }}</div>
            <div class="list-item-subtitle">{{ Str::limit($course->description ?? '', 60) }}</div>
            <div style="margin-top: 8px; display: flex; gap: 8px; flex-wrap: wrap;">
                @if(isset($course->modules_count))
                    <span class="badge badge-primary">{{ $course->modules_count }} Modules</span>
                @endif
                @if(isset($course->lessons_count))
                    <span class="badge badge-success">{{ $course->lessons_count }} Lessons</span>
                @endif
                @if(isset($course->progress))
                    <span class="badge badge-warning">{{ $course->progress }}% Complete</span>
                @endif
            </div>
        </div>
        <div class="list-item-arrow">›</div>
    </a>
    @endforeach

    <!-- Pagination -->
    @if(method_exists($courses, 'links'))
        <div class="mt-2">
            {{ $courses->links() }}
        </div>
    @endif
@else
    <div class="card text-center" style="padding: 60px 20px;">
        <div style="font-size: 64px; margin-bottom: 16px;">📚</div>
        <h3 style="font-size: 20px; font-weight: 600; margin-bottom: 8px;">No Courses Found</h3>
        <p style="color: #6b7280; margin-bottom: 20px;">
            @if(request('search'))
                No courses match your search. Try different keywords.
            @else
                Start your learning journey by exploring available courses.
            @endif
        </p>
    </div>
@endif
@endsection
