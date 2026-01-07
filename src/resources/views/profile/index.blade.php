@extends('layouts.app')

@section('title', 'Profile')
@section('app-title', 'My Profile')

@section('content')
<!-- Profile Header -->
<div class="card text-center">
    <div style="width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); color: white; display: flex; align-items: center; justify-content: center; font-size: 36px; font-weight: 700; margin: 0 auto 16px;">
        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
    </div>
    <h1 style="font-size: 24px; font-weight: 700; margin-bottom: 4px;">{{ auth()->user()->name }}</h1>
    <p style="color: #6b7280; margin-bottom: 16px;">{{ auth()->user()->email }}</p>
    
    @if(isset(auth()->user()->role))
        <span class="badge badge-primary">{{ ucfirst(auth()->user()->role) }}</span>
    @endif
</div>

<!-- Stats -->
<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 16px;">
    <div class="card text-center" style="padding: 16px;">
        <div style="font-size: 28px; font-weight: 700; color: var(--primary-color);">{{ $stats['enrolled_courses'] ?? 0 }}</div>
        <div style="color: #6b7280; font-size: 13px;">Courses</div>
    </div>
    <div class="card text-center" style="padding: 16px;">
        <div style="font-size: 28px; font-weight: 700; color: var(--success-color);">{{ $stats['completed_lessons'] ?? 0 }}</div>
        <div style="color: #6b7280; font-size: 13px;">Lessons</div>
    </div>
    <div class="card text-center" style="padding: 16px;">
        <div style="font-size: 28px; font-weight: 700; color: var(--warning-color);">{{ $stats['quiz_attempts'] ?? 0 }}</div>
        <div style="color: #6b7280; font-size: 13px;">Quizzes</div>
    </div>
</div>

<!-- Menu Options -->
<a href="{{ route('profile.edit') }}" class="list-item">
    <div class="list-item-icon">✏️</div>
    <div class="list-item-content">
        <div class="list-item-title">Edit Profile</div>
        <div class="list-item-subtitle">Update your personal information</div>
    </div>
    <div class="list-item-arrow">›</div>
</a>

<a href="{{ route('profile.learning') }}" class="list-item">
    <div class="list-item-icon">📊</div>
    <div class="list-item-content">
        <div class="list-item-title">Learning Progress</div>
        <div class="list-item-subtitle">View your learning analytics</div>
    </div>
    <div class="list-item-arrow">›</div>
</a>

<a href="{{ route('bookmarks.index') }}" class="list-item">
    <div class="list-item-icon">🔖</div>
    <div class="list-item-content">
        <div class="list-item-title">Bookmarks</div>
        <div class="list-item-subtitle">{{ $stats['bookmarks'] ?? 0 }} saved items</div>
    </div>
    <div class="list-item-arrow">›</div>
</a>

<a href="{{ route('goals.index') }}" class="list-item">
    <div class="list-item-icon">🎯</div>
    <div class="list-item-content">
        <div class="list-item-title">Learning Goals</div>
        <div class="list-item-subtitle">{{ $stats['active_goals'] ?? 0 }} active goals</div>
    </div>
    <div class="list-item-arrow">›</div>
</a>

<a href="{{ route('profile.preferences') }}" class="list-item">
    <div class="list-item-icon">⚙️</div>
    <div class="list-item-content">
        <div class="list-item-title">Preferences</div>
        <div class="list-item-subtitle">Customize your learning experience</div>
    </div>
    <div class="list-item-arrow">›</div>
</a>

<!-- Logout Button -->
<form action="{{ route('logout') }}" method="POST">
    @csrf
    <button type="submit" class="btn btn-danger btn-full" style="margin-top: 16px;">
        🚪 Logout
    </button>
</form>
@endsection
