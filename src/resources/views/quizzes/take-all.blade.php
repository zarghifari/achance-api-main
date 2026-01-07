@extends('layouts.app')

@section('title', 'Take Quiz')
@section('app-title', $quiz->title ?? 'Quiz')

@section('content')
<!-- Quiz Header -->
<div class="card">
    <h1 style="font-size: 24px; font-weight: 700; margin-bottom: 8px;">{{ $quiz->title }}</h1>
    @if($quiz->description)
    <p style="color: #6b7280; margin-bottom: 16px;">{{ $quiz->description }}</p>
    @endif
    <div style="display: flex; gap: 16px; flex-wrap: wrap;">
        <span style="font-weight: 600;">📝 {{ $totalQuestions }} Questions</span>
        @if(isset($quiz->time_limit))
        <span style="font-weight: 600; color: var(--warning-color);">⏱ {{ $quiz->time_limit }} minutes</span>
        @endif
    </div>
</div>

<form id="quiz-form" action="{{ route('quiz-attempts.submit-all', $attemptId) }}" method="POST">
    @csrf
    
    <!-- All Questions -->
    @foreach($questions as $index => $question)
    <div class="card" id="question-{{ $question->id }}">
        <div style="margin-bottom: 16px;">
            <span class="badge badge-primary">Question {{ $index + 1 }}</span>
            @if(isset($question->question_type))
                <span class="badge badge-secondary">{{ ucfirst(str_replace('_', ' ', $question->question_type)) }}</span>
            @endif
        </div>
        
        <h2 style="font-size: 18px; font-weight: 600; margin-bottom: 20px; line-height: 1.5;">
            {{ $question->question_text ?? $question->question }}
        </h2>

        @if($question->question_img)
        <div style="margin-bottom: 20px;">
            <img src="{{ asset($question->question_img) }}" alt="Question image" style="max-width: 100%; border-radius: 8px;">
        </div>
        @endif

        <!-- Answer Options -->
        <div style="display: flex; flex-direction: column; gap: 12px;">
            @foreach($question->answers as $answer)
            <label class="answer-option" style="display: flex; align-items: start; padding: 16px; background: #f9fafb; border: 2px solid #e5e7eb; border-radius: 12px; cursor: pointer; transition: all 0.3s;">
                <input 
                    type="radio" 
                    name="answers[{{ $question->id }}]" 
                    value="{{ $answer->id }}" 
                    style="margin-right: 12px; margin-top: 2px;"
                    {{ isset($answeredQuestions[$question->id]) && $answeredQuestions[$question->id] == $answer->id ? 'checked' : '' }}
                    onchange="markAnswered({{ $question->id }})"
                >
                <span style="flex: 1;">{{ $answer->answer }}</span>
            </label>
            @endforeach
        </div>
    </div>
    @endforeach

    <!-- Submit Button -->
    <div class="card" style="position: sticky; bottom: 16px; box-shadow: 0 -4px 20px rgba(0,0,0,0.1);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
            <div>
                <span id="answered-count" style="font-weight: 600;">0</span> / {{ $totalQuestions }} answered
            </div>
            <button type="button" onclick="confirmSubmit()" class="btn btn-success" style="padding: 12px 32px;">
                ✓ Submit Quiz
            </button>
        </div>
        <div class="progress-bar">
            <div id="progress-fill" class="progress-fill" style="width: 0%"></div>
        </div>
    </div>
</form>

<!-- Instructions -->
<div style="background: #fef3c7; padding: 12px; border-radius: 8px; font-size: 14px; margin-top: 16px;">
    <strong>💡 Instructions:</strong> Answer all questions and click "Submit Quiz" when you're done. You can change your answers before submitting.
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
    
    .question-answered {
        border-left: 4px solid var(--success-color);
    }
</style>
@endpush

@push('scripts')
<script>
let answeredQuestions = new Set();

// Initialize with already answered questions
@foreach($answeredQuestions as $questionId => $answerId)
    answeredQuestions.add({{ $questionId }});
@endforeach

updateProgress();

function markAnswered(questionId) {
    answeredQuestions.add(questionId);
    updateProgress();
    
    // Add visual indicator
    const card = document.getElementById('question-' + questionId);
    if (card) {
        card.classList.add('question-answered');
    }
    
    // Auto-save (optional)
    saveAnswer(questionId);
}

function updateProgress() {
    const total = {{ $totalQuestions }};
    const answered = answeredQuestions.size;
    const percentage = (answered / total) * 100;
    
    document.getElementById('answered-count').textContent = answered;
    document.getElementById('progress-fill').style.width = percentage + '%';
}

function saveAnswer(questionId) {
    // Optional: Auto-save via AJAX
    const formData = new FormData(document.getElementById('quiz-form'));
    
    fetch('{{ route('quiz-attempts.save-answer', $attemptId) }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
        },
        body: formData
    }).then(() => {
        // Visual feedback
        showToast('Answer saved', 'success');
    }).catch(err => {
        console.error('Failed to save:', err);
    });
}

function confirmSubmit() {
    const total = {{ $totalQuestions }};
    const answered = answeredQuestions.size;
    
    if (answered < total) {
        const unanswered = total - answered;
        if (!confirm(`You have ${unanswered} unanswered question(s). Are you sure you want to submit?`)) {
            return;
        }
    }
    
    if (confirm('Are you sure you want to submit your quiz? You cannot change your answers after submission.')) {
        document.getElementById('quiz-form').submit();
    }
}

// Warn before leaving page
window.addEventListener('beforeunload', function(e) {
    e.preventDefault();
    e.returnValue = '';
});

// Smooth scroll to question when clicking on progress
document.querySelectorAll('[id^="question-"]').forEach(card => {
    card.style.scrollMarginTop = '80px';
});
</script>
@endpush
