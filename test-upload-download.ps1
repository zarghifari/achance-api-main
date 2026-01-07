# Upload Content and Test Download
$baseUrl = "http://localhost/api"
$token = "3|w6wx4UxxnKypLdxaeNQQqR3fzsuGDJFEEvCZfWJde9cb072a"

Write-Host "=== Upload Content & Test Download ===" -ForegroundColor Cyan
Write-Host ""

$headers = @{
    "Authorization" = "Bearer $token"
    "Accept" = "application/json"
}

# Course, Module, Lesson IDs
$courseId = 1
$moduleId = 1
$lessonId = 1

# Check if sample.docx exists
$sampleFile = "d:\Ghazi\achance-api-main\src\sample.docx"
if (-not (Test-Path $sampleFile)) {
    # Try to copy from source
    Write-Host "Looking for sample file in Docker..." -ForegroundColor Yellow
    docker-compose exec app1 cp /var/www/storage/app/public/uploads/contents/source/CfDQ3mEL6oU7iSml2VJ5mhtvwYAO1yjODKScI5J6.docx /var/www/sample-test.docx
    docker-compose cp achance-api-main-app1-1:/var/www/sample-test.docx $sampleFile
}

if (Test-Path $sampleFile) {
    Write-Host "1. Uploading content..." -ForegroundColor Yellow
    Write-Host "   File: $sampleFile" -ForegroundColor Gray
    
    try {
        # Create multipart form data
        $boundary = [System.Guid]::NewGuid().ToString()
        $headers_upload = @{
            "Authorization" = "Bearer $token"
            "Content-Type" = "multipart/form-data; boundary=$boundary"
        }
        
        # Read file
        $fileBytes = [System.IO.File]::ReadAllBytes($sampleFile)
        $fileName = [System.IO.Path]::GetFileName($sampleFile)
        
        # Build multipart body
        $bodyLines = @(
            "--$boundary",
            "Content-Disposition: form-data; name=`"file`"; filename=`"$fileName`"",
            "Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document",
            "",
            [System.Text.Encoding]::GetEncoding("iso-8859-1").GetString($fileBytes),
            "--$boundary",
            "Content-Disposition: form-data; name=`"title`"",
            "",
            "Test Document Upload",
            "--$boundary",
            "Content-Disposition: form-data; name=`"words_per_page`"",
            "",
            "500",
            "--$boundary--"
        )
        
        $body = $bodyLines -join "`r`n"
        
        $uploadUrl = "$baseUrl/courses/$courseId/modules/$moduleId/lessons/$lessonId/content/import"
        $response = Invoke-RestMethod -Uri $uploadUrl -Method Post -Headers $headers_upload -Body ([System.Text.Encoding]::GetEncoding("iso-8859-1").GetBytes($body))
        
        Write-Host "   Upload successful!" -ForegroundColor Green
        Write-Host "   Content ID: $($response.data.id)" -ForegroundColor Green
        Write-Host "   Total Pages: $($response.data.total_pages)" -ForegroundColor Gray
        
        $contentId = $response.data.id
        
        # Now try to download
        Write-Host ""
        Write-Host "2. Downloading ZIP..." -ForegroundColor Yellow
        $downloadUrl = "$baseUrl/courses/$courseId/modules/$moduleId/lessons/$lessonId/content/download"
        $outputFile = "d:\Ghazi\achance-api-main\test-download.zip"
        
        Invoke-WebRequest -Uri $downloadUrl -Method Get -Headers $headers -OutFile $outputFile
        
        if (Test-Path $outputFile) {
            $fileInfo = Get-Item $outputFile
            Write-Host ""
            Write-Host "SUCCESS! ZIP downloaded!" -ForegroundColor Green
            Write-Host "File: $outputFile" -ForegroundColor Green
            Write-Host "Size: $([math]::Round($fileInfo.Length/1KB, 2)) KB" -ForegroundColor Green
            
            # Extract and show contents
            Write-Host ""
            Write-Host "3. ZIP Contents:" -ForegroundColor Yellow
            Add-Type -AssemblyName System.IO.Compression.FileSystem
            $zip = [System.IO.Compression.ZipFile]::OpenRead($outputFile)
            $zip.Entries | Select-Object -First 20 | ForEach-Object {
                Write-Host "   - $($_.FullName)" -ForegroundColor Gray
            }
            if ($zip.Entries.Count -gt 20) {
                Write-Host "   ... and $($zip.Entries.Count - 20) more files" -ForegroundColor Gray
            }
            $zip.Dispose()
        }
        
    } catch {
        Write-Host "ERROR: $($_.Exception.Message)" -ForegroundColor Red
        if ($_.ErrorDetails.Message) {
            Write-Host "$($_.ErrorDetails.Message)" -ForegroundColor Red
        }
    }
} else {
    Write-Host "ERROR: Sample file not found" -ForegroundColor Red
    Write-Host "Please ensure sample.docx exists in d:\Ghazi\achance-api-main\src\" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "=== Test Complete ===" -ForegroundColor Cyan
