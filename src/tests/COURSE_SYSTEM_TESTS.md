# 📚 Course System API Tests - Complete Guide

This guide covers the comprehensive test suite for the Course System API, including courses, modules, lessons, epubs, and tasks functionality.

## 🚀 Quick Start

### One-Command Test Execution

```powershell
# Navigate to the scripts directory and run tests
cd src\tests\scripts
.\run-course-system-tests.ps1 -InstallNewman -Verbose
```

This single command will:
- ✅ Install Newman (Postman CLI) if needed
- ✅ Check all prerequisites
- ✅ Test API connectivity
- ✅ Run all course system tests
- ✅ Generate detailed HTML reports
- ✅ Show comprehensive test results

## 📋 What Gets Tested

### 🎓 Complete Course Workflow
1. **Teacher Authentication** → Login and get auth token
2. **Course Creation** → Create a new programming course
3. **Module Addition** → Add introduction module to course
4. **Lesson Creation** → Create lesson with content
5. **Epub Upload** → Add epub book to lesson
6. **Task Assignment** → Create programming assignment
7. **Student Workflow** → Student login and task submission
8. **Teacher Review** → Teacher views student submissions
9. **Cleanup** → Remove all test data

### 🔒 Security & Permissions
- Teacher vs Student role separation
- Authentication token management
- Authorization for CRUD operations
- Cross-role access restrictions

### 📊 Data Integrity
- Proper parent-child relationships
- Cascade delete operations
- Unique identifier generation
- Error handling for edge cases

## 📁 Generated Reports

After running tests, you'll find:

```
test-results/
├── course-system-test-report-2025-08-30_14-30-15.html  # Interactive HTML report
├── course-system-test-report-2025-08-30_14-30-15.json  # Raw test data
└── temp_environment.json                               # Test environment config
```

### 📈 HTML Report Features
- ✅ Visual pass/fail indicators
- 📊 Response time graphs
- 🔍 Request/response details
- 📋 Test assertion results
- 🎯 Overall success metrics

## 🛠️ Advanced Usage

### Custom API URL
```powershell
# Test against different environment
.\run-course-system-tests.ps1 -BaseUrl "https://your-api.com/api"
```

### Different Report Formats
```powershell
# Generate JUnit XML for CI/CD
.\run-course-system-tests.ps1 -ReportFormat "junit"

# Generate JSON for automation
.\run-course-system-tests.ps1 -ReportFormat "json"
```

### Verbose Debugging
```powershell
# See detailed request/response data
.\run-course-system-tests.ps1 -Verbose
```

## 🔧 Manual Newman Execution

For advanced users who want direct control:

```bash
# Navigate to postman directory
cd src/tests/postman

# Run with custom options
newman run Course_System_Complete_Tests.postman_collection.json \
  --environment Course_System_Test_Environment.postman_environment.json \
  --reporters cli,html,json \
  --reporter-html-export detailed-report.html \
  --reporter-json-export raw-data.json \
  --delay-request 100 \
  --timeout-request 30000 \
  --verbose
```

## 🎯 Test Scenarios Covered

### Authentication Flow
```
1. Teacher Login → Get teacher token
2. Student Login → Get student token  
3. Token Validation → Verify token works
4. Role Switching → Switch between user types
```

### Course Management
```
1. Create Course → POST /api/courses
2. List Courses → GET /api/courses/all
3. Get Course → GET /api/courses/{id}
4. Update Course → PUT /api/courses/{id}
5. Delete Course → DELETE /api/courses/{id}
```

### Module Management
```
1. Add Module → POST /api/courses/{id}/modules
2. List Modules → GET /api/courses/{id}/modules
3. Get Module → GET /api/courses/{id}/modules/{id}
4. Update Module → PUT /api/courses/{id}/modules/{id}
5. Delete Module → DELETE /api/courses/{id}/modules/{id}
```

### Lesson Management
```
1. Create Lesson → POST /api/courses/{id}/modules/{id}/lessons
2. List Lessons → GET /api/courses/{id}/modules/{id}/lessons
3. Get Lesson → GET /api/courses/{id}/modules/{id}/lessons/{id}
4. Update Lesson → PUT /api/courses/{id}/modules/{id}/lessons/{id}
5. Delete Lesson → DELETE /api/courses/{id}/modules/{id}/lessons/{id}
```

### Epub Management
```
1. Create Epub → POST /api/courses/{id}/modules/{id}/lessons/{id}/epubs
2. Get Epub → GET /api/courses/{id}/modules/{id}/lessons/{id}/epubs
3. Update Epub → PUT /api/courses/{id}/modules/{id}/lessons/{id}/epubs/{id}
4. Delete Epub → DELETE /api/courses/{id}/modules/{id}/lessons/{id}/epubs/{id}
```

### Task Management
```
1. Create Task → POST /api/courses/{id}/modules/{id}/tasks
2. List Tasks → GET /api/courses/{id}/modules/{id}/tasks
3. Get Task → GET /api/courses/{id}/modules/{id}/tasks/{id}
4. Update Task → PUT /api/courses/{id}/modules/{id}/tasks/{id}
5. Student Submit → POST /api/courses/{id}/modules/{id}/tasks/{id}/answers
6. Teacher View → GET /api/courses/{id}/modules/{id}/tasks/{id}/answers/{id}
7. Delete Answer → DELETE /api/courses/{id}/modules/{id}/tasks/{id}/answers/{id}
8. Delete Task → DELETE /api/courses/{id}/modules/{id}/tasks/{id}
```

## 🎭 Test Data Examples

The tests use realistic data to ensure comprehensive coverage:

### Sample Course Data
```json
{
  "title": "Course_1725026415789_456 - Programming Course",
  "description": "Learn programming fundamentals step by step",
  "level": "beginner",
  "category": "technology"
}
```

### Sample Task Assignment
```json
{
  "title": "Task_1725026415789_456 - Programming Assignment",
  "description": "Create a simple calculator program using the concepts learned in this module",
  "type": "assignment",
  "due_date": "2025-09-15T23:59:59Z",
  "max_score": 100
}
```

### Sample Student Submission
```json
{
  "answer": "Here is my calculator program:\n\n```python\ndef calculator():\n    # Implementation here\n```\n\nThis calculator handles basic arithmetic operations and includes error handling.",
  "submission_type": "text"
}
```

## 🚨 Troubleshooting

### Prerequisites Check
The script automatically checks for:
- ✅ Node.js installation
- ✅ Newman (Postman CLI) availability
- ✅ Collection and environment files
- ✅ API server connectivity

### Common Solutions

| Issue | Solution |
|-------|----------|
| Newman not found | Use `-InstallNewman` flag |
| API not responding | Check if server is running at correct URL |
| Authentication fails | Verify teacher/student accounts exist |
| Permission errors | Run PowerShell as Administrator |
| Script blocked | Run `Set-ExecutionPolicy RemoteSigned` |

### Debug Mode Output
```
=====================================================
    COURSE SYSTEM API TESTS RUNNER
=====================================================
Base URL: http://localhost:80/api
Report Format: html
Output Directory: test-results
=====================================================

[1/6] Checking prerequisites...
✓ Node.js is installed: v18.17.0
✓ Newman is installed: 5.3.2
✓ Collection file found: D:\path\to\collection.json
✓ Environment file found: D:\path\to\environment.json

[2/6] Testing API connection...
✓ API is accessible (401 Unauthorized is expected without auth)

[3/6] Preparing output directory...
✓ Output directory prepared: test-results

[4/6] Updating environment configuration...
✓ Updated base URL to: http://localhost:80/api

[5/6] Running Course System API tests...
Running: newman run collection.json --environment env.json --reporters cli,json,html

[6/6] Test Results Summary
📈 DETAILED STATISTICS:
   Total Requests: 28
   Failed Requests: 0
   Total Assertions: 84
   Failed Assertions: 0
   Average Response Time: 145ms

🎉 ALL TESTS PASSED! 🎉
```

## 🎯 Success Metrics

A successful test run should show:
- **28 Total Requests** (all endpoints tested)
- **0 Failed Requests** (all API calls successful)
- **84+ Total Assertions** (comprehensive validations)
- **0 Failed Assertions** (all tests passed)
- **< 500ms Average Response Time** (good performance)

## 🔄 CI/CD Integration

### GitHub Actions Example
```yaml
name: Course System Tests
on: [push, pull_request]
jobs:
  test:
    runs-on: windows-latest
    steps:
      - uses: actions/checkout@v3
      - name: Setup Node.js
        uses: actions/setup-node@v3
        with:
          node-version: '18'
      - name: Run Course System Tests
        run: |
          cd src/tests/scripts
          ./run-course-system-tests.ps1 -InstallNewman -ReportFormat junit
        shell: powershell
      - name: Publish Results
        uses: dorny/test-reporter@v1
        with:
          name: Course System Tests
          path: 'test-results/*.xml'
          reporter: jest-junit
```

## 📊 What Makes This Test Suite Special

### 🔄 Automatic Cleanup
- Creates unique test data for each run
- Automatically removes all created resources
- No leftover data polluting your database

### 🎭 Role-Based Testing  
- Switches between teacher and student tokens
- Tests proper access control
- Validates role-specific operations

### 📈 Comprehensive Reporting
- Beautiful HTML reports with charts
- JSON data for automation
- JUnit XML for CI/CD integration

### 🔍 Deep Validation
- Tests data relationships
- Validates response structures
- Checks error handling
- Measures performance

---

🎉 **Ready to test your Course System API?** Run the script and watch your API get thoroughly validated! 🚀
