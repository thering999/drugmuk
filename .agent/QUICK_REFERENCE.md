# 🚀 Drugmuk Quick Reference Guide

**เวอร์ชัน:** 3.1.0  
**อัพเดท:** 21 มกราคม 2569

---

## 📋 Table of Contents

1. [Running Tests](#running-tests)
2. [Performance Optimization](#performance-optimization)
3. [Security Features](#security-features)
4. [Caching](#caching)
5. [Database Management](#database-management)
6. [Deployment](#deployment)
7. [Troubleshooting](#troubleshooting)

---

## 🧪 Running Tests

### Setup Test Database
```bash
# Create test database
docker-compose exec db mysql -u root -p123456 -e "CREATE DATABASE IF NOT EXISTS drugmuk_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Import schema
docker-compose exec -T db sh -c "mysqldump -u root -p123456 --no-data drugmuk 2>/dev/null | mysql -u root -p123456 drugmuk_test 2>/dev/null"
```

### Run All Tests
```bash
# Run all tests
docker-compose exec app php vendor/phpunit/phpunit/phpunit

# Run with testdox (readable output)
docker-compose exec app php vendor/phpunit/phpunit/phpunit --testdox

# Run specific test suite
docker-compose exec app php vendor/phpunit/phpunit/phpunit --testsuite Unit
docker-compose exec app php vendor/phpunit/phpunit/phpunit --testsuite Integration
```

### Run Specific Tests
```bash
# Run specific test file
docker-compose exec app php vendor/phpunit/phpunit/phpunit tests/Unit/Models/DrugTest.php

# Run specific test method
docker-compose exec app php vendor/phpunit/phpunit/phpunit tests/Unit/Models/DrugTest.php::testCreateDrugSuccess
```

### Generate Coverage Report
```bash
# Generate HTML coverage report
docker-compose exec app php vendor/phpunit/phpunit/phpunit --coverage-html tests/coverage

# View coverage report
# Open tests/coverage/index.html in browser
```

---

## ⚡ Performance Optimization

### Apply Database Indexes
```bash
# Apply performance indexes
Get-Content database/performance_indexes.sql | docker-compose exec -T db mysql -u root -p123456 drugmuk

# Verify indexes
docker-compose exec db mysql -u root -p123456 -e "SHOW INDEX FROM drugs;" drugmuk
```

### Check Query Performance
```sql
-- Enable slow query log
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 0.1;

-- Check slow queries
SELECT * FROM mysql.slow_log ORDER BY start_time DESC LIMIT 10;
```

### Optimize Tables
```sql
-- Analyze tables
ANALYZE TABLE drugs, inventory, dispensing, orders;

-- Optimize tables
OPTIMIZE TABLE drugs, inventory, dispensing, orders;
```

---

## 🔒 Security Features

### Enable Security Headers
```php
// Add to public/index.php
use App\Middleware\SecurityHeadersMiddleware;

SecurityHeadersMiddleware::apply();
```

### Enable Rate Limiting
```php
// Example: Login rate limiting
use App\Middleware\RateLimitMiddleware;

$rateLimit = new RateLimitMiddleware();
$clientIp = RateLimitMiddleware::getClientIp();

if (!$rateLimit->attempt($clientIp, 5, 1)) {
    // Too many attempts
    http_response_code(429);
    die('Too many login attempts. Please try again later.');
}
```

### Change Default Passwords
```bash
# Generate new password hash
docker-compose exec app php -r "echo password_hash('NewSecurePassword123!', PASSWORD_BCRYPT);"

# Update admin password
docker-compose exec db mysql -u root -p123456 drugmuk -e "UPDATE users SET password = 'NEW_HASH_HERE' WHERE username = 'admin';"
```

---

## 💾 Caching

### Use Cache Service
```php
use App\Services\CacheService;

$cache = new CacheService();

// Set cache
$cache->set('key', $value, 3600); // TTL: 1 hour

// Get cache
$value = $cache->get('key');

// Remember pattern
$drugs = $cache->remember('drugs:all', 3600, function() {
    return Drug::getAll();
});

// Clear cache
$cache->delete('key');
$cache->clear(); // Clear all
$cache->clearByPattern('drugs:*'); // Clear by pattern
```

### Cache Statistics
```php
$stats = $cache->getStats();
/*
Array (
    'total_files' => 50,
    'valid_count' => 45,
    'expired_count' => 5,
    'total_size' => 1048576,
    'total_size_mb' => 1.00
)
*/
```

### Cleanup Expired Cache
```php
$deleted = $cache->cleanup();
echo "Deleted $deleted expired cache files";
```

---

## 🗄️ Database Management

### Backup Database
```bash
# Full backup
docker-compose exec db mysqldump -u root -p123456 drugmuk > backup_$(date +%Y%m%d_%H%M%S).sql

# Backup with compression
docker-compose exec db mysqldump -u root -p123456 drugmuk | gzip > backup_$(date +%Y%m%d_%H%M%S).sql.gz
```

### Restore Database
```bash
# Restore from backup
Get-Content backup_20260121_120000.sql | docker-compose exec -T db mysql -u root -p123456 drugmuk

# Restore from compressed backup
gunzip < backup_20260121_120000.sql.gz | docker-compose exec -T db mysql -u root -p123456 drugmuk
```

### Database Maintenance
```sql
-- Check table status
SHOW TABLE STATUS;

-- Repair table
REPAIR TABLE drugs;

-- Check table integrity
CHECK TABLE drugs;

-- Analyze table
ANALYZE TABLE drugs;
```

---

## 🚀 Deployment

### Pre-Deployment Checklist
```bash
# 1. Run all tests
docker-compose exec app php vendor/phpunit/phpunit/phpunit

# 2. Check for errors
docker-compose logs app | grep -i error

# 3. Backup database
docker-compose exec db mysqldump -u root -p123456 drugmuk > backup_pre_deploy.sql

# 4. Update environment
cp .env .env.production
# Edit .env.production with production values

# 5. Clear cache
rm -rf storage/cache/*
```

### Deploy to Production
```bash
# 1. Pull latest code
git pull origin main

# 2. Install dependencies
composer install --no-dev --optimize-autoloader

# 3. Apply database migrations
# (if any)

# 4. Apply performance indexes
Get-Content database/performance_indexes.sql | mysql -u user -p database

# 5. Clear cache
rm -rf storage/cache/*

# 6. Restart services
systemctl restart php-fpm
systemctl restart nginx
```

### Post-Deployment
```bash
# 1. Check application status
curl -I http://localhost:8080

# 2. Monitor logs
tail -f logs/app.log

# 3. Check database connection
docker-compose exec db mysql -u root -p123456 -e "SELECT 1;"

# 4. Run smoke tests
docker-compose exec app php vendor/phpunit/phpunit/phpunit --testsuite Integration
```

---

## 🔧 Troubleshooting

### Tests Not Running
```bash
# Check test database exists
docker-compose exec db mysql -u root -p123456 -e "SHOW DATABASES;" | grep drugmuk_test

# Recreate test database
docker-compose exec db mysql -u root -p123456 -e "DROP DATABASE IF EXISTS drugmuk_test; CREATE DATABASE drugmuk_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Import schema
docker-compose exec -T db sh -c "mysqldump -u root -p123456 --no-data drugmuk 2>/dev/null | mysql -u root -p123456 drugmuk_test 2>/dev/null"
```

### Database Connection Issues
```bash
# Check database is running
docker-compose ps db

# Check database logs
docker-compose logs db

# Test connection
docker-compose exec db mysql -u root -p123456 -e "SELECT 1;"
```

### Performance Issues
```bash
# Check slow queries
docker-compose exec db mysql -u root -p123456 -e "SELECT * FROM mysql.slow_log ORDER BY start_time DESC LIMIT 10;"

# Check table sizes
docker-compose exec db mysql -u root -p123456 -e "SELECT table_name, ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)' FROM information_schema.TABLES WHERE table_schema = 'drugmuk' ORDER BY (data_length + index_length) DESC;"

# Optimize tables
docker-compose exec db mysql -u root -p123456 drugmuk -e "OPTIMIZE TABLE drugs, inventory, dispensing, orders;"
```

### Cache Issues
```bash
# Clear all cache
rm -rf storage/cache/*

# Check cache directory permissions
ls -la storage/cache/

# Fix permissions
chmod -R 755 storage/cache/
```

### Docker Issues
```bash
# Restart containers
docker-compose restart

# Rebuild containers
docker-compose down
docker-compose up -d --build

# Check container logs
docker-compose logs app
docker-compose logs db
docker-compose logs web
```

---

## 📊 Monitoring

### Check Application Health
```bash
# Check HTTP status
curl -I http://localhost:8080

# Check database connection
docker-compose exec app php -r "try { new PDO('mysql:host=db;dbname=drugmuk', 'root', '123456'); echo 'OK'; } catch (Exception \$e) { echo 'FAIL: ' . \$e->getMessage(); }"

# Check disk space
df -h

# Check memory usage
free -h
```

### Performance Metrics
```bash
# Check response time
curl -w "@curl-format.txt" -o /dev/null -s http://localhost:8080

# Monitor database queries
docker-compose exec db mysql -u root -p123456 -e "SHOW PROCESSLIST;"

# Check cache statistics
docker-compose exec app php -r "require 'vendor/autoload.php'; \$cache = new App\\Services\\CacheService(); print_r(\$cache->getStats());"
```

---

## 🎯 Quick Commands

### Development
```bash
# Start development server
docker-compose up -d

# View logs
docker-compose logs -f app

# Run tests
docker-compose exec app php vendor/phpunit/phpunit/phpunit --testdox

# Clear cache
rm -rf storage/cache/*
```

### Production
```bash
# Deploy
git pull && composer install --no-dev && systemctl restart php-fpm

# Backup
docker-compose exec db mysqldump -u root -p123456 drugmuk > backup.sql

# Monitor
tail -f logs/app.log
```

---

**สำหรับข้อมูลเพิ่มเติม:** ดูที่ `README.md` และ `.agent/development_summary_week1-2.md`
