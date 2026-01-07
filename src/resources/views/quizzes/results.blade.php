@extends('layouts.app')

@section('title', 'Quiz Results')
@section('app-title', 'Quiz Results')

@section('content')
<!-- Result Header -->
<div class="card text-center">
    <div style="font-size: 64px; margin-bottom: 16px;">
        @if($attempt->score >= ($quiz->pass_score ?? 70))
            🎉
        @else
            😔
        @endif
    </div>
    
    <h1 style="font-size: 28px; font-weight: 700; margin-bottom: 8px;">
        @if($attempt->score >= ($quiz->pass_score ?? 70))
            Congratulations!
        @else
            Keep Trying!
        @endif
    </h1>
    
    <p style="color: #6b7280; margin-bottom: 24px;">{{ $quiz->title }}</p>
    
    @php
        $scoreClass = $attempt->score >= ($quiz->pass_score ?? 70) ? 'text-success' : 'text-danger';
    @endphp
    <div class="{{ $scoreClass }}" style="font-size: 48px; font-weight: 700; margin-bottom: 8px;">
        {{ $attempt->score }}%
    </div>
    
    <div style="font-size: 16px; color: #6b7280; margin-bottom: 24px;">
        {{ $attempt->correct_answers }} out of {{ $attempt->total_questions }} correct
    </div>
    
    @if($attempt->score >= ($quiz->pass_score ?? 70))
        <span class="badge badge-success" style="font-size: 18px; padding: 12px 24px;">✓ Passed</span>
    @else
        <span class="badge badge-danger" style="font-size: 18px; padding: 12px 24px;">Failed</span>
    @endif
</div>

<!-- Score Breakdown -->
<div class="card">
    <div class="card-header" style="padding: 0; margin-bottom: 16px;">Score Breakdown</div>
    
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 16px;">
        <div style="text-align: center; padding: 12px; background: #d1fae5; border-radius: 8px;">
            <div style="font-size: 24px; font-weight: 700; color: var(--success-color);">{{ $attempt->correct_answers }}</div>
            <div style="font-size: 14px; color: #065f46;">Correct</div>
        </div>
        <div style="text-align: center; padding: 12px; background: #fee2e2; border-radius: 8px;">
            <div style="font-size: 24px; font-weight: 700; color: var(--danger-color);">{{ $attempt->wrong_answers }}</div>
            <div style="font-size: 14px; color: #991b1b;">Wrong</div>
        </div>
        <div style="text-align: center; padding: 12px; background: #e5e7eb; border-radius: 8px;">
            <div style="font-size: 24px; font-weight: 700; color: #6b7280;">{{ $attempt->unanswered ?? 0 }}</div>
            <div style="font-size: 14px; color: #374151;">Skipped</div>
        </div>
    </div>
    
    @if(isset($attempt->time_taken))
    <div style="text-align: center; padding: 12px; background: #dbeafe; border-radius: 8px;">
        <div style="font-size: 18px; font-weight: 600; color: var(--primary-color);">
            ⏱ Time Taken: {{ gmdate('i:s', $attempt->time_taken) }}
        </div>
    </div>
    @endif
</div>

<!-- Question Review -->
@if(isset($reviewQuestions) && count($reviewQuestions) > 0)
<div class="card">
    <div class="card-header" style="padding: 0; margin-bottom: 16px;">Review Your Answers</div>
    
    @foreach($reviewQuestions as $index => $review)
    <div style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #e5e7eb;">
        <div style="margin-bottom: 12px;">
            <span class="badge badge-primary">Question {{ $index + 1 }}</span>
            @if($review->is_correct)
                <span class="badge badge-success">✓ Correct</span>
            @else
                <span class="badge badge-danger">✗ Wrong</span>
            @endif
        </div>
        
        <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 12px;">
            {{ $review->question }}
        </h3>
        
        @if(!$review->is_correct)
            <div style="padding: 12px; background: #fee2e2; border-radius: 8px; margin-bottom: 8px;">
                <strong style="color: #991b1b;">Your Answer:</strong>
                <div style="color: #7f1d1d;">{{ $review->user_answer }}</div>
            </div>
            <div style="padding: 12px; background: #d1fae5; border-radius: 8px;">
                <strong style="color: #065f46;">Correct Answer:</strong>
                <div style="color: #064e3b;">{{ $review->correct_answer }}</div>
            </div>
        @else
            <div style="padding: 12px; background: #d1fae5; border-radius: 8px;">
                <strong style="color: #065f46;">Your Answer:</strong>
                <div style="color: #064e3b;">{{ $review->user_answer }}</div>
            </div>
        @endif
        
        @if(isset($review->explanation))
            <div style="margin-top: 12px; padding: 12px; background: #dbeafe; border-radius: 8px; font-size: 14px;">
                <strong style="color: #1e40af;">💡 Explanation:</strong>
                <div style="color: #1e3a8a; margin-top: 4px;">{{ $review->explanation }}</div>
            </div>
        @endif
    </div>
    @endforeach
</div>
@endif

<!-- Actions -->
<div style="display: flex; flex-direction: column; gap: 12px;">
    @if(isset($canRetake) && $canRetake)
        <a href="{{ route('quizzes.show', $quiz->id) }}" class="btn btn-primary btn-full">
            🔄 Retake Quiz
        </a>
    @endif
    <a href="{{ route('quizzes.index') }}" class="btn btn-secondary btn-full">
        📚 Browse More Quizzes
    </a>
    <a href="{{ route('home') }}" class="btn btn-secondary btn-full">
        🏠 Back to Home
    </a>
</div>
@endsection
