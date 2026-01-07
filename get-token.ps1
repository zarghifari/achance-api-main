# Get Fresh Authentication Token
$baseUrl = "http://localhost/api"

Write-Host "Getting fresh authentication token..." -ForegroundColor Cyan

# Login as admin
$loginData = @{
    email = "admin@example.com"
    password = "password"
} | ConvertTo-Json

try {
    $response = Invoke-RestMethod -Uri "$baseUrl/login" -Method Post -Body $loginData -ContentType "application/json"
    
    if ($response.token) {
        Write-Host "SUCCESS!" -ForegroundColor Green
        Write-Host "Token: $($response.token)" -ForegroundColor Yellow
        Write-Host ""
        Write-Host "Copy this token and update test-content-download.ps1" -ForegroundColor Cyan
    } else {
        Write-Host "ERROR: No token returned" -ForegroundColor Red
        $response | ConvertTo-Json -Depth 10
    }
} catch {
    Write-Host "ERROR: $($_.Exception.Message)" -ForegroundColor Red
    if ($_.ErrorDetails.Message) {
        Write-Host "Details: $($_.ErrorDetails.Message)" -ForegroundColor Red
    }
}
