# 🧪 AChance API Testing Suite

This directory contains comprehensive tests and utilities for the AChance Quiz API system.

## 📁 Directory Structure

```
tests/
├── api/                    # API endpoint tests
│   ├── test_quiz_api.php          # Main Quiz API test (Postman style)
│   ├── test-import.php            # Quiz import functionality test
│   └── test-zip-import-full.php   # ZIP import test
├── scripts/                # HTTP testing scripts
│   ├── test_quiz_http.sh          # Bash/curl HTTP tests
│   └── test_quiz_http.ps1         # PowerShell HTTP tests
├── utilities/              # Testing utilities and helpers
│   ├── get_admin_token.php        # Generate admin authentication tokens
│   ├── get_token.php              # General token generation
│   ├── check_permissions.php      # Verify user permissions
│   ├── debug-csv.php              # CSV import debugging
│   └── debug-parse.php            # Data parsing debugging
└── postman/                # Postman collections (existing)
    ├── Quiz_API_Tests.postman_collection.json
    ├── Quiz_API_Environment.postman_environment.json
    └── *.md                       # Documentation files
```

## 🚀 Quick Start

### Prerequisites
- Docker and Docker Compose installed
- Laravel environment running
- Database seeded with permissions and users

### Running Tests

#### 1. Full API Test Suite (Recommended)
```bash
# Run inside Docker container
docker-compose exec app1 php tests/api/test_quiz_api.php

# Or run locally (requires proper .env setup)
cd src
php tests/api/test_quiz_api.php
```

#### 2. HTTP Tests (External API Testing)
```bash
# Using bash/curl
bash tests/scripts/test_quiz_http.sh

# Using PowerShell
powershell -ExecutionPolicy Bypass -File "tests/scripts/test_quiz_http.ps1"
```

#### 3. Import Tests
```bash
# Test basic import functionality
php tests/api/test-import.php

# Test ZIP file import
php tests/api/test-zip-import-full.php
```

## 📋 Test Coverage

### 🔐 Authentication Tests
- [x] Admin login with Sanctum tokens
- [x] User permission verification
- [x] Token validation and refresh

### 📚 Quiz CRUD Operations  
- [x] Create quiz with validation
- [x] List all quizzes with pagination
- [x] Get quiz details with questions
- [x] Update quiz information
- [x] Delete quiz with cleanup

### ❓ Question Management
- [x] Add questions with multiple answers
- [x] Update question content and scoring
- [x] Delete questions with answer cleanup
- [x] Question ordering and numbering

### 🎯 Quiz Taking Workflow
- [x] Publish/unpublish quizzes
- [x] Start quiz attempts
- [x] Submit individual answers
- [x] Complete quiz with scoring
- [x] Calculate final scores

### 📊 Analytics & Results
- [x] Individual attempt results
- [x] User attempt history
- [x] Quiz statistics and metrics
- [x] Leaderboards and rankings

### ⚠️ Error Handling
- [x] Authentication failures (401)
- [x] Authorization errors (403)
- [x] Resource not found (404)
- [x] Validation errors (400)
- [x] Server errors (500)

### 📥 Import/Export
- [x] CSV quiz import
- [x] ZIP file batch import
- [x] Data validation and parsing
- [x] Error reporting and logging

## 🛠️ Utilities

### Token Generation
```bash
# Generate admin token for API testing
php tests/utilities/get_admin_token.php

# Generate user token
php tests/utilities/get_token.php
```

### Permission Management
```bash
# Check user permissions
php tests/utilities/check_permissions.php
```

### Debugging Tools
```bash
# Debug CSV import issues
php tests/utilities/debug-csv.php

# Debug data parsing
php tests/utilities/debug-parse.php
```

## 🔧 Configuration

### Environment Variables
Ensure these are set in your `.env` file:
```env
DB_CONNECTION=mysql
DB_HOST=mysql  # or localhost for local testing
REDIS_HOST=redis-master  # or localhost
API_RATE_LIMIT=60
```

### Database Setup
```bash
# Run migrations
php artisan migrate

# Seed permissions and users
php artisan db:seed
```

## 📊 Test Results Format

All tests output structured results:
- ✅ Success indicators
- ❌ Failure indicators  
- 📊 Summary statistics
- 🔧 API endpoint coverage
- 🎯 Performance metrics

### Example Output
```
🧪 Starting Quiz API Tests (Postman Style)...
✅ Authentication Tests (2/2)
✅ Quiz CRUD Operations (4/4)  
✅ Quiz Questions Management (4/4)
✅ Quiz Publishing and Taking (5/5)
✅ Quiz Results and Analytics (3/3)
✅ Error Handling Tests (5/5)
✅ Cleanup (2/2)
🚀 Total Tests: 25/25 Passed
```

## 🚀 CI/CD Integration

These tests can be integrated into your CI/CD pipeline:

```yaml
# Example GitHub Actions
- name: Run API Tests
  run: docker-compose exec app1 php tests/api/test_quiz_api.php

- name: Run HTTP Tests  
  run: bash tests/scripts/test_quiz_http.sh
```

## 🐛 Troubleshooting

### Common Issues

1. **Database Connection Errors**
   ```bash
   # Check Docker services
   docker-compose ps
   
   # Restart services
   docker-compose restart mysql redis-master
   ```

2. **Permission Errors**
   ```bash
   # Run permission setup
   php tests/utilities/check_permissions.php
   ```

3. **Token Issues**
   ```bash
   # Generate fresh admin token
   php tests/utilities/get_admin_token.php
   ```

### Debug Mode
Set `APP_DEBUG=true` in `.env` for detailed error output.

## 📚 Documentation

- [Quiz System Guide](../QUIZ_SYSTEM_GUIDE.md)
- [Testing Instructions](postman/TESTING_INSTRUCTIONS.md)
- [Postman Collection Usage](postman/README.md)

## 🤝 Contributing

When adding new tests:
1. Follow the existing naming convention
2. Add comprehensive error handling
3. Update this README with new test descriptions
4. Ensure cleanup operations are included

## 📞 Support

For issues with testing:
1. Check the troubleshooting section above
2. Review Laravel logs: `storage/logs/laravel.log`
3. Verify Docker container status: `docker-compose ps`
4. Check database connectivity and permissions

---

🎉 **Happy Testing!** Your Quiz API is thoroughly tested and ready for production! 🚀
