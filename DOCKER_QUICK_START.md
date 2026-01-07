# 🐳 Docker Quick Start Guide
**Updated: January 6, 2026** - ZIP-free attachment system

## Prerequisites

- Docker Desktop installed and running
- 4GB+ RAM available
- Ports available: 8000 (API), 3306 (MySQL), 6379 (Redis)

## 🚀 Quick Start (2 Steps)

### Step 1: Start Docker
```powershell
# Navigate to project directory
cd d:\Ghazi\achance-api-main

# Run quick start script
.\docker-start.ps1
```

This will:
- ✅ Build Docker images
- ✅ Start all services (PHP, Nginx, MySQL, Redis)
- ✅ Run migrations
- ✅ Create storage symlink
- ✅ Install dependencies

### Step 2: Access the API
```
API: http://localhost:8000
Database: localhost:3306 (root/root)
```

## 📋 Management Commands

### Using Docker Manager (Interactive)
```powershell
.\docker-manager.ps1
```

### Manual Commands

**Start Services:**
```powershell
docker-compose up -d
```

**Stop Services:**
```powershell
docker-compose down
```

**View Logs:**
```powershell
docker-compose logs -f app
```

**Run Migrations:**
```powershell
docker-compose exec app php artisan migrate
```

**Seed Database:**
```powershell
docker-compose exec app php artisan db:seed
```

**Clear Cache:**
```powershell
docker-compose exec app php artisan cache:clear
```

**Access PHP Container:**
```powershell
docker-compose exec app bash
```

## 🎯 Testing the Setup

### 1. Check API Health
```powershell
curl http://localhost:8000/api/health
```

### 2. Upload a Document with Images
```http
POST http://localhost:8000/api/courses/1/modules/1/lessons/1/content/import
Content-Type: multipart/form-data
Authorization: Bearer {token}

file: document.docx
title: Test Document
```

### 3. Access Uploaded Images
```
http://localhost:8000/storage/uploads/contents/images/image_*.jpg
```

## 🔧 Configuration

### Environment Variables
Edit `src/.env`:
```env
DB_HOST=mysql
DB_DATABASE=achance
DB_USERNAME=root
DB_PASSWORD=root

REDIS_HOST=redis
REDIS_PORT=6379

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

### PHP Settings
Edit `docker/php/php.ini`:
- `upload_max_filesize = 100M`
- `post_max_size = 100M`
- `memory_limit = 512M`

## 📁 Volume Mounts

- `./src` → `/var/www` (Laravel application)
- `mysql-data` → MySQL database
- `redis-data` → Redis cache

## 🔍 Troubleshooting

### Services not starting?
```powershell
# Check Docker is running
docker info

# View error logs
docker-compose logs

# Rebuild containers
docker-compose down
docker-compose up -d --build
```

### Permission errors?
```powershell
docker-compose exec app chown -R www-data:www-data /var/www/storage
docker-compose exec app chmod -R 755 /var/www/storage
```

### Images not displaying?
```powershell
# Recreate storage symlink
docker-compose exec app php artisan storage:link

# Check permissions
docker-compose exec app ls -la /var/www/storage/app/public
```

### Database connection issues?
```powershell
# Wait for MySQL to fully start
Start-Sleep -Seconds 10

# Check MySQL is running
docker-compose ps mysql

# Test connection
docker-compose exec mysql mysql -uroot -proot -e "SHOW DATABASES;"
```

## 🎨 What's Different (Updated System)

### ✅ New Features
- **No ZIP Creation** - Direct file access
- **PhpWord Image Extraction** - Images from .docx files properly extracted
- **Optimized Storage** - Files stored in organized folders
- **Better Performance** - No ZIP overhead

### 📂 Storage Structure
```
storage/app/public/
├── uploads/
│   └── contents/
│       ├── source/     # Original .doc/.docx files
│       ├── images/     # Extracted images
│       └── assets/     # Other attachments
```

### 🔗 Attachment Access
- **Web**: `/storage/uploads/contents/images/image_*.jpg`
- **API**: `/api/courses/{id}/modules/{id}/lessons/{id}/content/files/{path}`

## 🚦 Service Status

Check all services:
```powershell
docker-compose ps
```

Expected output:
```
NAME              STATUS    PORTS
achance-app       Up        9000/tcp
achance-nginx     Up        0.0.0.0:8000->80/tcp
achance-mysql     Up        0.0.0.0:3306->3306/tcp
achance-redis     Up        0.0.0.0:6379->6379/tcp
achance-queue     Up
achance-scheduler Up
```

## 🔄 Updates & Maintenance

### Pull Latest Changes
```powershell
git pull origin main
docker-compose down
docker-compose up -d --build
docker-compose exec app php artisan migrate --force
docker-compose exec app php artisan cache:clear
```

### Backup Database
```powershell
docker-compose exec mysql mysqldump -uroot -proot achance > backup.sql
```

### Restore Database
```powershell
docker-compose exec -T mysql mysql -uroot -proot achance < backup.sql
```

## 📚 Additional Resources

- Full API Documentation: `API_DOCUMENTATION.md`
- Attachment System: `ATTACHMENT_ACCESS_SUMMARY.md`
- Web Interface Guide: `WEB_INTERFACE_README.md`
- ZIP Removal Guide: `ZIP_REMOVAL_VERIFICATION.md`

---

**Ready to develop!** 🎉

For issues or questions, check logs with:
```powershell
docker-compose logs -f
```
