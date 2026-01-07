# Quick Start Script for Docker
Write-Host "🚀 Starting Achance API with Docker..." -ForegroundColor Cyan

# Check if Docker is running
$dockerRunning = docker info 2>&1
if ($LASTEXITCODE -ne 0) {
    Write-Host "❌ Docker is not running! Please start Docker Desktop first." -ForegroundColor Red
    exit 1
}

# Start containers
Write-Host "`n📦 Building and starting containers..." -ForegroundColor Yellow
docker-compose up -d --build

# Wait for MySQL to be ready
Write-Host "`n⏳ Waiting for MySQL to be ready..." -ForegroundColor Yellow
Start-Sleep -Seconds 10

# Run initial setup
Write-Host "`n🔧 Running initial setup..." -ForegroundColor Green

# Generate key
Write-Host "  → Generating application key..." -ForegroundColor Cyan
docker-compose exec -T app php artisan key:generate --force

# Run migrations
Write-Host "  → Running migrations..." -ForegroundColor Cyan
docker-compose exec -T app php artisan migrate --force

# Create storage link
Write-Host "  → Creating storage symlink..." -ForegroundColor Cyan
docker-compose exec -T app php artisan storage:link

# Install dependencies
Write-Host "  → Installing dependencies..." -ForegroundColor Cyan
docker-compose exec -T app composer install --optimize-autoloader

# Cache config
Write-Host "  → Caching configuration..." -ForegroundColor Cyan
docker-compose exec -T app php artisan config:cache

Write-Host "`n✅ Setup complete!" -ForegroundColor Green
Write-Host "`n📍 Access Points:" -ForegroundColor Yellow
Write-Host "   API: http://localhost:8000" -ForegroundColor White
Write-Host "   Database: localhost:3306 (user: root, pass: root)" -ForegroundColor White
Write-Host "`n💡 Tip: Run ./docker-manager.ps1 for more options" -ForegroundColor Cyan
