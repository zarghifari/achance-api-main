# EPUB Test Files Reference

## Available EPUB Files

The system includes several EPUB files for testing purposes:

### 1. Books Sample (1.9 MB)
- **File**: `1761367747_books-sample.epub` / `books-sample.epub`
- **Size**: ~1.9 MB (1,992,294 bytes)
- **Location**: `storage/app/public/uploads/epubs/`
- **Database ID**: 1, 4
- **Purpose**: General testing, medium-size EPUB
- **Format**: Valid EPUB/ZIP format

### 2. CorelDRAW DDKV Semester 2 (15 MB)
- **File**: `coreldraw-ddkv-sem2.epub` / `coreldraw_ddkv_sem2.epub`
- **Size**: ~15 MB (14,982,433 bytes)
- **Location**: `storage/app/public/uploads/epubs/`
- **Database ID**: 2, 3
- **Purpose**: Large file testing, performance testing
- **Format**: Valid EPUB/ZIP format

## Database Seeding

The seeder automatically:
1. Scans `public/uploads/epubs/` directory
2. Finds all `.epub` files
3. Creates EPUB records for each lesson
4. Validates file format (checks ZIP signature)
5. Sets `is_active = true` only for valid EPUBs

## Test Expectations

### Postman Tests
The Postman collection expects:

**Download Epub File (Teacher):**
- Status: 200
- Content-Type: `application/epub+zip`
- File Size: > 1000 bytes (basic validation)

**Download Seeded Epub File:**
- Status: 200
- Content-Type: `application/epub+zip`
- File Size: > 1000 bytes
- Books Sample: > 100,000 bytes (100 KB)

### File Size Validation Logic
```javascript
// Postman test example
pm.test('Download Epub - File content received', function () {
    const responseSize = pm.response.responseSize;
    pm.expect(responseSize).to.be.greaterThan(1000);
});

pm.test('Download Epub - Books Sample file', function () {
    const responseSize = pm.response.responseSize;
    const epubTitle = pm.environment.get('epubTitle');
    
    if (epubTitle && epubTitle.includes('Books Sample')) {
        pm.expect(responseSize).to.be.greaterThan(100000);
        console.log('Books Sample EPUB size:', responseSize, 'bytes');
    }
});
```

## Setup Requirements

### Initial Setup
```bash
# 1. Create storage link
docker-compose exec app1 php artisan storage:link

# 2. Ensure EPUB files exist
docker-compose exec app1 ls -lh storage/app/public/uploads/epubs/

# 3. Copy real EPUB to standard name (important!)
docker-compose exec app1 cp \
  storage/app/public/uploads/epubs/1761367747_books-sample.epub \
  storage/app/public/uploads/epubs/books-sample.epub

# 4. Update database with correct file size
docker-compose exec mysql mysql -uroot -proot achance \
  -e "UPDATE epubs SET file_size = 1992294 WHERE file_path = 'uploads/epubs/books-sample.epub';"

# 5. Seed database
docker-compose exec app1 php artisan db:seed
```

### Automated Setup
```powershell
.\setup-epub-storage.ps1
```

## Common Issues

### Issue 1: File Size Too Small (74 bytes)
**Symptom:**
```
Download Seeded Epub - File content received | AssertionError: expected 74 to be above 1000
```

**Cause:** The `books-sample.epub` is a placeholder text file (74 bytes), not a real EPUB.

**Fix:**
```bash
# Copy real EPUB file
docker-compose exec app1 cp \
  storage/app/public/uploads/epubs/1761367747_books-sample.epub \
  storage/app/public/uploads/epubs/books-sample.epub

# Update database
docker-compose exec mysql mysql -uroot -proot achance \
  -e "UPDATE epubs SET file_size = 1992294 WHERE file_path = 'uploads/epubs/books-sample.epub';"
```

### Issue 2: File Not Found (404)
**Symptom:**
```json
{
    "message": "File not found"
}
```

**Fix:**
```bash
# Ensure storage link exists
docker-compose exec app1 php artisan storage:link

# Verify file exists
docker-compose exec app1 ls -lh storage/app/public/uploads/epubs/books-sample.epub
```

### Issue 3: Invalid EPUB Format
**Symptom:** File downloads but fails to open in EPUB readers

**Fix:** Ensure you're using actual EPUB files, not text placeholders. EPUB files should:
- Be ZIP archives (signature: `PK\x03\x04`)
- Contain `mimetype` file with `application/epub+zip`
- Have proper EPUB structure

## File Storage Architecture

```
Project Root
├── src/
│   ├── sample.epub                          # Placeholder (74 bytes) ⚠️
│   ├── public/
│   │   ├── storage/                        # Symlink → storage/app/public
│   │   └── uploads/
│   │       └── epubs/                      # Legacy location (not used)
│   └── storage/
│       └── app/
│           └── public/
│               └── uploads/
│                   └── epubs/              # Actual storage location ✓
│                       ├── books-sample.epub              (1.9 MB)
│                       ├── 1761367747_books-sample.epub   (1.9 MB)
│                       ├── 1761368029_books-sample.epub   (1.9 MB)
│                       ├── coreldraw-ddkv-sem2.epub       (15 MB)
│                       └── coreldraw_ddkv_sem2.epub       (15 MB)
```

### Access URLs
- **API Download**: `/api/courses/{id}/modules/{id}/lessons/{id}/epubs/{id}/download`
- **Direct Access**: `/storage/uploads/epubs/books-sample.epub` (after `storage:link`)

## Database Schema

```sql
CREATE TABLE epubs (
    id BIGINT PRIMARY KEY,
    lesson_id BIGINT,
    title VARCHAR(255),
    file_path VARCHAR(255),        -- 'uploads/epubs/books-sample.epub'
    original_filename VARCHAR(255), -- 'books-sample.epub'
    file_size BIGINT,              -- 1992294 (bytes)
    mime_type VARCHAR(100),         -- 'application/epub+zip'
    position INT,
    is_active BOOLEAN               -- Only true for valid EPUBs
);
```

## Performance Considerations

### File Size Impact
- **Books Sample (1.9 MB)**: ~200-300ms download time
- **CorelDRAW (15 MB)**: ~800-1200ms download time
- **Network**: Varies by connection speed

### Optimization
- ✅ Files served directly from Docker volume (fast)
- ✅ OPcache enabled for PHP code
- ✅ No compression (EPUBs are already compressed ZIP files)
- ✅ Direct file streaming via Laravel response

## Testing Checklist

Before running Postman tests:
- [ ] Storage link created: `ls -l src/public/storage`
- [ ] EPUB files exist: `ls -lh src/storage/app/public/uploads/epubs/`
- [ ] books-sample.epub is real file (>1MB): `du -h src/storage/app/public/uploads/epubs/books-sample.epub`
- [ ] Database seeded: `SELECT COUNT(*) FROM epubs;` should return 4
- [ ] File sizes correct in DB: `SELECT file_size FROM epubs;` should show 1992294, 14982433
- [ ] API accessible: `curl http://localhost/api/health`

## Maintenance

### Adding New EPUB Files
1. Copy `.epub` file to `src/public/uploads/epubs/`
2. Run seeder: `php artisan db:seed --class=CourseSeeder`
3. Seeder will automatically detect and import the new file

### Updating Existing EPUBs
1. Replace file in `storage/app/public/uploads/epubs/`
2. Update database:
   ```sql
   UPDATE epubs 
   SET file_size = <new_size>, 
       original_filename = '<new_name>'
   WHERE id = <epub_id>;
   ```
3. Clear cache: `php artisan cache:clear`

### Removing EPUBs
1. Delete via API: `DELETE /api/courses/{id}/modules/{id}/lessons/{id}/epubs/{id}`
2. Or manually:
   ```sql
   DELETE FROM epubs WHERE id = <epub_id>;
   ```
3. Clean up file: `rm storage/app/public/uploads/epubs/<filename>`

## Security Notes

- ✅ Authentication required for download (Bearer token)
- ✅ User permissions checked (`view courses`)
- ✅ File path validated (no directory traversal)
- ✅ MIME type validated
- ✅ Activity tracking (downloads logged)

## Related Documentation

- [EPUB_DOWNLOAD_FIX.md](EPUB_DOWNLOAD_FIX.md) - Fix for 404 errors
- [COMMON_ISSUES.md](COMMON_ISSUES.md) - Troubleshooting guide
- [PERFORMANCE_TESTING.md](PERFORMANCE_TESTING.md) - Performance testing
- [README.md](README.md) - Setup guide
