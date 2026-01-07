@extends('layouts.app')

@section('title', 'Bookmarks')
@section('app-title', 'My Bookmarks')

@section('content')
<!-- Filter Tabs -->
<div class="card">
    <div style="display: flex; gap: 8px; overflow-x: auto;">
        <a href="{{ route('bookmarks.index') }}" class="btn {{ !request('type') ? 'btn-primary' : 'btn-secondary' }}">All</a>
        <a href="{{ route('bookmarks.index', ['type' => 'course']) }}" class="btn {{ request('type') == 'course' ? 'btn-primary' : 'btn-secondary' }}">Courses</a>
        <a href="{{ route('bookmarks.index', ['type' => 'module']) }}" class="btn {{ request('type') == 'module' ? 'btn-primary' : 'btn-secondary' }}">Modules</a>
        <a href="{{ route('bookmarks.index', ['type' => 'lesson']) }}" class="btn {{ request('type') == 'lesson' ? 'btn-primary' : 'btn-secondary' }}">Lessons</a>
    </div>
</div>

<!-- Bookmarks List -->
@if(isset($bookmarks) && count($bookmarks) > 0)
    @foreach($bookmarks as $bookmark)
    <div class="list-item">
        <div class="list-item-icon">
            @if($bookmark->type === 'course')
                📚
            @elseif($bookmark->type === 'module')
                📦
            @else
                📖
            @endif
        </div>
        <div class="list-item-content">
            <div class="list-item-title">{{ $bookmark->title }}</div>
            <div class="list-item-subtitle">
                <span class="badge badge-primary">{{ ucfirst($bookmark->type) }}</span>
                @if($bookmark->note)
                    <span style="margin-left: 8px;">{{ Str::limit($bookmark->note, 40) }}</span>
                @endif
            </div>
            <div style="font-size: 12px; color: #9ca3af; margin-top: 4px;">
                Saved {{ $bookmark->created_at->diffForHumans() }}
            </div>
        </div>
        <div style="display: flex; gap: 8px;">
            <a href="{{ $bookmark->url }}" class="btn btn-primary" style="padding: 8px 16px; font-size: 14px;">Open</a>
            <button class="remove-bookmark-btn" data-bookmark-id="{{ $bookmark->id }}" style="background: none; border: none; color: var(--danger-color); font-size: 20px; cursor: pointer;">🗑️</button>
        </div>
    </div>
    @endforeach
@else
    <div class="card text-center" style="padding: 60px 20px;">
        <div style="font-size: 64px; margin-bottom: 16px;">🔖</div>
        <h3 style="font-size: 20px; font-weight: 600; margin-bottom: 8px;">No Bookmarks Yet</h3>
        <p style="color: #6b7280; margin-bottom: 20px;">
            @if(request('type'))
                No {{ request('type') }} bookmarks found.
            @else
                Start bookmarking courses, modules, and lessons to find them easily later.
            @endif
        </p>
        <a href="{{ route('courses.index') }}" class="btn btn-primary">Browse Courses</a>
    </div>
@endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.remove-bookmark-btn').forEach(btn => {
        btn.addEventListener('click', async function() {
            const bookmarkId = this.dataset.bookmarkId;
            if (!confirm('Are you sure you want to remove this bookmark?')) return;
            
            try {
                showLoading();
                const response = await fetch(`/api/bookmarks/${bookmarkId}`, {
                    method: 'DELETE',
                    headers: {
                        'Authorization': 'Bearer ' + localStorage.getItem('token'),
                        'X-CSRF-TOKEN': csrfToken
                    }
                });
                
                hideLoading();
                if (response.ok) {
                    showToast('Bookmark removed', 'success');
                    setTimeout(() => location.reload(), 500);
                } else {
                    showToast('Failed to remove bookmark', 'error');
                }
            } catch (error) {
                hideLoading();
                showToast('An error occurred', 'error');
            }
        });
    });
});
</script>
@endpush
