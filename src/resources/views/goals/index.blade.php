@extends('layouts.app')

@section('title', 'Learning Goals')
@section('app-title', 'My Goals')

@section('content')
<!-- Create Goal Button -->
<a href="{{ route('goals.create') }}" class="btn btn-primary btn-full" style="margin-bottom: 16px;">
    ➕ Create New Goal
</a>

<!-- Active Goals -->
@if(isset($activeGoals) && count($activeGoals) > 0)
    <div class="card-header">Active Goals</div>
    @foreach($activeGoals as $goal)
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 12px;">
            <h3 style="font-size: 18px; font-weight: 600; flex: 1;">{{ $goal->title }}</h3>
            <a href="{{ route('goals.edit', $goal->id) }}" style="color: var(--primary-color); text-decoration: none; margin-left: 8px;">✏️</a>
        </div>
        
        @if($goal->description)
            <p style="color: #6b7280; font-size: 14px; margin-bottom: 12px;">{{ $goal->description }}</p>
        @endif
        
        <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 12px;">
            @if($goal->target_date)
                <span class="badge badge-primary">📅 {{ date('M d, Y', strtotime($goal->target_date)) }}</span>
            @endif
            <span class="badge badge-{{ $goal->priority === 'high' ? 'danger' : ($goal->priority === 'medium' ? 'warning' : 'info') }}">
                {{ ucfirst($goal->priority ?? 'normal') }} Priority
            </span>
        </div>
        
        @if(isset($goal->progress_percentage))
            <div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                    <span style="font-weight: 500; font-size: 14px;">Progress</span>
                    <span style="color: var(--primary-color); font-weight: 600;">{{ $goal->progress_percentage }}%</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: {{ $goal->progress_percentage }}%"></div>
                </div>
            </div>
        @endif
        
        <div style="display: flex; gap: 8px; margin-top: 12px;">
            <a href="{{ route('goals.show', $goal->id) }}" class="btn btn-primary" style="flex: 1;">View Details</a>
            @if($goal->progress_percentage < 100)
                <button onclick="updateProgress({{ $goal->id }})" class="btn btn-success" style="flex: 1;">Update Progress</button>
            @endif
        </div>
    </div>
    @endforeach
@else
    <div class="card text-center" style="padding: 60px 20px;">
        <div style="font-size: 64px; margin-bottom: 16px;">🎯</div>
        <h3 style="font-size: 20px; font-weight: 600; margin-bottom: 8px;">No Goals Yet</h3>
        <p style="color: #6b7280; margin-bottom: 20px;">Set learning goals to track your progress</p>
        <a href="{{ route('goals.create') }}" class="btn btn-primary">Create Your First Goal</a>
    </div>
@endif

<!-- Completed Goals -->
@if(isset($completedGoals) && count($completedGoals) > 0)
    <div class="card-header mt-2">Completed Goals</div>
    @foreach($completedGoals as $goal)
    <a href="{{ route('goals.show', $goal->id) }}" class="list-item">
        <div class="list-item-icon">✅</div>
        <div class="list-item-content">
            <div class="list-item-title">{{ $goal->title }}</div>
            <div class="list-item-subtitle">Completed on {{ date('M d, Y', strtotime($goal->completed_at)) }}</div>
        </div>
        <div class="list-item-arrow">›</div>
    </a>
    @endforeach
@endif
@endsection

@push('scripts')
<script>
async function updateProgress(goalId) {
    const progress = prompt('Enter progress update (0-100):');
    if (progress === null) return;
    
    const progressValue = parseInt(progress);
    if (isNaN(progressValue) || progressValue < 0 || progressValue > 100) {
        showToast('Please enter a valid progress value (0-100)', 'error');
        return;
    }
    
    try {
        showLoading();
        const response = await fetch(`/api/learning-goals/${goalId}/progress`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + localStorage.getItem('token'),
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ progress: progressValue })
        });
        
        hideLoading();
        if (response.ok) {
            showToast('Progress updated successfully!', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast('Failed to update progress', 'error');
        }
    } catch (error) {
        hideLoading();
        showToast('An error occurred', 'error');
    }
}
</script>
@endpush
