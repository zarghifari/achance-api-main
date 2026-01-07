# Simple approach: Just update the existing content to link to one of the ZIP files
$baseUrl = "http://localhost/api"  
$token = "3|w6wx4UxxnKypLdxaeNQQqR3fzsuGDJFEEvCZfWJde9cb072a"

Write-Host "=== Fix Content ZIP and Download ===" -ForegroundColor Cyan

# First, link the existing content to a ZIP file
Write-Host "1. Linking content to ZIP file..." -ForegroundColor Yellow
docker-compose exec -T app1 php artisan tinker --execute="
\$content = App\Models\Content::first();
if (\$content) {
    \$content->zip_file_path = 'uploads/contents/archives/import test_Pengantar Komputer Grafis_695a307fc6cb3.zip';
    \$content->zip_file_size = filesize(storage_path('app/public/uploads/contents/archives/import test_Pengantar Komputer Grafis_695a307fc6cb3.zip'));
    \$content->save();
    echo 'Content updated!' . PHP_EOL;
    echo 'ZIP Path: ' . \$content->zip_file_path . PHP_EOL;
    echo 'ZIP Size: ' . \$content->zip_file_size . ' bytes' . PHP_EOL;
} else {
    echo 'No content found';
}
"

Write-Host ""
Write-Host "2. Testing download..." -ForegroundColor Yellow

$headers = @{
    "Authorization" = "Bearer $token"
}

$downloadUrl = "$baseUrl/courses/1/modules/1/lessons/1/content/download"
$outputFile = "d:\Ghazi\achance-api-main\downloaded-content.zip"

try {
    Invoke-WebRequest -Uri $downloadUrl -Method Get -Headers $headers -OutFile $outputFile
    
    if (Test-Path $outputFile) {
        $fileInfo = Get-Item $outputFile
        Write-Host ""
        Write-Host "SUCCESS! ZIP downloaded!" -ForegroundColor Green
        Write-Host "File: $outputFile" -ForegroundColor Green
        Write-Host "Size: $([math]::Round($fileInfo.Length/1MB, 2)) MB ($($fileInfo.Length) bytes)" -ForegroundColor Green
        
        Write-Host ""
        Write-Host "3. ZIP Contents (first 30 files):" -ForegroundColor Yellow
        Add-Type -AssemblyName System.IO.Compression.FileSystem
        $zip = [System.IO.Compression.ZipFile]::OpenRead($outputFile)
        
        Write-Host "   Total files in ZIP: $($zip.Entries.Count)" -ForegroundColor Cyan
        Write-Host ""
        
        $zip.Entries | Select-Object -First 30 | ForEach-Object {
            $sizeKB = [math]::Round($_.Length/1KB, 2)
            Write-Host "   - $($_.FullName) ($sizeKB KB)" -ForegroundColor Gray
        }
        
        if ($zip.Entries.Count -gt 30) {
            Write-Host "   ... and $($zip.Entries.Count - 30) more files" -ForegroundColor Gray
        }
        
        $zip.Dispose()
        
        Write-Host ""
        Write-Host "4. Opening file location..." -ForegroundColor Yellow
        Start-Process "explorer.exe" -ArgumentList "/select,`"$outputFile`""
    }
} catch {
    Write-Host "ERROR: $($_.Exception.Message)" -ForegroundColor Red
    if ($_.ErrorDetails.Message) {
        Write-Host "$($_.ErrorDetails.Message)" -ForegroundColor Red
    }
}

Write-Host ""
Write-Host "=== Test Complete ===" -ForegroundColor Cyan
