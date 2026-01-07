# EPUB System Cleanup Script

Write-Host "🧹 Cleaning up EPUB Implementation..." -ForegroundColor Cyan
Write-Host ""

$filesToDelete = @(
    "src\app\Http\Controllers\EpubController.php",
    "src\app\Http\Requests\EpubCreateRequest.php",
    "src\app\Http\Requests\EpubUpdateRequest.php",
    "src\app\Http\Resources\EpubResource.php",
    "src\app\Models\Epub.php"
)

$deletedCount = 0
$notFoundCount = 0

foreach ($file in $filesToDelete) {
    $fullPath = Join-Path (Get-Location) $file
    if (Test-Path $fullPath) {
        Remove-Item $fullPath -Force
        Write-Host "✅ Deleted: $file" -ForegroundColor Green
        $deletedCount++
    } else {
        Write-Host "⏭️  Not found: $file" -ForegroundColor Yellow
        $notFoundCount++
    }
}

Write-Host ""
Write-Host "📊 Summary" -ForegroundColor Cyan
Write-Host "  Deleted: $deletedCount files" -ForegroundColor Green
Write-Host "  Not found: $notFoundCount files" -ForegroundColor Yellow

# Run migration to drop epubs table
Write-Host ""
Write-Host "🗄️  Dropping epubs table..." -ForegroundColor Cyan
docker-compose exec app1 php artisan migrate --force

# Delete EPUB files
Write-Host ""
Write-Host "📁 Cleaning up EPUB files..." -ForegroundColor Cyan
docker-compose exec app1 bash -c "rm -rf storage/app/public/uploads/epubs/* && echo 'EPUB files deleted'"

Write-Host ""
Write-Host "✨ EPUB cleanup completed!" -ForegroundColor Green
Write-Host "�� The system now uses HTML Content exclusively" -ForegroundColor Cyan
