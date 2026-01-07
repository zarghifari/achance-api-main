# 📋 Content API Quick Reference

## 🔗 Base Path
```
/api/courses/{course_id}/modules/{module_id}/lessons/{lesson_id}/content
```

## 📍 All Endpoints

### 1. Import Document
```http
POST /content/import
Content-Type: multipart/form-data

file: document.docx (required)
words_per_page: 500 (optional)
```
**Response:** Content object with `id`, `total_pages`, `zip_file_path`

---

### 2. Get Full Content
```http
GET /content
Authorization: Bearer {token}
```
**Response:** Complete content with `html_content`, `paginated_content`, `images`, `metadata`

---

### 3. Get Metadata Only
```http
GET /content/metadata
Authorization: Bearer {token}
```
**Response:** Light response with `id`, `title`, `total_pages`, `metadata` (NO HTML)

---

### 4. Get Specific Page
```http
GET /content/page/{page}
Authorization: Bearer {token}
```
**Response:** Single page content with `page`, `total_pages`, `content` (HTML string)

---

### 5. Download ZIP
```http
GET /content/download-zip
Authorization: Bearer {token}
```
**Response:** Binary ZIP file download

---

### 6. Update Content
```http
PUT /content
Authorization: Bearer {token}
Content-Type: application/json

{
  "title": "Updated Title"
}
```

---

### 7. Reprocess Content
```http
POST /content/reprocess
Authorization: Bearer {token}
Content-Type: application/json

{
  "words_per_page": 600
}
```
**Response:** Reprocessed content with new pagination

---

### 8. Delete Content
```http
DELETE /content
Authorization: Bearer {token}
```
**Response:** Success message

---

## 🎯 Common Use Cases

### Load Lesson with Content
```javascript
GET /api/courses/1/modules/1/lessons/1

Response includes:
{
  "lesson": {
    "id": 1,
    "title": "Introduction",
    "content": {          // ← Content included
      "id": 1,
      "total_pages": 3,
      "type": "html"
    }
  }
}
```

### Paginated Reading
```javascript
// Page 1
GET /content/page/1

// Page 2  
GET /content/page/2

// Page 3
GET /content/page/3
```

### Offline Download
```javascript
// Download complete ZIP with all assets
GET /content/download-zip

// ZIP contains:
// - content.html
// - metadata.json
// - images/
// - assets/ (videos, PDFs, etc.)
```

---

## 📦 Response Structures

### Full Content Response
```json
{
  "data": {
    "id": 1,
    "lesson_id": 1,
    "title": "HTML Basics",
    "type": "html",
    "html_content": "<h1>Content...</h1>",
    "paginated_content": ["page1", "page2"],
    "images": ["path/to/image.jpg"],
    "total_pages": 3,
    "is_processed": true,
    "zip_file_path": "uploads/.../lesson-1.zip",
    "zip_file_size": 245760,
    "metadata": {
      "word_count": 1500,
      "image_count": 3,
      "asset_count": 2
    }
  }
}
```

### Page Response
```json
{
  "data": {
    "page": 1,
    "total_pages": 3,
    "content": "<h1>Page 1 HTML</h1>",
    "images": ["image1.jpg"],
    "metadata": {
      "word_count": 500
    }
  }
}
```

### Metadata Only Response
```json
{
  "data": {
    "id": 1,
    "lesson_id": 1,
    "title": "HTML Basics",
    "type": "html",
    "total_pages": 3,
    "is_processed": true,
    "metadata": {
      "word_count": 1500
    }
  }
}
```

---

## ⚡ Performance Tips

1. **List Views**: Use `/content/metadata` (faster, no HTML)
2. **Reading View**: Use `/content/page/{page}` (load on-demand)
3. **Full Export**: Use `/content/download-zip` (complete bundle)
4. **Caching**: Content cached for 15 minutes
5. **ETag**: Send `If-None-Match` for 304 responses

---

## 🔒 Authentication

All endpoints require Bearer token:

```http
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

Get token from login:
```http
POST /api/login
{
  "email": "teacher@example.com",
  "password": "password"
}
```

---

## 📊 Postman Variables

```json
{
  "courseId": "1",
  "moduleId": "1", 
  "lessonId": "1",
  "contentId": "1",
  "teacherToken": "...",
  "studentToken": "..."
}
```

---

## ✅ Status Codes

- `200` - Success
- `201` - Created (import)
- `304` - Not Modified (ETag)
- `400` - Bad Request
- `401` - Unauthorized
- `404` - Not Found
- `422` - Validation Error
- `500` - Server Error

---

## 🎨 Frontend Integration

### React Example
```jsx
function LessonContent({ courseId, moduleId, lessonId }) {
  const [page, setPage] = useState(1);
  const [content, setContent] = useState(null);

  useEffect(() => {
    fetch(`/api/courses/${courseId}/modules/${moduleId}/lessons/${lessonId}/content/page/${page}`, {
      headers: {
        'Authorization': `Bearer ${token}`
      }
    })
    .then(res => res.json())
    .then(data => setContent(data.data));
  }, [page]);

  return (
    <div>
      <div dangerouslySetInnerHTML={{ __html: content?.content }} />
      <button onClick={() => setPage(page - 1)} disabled={page === 1}>
        Previous
      </button>
      <span>Page {page} of {content?.total_pages}</span>
      <button onClick={() => setPage(page + 1)} disabled={page === content?.total_pages}>
        Next
      </button>
    </div>
  );
}
```

### Vue Example
```vue
<template>
  <div>
    <div v-html="content"></div>
    <button @click="prevPage" :disabled="page === 1">Previous</button>
    <span>Page {{ page }} of {{ totalPages }}</span>
    <button @click="nextPage" :disabled="page === totalPages">Next</button>
  </div>
</template>

<script>
export default {
  data() {
    return {
      page: 1,
      content: '',
      totalPages: 0
    }
  },
  methods: {
    async loadPage() {
      const res = await fetch(`/api/.../content/page/${this.page}`, {
        headers: { 'Authorization': `Bearer ${this.token}` }
      });
      const data = await res.json();
      this.content = data.data.content;
      this.totalPages = data.data.total_pages;
    },
    nextPage() { this.page++; this.loadPage(); },
    prevPage() { this.page--; this.loadPage(); }
  },
  mounted() {
    this.loadPage();
  }
}
</script>
```

---

**Quick Access:**
- Full Docs: [API_DOCUMENTATION.md](API_DOCUMENTATION.md)
- Attachments: [ATTACHMENT_SUPPORT.md](ATTACHMENT_SUPPORT.md)
- Postman: [POSTMAN_UPDATE_SUMMARY.md](POSTMAN_UPDATE_SUMMARY.md)
