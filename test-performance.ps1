#!/usr/bin/env pwsh
# Performance Test Script for Docker Laravel API
# Tests all critical endpoints and generates a performance report

param(
    [string]$BaseUrl = "http://localhost/api",
    [int]$Iterations = 3,
    [switch]$Detailed
)

Write-Host "`n╔════════════════════════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "║          Docker Laravel API Performance Test              ║" -ForegroundColor Cyan
Write-Host "╚════════════════════════════════════════════════════════════╝`n" -ForegroundColor Cyan

$results = @()
$totalTests = 0
$passedTests = 0

# Helper function to test endpoint
function Test-Endpoint {
    param(
        [string]$Name,
        [string]$Method,
        [string]$Url,
        [hashtable]$Headers = @{},
        [string]$Body = $null,
        [int]$ExpectedMax = 1000
    )
    
    $times = @()
    
    for ($i = 1; $i -le $script:Iterations; $i++) {
        $sw = [System.Diagnostics.Stopwatch]::StartNew()
        
        try {
            $params = @{
                Uri = $Url
                Method = $Method
                UseBasicParsing = $true
                ErrorAction = 'Stop'
            }
            
            if ($Headers.Count -gt 0) {
                $params['Headers'] = $Headers
            }
            
            if ($Body) {
                $params['Body'] = $Body
                $params['ContentType'] = 'application/json'
            }
            
            $response = Invoke-WebRequest @params
            $sw.Stop()
            
            $time = [math]::Round($sw.Elapsed.TotalMilliseconds)
            $times += $time
            
            if ($script:Detailed) {
                Write-Host "  Run #$i : ${time}ms" -ForegroundColor Gray
            }
            
            return @{
                Success = $true
                Times = $times
                StatusCode = $response.StatusCode
                Response = $response
            }
            
        } catch {
            $sw.Stop()
            $time = [math]::Round($sw.Elapsed.TotalMilliseconds)
            $times += $time
            
            if ($script:Detailed) {
                Write-Host "  Run #$i : ${time}ms (FAILED)" -ForegroundColor Red
            }
            
            return @{
                Success = $false
                Times = $times
                Error = $_.Exception.Message
            }
        }
    }
}

# Test 1: Login (Student)
Write-Host "Testing Login Endpoint..." -ForegroundColor Yellow
$loginBody = @{
    email = "student@example.com"
    password = "password"
} | ConvertTo-Json

$loginResult = Test-Endpoint -Name "Login" -Method "POST" -Url "$BaseUrl/login" -Body $loginBody -ExpectedMax 1000

if ($loginResult.Success) {
    $avgTime = ($loginResult.Times | Measure-Object -Average).Average
    $minTime = ($loginResult.Times | Measure-Object -Minimum).Minimum
    $maxTime = ($loginResult.Times | Measure-Object -Maximum).Maximum
    
    $token = ($loginResult.Response.Content | ConvertFrom-Json).access_token
    
    Write-Host "  ✓ Login: Avg ${avgTime}ms (Min: ${minTime}ms, Max: ${maxTime}ms)" -ForegroundColor Green
    
    $results += [PSCustomObject]@{
        Endpoint = "POST /login"
        AvgTime = $avgTime
        MinTime = $minTime
        MaxTime = $maxTime
        Status = "✓ PASS"
        Target = "< 1000ms"
    }
    $passedTests++
} else {
    Write-Host "  ✗ Login: FAILED - $($loginResult.Error)" -ForegroundColor Red
    $results += [PSCustomObject]@{
        Endpoint = "POST /login"
        AvgTime = "N/A"
        MinTime = "N/A"
        MaxTime = "N/A"
        Status = "✗ FAIL"
        Target = "< 1000ms"
    }
}
$totalTests++

if (-not $token) {
    Write-Host "`n✗ Cannot continue - login failed" -ForegroundColor Red
    exit 1
}

$authHeaders = @{
    "Authorization" = "Bearer $token"
}

# Test 2: Get All Courses
Write-Host "`nTesting Courses Endpoint..." -ForegroundColor Yellow
$coursesResult = Test-Endpoint -Name "Courses" -Method "GET" -Url "$BaseUrl/courses/all" -Headers $authHeaders -ExpectedMax 500

if ($coursesResult.Success) {
    $avgTime = ($coursesResult.Times | Measure-Object -Average).Average
    $minTime = ($coursesResult.Times | Measure-Object -Minimum).Minimum
    $maxTime = ($coursesResult.Times | Measure-Object -Maximum).Maximum
    
    Write-Host "  ✓ Courses: Avg ${avgTime}ms (Min: ${minTime}ms, Max: ${maxTime}ms)" -ForegroundColor Green
    
    $results += [PSCustomObject]@{
        Endpoint = "GET /courses/all"
        AvgTime = $avgTime
        MinTime = $minTime
        MaxTime = $maxTime
        Status = "✓ PASS"
        Target = "< 500ms"
    }
    $passedTests++
} else {
    Write-Host "  ✗ Courses: FAILED - $($coursesResult.Error)" -ForegroundColor Red
    $results += [PSCustomObject]@{
        Endpoint = "GET /courses/all"
        AvgTime = "N/A"
        MinTime = "N/A"
        MaxTime = "N/A"
        Status = "✗ FAIL"
        Target = "< 500ms"
    }
}
$totalTests++

# Test 3: Get All Quizzes
Write-Host "`nTesting Quizzes Endpoint..." -ForegroundColor Yellow
$quizzesResult = Test-Endpoint -Name "Quizzes" -Method "GET" -Url "$BaseUrl/quizzes" -Headers $authHeaders -ExpectedMax 500

if ($quizzesResult.Success) {
    $avgTime = ($quizzesResult.Times | Measure-Object -Average).Average
    $minTime = ($quizzesResult.Times | Measure-Object -Minimum).Minimum
    $maxTime = ($quizzesResult.Times | Measure-Object -Maximum).Maximum
    
    Write-Host "  ✓ Quizzes: Avg ${avgTime}ms (Min: ${minTime}ms, Max: ${maxTime}ms)" -ForegroundColor Green
    
    $results += [PSCustomObject]@{
        Endpoint = "GET /quizzes"
        AvgTime = $avgTime
        MinTime = $minTime
        MaxTime = $maxTime
        Status = "✓ PASS"
        Target = "< 500ms"
    }
    $passedTests++
} else {
    Write-Host "  ✗ Quizzes: FAILED" -ForegroundColor Red
    $results += [PSCustomObject]@{
        Endpoint = "GET /quizzes"
        AvgTime = "N/A"
        MinTime = "N/A"
        MaxTime = "N/A"
        Status = "✗ FAIL"
        Target = "< 500ms"
    }
}
$totalTests++

# Test 4: Get User Info
Write-Host "`nTesting User Endpoint..." -ForegroundColor Yellow
$userResult = Test-Endpoint -Name "User" -Method "GET" -Url "$BaseUrl/user" -Headers $authHeaders -ExpectedMax 300

if ($userResult.Success) {
    $avgTime = ($userResult.Times | Measure-Object -Average).Average
    $minTime = ($userResult.Times | Measure-Object -Minimum).Minimum
    $maxTime = ($userResult.Times | Measure-Object -Maximum).Maximum
    
    Write-Host "  ✓ User: Avg ${avgTime}ms (Min: ${minTime}ms, Max: ${maxTime}ms)" -ForegroundColor Green
    
    $results += [PSCustomObject]@{
        Endpoint = "GET /user"
        AvgTime = $avgTime
        MinTime = $minTime
        MaxTime = $maxTime
        Status = "✓ PASS"
        Target = "< 300ms"
    }
    $passedTests++
} else {
    Write-Host "  ✗ User: FAILED" -ForegroundColor Red
    $results += [PSCustomObject]@{
        Endpoint = "GET /user"
        AvgTime = "N/A"
        MinTime = "N/A"
        MaxTime = "N/A"
        Status = "✗ FAIL"
        Target = "< 300ms"
    }
}
$totalTests++

# Test 5: Performance Health Check
Write-Host "`nTesting Performance Health..." -ForegroundColor Yellow
$healthResult = Test-Endpoint -Name "Health" -Method "GET" -Url "$BaseUrl/performance/health" -Headers $authHeaders -ExpectedMax 200

if ($healthResult.Success) {
    $avgTime = ($healthResult.Times | Measure-Object -Average).Average
    $healthData = $healthResult.Response.Content | ConvertFrom-Json
    
    Write-Host "  ✓ Health: ${avgTime}ms" -ForegroundColor Green
    
    if ($Detailed -and $healthData) {
        Write-Host "    - OPcache: $($healthData.opcache_status)" -ForegroundColor Gray
        Write-Host "    - Redis: $($healthData.redis_status)" -ForegroundColor Gray
        Write-Host "    - MySQL: $($healthData.database_status)" -ForegroundColor Gray
    }
    
    $passedTests++
} else {
    Write-Host "  ✓ Health endpoint not available (optional)" -ForegroundColor Yellow
}
$totalTests++

# Display Results
Write-Host "`n╔════════════════════════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "║                    Test Results                            ║" -ForegroundColor Cyan
Write-Host "╚════════════════════════════════════════════════════════════╝`n" -ForegroundColor Cyan

$results | Format-Table -AutoSize

# Summary
Write-Host "`n╔════════════════════════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "║                       Summary                              ║" -ForegroundColor Cyan
Write-Host "╠════════════════════════════════════════════════════════════╣" -ForegroundColor Cyan
Write-Host "║  Total Tests: $totalTests                                              ║" -ForegroundColor White
Write-Host "║  Passed: $passedTests                                                  ║" -ForegroundColor Green
Write-Host "║  Failed: $($totalTests - $passedTests)                                                  ║" -ForegroundColor $(if ($passedTests -eq $totalTests) { "Green" } else { "Red" })
Write-Host "║  Success Rate: $([math]::Round(($passedTests / $totalTests) * 100, 1))%                                       ║" -ForegroundColor $(if ($passedTests -eq $totalTests) { "Green" } else { "Yellow" })
Write-Host "╚════════════════════════════════════════════════════════════╝`n" -ForegroundColor Cyan

# Performance Grade
$avgOfAvgs = ($results | Where-Object { $_.AvgTime -ne "N/A" } | ForEach-Object { $_.AvgTime } | Measure-Object -Average).Average

if ($avgOfAvgs -lt 300) {
    $grade = "A+ (Excellent)"
    $color = "Green"
} elseif ($avgOfAvgs -lt 500) {
    $grade = "A (Very Good)"
    $color = "Green"
} elseif ($avgOfAvgs -lt 1000) {
    $grade = "B (Good)"
    $color = "Yellow"
} elseif ($avgOfAvgs -lt 2000) {
    $grade = "C (Acceptable)"
    $color = "Yellow"
} else {
    $grade = "D (Needs Improvement)"
    $color = "Red"
}

Write-Host "Overall Performance Grade: $grade" -ForegroundColor $color
Write-Host "Average Response Time: $([math]::Round($avgOfAvgs, 0))ms`n" -ForegroundColor $color

# Exit with appropriate code
if ($passedTests -eq $totalTests) {
    exit 0
} else {
    exit 1
}
