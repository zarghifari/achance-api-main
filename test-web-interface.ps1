# Web Interface Test Script

Write-Host "Testing aChance Learning Web Interface..." -ForegroundColor Cyan
Write-Host ""

# Check if Laravel is installed
$composerJson = ".\src\composer.json"
if (Test-Path $composerJson) {
    Write-Host "✓ Laravel project found" -ForegroundColor Green
} else {
    Write-Host "✗ Laravel project not found!" -ForegroundColor Red
    exit 1
}

# Check if views directory exists
$viewsDir = ".\src\resources\views"
if (Test-Path $viewsDir) {
    Write-Host "✓ Views directory exists" -ForegroundColor Green
    
    # Count view files
    $viewCount = (Get-ChildItem -Path $viewsDir -Recurse -Filter "*.blade.php").Count
    Write-Host "  Found $viewCount Blade template files" -ForegroundColor Gray
} else {
    Write-Host "✗ Views directory not found!" -ForegroundColor Red
}

# Check if controllers exist
$webControllersDir = ".\src\app\Http\Controllers\Web"
if (Test-Path $webControllersDir) {
    Write-Host "✓ Web controllers directory exists" -ForegroundColor Green
    
    # Count controller files
    $controllerCount = (Get-ChildItem -Path $webControllersDir -Filter "*.php").Count
    Write-Host "  Found $controllerCount Web controller files" -ForegroundColor Gray
} else {
    Write-Host "✗ Web controllers directory not found!" -ForegroundColor Red
}

# Check web routes
$webRoutes = ".\src\routes\web.php"
if (Test-Path $webRoutes) {
    Write-Host "✓ Web routes file exists" -ForegroundColor Green
    
    $routeContent = Get-Content $webRoutes -Raw
    if ($routeContent -match "HomeController") {
        Write-Host "  Web routes configured properly" -ForegroundColor Gray
    }
} else {
    Write-Host "✗ Web routes file not found!" -ForegroundColor Red
}

Write-Host ""
Write-Host "Key Files Created:" -ForegroundColor Cyan
Write-Host "  - Layout: resources/views/layouts/app.blade.php" -ForegroundColor Gray
Write-Host "  - Home: resources/views/home.blade.php" -ForegroundColor Gray
Write-Host "  - Auth: resources/views/auth/*.blade.php" -ForegroundColor Gray
Write-Host "  - Courses: resources/views/courses/*.blade.php" -ForegroundColor Gray
Write-Host "  - Quizzes: resources/views/quizzes/*.blade.php" -ForegroundColor Gray
Write-Host "  - Profile: resources/views/profile/*.blade.php" -ForegroundColor Gray
Write-Host ""

Write-Host "Next Steps:" -ForegroundColor Yellow
Write-Host "1. Start the application:" -ForegroundColor White
Write-Host "   .\docker-start.ps1" -ForegroundColor Gray
Write-Host "   OR" -ForegroundColor White
Write-Host "   cd src && php artisan serve" -ForegroundColor Gray
Write-Host ""
Write-Host "2. Open your browser:" -ForegroundColor White
Write-Host "   http://localhost:8000" -ForegroundColor Cyan
Write-Host ""
Write-Host "3. Register a new account or login" -ForegroundColor White
Write-Host ""
Write-Host "4. For mobile testing, use your device:" -ForegroundColor White
Write-Host "   http://YOUR_LOCAL_IP:8000" -ForegroundColor Cyan
Write-Host ""

Write-Host "Documentation:" -ForegroundColor Yellow
Write-Host "  See WEB_INTERFACE_README.md for complete documentation" -ForegroundColor Gray
Write-Host ""
