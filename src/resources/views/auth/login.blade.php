@extends('layouts.app')

@section('title', 'Login')
@section('app-title', 'Welcome Back')

@section('content')
<div class="card" style="margin-top: 40px;">
    <div class="text-center mb-3">
        <div style="font-size: 48px; margin-bottom: 16px;">🎓</div>
        <h1 style="font-size: 24px; font-weight: 700; margin-bottom: 8px;">aChance Learning</h1>
        <p style="color: #6b7280;">Sign in to continue your learning journey</p>
    </div>

    <form action="{{ route('login.post') }}" method="POST">
        @csrf
        
        <div class="form-group">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-input" placeholder="Enter your email" value="{{ old('email') }}" required autofocus>
        </div>

        <div class="form-group">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-input" placeholder="Enter your password" required>
        </div>

        <div class="form-group">
            <label style="display: flex; align-items: center; cursor: pointer;">
                <input type="checkbox" name="remember" style="margin-right: 8px;">
                <span>Remember me</span>
            </label>
        </div>

        <button type="submit" class="btn btn-primary btn-full">Sign In</button>
    </form>

    <div class="text-center mt-3">
        <p style="color: #6b7280;">Don't have an account? 
            <a href="{{ route('register') }}" style="color: var(--primary-color); font-weight: 600;">Sign Up</a>
        </p>
    </div>
</div>
@endsection
