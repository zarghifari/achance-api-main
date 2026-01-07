# Test downloading from different lessons
$baseUrl = "http://localhost/api"
$token = "3|w6wx4UxxnKypLdxaeNQQqR3fzsuGDJFEEvCZfWJde9cb072a"

Write-Host "=== Testing Multiple Content Downloads ===" -ForegroundColor Cyan
Write-Host ""

$headers = @{
    "Authorization" = "Bearer $token"
}

# Test lessons 1-4
for ($i = 1; $i -le 4; $i++) {
    Write-Host "Testing Lesson $i..." -ForegroundColor Yellow
    
    try {
        $downloadUrl = "$baseUrl/courses/1/modules/1/lessons/$i/content/download"
        $outputFile = "d:\Ghazi\achance-api-main\lesson-$i-content.zip"
        
        Invoke-WebRequest -Uri $downloadUrl -Method Get -Headers $headers -OutFile $outputFile
        
        if (Test-Path $outputFile) {
            $fileInfo = Get-Item $outputFile
            Write-Host "  ✓ Downloaded: $([math]::Round($fileInfo.Length/1KB, 2)) KB" -ForegroundColor Green
            
            # Show ZIP contents
            Add-Type -AssemblyName System.IO.Compression.FileSystem
            $zip = [System.IO.Compression.ZipFile]::OpenRead($outputFile)
            Write-Host "  Files: $($zip.Entries.Count)" -ForegroundColor Gray
            $zip.Dispose()
        }
    } catch {
        Write-Host "  ✗ Failed: $($_.Exception.Message)" -ForegroundColor Red
    }
    Write-Host ""
}

Write-Host "=== All Downloads Complete ===" -ForegroundColor Cyan
Write-Host "Files saved to: d:\Ghazi\achance-api-main\lesson-*-content.zip" -ForegroundColor Green
