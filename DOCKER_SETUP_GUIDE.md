# 🐳 Docker Setup Guide - Achance API

Complete guide for running the Achance API project with Docker, including full support for Content Management (document conversion, attachments, ZIP downloads).

## 📋 Prerequisites

- **Docker Desktop** 4.x or higher
- **Docker Compose** 2.x or higher
- **Git** (for cloning repository)
- **4GB+ RAM** available for Docker
- **Windows/macOS/Linux** supported

## 🚀 Quick Start (5 Minutes)

### 1. Clone & Navigate
```powershell
cd d:\Ghazi\achance-api-main
```

### 2. Setup Environment File
```powershell
# Copy environment file
Copy-Item src\.env.example src\.env

# Generate application key (we'll do this after containers start)
```

### 3. Build & Start Containers
```powershell
# Build and start all services
docker-compose up -d --build

# This will start:
# ✅ nginx-lb (Load Balancer) - Port 80, 443
# ✅ app1 (PHP-FPM Laravel) - Internal
# ✅ mysql (Database) - Port 3306
# ✅ redis-master (Cache) - Port 6379
# ✅ redis-slave (Cache Replica) - Port 6380
# ✅ queue-worker (Background Jobs) - Internal
# ✅ phpmyadmin (DB Admin) - Port 8080
```

### 4. Initial Setup Commands
```powershell
# Generate application key
docker-compose exec app1 php artisan key:generate

# Run migrations
docker-compose exec app1 php artisan migrate

# Seed database (optional)
docker-compose exec app1 php artisan db:seed --class=ContentSeeder

# Create storage symlink
docker-compose exec app1 php artisan storage:link

# Cache configuration
docker-compose exec app1 php artisan config:cache
```

### 5. Verify Installation
```powershell
# Check all services are running
docker-compose ps

# Check application logs
docker-compose logs -f app1

# Test API endpoint
curl http://localhost/api/health
```

## 🎯 Access Points

| Service | URL | Credentials |
|---------|-----|-------------|
| **API** | http://localhost | N/A |
| **phpMyAdmin** | http://localhost:8080 | user: `root`, pass: `root` |
| **MySQL** | localhost:3306 | user: `root`, pass: `root`, db: `achance` |
| **Redis Master** | localhost:6379 | No password |
| **Redis Slave** | localhost:6380 | No password |

## 📦 What's Included

### Services Architecture
```
┌─────────────────────────────────────────────────────────┐
│                    nginx-lb (Port 80)                    │
│                    Load Balancer                         │
└────────────────────────┬────────────────────────────────┘
                         │
                         ▼
              ┌──────────────────────┐
              │   app1 (PHP-FPM)     │
              │   Laravel API        │
              └──┬────────────────┬──┘
                 │                │
        ┌────────▼─────┐   ┌─────▼────────┐
        │    MySQL     │   │Redis Master  │
        │   Database   │   │    Cache     │
        └──────────────┘   └──────┬───────┘
                                   │
                            ┌──────▼───────┐
                            │ Redis Slave  │
                            │Cache Replica │
                            └──────────────┘
```

### PHP Extensions (Pre-installed)
- ✅ **gd** - Image processing (Intervention/Image)
- ✅ **zip** - ZIP archive creation/extraction
- ✅ **dom, xml, xmlreader, xmlwriter** - Document parsing (PHPWord)
- ✅ **soap, xsl** - Advanced XML processing
- ✅ **pdo_mysql** - Database connectivity
- ✅ **redis** - Cache driver
- ✅ **opcache** - Performance optimization
- ✅ **mbstring, intl** - String/internationalization

### Storage Directories (Auto-created)
```
storage/
├── app/
│   └── public/
│       └── uploads/
│           └── contents/
│               ├── source/      # Original uploaded files (.doc, .docx, .html)
│               ├── images/      # Extracted/processed images
│               ├── assets/      # Videos, audio, PDFs, documents
│               └── archives/    # Generated ZIP files
├── framework/
│   ├── cache/
│   ├── sessions/
│   └── views/
└── logs/
```

## 🛠️ Common Commands

### Container Management
```powershell
# Start all services
docker-compose up -d

# Stop all services
docker-compose down

# Restart specific service
docker-compose restart app1

# View logs
docker-compose logs -f app1
docker-compose logs -f mysql
docker-compose logs -f redis-master

# Execute commands in container
docker-compose exec app1 php artisan route:list
docker-compose exec app1 php artisan tinker
docker-compose exec mysql mysql -uroot -proot achance
```

### Laravel Commands
```powershell
# Clear all caches
docker-compose exec app1 php artisan cache:clear
docker-compose exec app1 php artisan config:clear
docker-compose exec app1 php artisan route:clear
docker-compose exec app1 php artisan view:clear

# Run migrations
docker-compose exec app1 php artisan migrate
docker-compose exec app1 php artisan migrate:fresh --seed

# Generate key
docker-compose exec app1 php artisan key:generate

# Create storage link
docker-compose exec app1 php artisan storage:link

# Run specific seeder
docker-compose exec app1 php artisan db:seed --class=ContentSeeder

# Check routes
docker-compose exec app1 php artisan route:list --columns=method,uri,name

# Run tests
docker-compose exec app1 php artisan test
```

### Database Commands
```powershell
# Access MySQL CLI
docker-compose exec mysql mysql -uroot -proot achance

# Backup database
docker-compose exec mysql mysqldump -uroot -proot achance > backup.sql

# Restore database
docker-compose exec -T mysql mysql -uroot -proot achance < backup.sql

# Check database status
docker-compose exec mysql mysql -uroot -proot -e "SHOW DATABASES;"
```

### Redis Commands
```powershell
# Access Redis CLI
docker-compose exec redis-master redis-cli

# Check Redis connection
docker-compose exec redis-master redis-cli ping

# View all keys
docker-compose exec redis-master redis-cli KEYS "*"

# Clear all cache
docker-compose exec redis-master redis-cli FLUSHALL

# Monitor Redis commands
docker-compose exec redis-master redis-cli MONITOR
```

## 🔧 Configuration

### Environment Variables

Edit `src/.env` to configure:

```env
# Application
APP_NAME="Achance API"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

# Database (Docker)
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=achance
DB_USERNAME=root
DB_PASSWORD=root

# Redis Cache (Docker)
CACHE_DRIVER=redis
REDIS_HOST=redis-master
REDIS_PORT=6379
REDIS_CLIENT=phpredis

# Queue
QUEUE_CONNECTION=redis

# Session
SESSION_DRIVER=redis
SESSION_LIFETIME=120

# Mail (optional - for testing)
MAIL_MAILER=log
```

### Auto Migration & Seeding

Control automatic database setup via docker-compose.yml:

```yaml
environment:
  - AUTO_MIGRATE=true   # Auto-run migrations on startup
  - AUTO_SEED=false     # Auto-seed database on startup
```

**Recommended**:
- **Development**: `AUTO_MIGRATE=true`, `AUTO_SEED=false` (migrate yes, seed manually)
- **Production**: `AUTO_MIGRATE=false`, `AUTO_SEED=false` (manual control)

### PHP Configuration

Modify `docker/php/php.ini` for PHP settings:
```ini
upload_max_filesize = 50M
post_max_size = 50M
memory_limit = 512M
max_execution_time = 300
```

Modify `docker/php/www-optimized.conf` for PHP-FPM settings:
```conf
pm = dynamic
pm.max_children = 30
pm.start_servers = 8
pm.min_spare_servers = 5
pm.max_spare_servers = 15
```

## 🧪 Testing the Setup

### 1. Test API Health
```powershell
curl http://localhost/api/health
```

Expected response:
```json
{
  "status": "healthy",
  "timestamp": "2026-01-04T10:30:00Z"
}
```

### 2. Test Authentication
```powershell
# Login
curl -X POST http://localhost/api/login `
  -H "Content-Type: application/json" `
  -d '{\"email\":\"admin@example.com\",\"password\":\"password\"}'
```

### 3. Test Content Import
```powershell
# Upload a document (requires authentication token)
curl -X POST http://localhost/api/courses/1/modules/1/lessons/1/content `
  -H "Authorization: Bearer YOUR_TOKEN" `
  -F "title=Test Document" `
  -F "file=@sample.docx" `
  -F "words_per_page=500"
```

### 4. Run Postman Tests
```powershell
# Install newman (if not installed)
npm install -g newman

# Run Postman collection
cd src/tests/postman
newman run Course_System_API_Tests.postman_collection.json `
  --environment Course_System_Test_Environment.postman_environment.json
```

## 🐛 Troubleshooting

### Container Issues

**Problem**: Container won't start
```powershell
# Check logs
docker-compose logs app1

# Rebuild container
docker-compose build --no-cache app1
docker-compose up -d app1
```

**Problem**: MySQL connection refused
```powershell
# Wait for MySQL to be ready (takes 30-60 seconds on first run)
docker-compose logs mysql

# Check MySQL health
docker-compose exec mysql mysqladmin ping -h localhost -uroot -proot
```

**Problem**: Permission denied errors
```powershell
# Fix storage permissions
docker-compose exec app1 chown -R www-data:www-data storage bootstrap/cache
docker-compose exec app1 chmod -R 775 storage bootstrap/cache
```

### Application Issues

**Problem**: 500 Internal Server Error
```powershell
# Check Laravel logs
docker-compose exec app1 tail -f storage/logs/laravel.log

# Clear cache
docker-compose exec app1 php artisan cache:clear
docker-compose exec app1 php artisan config:clear
```

**Problem**: Storage symlink broken
```powershell
# Recreate symlink
docker-compose exec app1 rm -f public/storage
docker-compose exec app1 php artisan storage:link
```

**Problem**: Composer dependencies missing
```powershell
# Reinstall dependencies
docker-compose exec app1 composer install
```

### Performance Issues

**Problem**: Slow response times
```powershell
# Enable OPcache
docker-compose exec app1 php -i | grep opcache

# Cache configuration
docker-compose exec app1 php artisan config:cache
docker-compose exec app1 php artisan route:cache
docker-compose exec app1 php artisan view:cache

# Check Redis connection
docker-compose exec redis-master redis-cli ping
```

**Problem**: High memory usage
```powershell
# Check container resource usage
docker stats

# Adjust PHP-FPM settings in docker/php/www-optimized.conf
# Reduce pm.max_children from 30 to 15
```

## 🔄 Update & Rebuild

### Update Code
```powershell
# Pull latest changes
git pull origin main

# Rebuild containers
docker-compose build --no-cache
docker-compose up -d

# Run migrations
docker-compose exec app1 php artisan migrate

# Clear cache
docker-compose exec app1 php artisan cache:clear
```

### Update Dependencies
```powershell
# Update Composer packages
docker-compose exec app1 composer update

# Rebuild autoload
docker-compose exec app1 composer dump-autoload
```

## 🗑️ Clean Up

### Remove All Data
```powershell
# Stop and remove containers
docker-compose down

# Remove volumes (⚠️ deletes database data)
docker-compose down -v

# Remove images
docker-compose down --rmi all
```

### Fresh Start
```powershell
# Complete clean restart
docker-compose down -v
docker-compose build --no-cache
docker-compose up -d
docker-compose exec app1 php artisan key:generate
docker-compose exec app1 php artisan migrate
docker-compose exec app1 php artisan db:seed
```

## 📊 Monitoring

### Check Service Health
```powershell
# All services status
docker-compose ps

# Service health checks
docker-compose exec app1 php artisan --version
docker-compose exec mysql mysqladmin ping -h localhost -uroot -proot
docker-compose exec redis-master redis-cli ping
```

### Monitor Logs
```powershell
# All services
docker-compose logs -f

# Specific service
docker-compose logs -f app1
docker-compose logs -f mysql
docker-compose logs -f queue-worker

# Last 100 lines
docker-compose logs --tail=100 app1
```

### Resource Usage
```powershell
# Real-time stats
docker stats

# Disk usage
docker system df

# Clean unused resources
docker system prune -a
```

## 🚀 Production Deployment

For production, use the production target:

```yaml
services:
  app1:
    build:
      target: production  # Change from development
    environment:
      - APP_ENV=production
      - APP_DEBUG=false
      - AUTO_MIGRATE=false
      - AUTO_SEED=false
```

Additional production steps:
1. Set strong `APP_KEY`
2. Configure SSL certificates in `docker/ssl/`
3. Set proper `DB_PASSWORD` (not `root`)
4. Enable Redis password authentication
5. Configure proper logging
6. Enable monitoring (Prometheus/Grafana)
7. Setup regular backups

## 📚 Additional Resources

- **Laravel Documentation**: https://laravel.com/docs/10.x
- **Docker Documentation**: https://docs.docker.com/
- **PHPWord Documentation**: https://phpword.readthedocs.io/
- **API Documentation**: See [API_DOCUMENTATION.md](./API_DOCUMENTATION.md)
- **Content API**: See [CONTENT_API_QUICK_REFERENCE.md](./CONTENT_API_QUICK_REFERENCE.md)
- **Postman Tests**: See [CONTENT_TESTS_INTEGRATION_COMPLETE.md](./CONTENT_TESTS_INTEGRATION_COMPLETE.md)

## 🆘 Support

For issues or questions:
1. Check logs: `docker-compose logs -f app1`
2. Review [Troubleshooting](#-troubleshooting) section
3. Check Laravel logs: `docker-compose exec app1 tail -f storage/logs/laravel.log`
4. Review API documentation for endpoint details

---

**Version**: 1.0.0  
**Last Updated**: January 4, 2026  
**Maintained by**: Achance API Team
