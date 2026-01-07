# Image & File Serving Implementation

## Problem
The frontend was receiving 404 errors when trying to access:
1. ZIP file downloads containing content and images
2. Individual image files from uploaded content

## Solution Implemented

### 1. **Added File Serving Endpoint** ✅
**Route:** `GET /api/courses/{course_id}/modules/{module_id}/lessons/{lesson_id}/content/files/{filepath}`

**Purpose:** Serves individual image and asset files with proper authentication and caching.

**Features:**
- Security: Prevents directory traversal attacks
- Authentication: Requires user to have course view permissions
- Caching: Sets 1-year cache headers for static assets
- Flexible path resolution: Checks multiple possible file locations
- Activity tracking: Logs file access for analytics

**Example URLs:**
```
GET /api/courses/1/modules/2/lessons/3/content/files/images/image_abc123.jpg
GET /api/courses/1/modules/2/lessons/3/content/files/assets/video/sample.mp4
```

### 2. **Updated ZIP Download Endpoint** ✅
**Route:** `GET /api/courses/{course_id}/modules/{module_id}/lessons/{lesson_id}/content/download`

**Improvements:**
- Now works WITHOUT requiring content_id (gets active content automatically)
- Original endpoint still available: `/content/{content_id}/download-zip`
- Proper error handling and logging
- Activity tracking for download analytics

### 3. **Enhanced ZIP Structure** ✅

**New ZIP Format:**
```
content.zip
├── content.html                     # Main HTML file
├── metadata.json                    # Content metadata
└── {filename}_files/                # Assets folder (e.g., "1_computer_grafis_files")
    ├── image_001.jpg               # Images
    ├── image_002.gif               # GIFs included
    ├── image_003.png               # PNGs
    └── video/                      # Optional: organized assets
        └── sample.mp4
```

**Key Features:**
- Images are in `{filename}_files/` folder (matches expected structure)
- All image formats included: JPG, PNG, GIF, WebP, SVG, etc.
- HTML uses relative paths: `{filename}_files/image.jpg`
- Metadata includes folder name for easy reference

### 4. **Updated ContentResource API Response** ✅

**New Fields Added:**
```json
{
  "id": 123,
  "title": "Computer Graphics",
  "download_url": "http://api.example.com/api/courses/1/modules/2/lessons/3/content/download",
  "zip_file_size": 856432,
  "images": [
    {
      "original_src": "data:image/jpeg;base64,...",
      "processed_path": "storage/uploads/contents/images/image_abc123.jpg",
      "type": "uploaded",
      "size": 45678,
      "url": "http://api.example.com/storage/uploads/contents/images/image_abc123.jpg",
      "api_url": "http://api.example.com/api/courses/1/modules/2/lessons/3/content/files/images/image_abc123.jpg"
    }
  ]
}
```

**Image Object Enhancement:**
- `url`: Direct storage URL (Laravel public storage symlink)
- `api_url`: Authenticated API endpoint with caching
- Both URLs point to the same image file

## Files Modified

1. **`src/routes/api.php`**
   - Added: `GET /content/download` (ZIP without content_id)
   - Added: `GET /content/files/{filepath}` (individual file serving)

2. **`src/app/Http/Controllers/ContentController.php`**
   - Updated: `downloadZip()` - Made content_id optional
   - Added: `serveFile()` - New method to serve individual files

3. **`src/app/Services/DocumentConverterService.php`**
   - Updated: `createZipArchive()` - Uses `{filename}_files/` structure
   - Added: `updateImagePathsForZip()` - Generates relative paths for ZIP

4. **`src/app/Http/Resources/ContentResource.php`**
   - Added: `download_url` field
   - Added: `zip_file_size` field
   - Enhanced: `images` array with `url` and `api_url` fields
   - Added: `processImagesWithUrls()` helper method

## Usage Examples

### Download ZIP File
```bash
# Without content_id (gets active content)
GET /api/courses/1/modules/2/lessons/3/content/download

# With specific content_id
GET /api/courses/1/modules/2/lessons/3/content/123/download-zip
```

### Access Individual Images
```bash
# Via authenticated API endpoint
GET /api/courses/1/modules/2/lessons/3/content/files/images/image_abc123.jpg

# Via direct storage URL (requires public symlink)
GET /storage/uploads/contents/images/image_abc123.jpg
```

### Get Content with Download URLs
```bash
GET /api/courses/1/modules/2/lessons/3/content
```

Response includes:
- `download_url`: Direct link to download ZIP
- `images[].url`: Direct storage URL
- `images[].api_url`: Authenticated API endpoint

## Frontend Implementation

### Option 1: Download ZIP (Recommended)
```javascript
const response = await fetch(`${API_URL}/courses/${courseId}/modules/${moduleId}/lessons/${lessonId}/content/download`, {
  headers: { 'Authorization': `Bearer ${token}` }
});

const blob = await response.blob();
// Extract ZIP and display content
```

### Option 2: Serve Individual Images
```javascript
// Use api_url for authenticated access
const imageUrl = content.images[0].api_url;

// Or use direct storage URL (if symlink configured)
const directUrl = content.images[0].url;

<img src={imageUrl} alt="Content image" />
```

## Security Features

1. **Authentication Required**: All endpoints check user permissions
2. **Path Traversal Protection**: Sanitizes file paths to prevent `../` attacks
3. **Scope Validation**: Verifies content belongs to the specified lesson
4. **Activity Tracking**: Logs all file access for audit trails

## Performance Optimizations

1. **Caching**: 1-year cache headers for static assets
2. **Direct File Streaming**: Uses `response()->file()` for efficiency
3. **Content Caching**: Lesson content cached for 1 hour
4. **CDN Ready**: Cache headers allow CDN distribution

## Testing

### Test ZIP Download
```bash
curl -X GET "http://localhost/api/courses/1/modules/2/lessons/3/content/download" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -O content.zip
```

### Test Image Serving
```bash
curl -X GET "http://localhost/api/courses/1/modules/2/lessons/3/content/files/images/image_123.jpg" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -O image.jpg
```

### Verify ZIP Structure
```bash
unzip -l content.zip
# Should show:
# content.html
# metadata.json
# {filename}_files/image_001.jpg
# {filename}_files/image_002.gif
```

## Migration Notes

**No database changes required** - This is purely a code update.

**Existing content:** Will work immediately with new endpoints.

**New uploads:** Will use the new `{filename}_files/` structure automatically.

**Backward compatibility:** Old `/content/{content_id}/download-zip` endpoint still works.

## Troubleshooting

### Images Still Returning 404

1. **Check storage symlink:**
   ```bash
   php artisan storage:link
   ```

2. **Verify file permissions:**
   ```bash
   chmod -R 755 storage/app/public/uploads
   ```

3. **Check file exists:**
   ```bash
   ls -la storage/app/public/uploads/contents/images/
   ```

4. **Check logs:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

### ZIP File Not Found

1. **Check zip_file_path in database:**
   ```sql
   SELECT id, title, zip_file_path FROM contents WHERE lesson_id = 123;
   ```

2. **Verify physical file:**
   ```bash
   ls -la storage/app/public/uploads/contents/archives/
   ```

3. **Re-process content:**
   ```bash
   POST /api/courses/{course_id}/modules/{module_id}/lessons/{lesson_id}/content/{content_id}/reprocess
   ```

## Next Steps

Consider implementing:
1. **Image resizing/optimization** on upload
2. **Progressive image loading** (thumbnails → full size)
3. **WebP conversion** for better compression
4. **CDN integration** for global distribution
5. **Image lazy loading** hints in HTML
