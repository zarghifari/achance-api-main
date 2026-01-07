# 📋 Attachment Support - Quick Reference

## ✅ YES - All These Are Included in ZIP

```
📦 ZIP Archive Contents:
├── content.html                    ✅ Processed HTML
├── metadata.json                   ✅ Document info
│
├── 🖼️ images/                      ✅ ALL IMAGES
│   ├── Base64 images               ✅ Extracted & saved
│   ├── External URLs               ✅ Downloaded
│   └── Local images                ✅ Copied
│
└── 📎 assets/                      ✅ ALL ATTACHMENTS
    ├── video/                      ✅ MP4, WEBM, OGG, AVI, MOV
    │   └── Downloaded videos
    ├── audio/                      ✅ MP3, OGG, WAV, AAC, FLAC
    │   └── Downloaded audio files
    ├── document/                   ✅ PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX
    │   └── Downloaded documents
    └── embed/                      ✅ Embedded PDFs, files
        └── Downloaded embeds
```

---

## 🔍 What HTML Tags Are Processed?

| HTML Tag | Extracted? | Downloaded? | Bundled in ZIP? |
|----------|------------|-------------|-----------------|
| `<img src="...">` | ✅ Yes | ✅ Yes | ✅ Yes |
| `<video src="...">` | ✅ Yes | ✅ Yes | ✅ Yes |
| `<audio src="...">` | ✅ Yes | ✅ Yes | ✅ Yes |
| `<source src="...">` | ✅ Yes | ✅ Yes | ✅ Yes |
| `<a href="*.pdf">` | ✅ Yes | ✅ Yes | ✅ Yes |
| `<a href="*.docx">` | ✅ Yes | ✅ Yes | ✅ Yes |
| `<a href="*.xlsx">` | ✅ Yes | ✅ Yes | ✅ Yes |
| `<a href="*.pptx">` | ✅ Yes | ✅ Yes | ✅ Yes |
| `<embed src="...">` | ✅ Yes | ✅ Yes | ✅ Yes |
| `<object data="...">` | ✅ Yes | ✅ Yes | ✅ Yes |
| `<iframe src="...">` (local) | ✅ Yes | ✅ Yes | ✅ Yes |

---

## ⚠️ External Services (NOT Downloaded)

These stay as external links:

| Service | Handled As | Reason |
|---------|-----------|--------|
| `https://youtube.com/...` | External Link | External service |
| `https://vimeo.com/...` | External Link | External service |
| `https://drive.google.com/...` | External Link | External service |

---

## 📊 File Size Limits

- **Max per file:** 100 MB
- **Download timeout:** 30 seconds
- **Files over 100MB:** Kept as external links

---

## 🎯 Example: Complete Document Processing

### Input Document Contains:
```
• 5 images (2 base64, 3 URLs)
• 2 videos (MP4 files)
• 1 audio (MP3 file)
• 3 PDFs
• 1 Word document
• 1 Excel file
```

### Output ZIP Contains:
```
✅ content.html                          (HTML with updated paths)
✅ metadata.json                         (word_count, asset_count, etc.)
✅ images/image_xxxxx_0.png              (Base64 image 1)
✅ images/image_xxxxx_1.jpg              (Base64 image 2)
✅ images/image_xxxxx_2.jpg              (Downloaded URL 1)
✅ images/image_xxxxx_3.png              (Downloaded URL 2)
✅ images/image_xxxxx_4.gif              (Downloaded URL 3)
✅ assets/video/video_xxxxx_0.mp4        (Video 1)
✅ assets/video/video_xxxxx_1.mp4        (Video 2)
✅ assets/audio/audio_xxxxx_0.mp3        (Audio file)
✅ assets/document/document_xxxxx_0.pdf  (PDF 1)
✅ assets/document/document_xxxxx_1.pdf  (PDF 2)
✅ assets/document/document_xxxxx_2.pdf  (PDF 3)
✅ assets/document/document_xxxxx_3.docx (Word doc)
✅ assets/document/document_xxxxx_4.xlsx (Excel file)

TOTAL: 15 files bundled
```

### HTML Path Updates:
```html
Before: <img src="data:image/png;base64,iVBORw0KG...">
After:  <img src="storage/uploads/contents/images/image_xxxxx_0.png">

Before: <video src="https://example.com/tutorial.mp4">
After:  <video src="storage/uploads/contents/assets/video/video_xxxxx_0.mp4">

Before: <a href="https://example.com/guide.pdf">Download Guide</a>
After:  <a href="storage/uploads/contents/assets/document/document_xxxxx_0.pdf">Download Guide</a>
```

---

## 🚀 How to Use

1. **Upload Document:**
```bash
POST /api/courses/1/modules/1/lessons/1/content/import
file: document.docx
```

2. **System Automatically:**
   - ✅ Extracts all images
   - ✅ Extracts all videos, audio, documents
   - ✅ Downloads remote files
   - ✅ Saves everything to storage
   - ✅ Creates organized ZIP archive
   - ✅ Updates all paths in HTML

3. **Download ZIP:**
```bash
GET /api/courses/1/modules/1/lessons/1/content/download-zip
```

4. **Get Content:**
```bash
GET /api/courses/1/modules/1/lessons/1/content
```

---

## ✅ Summary

**Question:** *"Is the converted HTML include all the image and another attachment?"*

**Answer:** **YES! ✅**

- ✅ **ALL images** (base64, URLs, local)
- ✅ **ALL videos** (MP4, WEBM, etc.)
- ✅ **ALL audio** (MP3, OGG, etc.)
- ✅ **ALL documents** (PDF, Word, Excel, PowerPoint)
- ✅ **ALL embedded files**
- ✅ Everything is **downloaded**, **saved locally**, and **bundled in ZIP**
- ✅ HTML paths are **automatically updated** to point to local files

**The ZIP archive is a complete, self-contained package with everything!**

---

See [ATTACHMENT_SUPPORT.md](ATTACHMENT_SUPPORT.md) for detailed documentation.
