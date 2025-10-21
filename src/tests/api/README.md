# 🔧 API Tests

This directory contains comprehensive API endpoint tests for the AChance Quiz system.

## 📁 Files

### `test_quiz_api.php` - Main Quiz API Test Suite
- **Purpose**: Comprehensive test matching Postman collection structure
- **Coverage**: 25 test scenarios covering full quiz workflow
- **Usage**: `php test_quiz_api.php`
- **Features**:
  - Authentication with Laravel Sanctum
  - Quiz CRUD operations
  - Question and answer management
  - Quiz attempt workflow
  - Score calculation and analytics
  - Error handling (401, 403, 404, 400)
  - Database cleanup

### `test-import.php` - Quiz Import Test
- **Purpose**: Test quiz import functionality from various sources
- **Coverage**: CSV parsing, data validation, batch operations
- **Usage**: `php test-import.php`

### `test-zip-import-full.php` - ZIP Import Test  
- **Purpose**: Test bulk quiz import from ZIP files
- **Coverage**: File extraction, batch processing, error handling
- **Usage**: `php test-zip-import-full.php`

## 🚀 Running Tests

### Inside Docker (Recommended)
```bash
# Main test suite
docker-compose exec app1 php tests/api/test_quiz_api.php

# Import tests
docker-compose exec app1 php tests/api/test-import.php
docker-compose exec app1 php tests/api/test-zip-import-full.php
```

### Local Environment
```bash
cd src
php tests/api/test_quiz_api.php
```

## 📊 Expected Results

- ✅ **25/25 tests passing** for main test suite
- 🎯 **100% score** on quiz attempts
- 📈 **Complete analytics** data generation
- 🧹 **Clean database** state after tests

## 🔧 Requirements

- Laravel environment running
- Database with migrations applied
- Admin user with proper permissions
- Redis for caching (optional but recommended)
