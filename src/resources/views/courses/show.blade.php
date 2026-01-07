@extends('layouts.app')

@section('title', $course->title ?? 'Course')
@section('app-title', $course->title ?? 'Course Details')

@section('content')
<!-- Course Header -->
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 16px;">
        <h1 style="font-size: 24px; font-weight: 700;">{{ $course->title }}</h1>
        <button onclick="toggleBookmark(@json($course->id), 'course')" class="app-bar-action" style="position: static;">
            <span id="bookmark-icon">{{ $isBookmarked ?? false ? '⭐' : '☆' }}</span>
        </button>
    </div>
    
    @if($course->description)
        <p style="color: #6b7280; margin-bottom: 16px;">{{ $course->description }}</p>
    @endif

    <!-- Course Stats -->
    <div style="display: flex; gap: 16px; flex-wrap: wrap;">
        @if(isset($course->modules_count))
            <div>
                <span style="font-weight: 600;">{{ $course->modules_count }}</span>
                <span style="color: #6b7280; font-size: 14px;"> Modules</span>
            </div>
        @endif
        @if(isset($course->lessons_count))
            <div>
                <span style="font-weight: 600;">{{ $course->lessons_count }}</span>
                <span style="color: #6b7280; font-size: 14px;"> Lessons</span>
            </div>
        @endif
        @if(isset($course->quizzes_count))
            <div>
                <span style="font-weight: 600;">{{ $course->quizzes_count }}</span>
                <span style="color: #6b7280; font-size: 14px;"> Quizzes</span>
            </div>
        @endif
    </div>

    <!-- Progress -->
    @if(isset($course->progress))
        <div style="margin-top: 16px;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                <span style="font-weight: 600;">Progress</span>
                <span style="color: var(--primary-color); font-weight: 600;">{{ $course->progress }}%</span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill" style="width: {{ $course->progress }}%"></div>
            </div>
        </div>
    @endif
</div>

<!-- Modules -->
<div class="card-header">Course Modules</div>
@if(isset($modules) && count($modules) > 0)
    @foreach($modules as $index => $module)
    <div class="card">
        <a href="{{ route('modules.show', ['course' => $course->id, 'module' => $module->id]) }}" style="text-decoration: none; color: inherit;">
            <div style="display: flex; align-items: start; gap: 12px;">
                <div style="width: 32px; height: 32px; border-radius: 50%; background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); color: white; display: flex; align-items: center; justify-content: center; font-weight: 600; flex-shrink: 0;">
                    {{ $index + 1 }}
                </div>
                <div style="flex: 1;">
                    <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 4px;">{{ $module->title }}</h3>
                    @if($module->description)
                        <p style="color: #6b7280; font-size: 14px; margin-bottom: 8px;">{{ Str::limit($module->description, 100) }}</p>
                    @endif
                    
                    <!-- Lessons List -->
                    @if($module->lessons && $module->lessons->count() > 0)
                        <div style="margin-top: 12px; padding-left: 12px; border-left: 2px solid #e5e7eb;">
                            @foreach($module->lessons as $lesson)
                            <div style="margin-bottom: 8px;">
                                <a href="{{ route('lessons.show', ['course' => $course->id, 'module' => $module->id, 'lesson' => $lesson->id]) }}" 
                                   style="color: #4f46e5; text-decoration: none; font-size: 14px; display: flex; align-items: center; gap: 8px;">
                                    <span>📄</span>
                                    <span>{{ $lesson->title }}</span>
                                </a>
                            </div>
                            @endforeach
                        </div>
                    @endif
                    
                    <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 8px;">
                        @if(isset($module->lessons_count))
                            <span class="badge badge-primary">{{ $module->lessons_count }} Lessons</span>
                        @endif
                        @if(isset($module->is_completed) && $module->is_completed)
                            <span class="badge badge-success">✓ Completed</span>
                        @endif
                    </div>

                    @if(isset($module->progress))
                        <div class="progress-bar" style="margin-top: 12px;">
                            <div class="progress-fill" style="width: {{ $module->progress }}%"></div>
                        </div>
                    @endif
                </div>
                <div style="color: #d1d5db; font-size: 20px;">›</div>
            </div>
        </a>
    </div>
    @endforeach
@else
    <div class="card text-center" style="padding: 40px 20px;">
        <div style="font-size: 48px; margin-bottom: 16px;">📦</div>
        <p style="color: #6b7280;">No modules available yet</p>
    </div>
@endif

<!-- Related Quizzes -->
@if(isset($quizzes) && count($quizzes) > 0)
    <div class="card-header mt-2">Course Quizzes</div>
    @foreach($quizzes as $quiz)
    <a href="{{ route('quizzes.show', $quiz->id) }}" class="list-item">
        <div class="list-item-icon">📝</div>
        <div class="list-item-content">
            <div class="list-item-title">{{ $quiz->title }}</div>
            <div class="list-item-subtitle">{{ $quiz->questions_count ?? 0 }} questions</div>
            @if(isset($quiz->best_score))
                <span class="badge badge-success mt-1">Best: {{ $quiz->best_score }}%</span>
            @endif
        </div>
        <div class="list-item-arrow">›</div>
    </a>
    @endforeach
@endif
@endsection

@push('scripts')
<script>
async function toggleBookmark(id, type) {
    try {
        const response = await fetch(`/api/bookmarks/${type}/${id}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + localStorage.getItem('token'),
                'X-CSRF-TOKEN': csrfToken
            }
        });
        
        const data = await response.json();
        document.getElementById('bookmark-icon').textContent = data.bookmarked ? '⭐' : '☆';
        showToast(data.message, 'success');
    } catch (error) {
        showToast('Failed to update bookmark', 'error');
    }
}
</script>
@endpush
