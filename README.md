# AChance API - Optimized Laravel Backend

## 🚀 Overview
AChance API is a high-performance Laravel-based API project designed for production-scale applications. It features Content Management with document conversion (Word, HTML), full attachment support (images, videos, audio, PDFs), and optimized Docker infrastructure.

### ✨ Key Features
- **📄 Content Management** - Import Word/HTML documents, convert to paginated HTML
- **🖼️ Full Attachment Support** - Images, videos, audio, PDFs automatically downloaded and bundled
- **📦 ZIP Downloads** - Complete content packages with HTML + all assets
- **🔐 Authentication & Permissions** - Role-based access control (Admin, Teacher, Student)
- **📊 Analytics & Tracking** - User activity monitoring and reporting
- **⚡ High Performance** - Redis caching, OPcache, optimized queries

### 🏗️ Architecture Highlights
- **Load-Balanced PHP-FPM** with Nginx
- **Redis Master/Slave Cluster** for caching and sessions
- **MySQL 9.0** with performance optimizations
- **Background Queue Processing** with Laravel workers
- **Complete Storage Management** for documents and media
- **Monitoring Ready** (Prometheus + Grafana support)

## 📋 Prerequisites

Before starting, ensure you have:
- **Docker Desktop** 4.x+ with **Docker Compose** 2.x+
- **4GB+ RAM** available for Docker (8GB recommended)
- **10GB+ free disk space**
- **Git** installed
- **Windows/macOS/Linux** supported

## 🚀 Quick Start (5 Minutes)

### Option 1: Automated Setup (Recommended)
```powershell
# Windows PowerShell
.\docker-start.ps1

# This script will:
# ✅ Check Docker is running
# ✅ Create .env file
# ✅ Build containers
# ✅ Start all services
# ✅ Run migrations
# ✅ Setup storage
# ✅ Test the API
```

### Option 2: Manual Setup
```powershell
# 1. Copy environment file
Copy-Item src\.env.example src\.env

# 2. Build and start containers
docker-compose up -d --build

# 3. Wait for MySQL (30-60 seconds)
docker-compose logs -f mysql

# 4. Generate application key
docker-compose exec app1 php artisan key:generate

# 5. Run migrations
docker-compose exec app1 php artisan migrate

# 6. Create storage link
docker-compose exec app1 php artisan storage:link

# 7. (Optional) Seed database
docker-compose exec app1 php artisan db:seed
```

## 🎯 Access Points

| Service | URL | Credentials |
|---------|-----|-------------|
| **API** | http://localhost | N/A |
| **phpMyAdmin** | http://localhost:8080 | user: `root`, pass: `root` |
| **MySQL** | localhost:3306 | user: `root`, pass: `root`, db: `achance` |
| **Redis** | localhost:6379 | No password |

## 🛠️ Common Commands

### Container Management
```powershell
# View all services
docker-compose ps

# View logs
docker-compose logs -f app1

# Restart service
docker-compose restart app1

# Stop all services
docker-compose down

# Remove all data (⚠️ deletes database)
docker-compose down -v
```

### Laravel Commands
```powershell
# Clear cache
docker-compose exec app1 php artisan cache:clear

# Run migrations
docker-compose exec app1 php artisan migrate

# Seed database
docker-compose exec app1 php artisan db:seed --class=ContentSeeder

# List routes
docker-compose exec app1 php artisan route:list

# Run tests
docker-compose exec app1 php artisan test
```

## 📚 Documentation

- **[Docker Setup Guide](DOCKER_SETUP_GUIDE.md)** - Complete Docker documentation
- **[API Documentation](API_DOCUMENTATION.md)** - Full API reference
- **[Content API Quick Reference](CONTENT_API_QUICK_REFERENCE.md)** - Content endpoints
- **[Attachment Support](ATTACHMENT_SUPPORT.md)** - Attachment handling details
- **[Postman Tests](CONTENT_TESTS_INTEGRATION_COMPLETE.md)** - Test collection guide

## 🧪 Testing

### Run Postman Tests
```powershell
# Install newman
npm install -g newman

# Run tests
cd src/tests/postman
newman run Course_System_API_Tests.postman_collection.json `
  --environment Course_System_Test_Environment.postman_environment.json
```

### Test API Manually
```powershell
# Test health endpoint
curl http://localhost/api/health

# Login
curl -X POST http://localhost/api/login `
  -H "Content-Type: application/json" `
  -d '{"email":"admin@example.com","password":"password"}'
```
docker-compose exec app1 php artisan storage:link

# Run database migrations and seeders
docker-compose exec app1 php artisan migrate:fresh --seed

# Set proper file permissions
docker-compose exec app1 chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
docker-compose exec app1 chmod -R 775 /var/www/storage /var/www/bootstrap/cache
```

### Step 6: Optimize Laravel
```bash
# Cache configurations for better performance
docker-compose exec app1 php artisan config:cache
docker-compose exec app1 php artisan route:cache
docker-compose exec app1 php artisan view:cache

# Test queue workers
docker-compose exec app1 php artisan queue:restart
```

### Step 7: Verify Deployment
```bash
# Check all services are healthy
docker-compose ps

# Test API endpoint
curl -I http://localhost

# Check application logs
docker-compose logs app1 app2 app3
```

## 🌐 Available Services

| Service | URL | Credentials | Purpose |
|---------|-----|-------------|---------|
| **🌍 Main API** | http://localhost | - | Load-balanced Laravel API |
| **🗄️ phpMyAdmin** | http://localhost:8080 | `root` / `root` | Database management interface |
| **🐬 MySQL** | localhost:3306 | `root` / `root` / `achance` | Primary database |
| **⚡ Redis Master** | localhost:6379 | - | Primary cache & sessions |
| **⚡ Redis Slave** | localhost:6380 | - | Backup cache (read-only) |
| **🔍 Elasticsearch** | http://localhost:9200 | - | Search engine |
| **📊 Prometheus** | http://localhost:9090 | - | Metrics collection |
| **📈 Grafana** | http://localhost:3000 | `admin` / `admin` | Monitoring dashboards |

## 🏗️ Infrastructure Architecture

```
                    ┌─────────────────┐
                    │  Nginx LB :80   │
                    │   (SSL :443)    │
                    └─────────┬───────┘
                              │
                    ┌─────────▼───────┐
                    │  Load Balancer  │
                    └─┬─────┬─────┬───┘
                      │     │     │
              ┌───────▼┐ ┌─▼───┐ ┌▼────────┐
              │ App1:  │ │App2:│ │ App3:   │
              │ 9000   │ │9000 │ │ 9000    │
              └────────┘ └─────┘ └─────────┘
                      │     │     │
                      └─────┼─────┘
                            │
        ┌───────────────────┼───────────────────┐
        │                   │                   │
   ┌────▼─────┐    ┌────────▼──┐       ┌───────▼────┐
   │ MySQL:   │    │ Redis     │       │Elasticsearch│
   │ 3306     │    │Master:6379│       │   :9200     │
   └──────────┘    │Slave:6380 │       └────────────┘
                   └───────────┘
```

## 🚀 Performance Features

### Load Balancing & Scaling
- **3x PHP-FPM instances** running simultaneously
- **Nginx load balancer** with round-robin distribution
- **Auto-restart** on container failure
- **Resource limits** to prevent memory leaks

### Caching Strategy
- **Redis Master/Slave** for high-availability caching
- **Laravel caching** for routes, configs, and views
- **OPcache enabled** for 5-10x faster PHP execution
- **Session clustering** across Redis instances

### Database Optimization
- **MySQL 9.0** with InnoDB optimizations
- **2GB buffer pool** for faster queries
- **Connection pooling** with max 1000 concurrent connections
- **Redo log optimization** for better write performance

### Background Processing
- **2x Queue workers** for async job processing
- **Automatic restart** on worker failure
- **Memory-efficient** worker management

## 🔧 Development vs Production

### Development Setup (Faster startup, single instance)
```bash
# Use simplified setup for development
docker-compose -f docker-compose.old.yml up -d

# Install dependencies
docker-compose -f docker-compose.old.yml exec app composer install
docker-compose -f docker-compose.old.yml exec app php artisan migrate:fresh --seed
```

### Production Setup (Full optimization)
```bash
# Use main optimized setup
docker-compose up -d

# Follow the deployment steps above
```

## 📊 Monitoring & Health Checks

### Service Health Monitoring
```bash
# Check all container status
docker-compose ps

# Monitor resource usage in real-time
docker stats

# Check specific service logs
docker-compose logs -f app1 mysql redis-master

# Test load balancer distribution
for i in {1..5}; do curl -H "X-Request-ID: $i" http://localhost/api/status; done
```

### Performance Monitoring
- **📈 Grafana Dashboard**: http://localhost:3000
  - Login: `admin` / `admin`
  - Pre-configured dashboards for Laravel, MySQL, Redis
- **📊 Prometheus Metrics**: http://localhost:9090
  - Query Laravel metrics, database performance
- **🔍 Elasticsearch Health**: http://localhost:9200/_cluster/health

### Health Check Endpoints
```bash
# API health check
curl http://localhost/api/health

# Database connectivity
curl http://localhost/api/db-check

# Redis connectivity  
curl http://localhost/api/cache-check

# Queue status
curl http://localhost/api/queue-status
```

## 🛠️ Troubleshooting Guide

### Common Issues & Solutions

#### 1. High Memory Usage
```bash
# Check memory consumption
docker stats --format "table {{.Container}}\t{{.MemUsage}}\t{{.MemPerc}}"

# Reduce Elasticsearch memory if needed
docker-compose exec elasticsearch bash -c 'echo "-Xms512m\n-Xmx512m" >> /usr/share/elasticsearch/config/jvm.options'
docker-compose restart elasticsearch
```

#### 2. Database Connection Issues
```bash
# Check MySQL status
docker-compose exec mysql mysqladmin -uroot -proot status

# Test database connectivity
docker-compose exec app1 php artisan tinker
# In tinker: DB::connection()->getPdo();

# Reset database if needed
docker-compose exec app1 php artisan migrate:fresh --seed
```

#### 3. Load Balancer Not Responding
```bash
# Check nginx configuration
docker-compose exec nginx-lb nginx -t

# Check if app instances are responding
docker-compose exec app1 php artisan route:list
docker-compose exec app2 php artisan route:list
docker-compose exec app3 php artisan route:list

# Restart load balancer
docker-compose restart nginx-lb
```

#### 4. Redis Connection Problems
```bash
# Test Redis master connectivity
docker-compose exec redis-master redis-cli ping

# Test Redis slave connectivity
docker-compose exec redis-slave redis-cli ping

# Check Redis logs
docker-compose logs redis-master redis-slave

# Restart Redis cluster
docker-compose restart redis-master redis-slave
```

#### 5. Queue Workers Not Processing
```bash
# Check queue worker status
docker-compose ps | grep queue-worker

# Check queue worker logs
docker-compose logs queue-worker

# Restart queue workers
docker-compose restart queue-worker

# Test queue manually
docker-compose exec app1 php artisan queue:work --once
```

### Complete Reset (⚠️ Data Loss Warning)
```bash
# Stop all services
docker-compose down -v

# Remove all containers, images, and volumes
docker system prune -af
docker volume prune -f

# Remove all project images
docker rmi $(docker images "*achance*" -q) 2>/dev/null || true

# Start fresh
docker-compose up -d --build
```

## 🎯 API Testing & Usage

### Default Login Accounts (After Seeding)
| Role | Email | Password | Access Level |
|------|-------|----------|-------------|
| 👑 **Super Admin** | admin@achance.com | password | Full system access |
| 👨‍🏫 **Teacher** | teacher@achance.com | password | Course management |
| 👨‍🎓 **Student** | student@achance.com | password | Learning access |

### Quick API Tests
```bash
# Health check
curl -X GET http://localhost/api/health

# Get API information
curl -X GET http://localhost/api/info

# Authentication test
curl -X POST http://localhost/api/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "admin@achance.com",
    "password": "password"
  }'

# Protected endpoint test (use token from login response)
curl -X GET http://localhost/api/user \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

### Load Testing
```bash
# Simple load test with curl
for i in {1..50}; do
  curl -s -o /dev/null -w "%{http_code} %{time_total}s\n" http://localhost/api/health &
done
wait

# Using Apache Bench (if installed)
ab -n 1000 -c 10 http://localhost/api/health
```

## 🔧 Advanced Configuration

### Scaling Services
```bash
# Scale queue workers
docker-compose up -d --scale queue-worker=4

# Scale specific services (edit docker-compose.yml to add app4, app5)
# Then update nginx load balancer configuration
```

### Environment Variables

Key environment variables in `src/.env`:

```env
# Application
APP_NAME="AChance API"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://localhost

# Database
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=achance
DB_USERNAME=root
DB_PASSWORD=root

# Redis
REDIS_HOST=redis-master
REDIS_PASSWORD=null
REDIS_PORT=6379

# Cache & Sessions
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Mail (configure for production)
MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
```

### Performance Tuning

#### PHP-FPM Optimization
```bash
# Monitor PHP-FPM status
docker-compose exec app1 curl http://localhost:9000/status?full

# Adjust FPM settings in docker-compose.yml:
# PHP_FPM_PM_MAX_CHILDREN=50
# PHP_FPM_PM_START_SERVERS=10
# PHP_FPM_PM_MIN_SPARE_SERVERS=5
# PHP_FPM_PM_MAX_SPARE_SERVERS=20
```

#### MySQL Performance
```bash
# Check MySQL performance
docker-compose exec mysql mysql -uroot -proot -e "SHOW STATUS LIKE 'Threads_connected';"
docker-compose exec mysql mysql -uroot -proot -e "SHOW STATUS LIKE 'Innodb_buffer_pool_hit_rate';"

# Monitor slow queries
docker-compose exec mysql mysql -uroot -proot -e "SHOW STATUS LIKE 'Slow_queries';"
```

#### Redis Performance
```bash
# Check Redis memory usage
docker-compose exec redis-master redis-cli info memory

# Monitor Redis commands
docker-compose exec redis-master redis-cli monitor

# Check Redis hit rate
docker-compose exec redis-master redis-cli info stats | grep hit_rate
```

## 📁 Project Structure

```
📁 achance-api/
├── 🐳 docker/                     # Docker configuration files
│   ├── nginx/                     # Nginx configurations
│   │   ├── default.conf          # Simple nginx config
│   │   ├── load-balancer.conf    # Production load balancer
│   │   └── lb-server.conf        # Load balancer server blocks
│   ├── php/                      # PHP-FPM configurations
│   │   ├── Dockerfile            # PHP container build
│   │   ├── php.ini              # PHP configuration
│   │   └── php-optimized.ini    # Production PHP settings
│   ├── mysql/                    # MySQL configurations
│   │   └── mysql.cnf            # MySQL optimization settings
│   ├── redis/                    # Redis configurations
│   │   ├── redis-master.conf    # Redis master config
│   │   └── redis-slave.conf     # Redis slave config
│   └── monitoring/               # Monitoring configurations
│       └── prometheus.yml       # Prometheus configuration
├── 📁 src/                       # Laravel application source
│   ├── app/                      # Core application logic
│   │   ├── Http/Controllers/     # API controllers
│   │   ├── Models/              # Eloquent models
│   │   ├── Services/            # Business logic services
│   │   └── Jobs/                # Queue jobs
│   ├── config/                   # Laravel configuration
│   ├── database/                 # Migrations and seeders
│   ├── routes/                   # Route definitions
│   ├── storage/                  # File storage and logs
│   ├── tests/                    # PHPUnit tests
│   └── public/                   # Web accessible files
├── 🐙 docker-compose.yml         # Production setup (optimized)
├── 🐙 docker-compose.old.yml     # Development setup (simple)
├── 📖 README.md                  # This comprehensive guide
└── 📋 quiz-import-sample.*       # Sample data files
```

## 🚢 Docker Containers Overview

### Production Stack (docker-compose.yml)
| Container | Image | Purpose | Replicas |
|-----------|-------|---------|----------|
| **nginx-lb** | nginx:alpine | Load balancer & SSL termination | 1 |
| **app1-3** | Custom PHP | Laravel application instances | 3 |
| **mysql** | mysql:9.0 | Primary database | 1 |
| **redis-master** | redis:8.0 | Primary cache & sessions | 1 |
| **redis-slave** | redis:8.0 | Backup cache (read-only) | 1 |
| **elasticsearch** | elasticsearch:8.11.0 | Search engine | 1 |
| **queue-worker** | Custom PHP | Background job processing | 2 |
| **prometheus** | prom/prometheus | Metrics collection | 1 |
| **grafana** | grafana/grafana | Monitoring dashboards | 1 |
| **phpmyadmin** | phpmyadmin:5.2.2 | Database management UI | 1 |

### Development Stack (docker-compose.old.yml)
| Container | Image | Purpose |
|-----------|-------|---------|
| **app** | Custom PHP | Single Laravel instance |
| **nginx** | nginx:alpine | Simple web server |
| **mysql** | mysql:9.0 | Database |
| **redis** | redis:8.0 | Cache |
| **phpmyadmin** | phpmyadmin:5.2.2 | Database UI |

## 🎓 Learning Resources

### Laravel Best Practices
- [Laravel Documentation](https://laravel.com/docs)
- [Laravel API Resources](https://laravel.com/docs/eloquent-resources)
- [Laravel Queue Jobs](https://laravel.com/docs/queues)

### Docker & DevOps
- [Docker Compose Best Practices](https://docs.docker.com/compose/production/)
- [Nginx Load Balancing](https://nginx.org/en/docs/http/load_balancing.html)
- [Redis Replication](https://redis.io/topics/replication)

## 🤝 Contributing

We welcome contributions! Please follow these steps:

1. **Fork the repository**
2. **Create a feature branch**: `git checkout -b feature/amazing-feature`
3. **Make your changes** and test thoroughly
4. **Run tests**: `docker-compose exec app1 php artisan test`
5. **Commit changes**: `git commit -m 'Add amazing feature'`
6. **Push to branch**: `git push origin feature/amazing-feature`
7. **Open a Pull Request**

### Development Workflow
```bash
# Start development environment
docker-compose -f docker-compose.old.yml up -d

# Install dependencies
docker-compose -f docker-compose.old.yml exec app composer install

# Run tests
docker-compose -f docker-compose.old.yml exec app php artisan test

# Code formatting
docker-compose -f docker-compose.old.yml exec app ./vendor/bin/pint

# Static analysis
docker-compose -f docker-compose.old.yml exec app ./vendor/bin/phpstan analyse
```

## 📝 License

This project is licensed under the **MIT License**. See the [LICENSE](LICENSE) file for details.

## 🆘 Support

### Getting Help
- **📧 Email**: support@achance.com
- **💬 Discord**: [AChance Community](https://discord.gg/achance)
- **🐛 Issues**: [GitHub Issues](https://github.com/muhammadghazi21/achance-api/issues)
- **📖 Wiki**: [Project Wiki](https://github.com/muhammadghazi21/achance-api/wiki)

### Reporting Issues
When reporting issues, please include:
1. **Environment details** (OS, Docker version)
2. **Steps to reproduce** the issue
3. **Expected vs actual behavior**
4. **Relevant logs** (`docker-compose logs`)
5. **Error messages** or screenshots

---

## 🚀 Quick Start Summary

```bash
# 1. Clone and enter directory
git clone https://github.com/muhammadghazi21/achance-api.git && cd achance-api

# 2. Start optimized infrastructure
docker-compose up -d

# 3. Install dependencies and setup
docker-compose exec app1 composer install --no-dev --optimize-autoloader
docker-compose exec app1 php artisan key:generate
docker-compose exec app1 php artisan storage:link
docker-compose exec app1 php artisan migrate:fresh --seed

# 4. Cache configurations
docker-compose exec app1 php artisan config:cache
docker-compose exec app1 php artisan route:cache

# 5. Test the API
curl http://localhost/api/health
```

**🎉 Your high-performance AChance API is now ready!**

Access your services:
- **API**: http://localhost
- **Database Admin**: http://localhost:8080 (root/root)
- **Monitoring**: http://localhost:3000 (admin/admin)

For detailed troubleshooting and advanced configuration, refer to the sections above.