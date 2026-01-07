# HTML Content System - Quick Test Script

Write-Host "🧪 Testing HTML Content System API..." -ForegroundColor Cyan
Write-Host ""

$baseUrl = "http://localhost/api"
$testResults = @()

# Test 1: Get content metadata
Write-Host "1️⃣ Testing: Get Content Metadata" -ForegroundColor Yellow
try {
    $response = Invoke-RestMethod -Uri "$baseUrl/courses/4/modules/4/lessons/5/content/metadata" `
        -Method Get `
        -Headers @{
            "Authorization" = "Bearer YOUR_TOKEN_HERE"
            "Accept" = "application/json"
        } -ErrorAction Stop
    
    Write-Host "   ✅ Content Metadata Retrieved" -ForegroundColor Green
    Write-Host "   - Title: $($response.data.title)" -ForegroundColor Gray
    Write-Host "   - Total Pages: $($response.data.total_pages)" -ForegroundColor Gray
    $testResults += "✅ Get Metadata"
} catch {
    Write-Host "   ❌ Failed: $($_.Exception.Message)" -ForegroundColor Red
    $testResults += "❌ Get Metadata"
}

Write-Host ""

# Test 2: Get full content
Write-Host "2️⃣ Testing: Get Full Content" -ForegroundColor Yellow
try {
    $response = Invoke-RestMethod -Uri "$baseUrl/courses/4/modules/4/lessons/5/content" `
        -Method Get `
        -Headers @{
            "Authorization" = "Bearer YOUR_TOKEN_HERE"
            "Accept" = "application/json"
        } -ErrorAction Stop
    
    Write-Host "   ✅ Full Content Retrieved" -ForegroundColor Green
    Write-Host "   - Title: $($response.data.title)" -ForegroundColor Gray
    Write-Host "   - Pages: $($response.data.total_pages)" -ForegroundColor Gray
    Write-Host "   - Word Count: $($response.data.metadata.word_count)" -ForegroundColor Gray
    $testResults += "✅ Get Full Content"
} catch {
    Write-Host "   ❌ Failed: $($_.Exception.Message)" -ForegroundColor Red
    $testResults += "❌ Get Full Content"
}

Write-Host ""

# Test 3: Get specific page
Write-Host "3️⃣ Testing: Get Page 1" -ForegroundColor Yellow
try {
    $response = Invoke-RestMethod -Uri "$baseUrl/courses/4/modules/4/lessons/5/content/page/1" `
        -Method Get `
        -Headers @{
            "Authorization" = "Bearer YOUR_TOKEN_HERE"
            "Accept" = "application/json"
        } -ErrorAction Stop
    
    Write-Host "   ✅ Page Content Retrieved" -ForegroundColor Green
    Write-Host "   - Page: $($response.data.page)/$($response.data.total_pages)" -ForegroundColor Gray
    Write-Host "   - Has Next: $($response.data.has_next)" -ForegroundColor Gray
    Write-Host "   - Content Length: $($response.data.content.Length) chars" -ForegroundColor Gray
    $testResults += "✅ Get Page"
} catch {
    Write-Host "   ❌ Failed: $($_.Exception.Message)" -ForegroundColor Red
    $testResults += "❌ Get Page"
}

Write-Host ""
Write-Host "📊 Test Summary" -ForegroundColor Cyan
Write-Host "=================" -ForegroundColor Cyan
foreach ($result in $testResults) {
    Write-Host $result
}

Write-Host ""
Write-Host "💡 Note: Replace 'YOUR_TOKEN_HERE' with a valid Bearer token from login" -ForegroundColor Yellow
Write-Host "💡 To get a token, run: .\test-performance.ps1 or login via Postman" -ForegroundColor Yellow
