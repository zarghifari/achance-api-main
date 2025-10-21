# 🛠️ Testing Utilities

This directory contains helper utilities for testing and development of the Quiz API.

## 📁 Files

### Authentication Utilities

#### `get_admin_token.php` - Admin Token Generator
- **Purpose**: Generate authentication tokens for admin users
- **Usage**: `php get_admin_token.php`
- **Output**: Bearer token for API authentication
- **Use Case**: Manual API testing, Postman setup

#### `get_token.php` - General Token Generator  
- **Purpose**: Generate tokens for any user
- **Usage**: `php get_token.php [email] [password]`
- **Output**: Bearer token for specified user
- **Use Case**: Multi-user testing scenarios

### Permission Management

#### `check_permissions.php` - Permission Checker
- **Purpose**: Verify and display user permissions
- **Usage**: `php check_permissions.php [user_id]`
- **Output**: List of user permissions and roles
- **Use Case**: Debugging permission issues

### Debugging Tools

#### `debug-csv.php` - CSV Import Debugger
- **Purpose**: Debug CSV quiz import issues
- **Usage**: `php debug-csv.php [csv_file]`
- **Output**: Parsing results and error details
- **Use Case**: Troubleshooting import failures

#### `debug-parse.php` - Data Parser Debugger
- **Purpose**: Debug data parsing and validation
- **Usage**: `php debug-parse.php [data_file]`
- **Output**: Parsing steps and validation results
- **Use Case**: Import data format issues

## 🚀 Usage Examples

### Generate Admin Token for Postman
```bash
# Get admin token
php tests/utilities/get_admin_token.php

# Example output:
# Admin Token: 15|xl2K3j9mZ8fG4hN7qP1wE6rT5yU8iO9pA2sD3fG4hJ6kL
# 
# Use in Postman:
# Authorization: Bearer 15|xl2K3j9mZ8fG4hN7qP1wE6rT5yU8iO9pA2sD3fG4hJ6kL
```

### Check User Permissions
```bash
# Check permissions for user ID 1
php tests/utilities/check_permissions.php 1

# Check current admin permissions
php tests/utilities/check_permissions.php
```

### Debug CSV Import
```bash
# Debug specific CSV file
php tests/utilities/debug-csv.php quiz-data.csv

# Debug with verbose output
DEBUG=true php tests/utilities/debug-csv.php quiz-data.csv
```

## 🔧 Environment Requirements

All utilities require:
- Laravel environment initialized
- Database connection available
- Proper user permissions in database

### Docker Usage
```bash
# Run utilities inside Docker container
docker-compose exec app1 php tests/utilities/get_admin_token.php
docker-compose exec app1 php tests/utilities/check_permissions.php
```

## 📊 Output Formats

### Token Generation
```
🔑 Admin Token Generated Successfully!
Token: 15|xl2K3j9mZ8fG4hN7qP1wE6rT5yU8iO9pA2sD3fG4hJ6kL
Expires: Never (Personal Access Token)
User: admin@example.com (ID: 1)

📋 Usage Instructions:
1. Copy the token above
2. In Postman: Authorization → Bearer Token
3. Or in curl: -H "Authorization: Bearer TOKEN"
```

### Permission Check
```
👤 User Permission Report
User: John Doe (admin@example.com)
ID: 1
Status: Active

🔐 Permissions:
✅ create quizzes
✅ view quizzes  
✅ edit quizzes
✅ delete quizzes
✅ view all attempt quizzes

👥 Roles:
✅ Super Admin
✅ Quiz Manager
```

### Debug Output
```
🐛 CSV Import Debug Report
File: quiz-data.csv
Size: 2.5 KB
Rows: 25

📊 Parsing Results:
✅ Headers validated
✅ Data types correct
❌ Row 15: Missing required field 'question_type'
❌ Row 23: Invalid answer format

🔧 Suggestions:
- Add question_type for row 15
- Check answer JSON format in row 23
```

## 🛠️ Development

### Adding New Utilities

1. **Create new utility file** in this directory
2. **Follow naming convention**: `action-purpose.php`
3. **Include proper error handling** and user feedback
4. **Add documentation** to this README
5. **Test with both local and Docker environments**

### Utility Template
```php
<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🛠️ Utility Name\n";
echo "Purpose: Description of what this utility does\n\n";

try {
    // Utility logic here
    echo "✅ Success message\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
```

## 🔍 Troubleshooting

### Common Issues

1. **Database Connection**
   ```bash
   # Test database connection
   php -r "require 'vendor/autoload.php'; \$app = require 'bootstrap/app.php'; echo 'DB Connected: ' . (DB::connection()->getPdo() ? 'Yes' : 'No') . PHP_EOL;"
   ```

2. **Permission Errors**
   ```bash
   # Check if Spatie Permission package is working
   php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
   ```

3. **Token Issues**
   ```bash
   # Clear token cache
   php artisan cache:clear
   php artisan config:clear
   ```

## 📚 Related Documentation

- [Main Testing README](../README.md)
- [API Tests Documentation](../api/README.md)
- [Quiz System Guide](../../QUIZ_SYSTEM_GUIDE.md)
