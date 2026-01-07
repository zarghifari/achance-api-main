# 📎 Content Attachment Support

## ✅ Fully Supported Attachments

The HTML Content System now **automatically extracts, downloads, and bundles** all types of attachments when converting documents.

---

## 📦 What Gets Included in ZIP Archives

### 1. **Images** ✅

| Type | Description | Handling |
|------|-------------|----------|
| **Base64 Images** | `data:image/png;base64,...` | ✅ Extracted → Saved to storage → Bundled in ZIP |
| **External URLs** | `https://example.com/image.jpg` | ✅ Downloaded → Saved locally → Bundled in ZIP |
| **Local Paths** | `./images/photo.png` | ✅ Copied → Bundled in ZIP |

**Supported Formats:** JPG, PNG, GIF, SVG, WEBP, BMP, ICO

---

### 2. **Videos** ✅

| Type | Handling |
|------|----------|
| `<video src="video.mp4">` | ✅ Downloaded → Bundled in ZIP |
| `<source src="video.webm">` | ✅ Downloaded → Bundled in ZIP |
| YouTube/Vimeo embeds | ⚠️ Kept as external links (not downloaded) |

**Supported Formats:** MP4, WEBM, OGG, AVI, MOV

---

### 3. **Audio Files** ✅

| Type | Handling |
|------|----------|
| `<audio src="audio.mp3">` | ✅ Downloaded → Bundled in ZIP |
| `<source src="audio.ogg">` | ✅ Downloaded → Bundled in ZIP |

**Supported Formats:** MP3, OGG, WAV, AAC, FLAC

---

### 4. **Documents** ✅

| Type | Handling |
|------|----------|
| PDFs | ✅ Downloaded → Bundled in ZIP |
| Word (.doc, .docx) | ✅ Downloaded → Bundled in ZIP |
| Excel (.xls, .xlsx) | ✅ Downloaded → Bundled in ZIP |
| PowerPoint (.ppt, .pptx) | ✅ Downloaded → Bundled in ZIP |
| Text files (.txt, .csv, .json) | ✅ Downloaded → Bundled in ZIP |

**Detected via:** `<a href="document.pdf">`, `<embed src="file.pdf">`, `<object data="doc.pdf">`

---

### 5. **Archives** ✅

| Type | Handling |
|------|----------|
| ZIP files | ✅ Downloaded → Bundled in ZIP |
| RAR files | ✅ Downloaded → Bundled in ZIP |
| 7Z files | ✅ Downloaded → Bundled in ZIP |

---

### 6. **Embedded Content** ✅

| Type | Handling |
|------|----------|
| `<embed src="file.pdf">` | ✅ Downloaded → Bundled in ZIP |
| `<object data="interactive.swf">` | ✅ Downloaded → Bundled in ZIP |
| `<iframe src="local-page.html">` | ✅ Downloaded → Bundled in ZIP |
| External iframes (YouTube, etc.) | ⚠️ Kept as external links |

---

## 📁 ZIP Archive Structure

When you import a document, the generated ZIP contains:

```
lesson-{id}-{timestamp}.zip
├── content.html                    # Processed HTML with updated paths
├── metadata.json                   # Document metadata
├── images/                         # All images
│   ├── image_xxxxx_0.png
│   ├── image_xxxxx_1.jpg
│   └── image_xxxxx_2.gif
└── assets/                         # All other attachments
    ├── video/
    │   ├── video_xxxxx_0.mp4
    │   └── video_xxxxx_1.webm
    ├── audio/
    │   └── audio_xxxxx_0.mp3
    ├── document/
    │   ├── document_xxxxx_0.pdf
    │   ├── document_xxxxx_1.docx
    │   └── document_xxxxx_2.xlsx
    └── embed/
        └── embed_xxxxx_0.pdf
```

---

## 🔄 Processing Flow

```
1. Upload .doc/.docx file
        ↓
2. Convert to HTML (PHPWord)
        ↓
3. Extract all assets:
   - Scan for <img> tags → Extract images
   - Scan for <video> tags → Extract videos
   - Scan for <audio> tags → Extract audio
   - Scan for <a href="*.pdf"> → Extract documents
   - Scan for <embed>, <object>, <iframe> → Extract embedded files
        ↓
4. Download remote assets:
   - Check if URL is external (https://...)
   - Download file (max 100MB, 30s timeout)
   - Save to storage/app/public/uploads/contents/assets/
        ↓
5. Update HTML paths:
   - Replace original URLs with local paths
   - Example: https://example.com/file.pdf → storage/uploads/contents/assets/document_xxxxx_0.pdf
        ↓
6. Create ZIP archive:
   - Bundle content.html
   - Bundle metadata.json
   - Bundle all images (in images/ folder)
   - Bundle all assets (in assets/ folder organized by type)
        ↓
7. Save to database:
   - html_content: Updated HTML
   - images: Array of image paths
   - zip_file_path: Path to ZIP
   - zip_file_size: Size in bytes
```

---

## ⚙️ Configuration Limits

| Setting | Default Value | Description |
|---------|---------------|-------------|
| **Max File Size** | 100 MB | Maximum size for downloadable assets |
| **Download Timeout** | 30 seconds | Timeout for downloading remote files |
| **User Agent** | Mozilla/5.0... | User agent for HTTP requests |

---

## 🚨 What Doesn't Get Downloaded

For **security and practicality**, these are **NOT** downloaded:

❌ **External Services:**
- YouTube videos (`https://youtube.com/...`)
- Vimeo videos (`https://vimeo.com/...`)
- Google Drive embeds (`https://drive.google.com/...`)

❌ **Too Large:**
- Files exceeding 100MB

❌ **Invalid URLs:**
- Broken links that return 404
- URLs that timeout after 30 seconds

These remain as **external links** in the HTML.

---

## 📊 Example Conversion

### Before (Original Word Document):
```html
<p>Check out this video:</p>
<video src="https://example.com/tutorial.mp4"></video>

<p>Download the PDF guide:</p>
<a href="https://example.com/guide.pdf">Guide</a>

<p>Listen to audio:</p>
<audio src="https://example.com/lesson.mp3"></audio>

<img src="data:image/png;base64,iVBORw0..." />
```

### After (Processed HTML):
```html
<p>Check out this video:</p>
<video src="storage/uploads/contents/assets/video_abc123_0.mp4"></video>

<p>Download the PDF guide:</p>
<a href="storage/uploads/contents/assets/document_def456_0.pdf">Guide</a>

<p>Listen to audio:</p>
<audio src="storage/uploads/contents/assets/audio_ghi789_0.mp3"></audio>

<img src="storage/uploads/contents/images/image_jkl012_0.png" />
```

### ZIP Contains:
```
✅ content.html                                    (Updated paths)
✅ metadata.json                                   (Document info)
✅ images/image_jkl012_0.png                      (Extracted base64 image)
✅ assets/video/video_abc123_0.mp4                (Downloaded video)
✅ assets/document/document_def456_0.pdf          (Downloaded PDF)
✅ assets/audio/audio_ghi789_0.mp3                (Downloaded audio)
```

---

## 🧪 Testing Attachment Support

### Test 1: Import Document with Attachments

```bash
POST /api/courses/1/modules/1/lessons/1/content/import
Content-Type: multipart/form-data

file: document-with-attachments.docx
```

**Expected Response:**
```json
{
    "id": 1,
    "title": "Lesson with Multiple Attachments",
    "total_pages": 5,
    "metadata": {
        "word_count": 2000,
        "image_count": 3,
        "asset_count": 5,           ← Videos, PDFs, Audio combined
        "processing_date": "2026-01-02 12:00:00"
    },
    "zip_file_path": "uploads/contents/archives/lesson-1-20260102.zip",
    "zip_file_size": 15728640       ← ~15MB including all assets
}
```

### Test 2: Download ZIP and Verify Contents

```bash
GET /api/courses/1/modules/1/lessons/1/content/download-zip
```

**Extract and verify:**
```bash
unzip lesson-1-20260102.zip
ls -R
```

**Expected structure:**
```
content.html
metadata.json
images/
    image_xxxxx_0.png
    image_xxxxx_1.jpg
assets/
    video/
        video_xxxxx_0.mp4
    document/
        document_xxxxx_0.pdf
    audio/
        audio_xxxxx_0.mp3
```

---

## 📈 Performance Impact

| Asset Type | Avg Download Time | Avg Size | Impact |
|------------|-------------------|----------|--------|
| Images | 0.5-2s | 50-500 KB | Low |
| PDFs | 1-3s | 500 KB-5 MB | Medium |
| Videos | 5-30s | 5-100 MB | High |
| Audio | 2-10s | 1-10 MB | Medium |

**Total Processing Time:** 
- Simple document (text only): **0.5-1s**
- With images (5-10): **2-5s**
- With videos (1-2): **10-30s**
- With many attachments: **30-60s**

**Recommendation:** Use background jobs for documents with many large attachments.

---

## 🔍 Logging & Debugging

All attachment processing is logged:

```php
// Success
Log::info('Asset downloaded successfully', [
    'url' => 'https://example.com/file.pdf',
    'saved_to' => 'uploads/contents/assets/document_xxx.pdf',
    'size' => 1048576
]);

// Warning
Log::warning('Failed to download asset', [
    'url' => 'https://broken-link.com/file.mp4',
    'error' => '404 Not Found'
]);

// ZIP Creation
Log::info('ZIP archive created', [
    'zip_path' => 'uploads/contents/archives/lesson-1.zip',
    'zip_size' => 15728640,
    'files_count' => 12
]);
```

Check logs at: `storage/logs/laravel.log`

---

## ✅ Summary

| Feature | Status |
|---------|--------|
| **Images** | ✅ Fully Supported (Base64, URLs, Local) |
| **Videos** | ✅ Fully Supported (Downloaded & Bundled) |
| **Audio** | ✅ Fully Supported (Downloaded & Bundled) |
| **Documents** | ✅ Fully Supported (PDF, Word, Excel, PPT) |
| **Archives** | ✅ Fully Supported (ZIP, RAR, 7Z) |
| **Embedded Content** | ✅ Supported (Local files only) |
| **External Services** | ⚠️ Kept as Links (YouTube, Vimeo, etc.) |
| **ZIP Bundling** | ✅ All assets organized by type |
| **Path Updates** | ✅ HTML automatically updated |
| **Size Limits** | ✅ 100MB max per file |
| **Download Timeout** | ✅ 30 seconds |

**Everything is automatically handled!** Just upload your document and all attachments will be:
1. Extracted from HTML
2. Downloaded if remote
3. Saved to storage
4. Bundled in ZIP
5. Paths updated in HTML

---

**Last Updated:** 2026-01-02  
**Version:** 2.0 (Full Attachment Support)
