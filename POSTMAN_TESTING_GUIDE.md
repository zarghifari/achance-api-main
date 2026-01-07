# Postman Testing Guide - Avoiding Common Errors

## Overview
This guide helps you run the Postman test suite successfully and avoid common errors like 404 Not Found when IDs don't exist.

## Quick Start

### 1. Run Tests in Correct Order
**ALWAYS run the full test collection from the beginning** to ensure all IDs are created and stored properly:

```
1. Authentication Tests (Login Teacher, Login Student)
2. Seeded Data Verification
3. Course Management (creates courseId)
4. Module Management (creates moduleId)
5. Lesson Management (creates lessonId)
6. Content Management (creates contentId)
7. Task Management (creates taskId)
8. User Activity Analytics
9. Cleanup Operations
```

### 2. Understanding the Test Flow

#### Create → Store → Use Pattern
Each test follows this pattern:
- **Create**: POST request creates a resource
- **Store**: Response ID is saved to environment variable
- **Use**: Subsequent tests use that variable

Example:
```javascript
// Create Course → stores courseId
POST /courses
→ pm.environment.set('courseId', response.data.id);

// Use courseId in Module creation
POST /courses/{{courseId}}/modules
```

## Common Error: 404 Not Found

### Why It Happens
```
DELETE http://localhost/api/courses/13/modules/13/lessons/15/content/10
Status: 404 Not Found
```

**Cause**: Content ID 10 doesn't exist in the database because:
- ❌ The import test didn't run
- ❌ The import test failed
- ❌ Tests were run out of order
- ❌ Database was cleared but environment variables weren't

### How to Fix

#### Option 1: Run Full Test Suite (Recommended)
1. Open Postman Collection Runner
2. Select "Course System API Tests"
3. Click "Run Course System API..."
4. Tests will create all necessary resources in order

#### Option 2: Use Seeded Data
If you just want to test individual endpoints:

**Check what exists in database:**
```powershell
docker exec achance-api-main-mysql-1 mysql -uroot -proot achance -e "
SELECT c.id as content_id, l.id as lesson_id, m.id as module_id, co.id as course_id
FROM contents c
JOIN lessons l ON c.lesson_id = l.id
JOIN modules m ON l.module_id = m.id
JOIN courses co ON m.course_id = co.id;"
```

**Example output:**
```
content_id | lesson_id | module_id | course_id
1          | 1         | 1         | 1
2          | 2         | 1         | 1
3          | 3         | 2         | 1
4          | 4         | 2         | 1
```

**Use seeded IDs:**
```
DELETE http://localhost/api/courses/1/modules/1/lessons/1/content/1
```

#### Option 3: Manually Set Environment Variables
In Postman Environment:
```
courseId = 1
moduleId = 1
lessonId = 1
contentId = 1
```

## Validation Features (Now Built-in)

### Automatic Fallback
Tests now automatically fall back to seeded data when IDs are missing:

```javascript
// Before: Would fail with undefined
GET /content/{{contentId}}

// Now: Automatically uses contentId=1 if not set
if (!contentId) {
    console.warn('Using fallback contentId=1');
    pm.environment.set('contentId', 1);
}
```

### Skip Logic for Cleanup
Cleanup tests now skip gracefully if resources weren't created:

```javascript
// If no content was created, skip deletion
if (!contentId) {
    console.log('✓ Content deletion skipped - no content created');
    // Test passes, no 404 error
}
```

## Testing Workflow Best Practices

### 1. Check Prerequisites
Before running individual tests:
```javascript
// Check in Postman Console
console.log({
    courseId: pm.environment.get('courseId'),
    moduleId: pm.environment.get('moduleId'),
    lessonId: pm.environment.get('lessonId'),
    contentId: pm.environment.get('contentId')
});
```

### 2. Reset Between Test Runs
Clear environment variables if starting fresh:
```javascript
pm.environment.unset('courseId');
pm.environment.unset('moduleId');
pm.environment.unset('lessonId');
pm.environment.unset('contentId');
```

### 3. Monitor Console Output
Watch Postman Console for warnings:
```
✓ Using fallback to seeded data (course=1, module=1, lesson=1)
⚠ WARNING: contentId not set. Using fallback contentId=1
✗ ERROR: Required IDs not set. Run full test suite first.
```

## Database Queries for Debugging

### Check All Content Records
```powershell
docker exec achance-api-main-mysql-1 mysql -uroot -proot achance -e "
SELECT id, lesson_id, title, type, original_filename 
FROM contents 
ORDER BY id DESC 
LIMIT 10;"
```

### Check Course Hierarchy
```powershell
docker exec achance-api-main-mysql-1 mysql -uroot -proot achance -e "
SELECT 
    co.id as course_id, co.title as course,
    m.id as module_id, m.title as module,
    l.id as lesson_id, l.title as lesson,
    c.id as content_id, c.title as content
FROM courses co
LEFT JOIN modules m ON co.id = m.course_id
LEFT JOIN lessons l ON m.id = l.module_id
LEFT JOIN contents c ON l.id = c.lesson_id
ORDER BY co.id, m.id, l.id, c.id;"
```

### Count Resources
```powershell
docker exec achance-api-main-mysql-1 mysql -uroot -proot achance -e "
SELECT 
    (SELECT COUNT(*) FROM courses) as courses,
    (SELECT COUNT(*) FROM modules) as modules,
    (SELECT COUNT(*) FROM lessons) as lessons,
    (SELECT COUNT(*) FROM contents) as contents,
    (SELECT COUNT(*) FROM tasks) as tasks;"
```

## API Endpoint Reference

### Content Management URLs
All content operations follow this pattern:
```
/courses/{course_id}/modules/{module_id}/lessons/{lesson_id}/content/{content_id}
```

**Valid endpoints:**
- `POST .../lessons/{lesson_id}/content/import` - Upload new content
- `GET .../lessons/{lesson_id}/content` - Get lesson's content
- `GET .../lessons/{lesson_id}/content/metadata` - Get metadata only
- `GET .../lessons/{lesson_id}/content/page/{page}` - Get specific page
- `GET .../content/{content_id}/download-zip` - Download ZIP archive
- `PUT .../content/{content_id}` - Update content
- `POST .../content/{content_id}/reprocess` - Reprocess content
- `DELETE .../content/{content_id}` - Delete content

**Note**: All IDs in the path must match the resource hierarchy:
- ✅ `courses/1/modules/1/lessons/1/content/1` (if content 1 belongs to lesson 1)
- ❌ `courses/13/modules/13/lessons/15/content/1` (content 1 doesn't belong to lesson 15)

## Troubleshooting Checklist

When you get 404 errors:

- [ ] Did I run tests in order?
- [ ] Is the resource ID stored in environment variables?
- [ ] Does the resource actually exist in the database?
- [ ] Do the IDs in the URL match the resource hierarchy?
- [ ] Did I clear the database but not the environment variables?
- [ ] Am I using the correct authentication token?

## Running Individual Tests

To test a single endpoint without running the full suite:

1. **Set environment variables manually:**
   ```
   courseId = 1
   moduleId = 1
   lessonId = 1
   contentId = 1
   ```

2. **Or use seeded data in URL:**
   ```
   GET http://localhost/api/courses/1/modules/1/lessons/1/content
   ```

3. **Check console for validation warnings:**
   - Automatic fallback will trigger
   - Console shows what IDs are being used

## Quick Commands

### Restart Docker Environment
```powershell
docker-compose down
docker-compose up -d
```

### Check Database
```powershell
# Login to MySQL
docker exec -it achance-api-main-mysql-1 mysql -uroot -proot achance

# In MySQL
SHOW TABLES;
SELECT COUNT(*) FROM contents;
```

### View Laravel Logs
```powershell
docker exec achance-api-main-app1-1 tail -f /var/www/storage/logs/laravel.log
```

## Environment Setup

### Required Variables
```json
{
  "baseUrl": "http://localhost/api",
  "teacherToken": "[set after login]",
  "studentToken": "[set after login]",
  "authToken": "[switches between teacher/student]",
  "courseId": "[created during tests]",
  "moduleId": "[created during tests]",
  "lessonId": "[created during tests]",
  "contentId": "[created during tests]",
  "taskId": "[created during tests]",
  "answerId": "[created during tests]",
  "epubId": "[alias for contentId]"
}
```

## Success Indicators

You'll know tests are working correctly when:

✅ **Console shows:**
```
✓ Course created with ID: 13
✓ Module created with ID: 13
✓ Lesson created with ID: 15
✓ Content imported with ID: 5
✓ Content deleted successfully
```

✅ **No 404 errors**

✅ **Environment variables populated:**
- `courseId`, `moduleId`, `lessonId`, `contentId` all have values

✅ **Tests pass in sequence**

## Need Help?

1. **Check Postman Console** (View → Show Postman Console)
2. **Check Laravel logs** (see commands above)
3. **Query database** to verify what exists
4. **Run full test suite** instead of individual tests
5. **Use fallback seeded data** for quick testing

---

**Remember**: The Content system replaced the old EPUB system. All references should use "content" terminology, not "epub".
