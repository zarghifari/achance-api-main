@extends('layouts.app')

@section('title', 'Quizzes')
@section('app-title', 'Quizzes')

@section('content')
<!-- Search Bar -->
<div class="card">
    <form action="{{ route('quizzes.index') }}" method="GET">
        <div class="form-group" style="margin-bottom: 0;">
            <input type="text" name="search" class="form-input" placeholder="🔍 Search quizzes..." value="{{ request('search') }}">
        </div>
    </form>
</div>

<!-- Filter Tabs -->
<div class="card">
    <div style="display: flex; gap: 8px; overflow-x: auto;">
        <a href="{{ route('quizzes.index') }}" class="btn {{ !request('filter') ? 'btn-primary' : 'btn-secondary' }}" style="white-space: nowrap;">All</a>
        <a href="{{ route('quizzes.index', ['filter' => 'not-attempted']) }}" class="btn {{ request('filter') == 'not-attempted' ? 'btn-primary' : 'btn-secondary' }}" style="white-space: nowrap;">Not Attempted</a>
        <a href="{{ route('quizzes.index', ['filter' => 'in-progress']) }}" class="btn {{ request('filter') == 'in-progress' ? 'btn-primary' : 'btn-secondary' }}" style="white-space: nowrap;">In Progress</a>
        <a href="{{ route('quizzes.index', ['filter' => 'completed']) }}" class="btn {{ request('filter') == 'completed' ? 'btn-primary' : 'btn-secondary' }}" style="white-space: nowrap;">Completed</a>
    </div>
</div>

<!-- Quizzes List -->
@if(isset($quizzes) && count($quizzes) > 0)
    @foreach($quizzes as $quiz)
    <a href="{{ route('quizzes.show', $quiz->id) }}" class="list-item">
        <div class="list-item-icon">📝</div>
        <div class="list-item-content">
            <div class="list-item-title">{{ $quiz->title }}</div>
            @if($quiz->description)
                <div class="list-item-subtitle">{{ Str::limit($quiz->description, 60) }}</div>
            @endif
            <div style="margin-top: 8px; display: flex; gap: 8px; flex-wrap: wrap;">
                <span class="badge badge-primary">{{ $quiz->questions_count ?? 0 }} Questions</span>
                @if(isset($quiz->time_limit) && $quiz->time_limit)
                    <span class="badge badge-warning">⏱ {{ $quiz->time_limit }} min</span>
                @endif
                @if(isset($quiz->attempts_count))
                    <span class="badge badge-info">{{ $quiz->attempts_count }} Attempts</span>
                @endif
                @if(isset($quiz->best_score))
                    <span class="badge badge-success">Best: {{ $quiz->best_score }}%</span>
                @endif
            </div>
        </div>
        <div class="list-item-arrow">›</div>
    </a>
    @endforeach

    <!-- Pagination -->
    @if(method_exists($quizzes, 'links'))
        <div class="mt-2">
            {{ $quizzes->links() }}
        </div>
    @endif
@else
    <div class="card text-center" style="padding: 60px 20px;">
        <div style="font-size: 64px; margin-bottom: 16px;">📝</div>
        <h3 style="font-size: 20px; font-weight: 600; margin-bottom: 8px;">No Quizzes Found</h3>
        <p style="color: #6b7280;">
            @if(request('search'))
                No quizzes match your search.
            @else
                No quizzes available at the moment.
            @endif
        </p>
    </div>
@endif
@endsection
