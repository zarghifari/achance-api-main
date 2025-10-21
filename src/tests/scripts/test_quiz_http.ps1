# Quiz API HTTP Testing Script (PowerShell)
# This script tests the Quiz API endpoints using Invoke-RestMethod

$BaseURL = "http://localhost/api"
$AdminEmail = "admin@example.com"
$AdminPassword = "password"

Write-Host "Testing Quiz API with PowerShell..." -ForegroundColor Green
Write-Host "Using base URL: $BaseURL" -ForegroundColor Yellow
Write-Host ""

# Function to make HTTP requests with error handling
function Test-APIEndpoint {
    param(
        [string]$Method,
        [string]$Endpoint,
        [hashtable]$Body = $null,
        [hashtable]$Headers = @{},
        [string]$Description
    )
    
    Write-Host "Testing: $Description" -ForegroundColor Cyan
    Write-Host "Method: $Method $Endpoint" -ForegroundColor Gray
    
    try {
        $params = @{
            Uri = "$BaseURL$Endpoint"
            Method = $Method
            Headers = $Headers
            ContentType = "application/json"
        }
        
        if ($Body) {
            $params.Body = ($Body | ConvertTo-Json -Depth 10)
        }
        
        $response = Invoke-RestMethod @params
        Write-Host "SUCCESS" -ForegroundColor Green
        
        if ($response) {
            $responseJson = $response | ConvertTo-Json -Depth 5
            Write-Host "Response: $($responseJson.Substring(0, [Math]::Min(200, $responseJson.Length)))..." -ForegroundColor White
        }
        Write-Host ""
        
        return $response
    }
    catch {
        Write-Host "FAILED: $($_.Exception.Message)" -ForegroundColor Red
        if ($_.Exception.Response) {
            $statusCode = $_.Exception.Response.StatusCode
            Write-Host "Status Code: $statusCode" -ForegroundColor Red
        }
        Write-Host ""
        return $null
    }
}

# Test 1: Login to get authentication token
Write-Host "1. Testing Authentication..." -ForegroundColor Magenta
$loginData = @{
    email = $AdminEmail
    password = $AdminPassword
}

$authResponse = Test-APIEndpoint -Method "POST" -Endpoint "/login" -Body $loginData -Description "Admin Login"

if (-not $authResponse -or -not $authResponse.access_token) {
    Write-Host "FAILED to get authentication token. Please check:" -ForegroundColor Red
    Write-Host "   - Laravel server is running on port 80" -ForegroundColor Yellow
    Write-Host "   - Admin user exists with email: $AdminEmail" -ForegroundColor Yellow
    Write-Host "   - Password is correct: $AdminPassword" -ForegroundColor Yellow
    exit 1
}

$authToken = $authResponse.access_token
Write-Host "Got authentication token: $($authToken.Substring(0, 20))..." -ForegroundColor Green

$authHeaders = @{
    "Authorization" = "Bearer $authToken"
}

# Test 2: Get current user
Write-Host "2. Testing User Authentication..." -ForegroundColor Magenta
$userResponse = Test-APIEndpoint -Method "GET" -Endpoint "/user" -Headers $authHeaders -Description "Get Current User"

# Test 3: Create a quiz
Write-Host "3. Testing Quiz Creation..." -ForegroundColor Magenta
$timestamp = [DateTimeOffset]::Now.ToUnixTimeSeconds()
$quizData = @{
    title = "PowerShell Test Quiz"
    slug = "powershell-test-quiz-$timestamp"
    type = "assessment"
    summary = "Test quiz created via PowerShell HTTP API"
    content = "This quiz tests the PowerShell HTTP API functionality"
}

$quizResponse = Test-APIEndpoint -Method "POST" -Endpoint "/quizzes" -Body $quizData -Headers $authHeaders -Description "Create Quiz"

if (-not $quizResponse) {
    Write-Host "FAILED to create quiz" -ForegroundColor Red
    exit 1
}

$quizId = $quizResponse.data.id
if (-not $quizId) {
    $quizId = $quizResponse.id
}

if (-not $quizId) {
    Write-Host "FAILED to get quiz ID from response" -ForegroundColor Red
    exit 1
}

Write-Host "Created quiz with ID: $quizId" -ForegroundColor Green

# Test 4: Get all quizzes
Write-Host "4. Testing Quiz List..." -ForegroundColor Magenta
Test-APIEndpoint -Method "GET" -Endpoint "/quizzes" -Headers $authHeaders -Description "Get All Quizzes"

# Test 5: Get specific quiz
Write-Host "5. Testing Quiz Retrieval..." -ForegroundColor Magenta
Test-APIEndpoint -Method "GET" -Endpoint "/quizzes/$quizId" -Headers $authHeaders -Description "Get Quiz by ID"

# Test 6: Create a question with answers
Write-Host "6. Testing Question Creation..." -ForegroundColor Magenta
$questionData = @{
    question_text = "What is the correct way to declare a variable in JavaScript?"
    question_type = "multiple_choice"
    quiz_answers = @(
        @{
            answer = "var myVar = 5;"
            is_correct = $true
        },
        @{
            answer = "variable myVar = 5;"
            is_correct = $false
        },
        @{
            answer = "let myVar = 5;"
            is_correct = $true
        },
        @{
            answer = "const myVar = 5;"
            is_correct = $false
        }
    )
}

$questionResponse = Test-APIEndpoint -Method "POST" -Endpoint "/quizzes/$quizId/questionswithanswers" -Body $questionData -Headers $authHeaders -Description "Create Question with Answers"

if ($questionResponse) {
    $questionId = $questionResponse.data.id
    if (-not $questionId) {
        $questionId = $questionResponse.id
    }
    if ($questionId) {
        Write-Host "Created question with ID: $questionId" -ForegroundColor Green
    }
}

# Test 7: Get quiz questions
Write-Host "7. Testing Quiz Questions Retrieval..." -ForegroundColor Magenta
Test-APIEndpoint -Method "GET" -Endpoint "/quizzes/$quizId/questions" -Headers $authHeaders -Description "Get Quiz Questions"

# Test 8: Update quiz
Write-Host "8. Testing Quiz Update..." -ForegroundColor Magenta
$updateTimestamp = [DateTimeOffset]::Now.ToUnixTimeSeconds()
$updateData = @{
    title = "Updated PowerShell Test Quiz"
    slug = "updated-powershell-test-quiz-$updateTimestamp"
    type = "assessment"
    summary = "Updated test quiz via PowerShell HTTP API"
    content = "This quiz has been updated via PowerShell HTTP API"
    published_at = (Get-Date).ToUniversalTime().ToString("yyyy-MM-ddTHH:mm:ssZ")
}

Test-APIEndpoint -Method "PUT" -Endpoint "/quizzes/$quizId" -Body $updateData -Headers $authHeaders -Description "Update Quiz"

# Test 9: Test error handling - unauthorized access
Write-Host "9. Testing Error Handling (Unauthorized)..." -ForegroundColor Magenta
Test-APIEndpoint -Method "GET" -Endpoint "/quizzes" -Description "Access without token"

# Test 10: Test error handling - invalid quiz ID
Write-Host "10. Testing Error Handling (Invalid Quiz ID)..." -ForegroundColor Magenta
Test-APIEndpoint -Method "GET" -Endpoint "/quizzes/99999" -Headers $authHeaders -Description "Get Non-existent Quiz"

# Test 11: Test quiz statistics endpoint
Write-Host "11. Testing Quiz Statistics..." -ForegroundColor Magenta
Test-APIEndpoint -Method "GET" -Endpoint "/quizzes/$quizId/statistics" -Headers $authHeaders -Description "Get Quiz Statistics"

# Test 12: Delete quiz (cleanup)
Write-Host "12. Testing Quiz Deletion..." -ForegroundColor Magenta
Test-APIEndpoint -Method "DELETE" -Endpoint "/quizzes/$quizId" -Headers $authHeaders -Description "Delete Quiz"

Write-Host ""
Write-Host "PowerShell HTTP Quiz API Testing Completed!" -ForegroundColor Green
Write-Host "If all tests passed, your Quiz API is working correctly." -ForegroundColor Yellow

Write-Host ""
Write-Host "📋 Summary of Tests Performed:" -ForegroundColor Cyan
Write-Host "1. Authentication (Login)" -ForegroundColor White
Write-Host "2. User information retrieval" -ForegroundColor White
Write-Host "3. Quiz creation with proper fields" -ForegroundColor White
Write-Host "4. Quiz listing" -ForegroundColor White
Write-Host "5. Quiz retrieval by ID" -ForegroundColor White
Write-Host "6. Question creation with answers" -ForegroundColor White
Write-Host "7. Quiz questions retrieval" -ForegroundColor White
Write-Host "8. Quiz updates" -ForegroundColor White
Write-Host "9. Error handling (unauthorized)" -ForegroundColor White
Write-Host "10. Error handling (invalid IDs)" -ForegroundColor White
Write-Host "11. Quiz statistics endpoint" -ForegroundColor White
Write-Host "12. Quiz deletion" -ForegroundColor White
