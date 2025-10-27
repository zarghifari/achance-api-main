# AChance API - Enhanced Course and EPUB APIs

## Overview

I've enhanced the API to provide:

1. **Course API with complete navigation** - Get course with all modules, lessons, and next/prev navigation info
2. **Lesson EPUB validation API** - Check EPUB file info and validate if local files are up-to-date

## New API Endpoints

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