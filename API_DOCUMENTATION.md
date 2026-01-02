# AChance API Documentation

## Overview

The AChance API provides comprehensive endpoints for:

1. **Self-Directed Learning (SDL) Features** ✨ NEW
   - Learning Goals with progress tracking and milestones
   - Learning Profiles with VARK assessment
   - Bookmarks for lessons, courses, and modules
   - Personalized content recommendations

2. **Course Management**
   - Complete course navigation with modules and lessons
   - EPUB file management and validation
   - Progress tracking and lesson completion

3. **Performance Optimizations**
   - HTTP/2, database indexes, ETag caching, and increased concurrency

## Performance Optimizations (December 2025)

### Active Optimizations

✅ **HTTP/2 Enabled**
- Multiplexed requests over single connection
- 20-30% faster for parallel requests
- Automatic header compression

✅ **Database Indexes** (9 indexes)
- `courses`: title, slug, isOpen + created_at composite
- `modules`: course_id, position, (course_id, position) composite
- `lessons`: module_id, position, (module_id, position) composite
- 30-50% faster query performance

✅ **ETag Support**
- Conditional GET requests with `If-None-Match` header
- 304 Not Modified responses for unchanged resources
- 50-80% bandwidth reduction on repeated requests
- Check response headers for `ETag` value

✅ **Increased Concurrency**
- PHP-FPM workers: 50 max children (+66% capacity)
- Better handling of concurrent requests
- Reduced wait times under load

✅ **Redis Connection Pooling**
- Persistent connections to cache server
- 5-10% faster cache operations
- Reduced connection overhead

### Expected Performance Improvements
- **Overall API Speed**: 25-40% improvement
- **Database Queries**: 30-50% faster
- **Concurrent Capacity**: +66% more simultaneous requests
- **Cache Hit Responses**: ~260ms (cached) vs ~567ms (first load)
- **Bandwidth**: 50-80% reduction with ETag caching

### Using ETag Caching

**First Request:**
```bash
curl -H "Authorization: Bearer TOKEN" http://localhost/api/courses/1
# Response includes: ETag: "abc123def456"
```

**Subsequent Request:**
```bash
curl -H "Authorization: Bearer TOKEN" \
     -H "If-None-Match: \"abc123def456\"" \
     http://localhost/api/courses/1
# Returns: 304 Not Modified (no body, saves bandwidth)
```

---

## Self-Directed Learning (SDL) Features ✨

The SDL features enable personalized learning experiences with goal tracking, learning style assessment, and content bookmarking.

### Learning Goals API

#### 1. Create Learning Goal
```
POST /api/learning-goals
```

**Description:** Create a new learning goal with optional milestones.

**Request Body:**
```json
{
  "goal_type": "skill",
  "title": "Master React Development",
  "description": "Become proficient in React to build modern web applications",
  "target_date": "2026-06-01",
  "target_metric": "Complete 3 React courses and build 2 projects",
  "target_value": 5,
  "related_courses": [1, 2],
  "related_skills": ["React", "JavaScript", "Component Design"],
  "milestones": [
    {
      "title": "Complete React Fundamentals",
      "description": "Learn the basics",
      "sequence_order": 1
    },
    {
      "title": "Build first React app",
      "description": "Create a todo app",
      "sequence_order": 2
    }
  ]
}
```

**Goal Types:**
- `skill` - Skill-based goals (e.g., "Learn React")
- `course_completion` - Course completion goals
- `time_based` - Time-based goals (e.g., "Study 30 hours")
- `certificate` - Certification goals

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "user_id": 1,
    "goal_type": "skill",
    "title": "Master React Development",
    "description": "Become proficient in React to build modern web applications",
    "target_date": "2026-06-01",
    "target_metric": "Complete 3 React courses and build 2 projects",
    "status": "active",
    "days_remaining": 165,
    "on_track": true,
    "progress": {
      "id": 1,
      "current_value": "0.00",
      "target_value": "5.00",
      "percentage": 0,
      "last_updated": "2025-12-18T10:30:00Z"
    },
    "milestones": [
      {
        "id": 1,
        "title": "Complete React Fundamentals",
        "description": "Learn the basics",
        "sequence_order": 1,
        "is_achieved": false,
        "achieved_at": null
      }
    ],
    "related_courses": [1, 2],
    "related_skills": ["React", "JavaScript", "Component Design"],
    "created_at": "2025-12-18T10:30:00Z"
  },
  "message": "Learning goal created successfully"
}
```

#### 2. Get All Learning Goals
```
GET /api/learning-goals?status=active&goal_type=skill
```

**Query Parameters:**
- `status` - Filter by status: `active`, `achieved`, `abandoned`, `on_hold`
- `goal_type` - Filter by goal type
- `on_track` - Filter by tracking status: `true`, `false`

**Response:**
```json
{
  "success": true,
  "data": {
    "summary": {
      "total_goals": 5,
      "active_goals": 3,
      "achieved_goals": 2,
      "on_track_percentage": 67
    },
    "goals": [
      {
        "id": 1,
        "title": "Master React Development",
        "goal_type": "skill",
        "status": "active",
        "progress_percentage": 40,
        "days_remaining": 165,
        "on_track": true,
        "target_date": "2026-06-01",
        "milestones_count": 4,
        "achieved_milestones_count": 1
      }
    ]
  }
}
```

#### 3. Get Specific Goal
```
GET /api/learning-goals/{id}
```

**Response:** Returns detailed goal information including:
- Complete progress history
- All milestones with achievement status
- Related courses and skills
- On-track analysis

#### 4. Track Progress
```
POST /api/learning-goals/{id}/progress
```

**Description:** Update progress towards a goal. Automatically checks for milestone achievements and goal completion.

**Request Body:**
```json
{
  "progress_value": 2,
  "note": "Completed React Hooks course"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "goal_id": 1,
    "current_value": "2.00",
    "target_value": "5.00",
    "percentage": 40,
    "status": "active",
    "milestone_achieved": {
      "id": 2,
      "title": "Build first React app",
      "congratulations": "🎉 Congratulations! You've achieved a milestone!",
      "sequence_order": 2
    }
  },
  "message": "Progress updated successfully"
}
```

**Automatic Features:**
- Milestone achievement detection
- Goal completion (status changes to "achieved")
- Progress history tracking
- On-track calculation

#### 5. Update Goal
```
PUT /api/learning-goals/{id}
```

**Request Body:** (All fields optional)
```json
{
  "title": "Master React & Next.js",
  "target_date": "2026-08-01",
  "status": "on_hold"
}
```

#### 6. Delete Goal
```
DELETE /api/learning-goals/{id}
```

---

### Learning Profile API

#### 1. Get Learning Profile
```
GET /api/learning-profile
```

**Description:** Get complete learning profile including VARK learning style, preferences, and recommendations.

**Response:**
```json
{
  "success": true,
  "data": {
    "user_id": 1,
    "learning_style": {
      "dominant_style": "Visual",
      "scores": {
        "visual": 62.5,
        "auditory": 18.75,
        "reading": 12.5,
        "kinesthetic": 6.25
      },
      "description": "You learn best through visual aids like diagrams, charts, and videos.",
      "recommendations": [
        "Use mind maps and flowcharts",
        "Watch video tutorials",
        "Use color coding in notes",
        "Create visual summaries"
      ],
      "last_assessment_date": "2025-12-18T10:00:00Z"
    },
    "preferences": {
      "preferred_content_type": "video",
      "preferred_lesson_length": "short",
      "learning_pace": "fast",
      "preferred_study_time": "morning",
      "daily_study_goal_minutes": 60,
      "likes_gamification": true,
      "likes_group_learning": false,
      "likes_challenges": true
    }
  }
}
```

**VARK Learning Styles:**
- **Visual (V)**: Learn through seeing - diagrams, charts, videos
- **Auditory (A)**: Learn through hearing - discussions, podcasts
- **Reading/Writing (R)**: Learn through text - articles, notes
- **Kinesthetic (K)**: Learn through doing - hands-on practice

#### 2. Submit VARK Assessment
```
POST /api/learning-profile/assessment
```

**Description:** Submit answers to the 16-question VARK learning style assessment.

**Request Body:**
```json
{
  "answers": ["V", "V", "A", "V", "K", "V", "R", "V", "A", "V", "V", "K", "V", "R", "V", "V"]
}
```

**Answer Options:** Each answer must be one of:
- `V` - Visual
- `A` - Auditory  
- `R` - Reading/Writing
- `K` - Kinesthetic

**Response:**
```json
{
  "success": true,
  "data": {
    "learning_style": {
      "dominant_style": "Visual",
      "scores": {
        "visual": 62.5,
        "auditory": 12.5,
        "reading": 12.5,
        "kinesthetic": 12.5
      },
      "recommendations": [
        "Focus on video-based learning materials",
        "Use visual note-taking techniques",
        "Create mind maps for complex topics"
      ]
    }
  },
  "message": "Learning style assessment completed successfully"
}
```

#### 3. Update Preferences
```
PUT /api/learning-profile/preferences
```

**Request Body:**
```json
{
  "preferred_content_type": "video",
  "preferred_lesson_length": "short",
  "learning_pace": "fast",
  "preferred_study_time": "morning",
  "daily_study_goal_minutes": 60,
  "likes_gamification": true,
  "likes_group_learning": false,
  "likes_challenges": true
}
```

**Valid Options:**
- `preferred_content_type`: `video`, `text`, `audio`, `interactive`, `mixed`
- `preferred_lesson_length`: `short` (<15 min), `medium` (15-30 min), `long` (>30 min)
- `learning_pace`: `slow`, `medium`, `fast`
- `preferred_study_time`: `morning`, `afternoon`, `evening`, `night`, `flexible`

#### 4. Get Personalized Feed
```
GET /api/personalized-feed
```

**Description:** Get personalized course recommendations based on learning profile, goals, and progress.

**Response:**
```json
{
  "success": true,
  "data": {
    "recommended_courses": [
      {
        "id": 5,
        "title": "Advanced React Patterns",
        "reason": "Matches your active goal: Master React Development",
        "match_score": 95,
        "estimated_duration": "4 hours"
      }
    ],
    "continue_learning": [
      {
        "course_id": 2,
        "course_title": "React Fundamentals",
        "lesson_id": 15,
        "lesson_title": "useState Hook",
        "progress_percentage": 65
      }
    ],
    "suggested_by_style": [
      {
        "id": 8,
        "title": "Visual Design Principles",
        "reason": "Recommended for Visual learners",
        "content_type": "video"
      }
    ]
  }
}
```

---

### Bookmarks API

#### 1. Toggle Bookmark
```
POST /api/bookmarks/{type}/{id}
```

**Description:** Create or remove a bookmark (toggle behavior).

**Parameters:**
- `type` - Resource type: `lesson`, `course`, or `module`
- `id` - Resource ID

**Request Body:** (optional)
```json
{
  "note": "Important concept to review before exam"
}
```

**Response (Created):**
```json
{
  "success": true,
  "bookmarked": true,
  "data": {
    "id": 1,
    "user_id": 1,
    "bookmarkable_type": "lesson",
    "bookmarkable_id": 5,
    "note": "Important concept to review before exam",
    "created_at": "2025-12-18T10:30:00Z"
  },
  "message": "Bookmark created successfully"
}
```

**Response (Removed):**
```json
{
  "success": true,
  "bookmarked": false,
  "message": "Bookmark removed successfully"
}
```

#### 2. Get All Bookmarks
```
GET /api/bookmarks?type=lesson
```

**Query Parameters:**
- `type` - Filter by type: `lesson`, `course`, `module`

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "type": "lesson",
      "resource": {
        "id": 5,
        "title": "React Hooks Deep Dive",
        "course": "React Fundamentals",
        "module": "Advanced Concepts"
      },
      "note": "Important concept to review",
      "bookmarked_at": "2025-12-18T10:30:00Z"
    },
    {
      "id": 2,
      "type": "course",
      "resource": {
        "id": 3,
        "title": "JavaScript Mastery",
        "description": "Complete JavaScript course"
      },
      "note": "Great course recommended by mentor",
      "bookmarked_at": "2025-12-17T15:20:00Z"
    }
  ],
  "total": 2
}
```

#### 3. Check Bookmark Status
```
GET /api/bookmarks/check/{type}/{id}
```

**Description:** Check if a resource is bookmarked.

**Response:**
```json
{
  "success": true,
  "bookmarked": true,
  "bookmark": {
    "id": 1,
    "note": "Important concept",
    "created_at": "2025-12-18T10:30:00Z"
  }
}
```

#### 4. Update Bookmark Note
```
PUT /api/bookmarks/{id}
```

**Request Body:**
```json
{
  "note": "Updated note - review before practical exam"
}
```

#### 5. Delete Bookmark
```
DELETE /api/bookmarks/{id}
```

**Response:**
```json
{
  "success": true,
  "message": "Bookmark deleted successfully"
}
```

---

## Course Management API

### 1. Course with Navigation
```
GET /api/courses/{course_id}/with-navigation
```

**Description:** Returns complete course information including modules, lessons, and navigation info (next/prev lesson) for each lesson.

**Response Example:**
```json
{
  "data": {
    "id": 1,
    "title": "Web Development Fundamentals",
    "slug": "web-dev-fundamentals",
    "description": "Complete course on web development",
    "modules": [
      {
        "id": 1,
        "title": "Introduction to HTML",
        "lessons": [
          {
            "id": 1,
            "title": "HTML Basics",
            "slug": "html-basics",
            "epub": {
              "id": 1,
              "title": "HTML Basics Guide",
              "file_path": "uploads/epubs/html-basics.epub",
              "file_info": {
                "download_url": "http://localhost/storage/uploads/epubs/html-basics.epub",
                "file_hash": "d41d8cd98f00b204e9800998ecf8427e",
                "last_modified": 1698765432
              }
            },
            "navigation": {
              "next_lesson": {
                "id": 2,
                "title": "HTML Tags",
                "slug": "html-tags",
                "module_id": 1
              },
              "prev_lesson": null
            }
          }
        ]
      }
    ]
  }
}
```

### 2. Lesson EPUB Information
```
GET /api/courses/{course_id}/modules/{module_id}/lessons/{lesson_id}/epub-info
```

**Description:** Get detailed EPUB information for a specific lesson, including file validation data.

**Response Example:**
```json
{
  "data": {
    "lesson": {
      "id": 1,
      "title": "HTML Basics",
      "slug": "html-basics"
    },
    "epub_info": {
      "id": 1,
      "title": "HTML Basics Guide",
      "file_path": "uploads/epubs/html-basics.epub",
      "original_filename": "html-basics-v1.2.epub",
      "file_size": 2048576,
      "mime_type": "application/epub+zip",
      "validation": {
        "file_exists": true,
        "file_hash": "d41d8cd98f00b204e9800998ecf8427e",
        "file_size_bytes": 2048576,
        "last_modified": 1698765432,
        "download_url": "http://localhost/storage/uploads/epubs/html-basics.epub",
        "version_check": {
          "db_updated_at": 1698765400,
          "file_modified_at": 1698765432,
          "is_latest": true
        }
      }
    },
    "navigation": {
      "next_lesson": {
        "id": 2,
        "title": "HTML Tags",
        "slug": "html-tags",
        "module_id": 1
      },
      "prev_lesson": null
    }
  }
}
```

### 3. EPUB Version Check
```
POST /api/courses/{course_id}/modules/{module_id}/lessons/{lesson_id}/epub-version-check
```

**Description:** Check if the client's local EPUB file is up-to-date compared to the server version.

**Request Body:**
```json
{
  "client_file_size": 2048576,
  "client_file_hash": "d41d8cd98f00b204e9800998ecf8427e",
  "client_last_modified": 1698765400
}
```

**Response Example:**
```json
{
  "needs_download": false,
  "server_file_info": {
    "exists": true,
    "size": 2048576,
    "hash": "d41d8cd98f00b204e9800998ecf8427e",
    "last_modified": 1698765432,
    "download_url": "http://localhost/storage/uploads/epubs/html-basics.epub"
  },
  "client_file_info": {
    "size": 2048576,
    "hash": "d41d8cd98f00b204e9800998ecf8427e",
    "last_modified": 1698765400
  },
  "epub_info": {
    "id": 1,
    "title": "HTML Basics Guide",
    "filename": "html-basics-v1.2.epub"
  }
}
```

## Usage Examples

### Frontend Implementation

```javascript
// 1. Load course with navigation
async function loadCourseWithNavigation(courseId) {
  const response = await fetch(`/api/courses/${courseId}/with-navigation`, {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json'
    }
  });
  
  const courseData = await response.json();
  
  // Now you have complete course structure with navigation
  courseData.data.modules.forEach(module => {
    module.lessons.forEach(lesson => {
      console.log(`Lesson: ${lesson.title}`);
      console.log(`Next: ${lesson.navigation.next_lesson?.title || 'None'}`);
      console.log(`Prev: ${lesson.navigation.prev_lesson?.title || 'None'}`);
    });
  });
}

// 2. Check and download EPUB if needed
async function checkAndDownloadEpub(courseId, moduleId, lessonId) {
  // First, get EPUB info
  const epubInfoResponse = await fetch(
    `/api/courses/${courseId}/modules/${moduleId}/lessons/${lessonId}/epub-info`,
    {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
      }
    }
  );
  
  const epubInfo = await epubInfoResponse.json();
  
  if (!epubInfo.data.epub_info) {
    console.log('No EPUB available for this lesson');
    return;
  }
  
  // Check local file (example using local storage for metadata)
  const localEpubData = localStorage.getItem(`epub_${lessonId}`);
  const localEpub = localEpubData ? JSON.parse(localEpubData) : null;
  
  // Check if we need to download
  const versionCheckResponse = await fetch(
    `/api/courses/${courseId}/modules/${moduleId}/lessons/${lessonId}/epub-version-check`,
    {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        client_file_size: localEpub?.file_size || 0,
        client_file_hash: localEpub?.file_hash || '',
        client_last_modified: localEpub?.last_modified || 0
      })
    }
  );
  
  const versionCheck = await versionCheckResponse.json();
  
  if (versionCheck.needs_download) {
    console.log('Downloading updated EPUB...');
    
    // Download the file
    const downloadResponse = await fetch(versionCheck.server_file_info.download_url);
    const epubBlob = await downloadResponse.blob();
    
    // Save to local storage or IndexedDB
    // Update local metadata
    localStorage.setItem(`epub_${lessonId}`, JSON.stringify({
      file_size: versionCheck.server_file_info.size,
      file_hash: versionCheck.server_file_info.hash,
      last_modified: versionCheck.server_file_info.last_modified,
      title: epubInfo.data.epub_info.title
    }));
    
    console.log('EPUB updated successfully');
  } else {
    console.log('Local EPUB is up-to-date');
  }
}

// 3. Navigate between lessons
function navigateToLesson(lessonData) {
  const { navigation } = lessonData;
  
  // Enable/disable navigation buttons
  const nextButton = document.getElementById('next-lesson-btn');
  const prevButton = document.getElementById('prev-lesson-btn');
  
  if (navigation.next_lesson) {
    nextButton.disabled = false;
    nextButton.onclick = () => {
      loadLesson(
        navigation.next_lesson.module_id,
        navigation.next_lesson.id
      );
    };
  } else {
    nextButton.disabled = true;
  }
  
  if (navigation.prev_lesson) {
    prevButton.disabled = false;
    prevButton.onclick = () => {
      loadLesson(
        navigation.prev_lesson.module_id,
        navigation.prev_lesson.id
      );
    };
  } else {
    prevButton.disabled = true;
  }
}
```

## Implementation Notes

### File Validation Strategy
1. **Hash Comparison**: Uses MD5 hash to detect file changes
2. **Size Comparison**: Quick size check for basic validation
3. **Timestamp Comparison**: Check last modified time
4. **Existence Check**: Verify file still exists on server

### Caching Strategy
- Course with navigation: Cached for 1 hour
- EPUB info: Cached for 30 minutes
- File hashes: Computed on-demand (consider caching for production)
- **ETag Support**: All GET endpoints support conditional requests
  - Send `If-None-Match` header with previous ETag value
  - Server returns 304 Not Modified if content unchanged
  - Saves bandwidth and improves client-side performance

### Security Considerations
- All endpoints require authentication
- File paths are validated to prevent directory traversal
- EPUB files are served through Laravel's storage system

### Performance Optimization
- Eager loading of relationships to reduce database queries
- Caching of computed navigation data
- Lazy loading of file hashes when needed

## Database Changes Required

The enhanced EPUB table includes these new fields:
- `original_filename`: Store the original uploaded filename
- `file_size`: File size in bytes for validation
- `mime_type`: File MIME type
- `position`: Order within lesson (for multiple EPUBs)
- `is_active`: Enable/disable EPUB without deletion

Run the migration to update your database:
```bash
docker-compose exec app1 php artisan migrate
```

## Monitoring and Troubleshooting

### Check Active Optimizations

**Verify HTTP/2 is enabled:**
```bash
docker-compose exec nginx-lb cat /etc/nginx/nginx.conf | grep "listen.*http2"
# Should show: listen 80 http2;
```

**Verify database indexes:**
```bash
docker-compose exec mysql mysql -u root -proot achance_db -e "SHOW INDEX FROM courses WHERE Key_name LIKE 'idx_%';"
```

**Verify PHP-FPM workers:**
```bash
docker-compose exec app1 cat /usr/local/etc/php-fpm.d/www.conf | grep "pm.max_children"
# Should show: pm.max_children = 50
```

**Test Redis connection:**
```bash
docker-compose exec redis-master redis-cli PING
# Should return: PONG
```

**Check ETag middleware:**
```bash
curl -I -H "Authorization: Bearer TOKEN" http://localhost/api/courses
# Should include: ETag: "hash_value"
```

### Performance Testing

**Measure response time:**
```bash
Measure-Command { Invoke-WebRequest -Uri "http://localhost/api/courses" -Headers @{"Authorization"="Bearer TOKEN"} }
```

**Test concurrent requests:**
```bash
# Use Apache Bench
ab -n 100 -c 10 -H "Authorization: Bearer TOKEN" http://localhost/api/courses
```

**Monitor cache hit rate:**
```bash
docker-compose exec redis-master redis-cli INFO stats | grep keyspace
```

### Response Headers to Monitor

- `ETag`: Cache validation token
- `X-Cache-Status`: HIT/MISS/BYPASS from nginx cache
- `X-Response-Time`: Server processing time (if configured)
- `Content-Length`: Response size (compare with/without ETag)

### Troubleshooting Common Issues

**Slow response times:**
1. Check if indexes are created: `SHOW INDEX FROM courses`
2. Verify Redis is running: `docker-compose ps redis-master`
3. Check PHP-FPM worker count: `docker-compose exec app1 cat /usr/local/etc/php-fpm.d/www.conf | grep max_children`

**ETag not working:**
1. Ensure middleware is registered in `app/Http/Kernel.php`
2. Check response includes `ETag` header
3. Verify client sends `If-None-Match` on subsequent requests

**HTTP/2 not active:**
1. Rebuild nginx container: `docker-compose build nginx-lb`
2. Restart nginx: `docker-compose restart nginx-lb`
3. Verify config: `docker-compose exec nginx-lb nginx -T | grep http2`