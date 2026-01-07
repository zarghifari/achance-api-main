@extends('layouts.app')

@section('title', $quiz->title ?? 'Quiz')
@section('app-title', $quiz->title ?? 'Quiz')

@section('content')
<!-- Quiz Header -->
<div class="card">
    <h1 style="font-size: 24px; font-weight: 700; margin-bottom: 8px;">{{ $quiz->title }}</h1>
    
    @if($quiz->description)
        <p style="color: #6b7280; margin-bottom: 16px;">{{ $quiz->description }}</p>
    @endif

    <!-- Quiz Info -->
    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px;">
        <div style="background: #f3f4f6; padding: 12px; border-radius: 8px; text-align: center;">
            <div style="font-size: 24px; font-weight: 700; color: var(--primary-color);">{{ $quiz->questions_count ?? 0 }}</div>
            <div style="font-size: 14px; color: #6b7280;">Questions</div>
        </div>
        @if(isset($quiz->time_limit) && $quiz->time_limit)
        <div style="background: #f3f4f6; padding: 12px; border-radius: 8px; text-align: center;">
            <div style="font-size: 24px; font-weight: 700; color: var(--warning-color);">{{ $quiz->time_limit }}</div>
            <div style="font-size: 14px; color: #6b7280;">Minutes</div>
        </div>
        @endif
        @if(isset($quiz->pass_score))
        <div style="background: #f3f4f6; padding: 12px; border-radius: 8px; text-align: center;">
            <div style="font-size: 24px; font-weight: 700; color: var(--success-color);">{{ $quiz->pass_score }}%</div>
            <div style="font-size: 14px; color: #6b7280;">Pass Score</div>
        </div>
        @endif
        @if(isset($quiz->max_attempts) && $quiz->max_attempts)
        <div style="background: #f3f4f6; padding: 12px; border-radius: 8px; text-align: center;">
            <div style="font-size: 24px; font-weight: 700; color: var(--secondary-color);">{{ $quiz->max_attempts }}</div>
            <div style="font-size: 14px; color: #6b7280;">Max Attempts</div>
        </div>
        @endif
    </div>
</div>

<!-- Your Best Score -->
@if(isset($bestAttempt))
<div class="card">
    <div class="card-header" style="padding: 0; margin-bottom: 12px;">Your Best Score</div>
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <div style="font-size: 32px; font-weight: 700; color: var(--success-color);">{{ $bestAttempt->score }}%</div>
            <div style="font-size: 14px; color: #6b7280;">Achieved on {{ date('M d, Y', strtotime($bestAttempt->created_at)) }}</div>
        </div>
        @if($bestAttempt->score >= ($quiz->pass_score ?? 70))
            <span class="badge badge-success" style="font-size: 16px; padding: 8px 16px;">✓ Passed</span>
        @else
            <span class="badge badge-danger" style="font-size: 16px; padding: 8px 16px;">Failed</span>
        @endif
    </div>
</div>
@endif

<!-- Recent Attempts -->
@if(isset($recentAttempts) && count($recentAttempts) > 0)
<div class="card">
    <div class="card-header" style="padding: 0; margin-bottom: 12px;">Recent Attempts</div>
    @foreach($recentAttempts as $attempt)
    <div class="list-item" style="margin-bottom: 8px;">
        <div class="list-item-content">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <div class="list-item-title">{{ date('M d, Y - h:i A', strtotime($attempt->created_at)) }}</div>
                    <div class="list-item-subtitle">
                        @if($attempt->status === 'completed')
                            Completed
                        @elseif($attempt->status === 'in_progress')
                            In Progress
                        @else
                            {{ ucfirst($attempt->status) }}
                        @endif
                    </div>
                </div>
                <div style="text-align: right;">
                    @if($attempt->status === 'completed')
                        <div style="font-size: 24px; font-weight: 700; color: {{ $attempt->score >= ($quiz->pass_score ?? 70) ? 'var(--success-color)' : 'var(--danger-color)' }};">
                            {{ $attempt->score }}%
                        </div>
                    @else
                        <a href="{{ route('quiz-attempts.continue', $attempt->id) }}" class="btn btn-primary" style="padding: 8px 16px; font-size: 14px;">Continue</a>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif

<!-- Start Quiz Button -->
<div class="card">
    @if(isset($canAttempt) && !$canAttempt)
        <div class="alert alert-error">
            You have reached the maximum number of attempts for this quiz.
        </div>
    @else
        <form action="{{ route('quiz-attempts.start', $quiz->id) }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-primary btn-full" style="font-size: 18px; padding: 16px;">
                🚀 Start Quiz
            </button>
        </form>
        
        @if(isset($quiz->instructions))
            <div style="margin-top: 16px; padding: 12px; background: #fef3c7; border-radius: 8px; font-size: 14px;">
                <strong>⚠️ Instructions:</strong><br>
                {{ $quiz->instructions }}
            </div>
        @endif
    @endif
</div>

<!-- Leaderboard -->
@if(isset($leaderboard) && count($leaderboard) > 0)
<div class="card">
    <div class="card-header" style="padding: 0; margin-bottom: 12px;">🏆 Leaderboard</div>
    @foreach($leaderboard as $index => $entry)
    <div class="list-item" style="margin-bottom: 8px;">
        <div style="width: 32px; height: 32px; border-radius: 50%; background: {{ $index < 3 ? 'linear-gradient(135deg, #f59e0b, #ef4444)' : '#e5e7eb' }}; color: {{ $index < 3 ? 'white' : '#6b7280' }}; display: flex; align-items: center; justify-content: center; font-weight: 600; margin-right: 12px;">
            {{ $index + 1 }}
        </div>
        <div class="list-item-content">
            <div class="list-item-title">{{ $entry->user_name }}</div>
            <div class="list-item-subtitle">{{ date('M d, Y', strtotime($entry->created_at)) }}</div>
        </div>
        <div style="font-size: 20px; font-weight: 700; color: var(--success-color);">
            {{ $entry->score }}%
        </div>
    </div>
    @endforeach
</div>
@endif
@endsection
