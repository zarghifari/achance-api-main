# 🌐 HTTP Test Scripts

This directory contains HTTP testing scripts that test the Quiz API from external clients.

## 📁 Files

### `test_quiz_http.sh` - Bash/Curl HTTP Tests
- **Purpose**: Test API endpoints using curl commands
- **Platform**: Linux, macOS, WSL, Git Bash
- **Usage**: `bash test_quiz_http.sh`
- **Features**:
  - Authentication flow testing
  - JSON request/response handling
  - Error code validation
  - Cross-platform compatibility

### `test_quiz_http.ps1` - PowerShell HTTP Tests
- **Purpose**: Test API endpoints using PowerShell's Invoke-RestMethod
- **Platform**: Windows PowerShell, PowerShell Core
- **Usage**: `powershell -ExecutionPolicy Bypass -File "test_quiz_http.ps1"`
- **Features**:
  - Native Windows testing
  - Structured output formatting
  - Error handling with detailed messages
  - JSON processing with PowerShell objects

## 🚀 Usage Examples

### Bash Script
```bash
# Make executable (Linux/macOS)
chmod +x tests/scripts/test_quiz_http.sh

# Run tests
bash tests/scripts/test_quiz_http.sh

# Or with custom base URL
BASE_URL="https://api.example.com" bash tests/scripts/test_quiz_http.sh
```

### PowerShell Script
```powershell
# Run with execution policy bypass
powershell -ExecutionPolicy Bypass -File "tests/scripts/test_quiz_http.ps1"

# Or in PowerShell session
Set-ExecutionPolicy -ExecutionPolicy Bypass -Scope Process
.\tests\scripts\test_quiz_http.ps1
```

## 🔧 Configuration

### Environment Variables
Both scripts support these environment variables:

```bash
# Base API URL (default: http://localhost/api)
export BASE_URL="http://localhost/api"

# Admin credentials (default: admin@example.com / password)
export ADMIN_EMAIL="admin@example.com"
export ADMIN_PASSWORD="password"
```

### Server Requirements
- Laravel server running on specified port
- Database accessible and seeded
- Admin user with quiz permissions

## 📊 Test Coverage

Both scripts test the same endpoints:
- `POST /login` - Authentication
- `GET /user` - Current user
- `POST /quizzes` - Create quiz
- `GET /quizzes` - List quizzes
- `GET /quizzes/{id}` - Get quiz details
- `PUT /quizzes/{id}` - Update quiz
- `POST /quizzes/{id}/questionswithanswers` - Add questions
- `GET /quizzes/{id}/questions` - Get questions
- `GET /quizzes/{id}/statistics` - Quiz analytics
- `DELETE /quizzes/{id}` - Delete quiz

## 🐛 Troubleshooting

### Common Issues

1. **Connection Refused**
   ```bash
   # Check if Laravel server is running
   curl http://localhost/api/health
   
   # Start Laravel server
   php artisan serve --port=80
   ```

2. **Authentication Errors**
   ```bash
   # Verify admin user exists
   php artisan tinker
   >>> App\Models\User::where('email', 'admin@example.com')->first()
   ```

3. **Permission Errors**
   ```bash
   # Check user permissions
   php tests/utilities/check_permissions.php
   ```

### Debug Mode
Set `set -x` in bash script or `$VerbosePreference = "Continue"` in PowerShell for detailed output.

## 🎯 Expected Output

Successful test run should show:
```
🧪 Starting HTTP Quiz API Tests...
✅ Admin Login successful
✅ Quiz Creation successful
✅ Question Creation successful
✅ Quiz Update successful
✅ Error Handling working
✅ Cleanup completed
🎉 All HTTP tests passed!
```
