# Attachment Access Summary

## ✅ ZIP Feature Removed

All ZIP conversion and creation features have been completely removed from the system:

### Files Updated:
1. **DocumentConverterService.php** - Removed ZIP archive creation methods
2. **ContentController.php** - Removed `downloadZip()` method and ZIP cleanup
3. **Content.php Model** - Removed `zip_file_path` and `zip_file_size` fields
4. **ContentSeeder.php** - Removed `createZipForContent()` method
5. **routes/api.php** - Removed ZIP download routes
6. **Migration** - Created `2026_01_06_000002_remove_zip_fields_from_contents.php`

## 📎 How Attachments Work Now

### Document Conversion (.doc/.docx to HTML)
The system still converts documents to HTML with full attachment support:

```php
// Document Import Flow:
1. Upload .doc/.docx file via API
2. PhpWord converts to HTML
3. Extract images and assets
4. Process and optimize content
5. Store in database (no ZIP created)
```

### Attachment Types Supported:
- ✅ **Images** (PNG, JPG, GIF, etc.)
- ✅ **Videos** (embedded YouTube, Vimeo, etc.)
- ✅ **Audio files**
- ✅ **PDFs and documents**
- ✅ **Other media assets**

## 🌐 How to Access Attachments from HTML

### Method 1: Via API (For Mobile Apps)
Use the dedicated file serving endpoint:

```
GET /api/courses/{course_id}/modules/{module_id}/lessons/{lesson_id}/content/files/{filepath}
```

**Example:**
```javascript
// Image in HTML
<img src="/storage/uploads/contents/images/image_123.png">

// Access via API
GET /api/courses/1/modules/2/lessons/3/content/files/uploads/contents/images/image_123.png
```

**Features:**
- ✅ Authorization check required
- ✅ Serves correct MIME type
- ✅ 1-year browser cache for performance
- ✅ Security: prevents directory traversal attacks

### Method 2: Via Web Interface (Direct Storage Access)
The web interface uses Laravel's storage symlink:

```php
// In blade templates, images are automatically accessible:
<img src="/storage/uploads/contents/images/image_123.png">
```

**Storage Path Resolution:**
```
HTML Path: /storage/uploads/contents/images/image_123.png
Actual Path: storage/app/public/uploads/contents/images/image_123.png
Public Access: http://yoursite.com/storage/uploads/contents/images/image_123.png
```

## 📁 Storage Structure

```
storage/app/public/
├── uploads/
│   └── contents/
│       ├── source/         # Original .doc/.docx files
│       ├── images/         # Extracted/processed images
│       │   ├── image_*.png
│       │   └── image_*.jpg
│       └── assets/         # Other attachments (PDFs, videos, etc.)
│           ├── document_*.pdf
│           └── audio_*.mp3
```

## 🔧 Setup Requirements

### 1. Create Storage Symlink (if not exists)
```bash
cd src
php artisan storage:link
```

This creates: `public/storage` → `storage/app/public`

### 2. Run New Migration
```bash
php artisan migrate
```

This removes old `zip_file_path` and `zip_file_size` columns from the `contents` table.

### 3. Set Proper Permissions
```bash
# Linux/Mac
chmod -R 755 storage/app/public/uploads

# Windows (PowerShell as Admin)
icacls storage\app\public\uploads /grant Users:F /T
```

## 📋 API Usage Examples

### Upload Document with Attachments
```http
POST /api/courses/{course_id}/modules/{module_id}/lessons/{lesson_id}/content/import
Content-Type: multipart/form-data

file: sample.docx
title: "My Lesson Content"
description: "Lesson with images and attachments"
words_per_page: 500
```

**Response:**
```json
{
  "message": "Document imported and processed successfully",
  "data": {
    "id": 1,
    "title": "My Lesson Content",
    "html_content": "<html>... with processed images ...</html>",
    "images": [
      {
        "original_src": "data:image/png;base64,...",
        "processed_path": "storage/uploads/contents/images/image_abc123.png",
        "type": "uploaded",
        "size": 45678
      }
    ],
    "assets": [
      {
        "type": "youtube",
        "processed_path": "https://www.youtube.com/embed/abc123",
        "status": "embed"
      }
    ]
  }
}
```

### Get Content with Attachments
```http
GET /api/courses/{course_id}/modules/{module_id}/lessons/{lesson_id}/content
Authorization: Bearer {token}
```

### Access Individual File
```http
GET /api/courses/{course_id}/modules/{module_id}/lessons/{lesson_id}/content/files/uploads/contents/images/image_abc123.png
Authorization: Bearer {token}
```

## 🎯 Web Interface Access

The web interface at `/lessons/{lesson_id}` automatically displays:
- ✅ HTML content with embedded images
- ✅ YouTube/Vimeo video embeds
- ✅ Attachments section (if configured)
- ✅ Responsive design for mobile

All images and assets are automatically accessible via the storage symlink without additional configuration.

## 🔍 Troubleshooting

### Images Not Showing in Web Interface
1. Check storage symlink exists: `ls -la public/storage`
2. Verify file exists: `ls storage/app/public/uploads/contents/images/`
3. Check permissions: Files should be readable (644)

### Images Not Accessible via API
1. Verify route exists in `routes/api.php`
2. Check authentication token is valid
3. Ensure content belongs to the lesson being accessed
4. Check file path in database matches actual file location

### After Running Seeders
```bash
# Verify content was created without ZIP
php artisan tinker
>>> App\Models\Content::first()->zip_file_path  // Should be null
>>> App\Models\Content::first()->images  // Should show image array
```

## ✨ Benefits of No ZIP

1. **Faster** - No ZIP creation/extraction overhead
2. **Simpler** - Direct file access
3. **Efficient** - Browser caching works better
4. **Flexible** - Serve files with proper MIME types
5. **Accessible** - Easy web and API access
6. **Cleaner** - Less storage space used

---

**Last Updated:** January 6, 2026
**System Status:** ✅ ZIP-free, attachment-ready
