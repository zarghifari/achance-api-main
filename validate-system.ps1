# System Validation and Error Scanner

Write-Host "🔍 Scanning for EPUB references and potential errors..." -ForegroundColor Cyan
Write-Host ""

# Scan for remaining EPUB references
Write-Host "1️⃣ Checking for EPUB references in code..." -ForegroundColor Yellow
$epubReferences = @()

$searchPaths = @(
    "src/app/Http/Controllers",
    "src/app/Models",
    "src/routes",
    "src/database/seeders"
)

foreach ($path in $searchPaths) {
    if (Test-Path $path) {
        $found = Select-String -Path "$path\*.php" -Pattern "epub|Epub|EPUB" -SimpleMatch
        if ($found) {
            $epubReferences += $found
        }
    }
}

if ($epubReferences.Count -gt 0) {
    Write-Host "⚠️  Found $($epubReferences.Count) EPUB references:" -ForegroundColor Yellow
    $epubReferences | ForEach-Object {
        Write-Host "   - $($_.Path):$($_.LineNumber)" -ForegroundColor Gray
    }
} else {
    Write-Host "✅ No EPUB references found in code" -ForegroundColor Green
}

Write-Host ""

# Check for syntax errors
Write-Host "2️⃣ Checking for PHP syntax errors..." -ForegroundColor Yellow
docker-compose exec app1 bash -c "find /var/www/html/app /var/www/html/routes /var/www/html/database/seeders -name '*.php' -exec php -l {} \; 2>&1 | grep -i 'error'" | Out-Null

if ($LASTEXITCODE -eq 0) {
    Write-Host "⚠️  Syntax errors detected! Check output above." -ForegroundColor Red
} else {
    Write-Host "✅ No syntax errors found" -ForegroundColor Green
}

Write-Host ""

# Verify Content system is working
Write-Host "3️⃣ Verifying Content system..." -ForegroundColor Yellow
$contentCheck = docker-compose exec app1 php artisan tinker --execute="echo App\Models\Content::count();" 2>&1
if ($contentCheck -match '\d+') {
    Write-Host "✅ Content model accessible - $($matches[0]) records found" -ForegroundColor Green
} else {
    Write-Host "❌ Content model check failed" -ForegroundColor Red
}

Write-Host ""

# Check database tables
Write-Host "4️⃣ Checking database tables..." -ForegroundColor Yellow
$tables = docker-compose exec app1 php artisan tinker --execute="echo implode(', ', DB::connection()->getDoctrineSchemaManager()->listTableNames());" 2>&1

if ($tables -match "contents") {
    Write-Host "✅ Contents table exists" -ForegroundColor Green
} else {
    Write-Host "❌ Contents table missing!" -ForegroundColor Red
}

if ($tables -notmatch "epubs") {
    Write-Host "✅ Epubs table successfully removed" -ForegroundColor Green
} else {
    Write-Host "⚠️  Epubs table still exists" -ForegroundColor Yellow
}

Write-Host ""

# Check for missing imports/classes
Write-Host "5️⃣ Checking for missing class imports..." -ForegroundColor Yellow
$classErrors = docker-compose exec app1 php artisan route:list 2>&1 | Select-String "Error|Exception|not found"

if ($classErrors) {
    Write-Host "⚠️  Potential class/import errors detected:" -ForegroundColor Yellow
    $classErrors | ForEach-Object { Write-Host "   - $_" -ForegroundColor Gray }
} else {
    Write-Host "✅ All routes loaded successfully" -ForegroundColor Green
}

Write-Host ""

# Check ZIP extension
Write-Host "6️⃣ Checking PHP ZIP extension..." -ForegroundColor Yellow
$zipCheck = docker-compose exec app1 php -m 2>&1 | Select-String "zip"
if ($zipCheck) {
    Write-Host "✅ ZIP extension is enabled" -ForegroundColor Green
} else {
    Write-Host "❌ ZIP extension not found - ZIP creation will fail!" -ForegroundColor Red
}

Write-Host ""

# Test content API
Write-Host "7️⃣ Testing Content API availability..." -ForegroundColor Yellow
$apiTest = docker-compose exec app1 php artisan route:list 2>&1 | Select-String "content/import"
if ($apiTest) {
    Write-Host "✅ Content API endpoints registered" -ForegroundColor Green
} else {
    Write-Host "❌ Content API endpoints not found!" -ForegroundColor Red
}

Write-Host ""
Write-Host "=" * 60 -ForegroundColor Cyan
Write-Host "📊 Validation Complete!" -ForegroundColor Cyan
Write-Host "=" * 60 -ForegroundColor Cyan

# Summary
Write-Host ""
Write-Host "💡 Next Steps:" -ForegroundColor Yellow
Write-Host "  1. Clear application cache: docker-compose exec app1 php artisan cache:clear"
Write-Host "  2. Clear config cache: docker-compose exec app1 php artisan config:clear"
Write-Host "  3. Test content import: Upload a .docx file via API"
Write-Host "  4. Verify ZIP creation in storage/app/public/uploads/contents/archives/"
Write-Host ""
