@extends('layouts.app')

@section('title', $lesson->title ?? 'Lesson')
@section('app-title', $lesson->title ?? 'Lesson')

@section('content')
<!-- Breadcrumb -->
<div class="card">
    <div style="font-size: 14px;">
        <a href="{{ route('courses.show', $course->id) }}" style="color: var(--primary-color); text-decoration: none;">{{ $course->name }}</a>
        <span style="color: #9ca3af;"> › </span>
        <a href="{{ route('modules.show', ['course' => $course->id, 'module' => $module->id]) }}" style="color: var(--primary-color); text-decoration: none;">{{ $module->name }}</a>
    </div>
</div>

<!-- Lesson Header -->
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 12px;">
        <h1 style="font-size: 22px; font-weight: 700; flex: 1;">{{ $lesson->title }}</h1>
        <button onclick="toggleBookmark({{ $lesson->id }}, 'lesson')" class="app-bar-action" style="position: static;">
            <span id="bookmark-icon">{{ $isBookmarked ?? false ? '⭐' : '☆' }}</span>
        </button>
    </div>
    
    @if($lesson->description)
        <p style="color: #6b7280; margin-bottom: 16px;">{{ $lesson->description }}</p>
    @endif

    <!-- Lesson Meta -->
    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
        @if(isset($lesson->content_type))
            <span class="badge badge-primary">{{ ucfirst($lesson->content_type) }}</span>
        @endif
        @if(isset($lesson->duration))
            <span class="badge badge-info">⏱ {{ $lesson->duration }} min</span>
        @endif
        @if(isset($lesson->is_completed) && $lesson->is_completed)
            <span class="badge badge-success">✓ Completed</span>
        @endif
    </div>
</div>

<!-- Content Display -->
@if(isset($content))
    @if($content->type === 'html' || $content->type === 'json')
        <div class="card">
            <div id="lesson-content" style="line-height: 1.6;">
                {!! $content->html_content ?? '' !!}
            </div>
            
            @if(isset($content->pages) && $content->pages > 1)
                <div style="margin-top: 20px; display: flex; justify-content: space-between; align-items: center;">
                    @if($currentPage > 1)
                        <a href="{{ route('lessons.show', ['course' => $course->id, 'module' => $module->id, 'lesson' => $lesson->id, 'page' => $currentPage - 1]) }}" class="btn btn-secondary">← Previous</a>
                    @else
                        <div></div>
                    @endif
                    
                    <span style="color: #6b7280;">Page {{ $currentPage }} of {{ $content->pages }}</span>
                    
                    @if($currentPage < $content->pages)
                        <a href="{{ route('lessons.show', ['course' => $course->id, 'module' => $module->id, 'lesson' => $lesson->id, 'page' => $currentPage + 1]) }}" class="btn btn-primary">Next →</a>
                    @else
                        <div></div>
                    @endif
                </div>
            @endif
        </div>
    @elseif($content->type === 'video')
        <div class="card">
            @if(isset($content->video_url))
                <div style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; border-radius: 8px;">
                    @if(strpos($content->video_url, 'youtube.com') !== false || strpos($content->video_url, 'youtu.be') !== false)
                        <iframe src="{{ $content->video_url }}" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;" frameborder="0" allowfullscreen></iframe>
                    @else
                        <video controls style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;">
                            <source src="{{ $content->video_url }}" type="video/mp4">
                            Your browser does not support the video tag.
                        </video>
                    @endif
                </div>
            @endif
        </div>
    @elseif($content->type === 'pdf')
        <div class="card">
            <a href="{{ $content->file_url }}" class="btn btn-primary btn-full" target="_blank">
                📄 Open PDF Document
            </a>
        </div>
    @endif
@else
    <div class="card text-center" style="padding: 40px 20px;">
        <div style="font-size: 48px; margin-bottom: 16px;">📄</div>
        <p style="color: #6b7280;">No content available yet</p>
    </div>
@endif

<!-- Attachments -->
@if(isset($attachments) && count($attachments) > 0)
    <div class="card">
        <div class="card-header" style="padding: 0; margin-bottom: 12px;">Attachments</div>
        @foreach($attachments as $attachment)
            <a href="{{ $attachment->url }}" target="_blank" class="list-item" style="margin-bottom: 8px;">
                <div class="list-item-icon">📎</div>
                <div class="list-item-content">
                    <div class="list-item-title">{{ $attachment->name }}</div>
                    <div class="list-item-subtitle">{{ $attachment->size ?? '' }}</div>
                </div>
                <div class="list-item-arrow">↓</div>
            </a>
        @endforeach
    </div>
@endif

<!-- Actions -->
<div class="card">
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
        @if(!($lesson->is_completed ?? false))
            <button onclick="markAsComplete()" class="btn btn-success">✓ Mark Complete</button>
        @endif
        
        @if(isset($downloadUrl))
            <a href="{{ $downloadUrl }}" class="btn btn-secondary">⬇ Download</a>
        @endif
    </div>
</div>

<!-- Navigation -->
<div style="display: flex; gap: 12px; margin-top: 16px;">
    @if(isset($previousLesson))
        <a href="{{ route('lessons.show', ['course' => $course->id, 'module' => $module->id, 'lesson' => $previousLesson->id]) }}" class="btn btn-secondary" style="flex: 1;">
            ← Previous Lesson
        </a>
    @endif
    
    @if(isset($nextLesson))
        <a href="{{ route('lessons.show', ['course' => $course->id, 'module' => $module->id, 'lesson' => $nextLesson->id]) }}" class="btn btn-primary" style="flex: 1;">
            Next Lesson →
        </a>
    @endif
</div>
@endsection

@push('styles')
<style>
    #lesson-content {
        font-size: 16px;
    }
    
    #lesson-content img {
        max-width: 100%;
        height: auto;
        border-radius: 8px;
        margin: 16px 0;
    }
    
    #lesson-content h1, #lesson-content h2, #lesson-content h3 {
        margin-top: 24px;
        margin-bottom: 12px;
        font-weight: 600;
    }
    
    #lesson-content p {
        margin-bottom: 12px;
    }
    
    #lesson-content ul, #lesson-content ol {
        margin-left: 20px;
        margin-bottom: 12px;
    }
    
    #lesson-content code {
        background: #f3f4f6;
        padding: 2px 6px;
        border-radius: 4px;
        font-family: 'Courier New', monospace;
    }
    
    #lesson-content pre {
        background: #1f2937;
        color: #f3f4f6;
        padding: 16px;
        border-radius: 8px;
        overflow-x: auto;
        margin: 16px 0;
    }
    
    .youtube-embed {
        position: relative;
        padding-bottom: 56.25%; /* 16:9 aspect ratio */
        height: 0;
        overflow: hidden;
        border-radius: 8px;
        margin: 20px 0;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    
    .youtube-embed iframe {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
    }
    
    /* Mini player popup */
    .video-mini-player {
        position: fixed;
        bottom: 80px;
        right: 16px;
        width: 320px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        padding: 12px;
        display: none;
        z-index: 999;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .video-mini-player:hover {
        box-shadow: 0 15px 35px rgba(0,0,0,0.3);
        transform: translateY(-2px);
    }
    
    .video-mini-player.show {
        display: block;
        animation: slideIn 0.3s ease;
    }
    
    @keyframes slideIn {
        from {
            transform: translateY(100px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }
    
    .video-mini-player-content {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .video-mini-player-thumbnail {
        width: 80px;
        height: 60px;
        background: #f3f4f6;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        flex-shrink: 0;
    }
    
    .video-mini-player-info {
        flex: 1;
        min-width: 0;
    }
    
    .video-mini-player-title {
        font-weight: 600;
        font-size: 14px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        margin-bottom: 4px;
    }
    
    .video-mini-player-status {
        color: #ef4444;
        font-size: 12px;
        display: flex;
        align-items: center;
        gap: 4px;
    }
    
    .pulse {
        animation: pulse 1.5s ease-in-out infinite;
    }
    
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }
</style>
@endpush

@push('scripts')
<!-- Load YouTube iframe API -->
<script src="https://www.youtube.com/iframe_api"></script>
<script>
let currentlyPlayingVideo = null;
let videoObservers = new Map();
let miniPlayerActive = false;
let youtubePlayers = new Map();
let youtubeAPIReady = false;

// YouTube API ready callback
window.onYouTubeIframeAPIReady = function() {
    youtubeAPIReady = true;
    console.log('YouTube API Ready');
};

// Convert YouTube links to embedded players
function embedYouTubeVideos() {
    const content = document.getElementById('lesson-content');
    if (!content) return;
    
    // Find all YouTube links
    const youtubeRegex = /(?:https?:\/\/)?(?:www\.)?(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]{11})(?:[?&][^\s]*)?/gi;
    
    // Get all text nodes and links
    const walker = document.createTreeWalker(
        content,
        NodeFilter.SHOW_TEXT | NodeFilter.SHOW_ELEMENT,
        null,
        false
    );
    
    const nodesToReplace = [];
    let node;
    
    while (node = walker.nextNode()) {
        if (node.nodeType === Node.TEXT_NODE) {
            const text = node.textContent;
            const matches = [...text.matchAll(youtubeRegex)];
            if (matches.length > 0) {
                nodesToReplace.push({ node, matches, isText: true });
            }
        } else if (node.nodeType === Node.ELEMENT_NODE && node.tagName === 'A') {
            const href = node.getAttribute('href');
            if (href) {
                const matches = [...href.matchAll(youtubeRegex)];
                if (matches.length > 0) {
                    nodesToReplace.push({ node, matches, isText: false, href });
                }
            }
        }
    }
    
    // Replace YouTube links with embedded players
    let playerIndex = 0;
    nodesToReplace.forEach(({ node, matches, isText, href }) => {
        matches.forEach(match => {
            const videoId = match[1];
            const playerId = `youtube-player-${playerIndex++}`;
            const embedDiv = document.createElement('div');
            embedDiv.className = 'youtube-embed';
            embedDiv.dataset.videoId = videoId;
            embedDiv.dataset.playerId = playerId;
            
            const playerDiv = document.createElement('div');
            playerDiv.id = playerId;
            embedDiv.appendChild(playerDiv);
            
            if (isText) {
                const parent = node.parentNode;
                parent.insertBefore(embedDiv, node);
                node.textContent = node.textContent.replace(match[0], '');
            } else {
                node.parentNode.insertBefore(embedDiv, node);
                node.remove();
            }
        });
    });
    
    // Wait for YouTube API and initialize
    if (youtubeAPIReady) {
        initializeYouTubePlayers();
    } else {
        const checkAPI = setInterval(() => {
            if (typeof YT !== 'undefined' && YT.Player) {
                clearInterval(checkAPI);
                initializeYouTubePlayers();
            }
        }, 100);
    }
}

// Initialize YouTube players with API
function initializeYouTubePlayers() {
    const videoEmbeds = document.querySelectorAll('.youtube-embed');
    
    // Create mini player element
    const miniPlayer = document.createElement('div');
    miniPlayer.className = 'video-mini-player';
    miniPlayer.id = 'video-mini-player';
    miniPlayer.innerHTML = `
        <div class="video-mini-player-content">
            <div class="video-mini-player-thumbnail">▶️</div>
            <div class="video-mini-player-info">
                <div class="video-mini-player-title">Video is playing</div>
                <div class="video-mini-player-status">
                    <span class="pulse">🔴</span> Now playing - Tap to return
                </div>
            </div>
        </div>
    `;
    document.body.appendChild(miniPlayer);
    
    // Mini player click handler
    miniPlayer.addEventListener('click', () => {
        if (currentlyPlayingVideo) {
            currentlyPlayingVideo.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });
    
    videoEmbeds.forEach((embed) => {
        const playerId = embed.dataset.playerId;
        const videoId = embed.dataset.videoId;
        
        // Create YouTube player
        const player = new YT.Player(playerId, {
            videoId: videoId,
            playerVars: {
                'enablejsapi': 1,
                'origin': window.location.origin
            },
            events: {
                'onStateChange': (event) => onPlayerStateChange(event, embed, miniPlayer)
            }
        });
        
        youtubePlayers.set(embed, player);
        
        // Setup Intersection Observer
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (currentlyPlayingVideo === embed) {
                    if (!entry.isIntersecting && !miniPlayerActive) {
                        miniPlayer.classList.add('show');
                        miniPlayerActive = true;
                    } else if (entry.isIntersecting && miniPlayerActive) {
                        miniPlayer.classList.remove('show');
                        miniPlayerActive = false;
                    }
                }
            });
        }, {
            threshold: 0.3
        });
        
        observer.observe(embed);
        videoObservers.set(embed, observer);
    });
}

// Handle player state changes
function onPlayerStateChange(event, embed, miniPlayer) {
    // YT.PlayerState.PLAYING = 1
    if (event.data === 1) {
        // Video started playing
        pauseOtherVideos(embed);
        currentlyPlayingVideo = embed;
        
        // Check if video is visible
        const rect = embed.getBoundingClientRect();
        const isVisible = rect.top >= 0 && rect.bottom <= window.innerHeight;
        if (!isVisible) {
            miniPlayer.classList.add('show');
            miniPlayerActive = true;
        }
    }
    // YT.PlayerState.PAUSED = 2 or YT.PlayerState.ENDED = 0
    else if (event.data === 2 || event.data === 0) {
        if (currentlyPlayingVideo === embed) {
            miniPlayer.classList.remove('show');
            miniPlayerActive = false;
            currentlyPlayingVideo = null;
        }
    }
}

// Pause all other videos except the current one
function pauseOtherVideos(currentEmbed) {
    youtubePlayers.forEach((player, embed) => {
        if (embed !== currentEmbed) {
            try {
                player.pauseVideo();
            } catch (e) {
                console.log('Error pausing video:', e);
            }
        }
    });
}

// Run on page load
document.addEventListener('DOMContentLoaded', embedYouTubeVideos);

async function markAsComplete() {
    try {
        showLoading();
        const response = await fetch('/api/lessons/{{ $lesson->id }}/complete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + localStorage.getItem('token'),
                'X-CSRF-TOKEN': csrfToken
            }
        });
        
        hideLoading();
        if (response.ok) {
            showToast('Lesson marked as complete!', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast('Failed to mark lesson as complete', 'error');
        }
    } catch (error) {
        hideLoading();
        showToast('An error occurred', 'error');
    }
}

async function toggleBookmark(id, type) {
    try {
        const response = await fetch(`/api/bookmarks/${type}/${id}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + localStorage.getItem('token'),
                'X-CSRF-TOKEN': csrfToken
            }
        });
        
        const data = await response.json();
        document.getElementById('bookmark-icon').textContent = data.bookmarked ? '⭐' : '☆';
        showToast(data.message, 'success');
    } catch (error) {
        showToast('Failed to update bookmark', 'error');
    }
}
</script>
@endpush
