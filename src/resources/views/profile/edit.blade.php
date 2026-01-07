@extends('layouts.app')

@section('title', 'Edit Profile')
@section('app-title', 'Edit Profile')

@section('content')
<div class="card">
    <form action="{{ route('profile.update') }}" method="POST">
        @csrf
        @method('PATCH')
        
        <div class="form-group">
            <label class="form-label">Full Name</label>
            <input type="text" name="name" class="form-input" value="{{ old('name', auth()->user()->name) }}" required>
        </div>

        <div class="form-group">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-input" value="{{ old('email', auth()->user()->email) }}" required>
        </div>

        <div class="form-group">
            <label class="form-label">Phone Number (Optional)</label>
            <input type="tel" name="phone" class="form-input" value="{{ old('phone', auth()->user()->phone ?? '') }}">
        </div>

        <div class="form-group">
            <label class="form-label">Bio (Optional)</label>
            <textarea name="bio" class="form-textarea">{{ old('bio', auth()->user()->bio ?? '') }}</textarea>
        </div>

        <button type="submit" class="btn btn-primary btn-full">Save Changes</button>
    </form>
</div>

<!-- Change Password -->
<div class="card">
    <div class="card-header" style="padding: 0; margin-bottom: 16px;">Change Password</div>
    
    <form action="{{ route('profile.password') }}" method="POST">
        @csrf
        @method('PATCH')
        
        <div class="form-group">
            <label class="form-label">Current Password</label>
            <input type="password" name="current_password" class="form-input" required>
        </div>

        <div class="form-group">
            <label class="form-label">New Password</label>
            <input type="password" name="password" class="form-input" required>
        </div>

        <div class="form-group">
            <label class="form-label">Confirm New Password</label>
            <input type="password" name="password_confirmation" class="form-input" required>
        </div>

        <button type="submit" class="btn btn-secondary btn-full">Update Password</button>
    </form>
</div>
@endsection
