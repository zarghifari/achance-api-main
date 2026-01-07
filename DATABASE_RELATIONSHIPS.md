# 📊 HTML Content System - Database Relationships

## Table Relationship Diagram

```
┌─────────────────┐
│    courses      │
│─────────────────│
│ id (PK)         │
│ title           │
│ slug            │
│ description     │
│ course_img      │
│ video_url       │
│ isOpen          │
│ total_hours     │
└────────┬────────┘
         │
         │ 1:N (one course has many modules)
         │
┌────────▼────────┐
│    modules      │
│─────────────────│
│ id (PK)         │
│ course_id (FK)  │───► references courses.id
│ title           │
│ slug            │
│ cover_image     │
│ video_url       │
│ position        │
└────────┬────────┘
         │
         │ 1:N (one module has many lessons)
         │
┌────────▼────────┐
│    lessons      │
│─────────────────│
│ id (PK)         │
│ module_id (FK)  │───► references modules.id
│ title           │
│ slug            │
│ cover_image     │
│ video_url       │
│ attachment      │
│ position        │
│ description     │
└────────┬────────┘
         │
         │ 1:1 (one lesson has one content)
         │
┌────────▼────────────────────────┐
│         contents                │
│─────────────────────────────────│
│ id (PK)                         │
│ lesson_id (FK) ◄───────────────│ UNIQUE - references lessons.id
│                                 │
│ === Content Information ===     │
│ title                           │
│ type (html/json/mixed)          │
│ description                     │
│                                 │
│ === Original File ===           │
│ original_filename               │
│ source_file_path                │
│ source_file_size                │
│                                 │
│ === ZIP Archive ===             │
│ zip_file_path                   │ ⭐ NEW: Compressed archive
│ zip_file_size                   │ ⭐ NEW: Archive size
│                                 │
│ === Processed Content ===       │
│ html_content (LONGTEXT)         │ Pre-processed HTML
│ json_content (JSON)             │ Paginated structure
│ metadata (JSON)                 │ Word count, stats, etc.
│                                 │
│ === Media Assets ===            │
│ images (JSON)                   │ Array of image info
│ assets (JSON)                   │ Array of other assets
│                                 │
│ === Pagination ===              │
│ total_pages                     │
│ words_per_page                  │
│                                 │
│ === Status ===                  │
│ position                        │
│ is_active                       │
│ is_processed                    │
│ processed_at                    │
│ created_at                      │
│ updated_at                      │
└─────────────────────────────────┘
```

## Relationship Details

### 1. **Course → Module** (One-to-Many)
```php
// Course Model
public function modules() {
    return $this->hasMany(Module::class);
}

// Module Model
public function course() {
    return $this->belongsTo(Course::class);
}
```

### 2. **Module → Lesson** (One-to-Many)
```php
// Module Model
public function lessons() {
    return $this->hasMany(Lesson::class);
}

// Lesson Model
public function module() {
    return $this->belongsTo(Module::class);
}
```

### 3. **Lesson → Content** (One-to-One) ⭐ KEY RELATIONSHIP
```php
// Lesson Model
public function content() {
    return $this->hasOne(Content::class);
}

// Content Model
public function lesson() {
    return $this->belongsTo(Lesson::class);
}
```

**Important:**
- Each lesson can have **ONE** content
- Each content belongs to **ONE** lesson
- Foreign key: `contents.lesson_id` → `lessons.id`
- Cascading delete: If lesson is deleted, content is deleted

## Eager Loading in CourseController

### ✅ Correct Implementation (Current)
```php
$course = Course::with([
    'modules' => function ($query) {
        $query->orderBy('position');
    },
    'modules.lessons' => function ($query) {
        $query->orderBy('position');
    },
    'modules.lessons.content', // ✅ Load content relationship
    'modules.tasks'
])->find($course_id);
```

### ❌ Old Implementation (Removed)
```php
$course = Course::with([
    'modules.lessons.epub', // ❌ DELETED - no longer exists
])->find($course_id);
```

## API Response Structure

### Course with Nested Content
```json
{
  "id": 4,
  "title": "Course Title",
  "modules": [
    {
      "id": 4,
      "title": "Module Title",
      "lessons": [
        {
          "id": 5,
          "title": "Lesson Title",
          "content": {
            "id": 1,
            "title": "Introduction to Web Development",
            "type": "html",
            "total_pages": 3,
            "is_processed": true,
            "metadata": {
              "word_count": 850,
              "character_count": 5234,
              "image_count": 0
            }
          }
        }
      ]
    }
  ]
}
```

## Database Queries

### Get Lesson with Content
```php
$lesson = Lesson::with('content')->find(5);
$content = $lesson->content; // Content object or null
```

### Get Content with Lesson
```php
$content = Content::with('lesson')->find(1);
$lesson = $content->lesson; // Lesson object
```

### Get Course with All Content
```php
$course = Course::with('modules.lessons.content')->find(4);

foreach ($course->modules as $module) {
    foreach ($module->lessons as $lesson) {
        if ($lesson->content) {
            echo $lesson->content->title;
        }
    }
}
```

## Foreign Key Constraints

### Migration Definition
```php
// contents table
Schema::create('contents', function (Blueprint $table) {
    $table->id();
    $table->foreignId('lesson_id')
          ->constrained('lessons')
          ->onDelete('cascade'); // ⚠️ Delete content if lesson is deleted
    
    // ... other columns
});
```

**Cascade Behavior:**
- ✅ Delete lesson → content is automatically deleted
- ✅ Maintains referential integrity
- ✅ No orphaned content records

## Index Optimization

### Applied Indexes
```sql
-- contents table
INDEX idx_lesson_id (lesson_id)
INDEX idx_type (type)
INDEX idx_is_active (is_active)
INDEX idx_is_processed (is_processed)
```

**Performance Benefits:**
- ✅ Fast lookup by lesson_id
- ✅ Quick filtering by type/status
- ✅ Efficient queries in API endpoints

## Data Flow

### Import Process
```
1. Upload .docx file
   ↓
2. Save to: storage/app/public/uploads/contents/source/
   ↓
3. Convert to HTML (PHPWord)
   ↓
4. Extract images → storage/app/public/uploads/contents/images/
   ↓
5. Process HTML (clean, paginate)
   ↓
6. Create ZIP archive → storage/app/public/uploads/contents/archives/
   ↓
7. Save to database:
   - html_content (LONGTEXT)
   - json_content (JSON paginated)
   - images array (JSON)
   - zip_file_path
   - metadata
   ↓
8. Link to lesson via lesson_id
```

## Querying Examples

### Get All Lessons with Content
```php
$lessons = Lesson::whereHas('content', function($q) {
    $q->where('is_processed', true);
})->with('content')->get();
```

### Get Content Stats
```php
$stats = Content::selectRaw('
    COUNT(*) as total_contents,
    SUM(total_pages) as total_pages,
    AVG(total_pages) as avg_pages
')->first();
```

### Find Lessons Without Content
```php
$lessonsWithoutContent = Lesson::doesntHave('content')->get();
```

## Relationship Status

### ✅ Active Relationships
- `Course → Module` (hasMany)
- `Module → Lesson` (hasMany)
- `Lesson → Content` (hasOne) ⭐
- `Content → Lesson` (belongsTo) ⭐

### ❌ Removed Relationships
- `Lesson → Epub` (DELETED)
- `Epub → Lesson` (DELETED)

## Validation

Run this to verify relationships:
```bash
docker-compose exec app1 php artisan tinker
```

```php
// Check relationship works
$lesson = Lesson::with('content')->first();
echo $lesson->content ? "✅ Content loaded" : "❌ No content";

// Check reverse relationship
$content = Content::with('lesson')->first();
echo $content->lesson ? "✅ Lesson loaded" : "❌ No lesson";

// Check cascade
$course = Course::with('modules.lessons.content')->first();
echo "Course has " . $course->modules->sum(function($m) {
    return $m->lessons->filter(fn($l) => $l->content)->count();
}) . " lessons with content";
```

## Summary

**Relationship Type:** One-to-One (Lesson ↔ Content)

**Foreign Key:** `contents.lesson_id` → `lessons.id`

**Cascade:** Delete lesson → delete content

**Eager Loading:** `'modules.lessons.content'`

**Status:** ✅ Fully Integrated & Working

**Performance:** ✅ Indexed & Optimized

All relationships are properly configured and the system is ready for production! 🚀
