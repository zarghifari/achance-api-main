# Quick Deployment Script
# Run this file: .\deploy_optimizations.ps1

Write-Host "`n═══════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "  DEPLOYING API OPTIMIZATIONS" -ForegroundColor Cyan
Write-Host "═══════════════════════════════════════════════════`n" -ForegroundColor Cyan

# Step 1: Rebuild containers
Write-Host "[1/5] Rebuilding Docker containers..." -ForegroundColor Yellow
docker-compose down
docker-compose build --no-cache nginx-lb app1
docker-compose up -d

Write-Host "`nWaiting 30 seconds for services to start..." -ForegroundColor Gray
Start-Sleep -Seconds 30

# Step 2: Run migrations
Write-Host "`n[2/5] Running database migrations..." -ForegroundColor Yellow
docker-compose exec app1 php artisan migrate --force

# Step 3: Clear caches
Write-Host "`n[3/5] Clearing caches..." -ForegroundColor Yellow
docker-compose exec app1 php artisan cache:clear
docker-compose exec app1 php artisan config:clear
docker-compose exec app1 php artisan route:clear

# Step 4: Warm cache
Write-Host "`n[4/5] Warming cache..." -ForegroundColor Yellow
docker-compose exec app1 php warm_cache.php

# Step 5: Test
Write-Host "`n[5/5] Running performance test..." -ForegroundColor Yellow
Write-Host "`nTesting /api/courses endpoint..." -ForegroundColor Gray

$response = Invoke-WebRequest -Uri "http://localhost/api/courses" -UseBasicParsing
Write-Host "  Status: $($response.StatusCode)" -ForegroundColor Green
Write-Host "  Response time: $($response.Headers['X-Response-Time'])" -ForegroundColor Green

Write-Host "`n═══════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "  ✅ DEPLOYMENT COMPLETE!" -ForegroundColor Green
Write-Host "═══════════════════════════════════════════════════`n" -ForegroundColor Cyan

Write-Host "Next steps:" -ForegroundColor Yellow
Write-Host "  1. Test your API endpoints" -ForegroundColor White
Write-Host "  2. Monitor response times" -ForegroundColor White
Write-Host "  3. Check logs: docker-compose logs -f app1" -ForegroundColor White
Write-Host "`n"
