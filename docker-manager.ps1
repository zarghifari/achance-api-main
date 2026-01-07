# Docker Management Script for Achance API
# Updated: January 6, 2026 - Supports new attachment system without ZIP

Write-Host "🐳 Achance API - Docker Manager" -ForegroundColor Cyan
Write-Host "================================`n" -ForegroundColor Cyan

function Show-Menu {
    Write-Host "Available Commands:" -ForegroundColor Yellow
    Write-Host "  1. Start All Services" -ForegroundColor Green
    Write-Host "  2. Stop All Services" -ForegroundColor Red
    Write-Host "  3. Restart All Services" -ForegroundColor Yellow
    Write-Host "  4. View Logs" -ForegroundColor Blue
    Write-Host "  5. Run Migrations" -ForegroundColor Magenta
    Write-Host "  6. Seed Database" -ForegroundColor Magenta
    Write-Host "  7. Clear Cache" -ForegroundColor Cyan
    Write-Host "  8. Create Storage Link" -ForegroundColor Cyan
    Write-Host "  9. Install Dependencies" -ForegroundColor Green
    Write-Host " 10. Run Artisan Command" -ForegroundColor White
    Write-Host " 11. Check Service Status" -ForegroundColor Yellow
    Write-Host " 12. Rebuild Containers" -ForegroundColor Red
    Write-Host "  0. Exit`n" -ForegroundColor Gray
}

function Start-Services {
    Write-Host "`n🚀 Starting all services..." -ForegroundColor Green
    docker-compose up -d
    Write-Host "✅ Services started!" -ForegroundColor Green
    Write-Host "📍 API: http://localhost:8000" -ForegroundColor Cyan
    Write-Host "📍 MySQL: localhost:3306 (user: root, pass: root)" -ForegroundColor Cyan
}

function Stop-Services {
    Write-Host "`n🛑 Stopping all services..." -ForegroundColor Red
    docker-compose down
    Write-Host "✅ Services stopped!" -ForegroundColor Green
}

function Restart-Services {
    Write-Host "`n🔄 Restarting all services..." -ForegroundColor Yellow
    docker-compose restart
    Write-Host "✅ Services restarted!" -ForegroundColor Green
}

function Show-Logs {
    Write-Host "`n📋 Showing logs (Ctrl+C to exit)..." -ForegroundColor Blue
    docker-compose logs -f --tail=100
}

function Run-Migrations {
    Write-Host "`n🔄 Running database migrations..." -ForegroundColor Magenta
    docker-compose exec app php artisan migrate --force
    Write-Host "✅ Migrations completed!" -ForegroundColor Green
}

function Seed-Database {
    Write-Host "`n🌱 Seeding database..." -ForegroundColor Magenta
    docker-compose exec app php artisan db:seed --class=RolesAndPermissionsSeeder
    docker-compose exec app php artisan db:seed --class=CourseSeeder
    docker-compose exec app php artisan db:seed --class=ContentSeeder
    Write-Host "✅ Database seeded!" -ForegroundColor Green
}

function Clear-Cache {
    Write-Host "`n🧹 Clearing all caches..." -ForegroundColor Cyan
    docker-compose exec app php artisan cache:clear
    docker-compose exec app php artisan config:clear
    docker-compose exec app php artisan route:clear
    docker-compose exec app php artisan view:clear
    Write-Host "✅ Cache cleared!" -ForegroundColor Green
}

function Create-StorageLink {
    Write-Host "`n🔗 Creating storage symlink..." -ForegroundColor Cyan
    docker-compose exec app php artisan storage:link
    Write-Host "✅ Storage link created!" -ForegroundColor Green
}

function Install-Dependencies {
    Write-Host "`n📦 Installing Composer dependencies..." -ForegroundColor Green
    docker-compose exec app composer install --optimize-autoloader --no-dev
    Write-Host "✅ Dependencies installed!" -ForegroundColor Green
}

function Run-ArtisanCommand {
    $command = Read-Host "`nEnter Artisan command (e.g., migrate, cache:clear)"
    Write-Host "`n⚡ Running: php artisan $command" -ForegroundColor White
    docker-compose exec app php artisan $command
}

function Check-Status {
    Write-Host "`n📊 Service Status:" -ForegroundColor Yellow
    docker-compose ps
}

function Rebuild-Containers {
    Write-Host "`n🔨 Rebuilding containers..." -ForegroundColor Red
    Write-Host "⚠️  This will stop and rebuild all containers!" -ForegroundColor Yellow
    $confirm = Read-Host "Continue? (y/n)"
    if ($confirm -eq 'y') {
        docker-compose down
        docker-compose build --no-cache
        docker-compose up -d
        Write-Host "✅ Containers rebuilt!" -ForegroundColor Green
    }
}

# Main loop
while ($true) {
    Show-Menu
    $choice = Read-Host "Select option"
    
    switch ($choice) {
        "1" { Start-Services }
        "2" { Stop-Services }
        "3" { Restart-Services }
        "4" { Show-Logs }
        "5" { Run-Migrations }
        "6" { Seed-Database }
        "7" { Clear-Cache }
        "8" { Create-StorageLink }
        "9" { Install-Dependencies }
        "10" { Run-ArtisanCommand }
        "11" { Check-Status }
        "12" { Rebuild-Containers }
        "0" { 
            Write-Host "`n👋 Goodbye!" -ForegroundColor Cyan
            exit 
        }
        default { 
            Write-Host "`n❌ Invalid option!" -ForegroundColor Red 
        }
    }
    
    Write-Host "`nPress any key to continue..." -ForegroundColor Gray
    $null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
    Clear-Host
}
