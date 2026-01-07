# Test Content Download Endpoint
# This script tests downloading ZIP content from the API

$baseUrl = "http://localhost/api"
$token = "3|w6wx4UxxnKypLdxaeNQQqR3fzsuGDJFEEvCZfWJde9cb072a"  # Admin token

Write-Host "=== Testing Content Download ===" -ForegroundColor Cyan
Write-Host ""

# First, get list of courses
Write-Host "1. Fetching courses..." -ForegroundColor Yellow
$headers = @{
    "Authorization" = "Bearer $token"
    "Accept" = "application/json"
}

try {
    $coursesResponse = Invoke-RestMethod -Uri "$baseUrl/courses/all" -Method Get -Headers $headers
    
    if ($coursesResponse.data -and $coursesResponse.data.Count -gt 0) {
        $course = $coursesResponse.data[0]
        Write-Host "   Found course: $($course.title) (ID: $($course.id))" -ForegroundColor Green
        
        # Get modules for this course
        Write-Host "2. Fetching modules for course $($course.id)..." -ForegroundColor Yellow
        $modulesResponse = Invoke-RestMethod -Uri "$baseUrl/courses/$($course.id)/modules" -Method Get -Headers $headers
        
        if ($modulesResponse.data -and $modulesResponse.data.Count -gt 0) {
            $module = $modulesResponse.data[0]
            Write-Host "   Found module: $($module.title) (ID: $($module.id))" -ForegroundColor Green
            
            # Get lessons for this module
            Write-Host "3. Fetching lessons for module $($module.id)..." -ForegroundColor Yellow
            $lessonsResponse = Invoke-RestMethod -Uri "$baseUrl/courses/$($course.id)/modules/$($module.id)/lessons" -Method Get -Headers $headers
            
            if ($lessonsResponse.data -and $lessonsResponse.data.Count -gt 0) {
                $lesson = $lessonsResponse.data[0]
                Write-Host "   Found lesson: $($lesson.title) (ID: $($lesson.id))" -ForegroundColor Green
                
                # Check if lesson has content
                Write-Host "4. Checking content for lesson $($lesson.id)..." -ForegroundColor Yellow
                try {
                    $contentMetadata = Invoke-RestMethod -Uri "$baseUrl/courses/$($course.id)/modules/$($module.id)/lessons/$($lesson.id)/content/metadata" -Method Get -Headers $headers
                    
                    if ($contentMetadata.data) {
                        Write-Host "   Content found: $($contentMetadata.data.title)" -ForegroundColor Green
                        Write-Host "   Total pages: $($contentMetadata.data.total_pages)" -ForegroundColor Gray
                        Write-Host "   ZIP size: $($contentMetadata.data.zip_file_size) bytes" -ForegroundColor Gray
                        
                        # Try to download the ZIP
                        Write-Host ""
                        Write-Host "5. Downloading ZIP content..." -ForegroundColor Yellow
                        $downloadUrl = "$baseUrl/courses/$($course.id)/modules/$($module.id)/lessons/$($lesson.id)/content/download"
                        $outputFile = "d:\Ghazi\achance-api-main\downloaded-content.zip"
                        
                        Write-Host "   URL: $downloadUrl" -ForegroundColor Gray
                        Write-Host "   Output: $outputFile" -ForegroundColor Gray
                        
                        Invoke-WebRequest -Uri $downloadUrl -Method Get -Headers $headers -OutFile $outputFile
                        
                        if (Test-Path $outputFile) {
                            $fileInfo = Get-Item $outputFile
                            Write-Host ""
                            Write-Host "SUCCESS! ZIP downloaded successfully!" -ForegroundColor Green
                            Write-Host "File: $outputFile" -ForegroundColor Green
                            Write-Host "Size: $($fileInfo.Length) bytes ($([math]::Round($fileInfo.Length/1KB, 2)) KB)" -ForegroundColor Green
                            
                            # Show ZIP contents
                            Write-Host ""
                            Write-Host "6. ZIP Contents:" -ForegroundColor Yellow
                            Add-Type -AssemblyName System.IO.Compression.FileSystem
                            $zip = [System.IO.Compression.ZipFile]::OpenRead($outputFile)
                            $zip.Entries | ForEach-Object {
                                Write-Host "   - $($_.FullName) ($($_.Length) bytes)" -ForegroundColor Gray
                            }
                            $zip.Dispose()
                        } else {
                            Write-Host "ERROR: File was not downloaded" -ForegroundColor Red
                        }
                    } else {
                        Write-Host "   No content found for this lesson" -ForegroundColor Red
                    }
                } catch {
                    Write-Host "   No content available for this lesson" -ForegroundColor Red
                    Write-Host "   Error: $($_.Exception.Message)" -ForegroundColor Red
                }
            } else {
                Write-Host "   No lessons found" -ForegroundColor Red
            }
        } else {
            Write-Host "   No modules found" -ForegroundColor Red
        }
    } else {
        Write-Host "   No courses found" -ForegroundColor Red
    }
} catch {
    Write-Host "ERROR: $($_.Exception.Message)" -ForegroundColor Red
    if ($_.ErrorDetails.Message) {
        Write-Host "Details: $($_.ErrorDetails.Message)" -ForegroundColor Red
    }
}

Write-Host ""
Write-Host "=== Test Complete ===" -ForegroundColor Cyan
