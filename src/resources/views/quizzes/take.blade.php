@extends('layouts.app')

@section('title', 'Take Quiz')
@section('app-title', $quiz->title ?? 'Quiz')

@section('content')
<!-- Quiz Progress -->
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
        <div>
            <span style="font-weight: 600;">Question {{ $currentQuestion }} of {{ $totalQuestions }}</span>
        </div>
        @if(isset($timeRemaining))
        <div id="timer" style="font-weight: 600; color: var(--warning-color);">
            ⏱ <span id="time-display">{{ $timeRemaining }}</span>
        </div>
        @endif
    </div>
    <div class="progress-bar">
        <div class="progress-fill" style="width: {{ ($currentQuestion / $totalQuestions) * 100 }}%"></div>
    </div>
</div>

<!-- Question Card -->
<div class="card">
    <div style="margin-bottom: 16px;">
        <span class="badge badge-primary">Question {{ $currentQuestion }}</span>
        @if(isset($question->points))
            <span class="badge badge-warning">{{ $question->points }} points</span>
        @endif
    </div>
    
    <h2 style="font-size: 18px; font-weight: 600; margin-bottom: 20px; line-height: 1.5;">
        {{ $question->question }}
    </h2>

    <!-- Answer Options -->
    <form id="answer-form" action="{{ route('quiz-attempts.answer', ['attempt' => $attemptId, 'question' => $question->id]) }}" method="POST">
        @csrf
        <input type="hidden" name="question_id" value="{{ $question->id }}">
        
        <div style="display: flex; flex-direction: column; gap: 12px;">
            @foreach($question->answers as $answer)
            <label class="answer-option" style="display: flex; align-items: start; padding: 16px; background: #f9fafb; border: 2px solid #e5e7eb; border-radius: 12px; cursor: pointer; transition: all 0.3s;">
                <input type="radio" name="answer_id" value="{{ $answer->id }}" style="margin-right: 12px; margin-top: 2px;" {{ isset($selectedAnswer) && $selectedAnswer == $answer->id ? 'checked' : '' }}>
                <span style="flex: 1;">{{ $answer->answer }}</span>
            </label>
            @endforeach
        </div>

        <!-- Navigation Buttons -->
        <div style="display: flex; gap: 12px; margin-top: 24px;">
            @if($currentQuestion > 1)
                <a href="{{ route('quiz-attempts.question', ['attempt' => $attemptId, 'question' => $currentQuestion - 1]) }}" class="btn btn-secondary" style="flex: 1;">
                    ← Previous
                </a>
            @endif
            
            @if($currentQuestion < $totalQuestions)
                <button type="submit" class="btn btn-primary" style="flex: 1;">
                    Next →
                </button>
            @else
                <button type="button" onclick="confirmSubmit()" class="btn btn-success" style="flex: 1;">
                    ✓ Submit Quiz
                </button>
            @endif
        </div>
    </form>
</div>

<!-- Question Navigator -->
<div class="card">
    <div class="card-header" style="padding: 0; margin-bottom: 12px;">Question Navigator</div>
    <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 8px;">
        @for($i = 1; $i <= $totalQuestions; $i++)
            <a href="{{ route('quiz-attempts.question', ['attempt' => $attemptId, 'question' => $i]) }}" 
               class="btn {{ $i == $currentQuestion ? 'btn-primary' : (isset($answeredQuestions[$i]) ? 'btn-success' : 'btn-secondary') }}" 
               style="padding: 12px; font-size: 14px;">
                {{ $i }}
            </a>
        @endfor
    </div>
</div>

<!-- Instructions -->
<div style="background: #fef3c7; padding: 12px; border-radius: 8px; font-size: 14px;">
    <strong>💡 Tip:</strong> You can navigate between questions using the navigator above. Your answers are saved automatically.
</div>
@endsection

@push('styles')
<style>
    .answer-option:hover {
        background: #f3f4f6 !important;
        border-color: var(--primary-color) !important;
    }
    
    .answer-option:has(input:checked) {
        background: #ede9fe !important;
        border-color: var(--primary-color) !important;
    }
</style>
@endpush

@push('scripts')
<script>
let timeRemaining = {{ $timeRemainingSeconds ?? 'null' }};

@if(isset($timeRemainingSeconds))
function updateTimer() {
    if (timeRemaining <= 0) {
        alert('Time is up! Submitting your quiz...');
        document.getElementById('answer-form').submit();
        return;
    }
    
    const minutes = Math.floor(timeRemaining / 60);
    const seconds = timeRemaining % 60;
    document.getElementById('time-display').textContent = 
        `${minutes}:${seconds.toString().padStart(2, '0')}`;
    
    // Change color when time is running out
    if (timeRemaining <= 60) {
        document.getElementById('timer').style.color = 'var(--danger-color)';
    } else if (timeRemaining <= 300) {
        document.getElementById('timer').style.color = 'var(--warning-color)';
    }
    
    timeRemaining--;
    setTimeout(updateTimer, 1000);
}

updateTimer();
@endif

function confirmSubmit() {
    if (confirm('Are you sure you want to submit your quiz? You cannot change your answers after submission.')) {
        document.getElementById('answer-form').action = '{{ route('quiz-attempts.submit', $attemptId) }}';
        document.getElementById('answer-form').submit();
    }
}

// Auto-save answer on selection
document.querySelectorAll('input[name="answer_id"]').forEach(radio => {
    radio.addEventListener('change', function() {
        // Visual feedback
        showToast('Answer saved', 'success');
    });
});

// Warn before leaving page
window.addEventListener('beforeunload', function(e) {
    e.preventDefault();
    e.returnValue = '';
});
</script>
@endpush
