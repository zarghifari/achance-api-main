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
   - HTML/JSON content management and validation
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

## Content Management API

### 1. Import Document and Convert to HTML
```
POST /api/courses/{course_id}/modules/{module_id}/lessons/{lesson_id}/content/import
```

**Description:** Upload and convert a document (.doc, .docx, .html) to HTML/JSON format with automatic asset extraction and ZIP bundling.

**Request:** `multipart/form-data`
```
file: sample.docx (required)
title: "Introduction to React" (optional - auto-generated from filename if not provided)
description: "Learn React basics" (optional)
words_per_page: 500 (optional - default: 500)
```

**Response (201 Created):**
```json
{
  "message": "Content imported successfully",
  "data": {
    "id": 5,
    "lesson_id": 15,
    "title": "Introduction to React",
    "description": "Learn React basics",
    "type": "html",
    "original_filename": "sample.docx",
    "source_file_path": "uploads/contents/source/sample_1704384000.docx",
    "source_file_size": 1497022,
    "zip_file_path": "uploads/contents/archives/sample_1704384000.zip",
    "zip_file_size": 856432,
    "total_pages": 12,
    "words_per_page": 500,
    "metadata": {
      "word_count": 5842,
      "estimated_reading_time_minutes": 23,
      "has_images": true,
      "has_videos": false,
      "image_count": 8
    },
    "created_at": "2026-01-04T10:30:00Z",
    "updated_at": "2026-01-04T10:30:00Z"
  }
}
```

### 2. Get Content for Lesson
```
GET /api/courses/{course_id}/modules/{module_id}/lessons/{lesson_id}/content
```

**Description:** Get complete HTML content with all pages and metadata.

**Response (200 OK):**
```json
{
  "data": {
    "id": 5,
    "lesson_id": 15,
    "title": "Introduction to React",
    "type": "html",
    "total_pages": 12,
    "html_content": "<h1>Introduction to React</h1><p>React is...</p>",
    "metadata": {
      "word_count": 5842,
      "estimated_reading_time_minutes": 23
    }
  }
}
```

### 3. Get Content Metadata Only
```
GET /api/courses/{course_id}/modules/{module_id}/lessons/{lesson_id}/content/metadata
```

**Description:** Get lightweight metadata without full HTML content (faster for list views).

**Response (200 OK):**
```json
{
  "data": {
    "id": 5,
    "lesson_id": 15,
    "title": "Introduction to React",
    "description": "Learn React basics",
    "type": "html",
    "total_pages": 12,
    "metadata": {
      "word_count": 5842,
      "estimated_reading_time_minutes": 23,
      "has_images": true
    },
    "created_at": "2026-01-04T10:30:00Z",
    "updated_at": "2026-01-04T10:30:00Z"
  }
}
```

### 4. Get Specific Page
```
GET /api/courses/{course_id}/modules/{module_id}/lessons/{lesson_id}/content/page/{page}
```

**Description:** Get a single page of content (for lazy loading).

**Parameters:**
- `page` - Page number (1-based)

**Response (200 OK):**
```json
{
  "data": {
    "page_number": 1,
    "total_pages": 12,
    "content": "<h1>Introduction to React</h1><p>Page 1 content...</p>",
    "has_next": true,
    "has_previous": false
  }
}
```

### 5. Download ZIP Archive
```
GET /api/courses/{course_id}/modules/{module_id}/lessons/{lesson_id}/content/{content_id}/download-zip
```

**Description:** Download the complete content bundle as a ZIP file.

**Response:** Binary file download (application/zip)

**ZIP Contents:**
- `content.html` - Processed HTML with updated paths
- `metadata.json` - Document metadata and structure
- `images/` - All extracted images
- `assets/video/` - Video files
- `assets/audio/` - Audio files  
- `assets/document/` - PDF, Word, Excel files

### 6. Update Content
```
PUT /api/courses/{course_id}/modules/{module_id}/lessons/{lesson_id}/content/{content_id}
```

**Description:** Update content title or description (requires 'create courses' permission).

**Request Body:**
```json
{
  "title": "Updated Content Title",
  "description": "Updated description"
}
```

**Response (200 OK):**
```json
{
  "message": "Content updated successfully",
  "data": {
    "id": 5,
    "title": "Updated Content Title",
    "description": "Updated description",
    "updated_at": "2026-01-04T11:00:00Z"
  }
}
```

### 7. Reprocess Content
```
POST /api/courses/{course_id}/modules/{module_id}/lessons/{lesson_id}/content/{content_id}/reprocess
```

**Description:** Reprocess content with different pagination settings (requires 'create courses' permission).

**Request Body:**
```json
{
  "words_per_page": 600
}
```

**Response (200 OK):**
```json
{
  "message": "Content reprocessed successfully",
  "data": {
    "id": 5,
    "total_pages": 10,
    "words_per_page": 600,
    "updated_at": "2026-01-04T11:05:00Z"
  }
}
```

### 8. Delete Content
```
DELETE /api/courses/{course_id}/modules/{module_id}/lessons/{lesson_id}/content/{content_id}
```

**Description:** Permanently delete content and all associated files (requires 'create courses' permission).

**What Gets Deleted:**
- ✅ Source file (.docx, .html)
- ✅ ZIP archive
- ✅ Uploaded images
- ✅ Processed assets
- ✅ Database record
- ✅ Redis cache entries

**Response (200 OK):**
```json
{
  "message": "Content deleted successfully"
}
```

**Error Responses:**
- `404 Not Found` - Content doesn't exist
- `403 Forbidden` - No permission to delete
- `422 Unprocessable` - Content ID doesn't match lesson

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
            "content": {
              "id": 1,
              "title": "HTML Basics Guide",
              "source_file_path": "uploads/contents/source/html-basics.docx",
              "file_info": {
                "download_url": "http://localhost/storage/uploads/contents/archives/html-basics.zip",
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

### 2. Lesson Content Information
```
GET /api/courses/{course_id}/modules/{module_id}/lessons/{lesson_id}/content/metadata
```

**Description:** Get detailed content information for a specific lesson, including file validation data.

**Response Example:**
```json
{
  "data": {
    "lesson": {
      "id": 1,
      "title": "HTML Basics",
      "slug": "html-basics"
    },
    "content_info": {
      "id": 1,
      "title": "HTML Basics Guide",
      "source_file_path": "uploads/contents/source/html-basics.docx",
      "original_filename": "html-basics-v1.2.docx",
      "source_file_size": 1497022,
      "type": "html",
      "validation": {
        "file_exists": true,
        "file_hash": "d41d8cd98f00b204e9800998ecf8427e",
        "file_size_bytes": 1497022,
        "last_modified": 1698765432,
        "download_url": "http://localhost/storage/uploads/contents/archives/html-basics.zip",
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

### 3. Content Version Check
```
POST /api/courses/{course_id}/modules/{module_id}/lessons/{lesson_id}/content/version-check
```

**Description:** Check if the client's local content file is up-to-date compared to the server version.

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
    "size": 1497022,
    "hash": "d41d8cd98f00b204e9800998ecf8427e",
    "last_modified": 1698765432,
    "download_url": "http://localhost/storage/uploads/contents/archives/html-basics.zip"
  },
  "client_file_info": {
    "size": 1497022,
    "hash": "d41d8cd98f00b204e9800998ecf8427e",
    "last_modified": 1698765400
  },
  "content_info": {
    "id": 1,
    "title": "HTML Basics Guide",
    "filename": "html-basics-v1.2.docx"
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

// 2. Check and download Content if needed
async function checkAndDownloadContent(courseId, moduleId, lessonId) {
  // First, get content info
  const contentInfoResponse = await fetch(
    `/api/courses/${courseId}/modules/${moduleId}/lessons/${lessonId}/content/metadata`,
    {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
      }
    }
  );
  
  const contentInfo = await contentInfoResponse.json();
  
  if (!contentInfo.data.content_info) {
    console.log('No content available for this lesson');
    return;
  }
  
  // Check local file (example using local storage for metadata)
  const localContentData = localStorage.getItem(`content_${lessonId}`);
  const localContent = localContentData ? JSON.parse(localContentData) : null;
  
  // Check if we need to download
  const versionCheckResponse = await fetch(
    `/api/courses/${courseId}/modules/${moduleId}/lessons/${lessonId}/content/version-check`,
    {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        client_file_size: localContent?.file_size || 0,
        client_file_hash: localContent?.file_hash || '',
        client_last_modified: localContent?.last_modified || 0
      })
    }
  );
  
  const versionCheck = await versionCheckResponse.json();
  
  if (versionCheck.needs_download) {
    console.log('Downloading updated content...');
    
    // Download the file
    const downloadResponse = await fetch(versionCheck.server_file_info.download_url);
    const contentBlob = await downloadResponse.blob();
    
    // Save to local storage or IndexedDB
    // Update local metadata
    localStorage.setItem(`content_${lessonId}`, JSON.stringify({
      file_size: versionCheck.server_file_info.size,
      file_hash: versionCheck.server_file_info.hash,
      last_modified: versionCheck.server_file_info.last_modified,
      title: contentInfo.data.content_info.title
    }));
    
    console.log('Content updated successfully');
  } else {
    console.log('Local content is up-to-date');
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

### Content System Architecture

**Document Processing Pipeline:**
1. **Upload & Validation**: Accept .doc/.docx/HTML files (max 50MB)
2. **Conversion**: PHPWord library converts to clean HTML
3. **Asset Discovery**: Scan HTML for all embedded resources
4. **Asset Extraction**: Download and process:
   - Images (base64, external URLs, local files)
   - Videos (MP4, WEBM, OGG, AVI, MOV)
   - Audio (MP3, OGG, WAV, AAC, FLAC)
   - Documents (PDF, Word, Excel, PowerPoint)
   - Embedded content (via `<embed>`, `<object>`, `<iframe>`)
5. **YouTube/Vimeo Detection**: Convert links to responsive iframe embeds
6. **Path Rewriting**: Update all URLs to point to extracted assets
7. **Pagination**: Split content into pages (default 500 words/page)
8. **ZIP Creation**: Bundle everything (excludes large source files for efficiency)
9. **Database Storage**: Save metadata, HTML, JSON, and file paths

**Storage Structure:**
```
storage/app/public/uploads/contents/
├── source/              # Original .docx/.html files
│   └── sample_1704384000.docx
├── images/              # Extracted images  
│   └── lesson_15/
│       ├── image1.jpg
│       └── image2.png
└── archives/            # ZIP bundles (no source files)
    └── sample_1704384000.zip
        ├── content.html
        ├── metadata.json
        ├── images/
        └── assets/
```

**File Naming Convention:**
- Original: `{filename}_{timestamp}.{ext}`
- ZIP: `{filename}_{timestamp}.zip`
- Images: Preserved or `image_{n}.{ext}`

### Pagination Strategy
- Default: 500 words per page
- Customizable via `words_per_page` parameter
- Preserves HTML structure (doesn't split mid-tag)
- Stores paginated content as JSON array

### Performance Considerations

**Content Processing:**
- **Server-side Pre-processing**: 10-50x faster than client-side rendering
- **Import Time**: 2-8 seconds depending on file size and assets
- **Asset Downloads**: Parallel processing (max 30s timeout per file)
- **ZIP Compression**: 30-50% space savings, excludes source files

**API Response Times:**
- `GET /content/metadata`: 50-100ms (lightweight, no HTML)
- `GET /content`: 100-300ms (full HTML)
- `GET /content/page/{page}`: 50-150ms (single page)
- `POST /content/import`: 2-8s (conversion + asset extraction)
- `GET /download-zip`: 200-500ms (direct file stream)
- `DELETE /content`: 200-400ms (file cleanup + cache clear)

**Optimization Strategies:**
- ✅ Use `/content/metadata` for course listings
- ✅ Use `/content/page/{page}` for progressive loading
- ✅ Cache ZIP downloads on client side
- ✅ ETag support reduces bandwidth by 50-80%
- ✅ Redis caching (15-30 min TTL)

**Caching Layers:**
1. **Redis** (application cache):
   - Content metadata: 30 minutes
   - Full content: 15 minutes
   - Individual pages: 15 minutes
2. **ETag** (HTTP cache):
   - Client sends `If-None-Match` header
   - Server returns 304 if unchanged
   - Saves bandwidth and latency

**Cache Invalidation:**
- Content update/reprocess → clears content cache
- Content delete → clears all related caches
- Manual: `Cache::forget("content_{lesson_id}")`

### Caching Strategy
- Course with navigation: Cached for 1 hour
- Content metadata: Cached for 30 minutes
- Full content: Cached for 15 minutes
- **ETag Support**: All GET endpoints support conditional requests
  - Send `If-None-Match` header with previous ETag value
  - Server returns 304 Not Modified if content unchanged
  - Saves bandwidth and improves client-side performance

### Security Considerations

**Authentication & Authorization:**
- ✅ All endpoints require `Authorization: Bearer {token}` header
- ✅ Teachers (with 'create courses' permission) can:
  - Import/upload content
  - Update content metadata
  - Reprocess content
  - Delete content
- ✅ Students can only:
  - View content (read-only)
  - Download ZIP archives
  - Track their own activities

**File Upload Security:**
- ✅ Whitelist: Only `.doc`, `.docx`, `.html`, `.htm` allowed
- ✅ Max upload size: 50MB (configurable)
- ✅ MIME type validation
- ✅ Filename sanitization (removes special chars)
- ✅ Unique timestamps prevent overwrites

**Asset Download Security:**
- ✅ Max file size per asset: 100MB
- ✅ Download timeout: 30 seconds
- ✅ External services whitelisted:
  - YouTube (youtube.com, youtu.be)
  - Vimeo (vimeo.com)
  - Google Drive (kept as links, not downloaded)
- ✅ Failed downloads keep original URLs

**Content Sanitization:**
- ✅ Script tags (`<script>`) removed from HTML
- ✅ Dangerous attributes stripped (`onclick`, `onerror`)
- ✅ Path traversal prevention (`../../` blocked)
- ✅ SQL injection protection (prepared statements)
- ✅ XSS prevention (escaped output)

**File System Security:**
- ✅ Storage outside web root
- ✅ No direct file access (served via controller)
- ✅ Proper permissions (644 files, 755 directories)
- ✅ Isolated per-lesson folders

**Rate Limiting:**
- Import: 5 requests/minute per user
- Download: 20 requests/minute per user
- API calls: 60 requests/minute per user

### Attachment Download Limits
- **Max File Size**: 100MB per attachment
- **Download Timeout**: 30 seconds per file
- **External Services**: YouTube, Vimeo, Google Drive links NOT downloaded (kept as external links)
- **Retry Logic**: Failed downloads keep original URLs in HTML

## Database Changes Required

The HTML Content system uses a new `contents` table:

```sql
CREATE TABLE contents (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    lesson_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    type VARCHAR(50) NOT NULL DEFAULT 'html',
    original_filename VARCHAR(500),
    source_file_path VARCHAR(500),
    source_file_size BIGINT,
    zip_file_path VARCHAR(500),
    zip_file_size BIGINT,
    html_content LONGTEXT,
    json_content JSON,
    paginated_content JSON,
    images JSON,
    assets JSON,
    metadata JSON,
    total_pages INT DEFAULT 0,
    words_per_page INT DEFAULT 500,
    is_processed BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE,
    INDEX idx_lesson_id (lesson_id),
    INDEX idx_processed (is_processed),
    INDEX idx_active (is_active)
);
```

Run the migrations to update your database:
```bash
docker-compose exec app1 php artisan migrate
```

**Migration includes:**
- Creating `contents` table
- Adding ZIP file columns
- Updating lesson relationships
- Removing legacy dependencies

## Testing with Postman

### Postman Collection Features

The included Postman collection (`src/tests/postman/Course_System_API_Tests.postman_collection.json`) provides:

**Automated Test Flow:**
1. ✅ Authentication (Teacher/Student login)
2. ✅ Course creation and management
3. ✅ Module and lesson setup
4. ✅ Content import and validation
5. ✅ User activity tracking
6. ✅ Cleanup operations

**Built-in Validation:**
- Automatic ID fallback to seeded data
- Pre-request validation checks
- Skip logic for missing resources
- Environment variable management

**Running Tests:**

1. **Full Test Suite** (Recommended):
   ```
   Postman → Collections → Course System API Tests
   → Click "Run" button
   → Run entire collection
   ```
   Creates all resources in sequence, then tests and cleans up.

2. **Individual Requests**:
   - Set environment variables manually:
     ```
     courseId = 1
     moduleId = 1
     lessonId = 1
     contentId = 1
     ```
   - Or tests will auto-fallback to seeded data

3. **Console Monitoring**:
   ```
   View → Show Postman Console
   ```
   Watch for:
   - ✓ Validation messages
   - ⚠ Fallback warnings
   - ✗ Error details

**Common Issues:**

❌ **404 Not Found on DELETE**
- Cause: Content ID doesn't exist
- Solution: Run import test first or use seeded IDs

❌ **403 Forbidden**
- Cause: Wrong token (student trying teacher action)
- Solution: Check `authToken` is set to `teacherToken`

❌ **422 Validation Error**
- Cause: Missing required fields
- Solution: Check request body matches schema

**Environment Setup:**
```json
{
  "baseUrl": "http://localhost/api",
  "teacherToken": "[auto-set after login]",
  "studentToken": "[auto-set after login]",
  "authToken": "[switches between teacher/student]",
  "courseId": "[created during tests]",
  "moduleId": "[created during tests]",
  "lessonId": "[created during tests]",
  "contentId": "[created during tests]"
}
```

**Validation Messages:**
- ✓ Green: Success
- ⚠ Yellow: Warning (fallback used)
- ✗ Red: Error

See [POSTMAN_TESTING_GUIDE.md](./POSTMAN_TESTING_GUIDE.md) for detailed instructions.

---

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