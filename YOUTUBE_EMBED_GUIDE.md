# YouTube Video Embedding Guide

## Overview
The Content Management system now automatically detects and converts YouTube links into responsive video embeds.

## Supported Formats

### 1. Direct YouTube Links (Auto-converted to embeds)
Simply include YouTube links in your document and they'll be automatically converted to embedded videos:

```html
<a href="https://www.youtube.com/watch?v=yMd3osygR8s">Watch this video</a>
<a href="https://youtu.be/yMd3osygR8s">Short URL format also works</a>
```

**What happens:**
- System extracts video ID: `yMd3osygR8s`
- Converts link to responsive iframe embed
- Preserves original URL in metadata

### 2. YouTube iFrame Embeds (Already embedded)
If your document already contains YouTube iframes, they'll be made responsive:

```html
<iframe src="https://www.youtube.com/embed/yMd3osygR8s"></iframe>
```

**What happens:**
- Wrapped in responsive container
- Maintains 16:9 aspect ratio
- Works on all screen sizes

### 3. Other Supported Video Platforms
The system also supports:
- **Vimeo**: `https://vimeo.com/123456789`
- **Dailymotion**: `https://www.dailymotion.com/video/x8abc123`
- **Google Maps**: Embedded maps remain functional

## How It Works

### Step 1: Document Upload
Upload your DOCX or EPUB file containing YouTube links:
```
POST /api/courses/{course}/modules/{module}/lessons/{lesson}/contents
Content-Type: multipart/form-data

file: document.docx
title: "My Lesson with Videos"
```

### Step 2: Automatic Processing
The system:
1. ✅ Extracts all YouTube URLs from links and iframes
2. ✅ Converts video IDs to embed URLs
3. ✅ Creates responsive video containers
4. ✅ Preserves video metadata
5. ✅ Does NOT download videos (they stream from YouTube)

### Step 3: Result
**Before (in your document):**
```html
Check out this tutorial: https://www.youtube.com/watch?v=yMd3osygR8s
```

**After (in the converted HTML):**
```html
<div style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;max-width:100%;margin:20px 0;">
    <iframe src="https://www.youtube.com/embed/yMd3osygR8s" 
            style="position:absolute;top:0;left:0;width:100%;height:100%;" 
            frameborder="0" 
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
            allowfullscreen>
    </iframe>
</div>
```

## Metadata Tracking

The system tracks video embeds in the content metadata:

```json
{
  "word_count": 1250,
  "image_count": 5,
  "asset_count": 8,
  "video_embed_count": 2,
  "video_embeds": [
    {
      "type": "youtube",
      "url": "https://www.youtube.com/embed/yMd3osygR8s",
      "video_id": "yMd3osygR8s"
    }
  ]
}
```

## ZIP Download Contents

When downloading the ZIP archive:

```
content_123.zip
├── content.html           ← Contains embedded YouTube iframes
├── metadata.json          ← Includes video_embeds info
├── images/
│   └── ...
└── assets/
    └── ...
```

**Note:** YouTube videos are NOT downloaded. The ZIP contains iframe embeds that stream from YouTube when the HTML is opened.

## Example: Creating Content with YouTube Videos

### Using DOCX
Create a Word document with:
```
Lesson 1: Introduction to Programming

Watch this introduction video:
https://www.youtube.com/watch?v=yMd3osygR8s

Key concepts covered:
- Variables
- Functions
- Loops
```

### Using API
```bash
curl -X POST http://localhost/api/courses/1/modules/1/lessons/1/contents \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: multipart/form-data" \
  -F "file=@lesson.docx" \
  -F "title=Lesson 1"
```

### Result
The content will display with:
- Text content properly formatted
- YouTube video embedded and playable
- All other assets extracted and bundled

## Supported URL Patterns

### YouTube
- ✅ `https://www.youtube.com/watch?v=VIDEO_ID`
- ✅ `https://youtu.be/VIDEO_ID`
- ✅ `https://www.youtube.com/embed/VIDEO_ID`
- ✅ With query parameters: `?v=VIDEO_ID&t=30s`

### Vimeo
- ✅ `https://vimeo.com/123456789`
- ✅ `https://player.vimeo.com/video/123456789`

### Dailymotion
- ✅ `https://www.dailymotion.com/video/x8abc123`

## Best Practices

1. **Use Standard YouTube Links**
   - Prefer `youtube.com/watch?v=` format
   - System handles URL variations automatically

2. **Test Video Access**
   - Ensure videos are public or unlisted
   - Embedded videos follow YouTube's privacy settings

3. **Multiple Videos**
   - Include as many videos as needed
   - Each video is converted independently
   - No impact on processing time

4. **Responsive Design**
   - All embeds are responsive by default
   - Works on mobile, tablet, and desktop
   - 16:9 aspect ratio maintained

5. **Network Considerations**
   - Videos require internet connection to play
   - No bandwidth cost for your server
   - YouTube handles video delivery

## Troubleshooting

### Video Not Appearing?
- Check if URL is valid YouTube link
- Verify video is public or unlisted
- Review logs for extraction errors

### Video Not Responsive?
- System automatically wraps iframes
- Check browser console for CSS issues

### Multiple Videos Overlapping?
- Each video has its own container
- Check for custom CSS conflicts

## API Response Example

```json
{
  "id": 123,
  "title": "Lesson with Videos",
  "metadata": {
    "word_count": 850,
    "image_count": 3,
    "video_embed_count": 2,
    "asset_count": 5
  },
  "pages": [
    {
      "page": 1,
      "content": "<div style=\"position:relative;...\"><iframe src=\"https://www.youtube.com/embed/yMd3osygR8s\"..."
    }
  ]
}
```

## Related Documentation
- [Content API Quick Reference](CONTENT_API_QUICK_REFERENCE.md)
- [API Documentation](API_DOCUMENTATION.md)
- [Docker Setup Guide](DOCKER_SETUP_GUIDE.md)
