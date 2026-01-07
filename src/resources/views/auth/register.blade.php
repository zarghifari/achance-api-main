@extends('layouts.app')

@section('title', 'Register')
@section('app-title', 'Create Account')

@section('content')
<div class="card" style="margin-top: 20px;">
    <div class="text-center mb-3">
        <div style="font-size: 48px; margin-bottom: 16px;">🎓</div>
        <h1 style="font-size: 24px; font-weight: 700; margin-bottom: 8px;">Join aChance</h1>
        <p style="color: #6b7280;">Start your learning adventure today</p>
    </div>

    <form action="{{ route('register.post') }}" method="POST">
        @csrf
        
        <div class="form-group">
            <label class="form-label">Full Name</label>
            <input type="text" name="name" class="form-input" placeholder="Enter your full name" value="{{ old('name') }}" required autofocus>
        </div>

        <div class="form-group">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-input" placeholder="Enter your email" value="{{ old('email') }}" required>
        </div>

        <div class="form-group">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-input" placeholder="Create a password (min 8 characters)" required>
        </div>

        <div class="form-group">
            <label class="form-label">Confirm Password</label>
            <input type="password" name="password_confirmation" class="form-input" placeholder="Confirm your password" required>
        </div>

        <div class="form-group">
            <label style="display: flex; align-items: flex-start; cursor: pointer;">
                <input type="checkbox" name="terms" style="margin-right: 8px; margin-top: 4px;" required>
                <span style="font-size: 14px; color: #6b7280;">I agree to the Terms of Service and Privacy Policy</span>
            </label>
        </div>

        <button type="submit" class="btn btn-primary btn-full">Create Account</button>
    </form>

    <div class="text-center mt-3">
        <p style="color: #6b7280;">Already have an account? 
            <a href="{{ route('login') }}" style="color: var(--primary-color); font-weight: 600;">Sign In</a>
        </p>
    </div>
</div>
@endsection
