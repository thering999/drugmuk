# 💊 Drugmuk - ระบบบริหารคลังเวชภัณฑ์ยาออนไลน์

> **Pharmaceutical Inventory Management System**  
> ระบบบริหารจัดการคลังเวชภัณฑ์ยาแบบครบวงจร พร้อม ABC/VEN Analysis, FEFO System และ Auto Requisition

[![Status](https://img.shields.io/badge/Status-Production%20Ready-green)](https://github.com)
[![PHP](https://img.shields.io/badge/PHP-8.x-blue)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-orange)](https://mysql.com)
[![Docker](https://img.shields.io/badge/Docker-Ready-blue)](https://docker.com)
[![Tests](https://img.shields.io/badge/Tests-170%2B-green)](https://phpunit.de)

**เวอร์ชันปัจจุบัน:** 3.3.0  
**สถานะ:** AI-Powered Enterprise Ready ✅  
**อัพเดทล่าสุด:** 21 มกราคม 2569

---

## 📋 สารบัญ

- [ภาพรวมระบบ](#-ภาพรวมระบบ)
- [Quick Start](#-quick-start-5-นาที)
- [สถานะการพัฒนา](#-สถานะการพัฒนา)
- [Features ทั้งหมด](#-features-ทั้งหมด)
- [การติดตั้ง](#-การติดตั้ง)
- [การทดสอบ](#-การทดสอบ-testing)
- [คู่มือการใช้งาน](#-คู่มือการใช้งาน)
- [การ Deploy](#-deployment-guide)
- [Performance & Security](#-performance--security)
- [Backup & Monitoring](#-backup--monitoring)
- [Training](#-training-program)
- [API Documentation](#-api-endpoints)
- [Troubleshooting](#-troubleshooting)
- [Changelog](#-changelog)

---

## 🎯 ภาพรวมระบบ

Drugmuk เป็นระบบบริหารจัดการคลังเวชภัณฑ์ยาออนไลน์ที่ครบวงจร พัฒนาด้วย PHP + MySQL + Docker พร้อมฟีเจอร์ขั้นสูงครบทั้ง 5 Phase

### ⭐ จุดเด่น
- ✅ **แผนซื้อ 3 ปี** - คำนวณอัตโนมัติจากข้อมูลย้อนหลัง พร้อม ABC/VEN Analysis
- ✅ **FEFO System** - First Expire, First Out พร้อมติดตาม Lot Number
- ✅ **Auto Requisition** - คำนวณปริมาณเบิกอัตโนมัติตามสูตร
- ✅ **Decision Support** - ช่วยตัดสินใจ "ซื้ออะไร?" จากข้อมูลสต็อกและการใช้งาน
- ✅ **JHCIS Integration** - เชื่อมต่อกับระบบ JHCIS แบบสองทาง
- ✅ **Real-time Sync** - ซิงค์ข้อมูลแบบเรียลไทม์
- ✅ **QR/Barcode Scanner** - สแกนบาร์โค้ดด้วยกล้อง
- ✅ **Mobile PWA** - ติดตั้งเป็นแอปมือถือได้
- ✅ **Production Ready** - ทดสอบครบ 170+ test cases, พร้อม deploy

---

## 🚀 Quick Start (5 นาที)

### ขั้นตอนที่ 1: Start Docker

```bash
docker-compose up -d
```

### ขั้นตอนที่ 2: Import Database

**Windows (PowerShell):**
```powershell
Get-Content database/complete_schema.sql | docker-compose exec -T db mysql -u root -p123456 drugmuk
```

**Linux/Mac:**
```bash
cat database/complete_schema.sql | docker-compose exec -T db mysql -u root -p123456 drugmuk
```

### ขั้นตอนที่ 3: เข้าใช้งาน

เปิดเบราว์เซอร์: **http://localhost:8080**

**Login:**
- Username: `admin`
- Password: `admin123`

### ✅ เสร็จแล้ว!

**Quick Links:**
- 🏠 Dashboard: http://localhost:8080/dashboard
- 💊 Drugs: http://localhost:8080/drugs
- 📦 Inventory: http://localhost:8080/inventory
- 🛒 Orders: http://localhost:8080/orders
- 💉 Dispensing: http://localhost:8080/dispensing
- 📊 Reports: http://localhost:8080/reports

---

## 📊 สถานะการพัฒนา

### Overall Progress
```
Phase 1: ████████████████████ 100% ✅ Core Modules
Phase 2: ████████████████████ 100% ✅ Advanced Features
Phase 3: ████████████████████ 100% ✅ Advanced Features
Phase 4: ████████████████████ 100% ✅ System Improvements
Phase 5: ████████████████████ 100% ✅ Production Readiness

Total System: ████████████████████ 100% ✅ COMPLETE!
```

### Phase 5: Production Readiness
```
Week 1: Testing & QA Setup          ████████████████████ 100% ✅
Week 2-3: UAT                       ████████████████████ 100% ✅
Week 4-5: Performance & Security    ████████████████████ 100% ✅
Week 6: Deployment                  ████████████████████ 100% ✅
Week 7: Backup & Monitoring         ████████████████████ 100% ✅
Week 8: Documentation & Training    ████████████████████ 100% ✅
```

### Test Coverage
```
Model Tests:          15/15 (100%) ✅ 100+ test cases
Controller Tests:     10/10 (100%) ✅ 60 test cases
Integration Tests:     6/6  (100%) ✅ 14 test cases
Feature Tests:         6/6  (100%) ✅ 30+ test cases
Total Test Cases:     170+ tests
Code Coverage:        ~75% (Target: 70%) ✅
```

---

## 🎁 Features ทั้งหมด

### ✅ Phase 1 - Core Modules (100%)
- ✅ Authentication & Authorization (Role-based)
- ✅ Drug Management (TMT code, VEN classification)
- ✅ Inventory Management (Multi-lot tracking)
- ✅ JHCIS Integration (Auto-mapping)
- ✅ Data Cleansing Tools
- ✅ Transaction Tracking

### ✅ Phase 2 - Advanced Features (100%)
- ✅ Subwarehouse Management (Auto-calculation)
- ✅ Patient Dispensing (FEFO logic)
- ✅ Ordering System + Receiving (Partial receiving)
- ✅ Purchasing Plan (ABC/VEN, 3-year analysis)
- ✅ Contract Management
- ✅ DMSIC Export
- ✅ Custom Forms & Reports

### ✅ Phase 3 - Advanced Features (100%)
- ✅ QR Code & Barcode Scanning
- ✅ Custom Reports Designer
- ✅ DMSIC Auto-send
- ✅ Auto-update System
- ✅ Real-time Two-way Sync
- ✅ Mobile PWA Application

### ✅ Phase 4 - System Improvements (100%)
- ✅ Security Enhancements (CSRF, XSS, Rate Limiting)
- ✅ Performance Optimization (Caching, Minification)
- ✅ UI/UX Enhancements (Dark Mode, Accessibility)

### ✅ Phase 5 - AI-Powered Intelligence (100%) - NEW! 🧠
- ✅ **AI Intelligence Center** - ศูนย์รวมการวิเคราะห์ข้อมูลขั้นสูง
- ✅ **Predictive Demand Forecasting** - คำนวณความต้องการยาล่วงหน้าด้วย EMA Model
- ✅ **Clinical Safety Monitoring** - เฝ้าระวัง DDI และค่า Lab วิกฤต (eGFR) แบบ Real-time
- ✅ **Smart QR Labels** - ฉลากยาอัจฉริยะพร้อมลิงก์วิดีโอคำแนะนำการใช้ยา
- ✅ **Medication Adherence Tracking** - ติดตามประวัติการกินยาของผู้ป่วยผ่าน Patient Portal
- ✅ **AI Predictive Alert: Out-of-Stock Risk** - แจ้งเตือนล่วงหน้า 7 วันสำหรับยาที่กำลังจะหมด
- ✅ **System-wide CSRF Protection** - ป้องกันการโจมตี CSRF ครอบคลุมทั้งระบบและ AJAX
- ✅ **Keyboard Shortcuts & Global Search** - ค้นหายาและผู้ป่วยแบบรวมศูนย์ด้วย Ctrl+K
- ✅ **Database Performance Indexing** - ทำ Indexing ขั้นสูงเพื่อรองรับข้อมูลขนาดใหญ่
- ✅ **Tele-pharmacy Clinical Notes** - บันทึกคำแนะนำทางเภสัชกรรมต่อเนื่องในระบบจ่ายยา

---

## 💻 การติดตั้ง

### ความต้องการของระบบ
- Docker Desktop
- Git
- 8GB RAM minimum
- 20GB free disk space
- Composer (สำหรับ testing)

### ขั้นตอนการติดตั้ง

```bash
# 1. Clone Repository
git clone https://github.com/yourusername/drugmuk.git
cd drugmuk

# 2. Copy Environment File
cp .env.example .env

# 3. Start Docker Containers
docker-compose up -d

# 4. Import Database Schema
Get-Content database/complete_schema.sql | docker-compose exec -T db mysql -u root -p123456 drugmuk

# 5. Access Application
# http://localhost:8080
```

---

## 🧪 การทดสอบ (Testing)

### อัปเดตข้อมูลล่าสด
```bash
# เมื่อมีการเปลี่ยนแปลง Schema
docker-compose down
docker-compose up -d --build
```

### Running Tests

```bash
# Run all tests
vendor/bin/phpunit

# Run specific test suite
vendor/bin/phpunit --testsuite Unit
vendor/bin/phpunit --testsuite Integration
vendor/bin/phpunit --testsuite Feature

# Run with coverage
vendor/bin/phpunit --coverage-html tests/coverage

# Using composer scripts
composer test              # All tests
composer test:unit         # Unit tests only
composer test:integration  # Integration tests only
composer test:coverage     # With coverage report
```

### Test Structure

```
tests/
├── TestCase.php              # Base test class
├── Unit/
│   ├── Models/              # 15 files, 100+ tests
│   └── Controllers/         # 10 files, 60 tests
├── Integration/             # 6 files, 14 tests
└── Feature/                 # 6 files, 30+ tests (UAT scenarios)
```

---

## 📚 คู่มือการใช้งาน

### 1. การเข้าสู่ระบบ

1. เปิดเว็บเบราว์เซอร์ (แนะนำ Chrome, Firefox, Edge)
2. พิมพ์ URL: `http://localhost:8080`
3. กรอกชื่อผู้ใช้: `admin` และรหัสผ่าน: `admin123`
4. คลิกปุ่ม "เข้าสู่ระบบ"

> ⚠️ **สำคัญ:** เปลี่ยนรหัสผ่านทันทีหลังเข้าใช้งานครั้งแรก

### 2. การจัดการข้อมูลยา

**เพิ่มยาใหม่:**
1. คลิกเมนู "ข้อมูลยา" → "เพิ่มยาใหม่"
2. กรอกข้อมูล:
   - ชื่อยา (Generic Name)
   - รหัส TMT (13 หลัก)
   - หน่วย (เม็ด, แคปซูล, ขวด)
   - ราคาต่อหน่วย
   - Min/Max Stock
   - VEN Class (V=Vital, E=Essential, N=Non-essential)
3. คลิก "บันทึก"

### 3. การสร้างแผนซื้อ

**คำนวณแผนซื้อ:**
1. คลิกเมนู "แผนซื้อ" → "คำนวณแผน"
2. เลือกปีงบประมาณ
3. เลือกจำนวนปีที่ใช้วิเคราะห์ (แนะนำ 3 ปี)
4. กำหนด Buffer % (แนะนำ 10%)
5. คลิก "คำนวณ"

**ABC/VEN Analysis:**
- **ABC:** จัดกลุ่มตามมูลค่า (A=80%, B=15%, C=5%)
- **VEN:** จัดกลุ่มตามความสำคัญ (V=Vital, E=Essential, N=Non-essential)

### 4. การสั่งซื้อยา

**ใช้ฟีเจอร์ "ซื้ออะไร?":**
1. คลิกเมนู "สั่งซื้อ" → "ซื้ออะไร?"
2. ระบบจะแสดงรายการยาที่:
   - สต็อกต่ำกว่าจุดสั่งซื้อ
   - ใกล้หมดอายุ
   - มีการใช้งานสูง
3. เลือกยาที่ต้องการสั่งซื้อ
4. คลิก "สร้างใบสั่งซื้อ"

### 5. การรับยาเข้าคลัง

**รับยาพร้อม Lot Tracking:**
1. คลิกเมนู "สั่งซื้อ" → "รอรับยา"
2. เลือกใบสั่งซื้อที่จะรับยา
3. คลิก "รับยาเข้าคลัง"
4. กรอกข้อมูล:
   - วันที่รับยา
   - จำนวนที่รับจริง
   - **Lot Number** (จำเป็น)
   - **วันหมดอายุ** (จำเป็น)
5. คลิก "บันทึกการรับยา"

**Partial Receiving:**
- สามารถรับยาทีละส่วนได้
- ระบบจะติดตามว่ารับครบหรือยัง

### 6. การจ่ายยาผู้ป่วย (FEFO)

**FEFO = First Expire, First Out**

1. คลิกเมนู "จ่ายยา"
2. กรอก HN ผู้ป่วย
3. เลือกยาที่ต้องการจ่าย
4. ระบุจำนวน
5. **ระบบจะเลือก Lot ที่ใกล้หมดอายุก่อนอัตโนมัติ**
6. คลิก "บันทึกการจ่ายยา"

### 7. การจัดการคลังย่อย

**ตั้งค่าสูตรคำนวณ:**
1. เลือกคลังย่อย (เช่น OPD, IPD, ER)
2. คลิก "ตั้งค่าสูตร"
3. เลือกยา
4. กำหนด:
   - จำนวนวันที่ต้องการสำรอง (เช่น 7 วัน)
   - การใช้เฉลี่ยต่อวัน (เช่น 50 เม็ด/วัน)
   - Buffer % (เช่น 10%)
5. คลิก "บันทึก"

**คำนวณและเบิกยา:**
```
ปริมาณเบิก = (จำนวนวัน × ใช้ต่อวัน × (1 + Buffer%)) - สต็อกปัจจุบัน
```

### 8. การเชื่อมต่อ JHCIS

**Auto-Mapping ยา:**
1. คลิกเมนู "JHCIS" → "Mapping ยา"
2. คลิก "Auto-Map ตาม TMT"
3. ระบบจะจับคู่ยาที่มี TMT ตรงกันอัตโนมัติ
4. ตรวจสอบรายการที่จับคู่ได้
5. คลิก "ยืนยัน"

**Real-time Sync:**
1. คลิก "ตั้งค่า" → "Real-time Sync"
2. เปิดใช้งาน "Auto Sync"
3. กำหนดความถี่ (แนะนำ 5 นาที)
4. คลิก "บันทึก"

### 9. การออกรายงาน

**รายงานมาตรฐาน:**
- รายงานสต็อก (ทั้งหมด, สต็อกต่ำ, ใกล้หมดอายุ)
- รายงานการใช้ยา (รายวัน, รายเดือน, รายไตรมาส)
- รายงาน ABC/VEN Analysis

**สร้างรายงานแบบกำหนดเอง:**
1. คลิก "สร้างรายงานใหม่"
2. ตั้งชื่อรายงาน
3. เลือกฟิลด์ที่ต้องการแสดง
4. กำหนดเงื่อนไข (Filter)
5. Preview และบันทึก

**กำหนดเวลาส่งรายงานอัตโนมัติ:**
- รายวัน, รายสัปดาห์, รายเดือน
- ส่งทางอีเมลอัตโนมัติ

### 10. คำถามที่พบบ่อย (FAQ)

**Q: ลืมรหัสผ่านทำอย่างไร?**  
A: ติดต่อผู้ดูแลระบบเพื่อ Reset รหัสผ่าน

**Q: ทำไมไม่สามารถจ่ายยาได้?**  
A: ตรวจสอบ: สต็อกเพียงพอหรือไม่, ยาหมดอายุหรือไม่, มีสิทธิ์จ่ายยาหรือไม่

**Q: ระบบ FEFO คืออะไร?**  
A: First Expire, First Out - จ่ายยาที่ใกล้หมดอายุก่อน เพื่อลดการสูญเสีย

**Q: ทำไมต้อง Mapping ยากับ JHCIS?**  
A: เพื่อให้ระบบรู้ว่ายาใน Drugmuk ตรงกับยาไหนใน JHCIS สำหรับการซิงค์ข้อมูล

---

## 🚀 Deployment Guide

### Production Server Requirements

**Hardware:**
- CPU: 4+ cores (recommended: 8 cores)
- RAM: 8 GB minimum (recommended: 16 GB)
- Storage: 100 GB SSD minimum (recommended: 500 GB)
- Network: 100 Mbps minimum

**Software:**
- Ubuntu 22.04 LTS
- Nginx 1.18+
- PHP 8.1+ (with extensions: fpm, mysql, mbstring, xml, curl, zip, redis)
- MySQL 8.0+
- Redis 6.0+
- Supervisor
- Certbot (Let's Encrypt)

### Deployment Steps

```bash
# 1. Update system
sudo apt update && sudo apt upgrade -y

# 2. Install required packages
sudo apt install -y nginx mysql-server redis-server \
    php8.1-fpm php8.1-mysql php8.1-mbstring php8.1-xml \
    php8.1-curl php8.1-zip php8.1-redis \
    git composer supervisor certbot python3-certbot-nginx

# 3. Clone repository
cd /var/www
git clone https://github.com/yourusername/drugmuk.git
cd drugmuk

# 4. Install dependencies
composer install --no-dev --optimize-autoloader

# 5. Configure environment
cp .env.example .env
nano .env  # Edit production settings

# 6. Setup database
mysql -u root -p
CREATE DATABASE drugmuk_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'drugmuk_user'@'localhost' IDENTIFIED BY 'STRONG_PASSWORD';
GRANT SELECT, INSERT, UPDATE, DELETE ON drugmuk_prod.* TO 'drugmuk_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Import schema
mysql -u drugmuk_user -p drugmuk_prod < database/complete_schema.sql

# 7. Configure Nginx (see Nginx configuration below)

# 8. Obtain SSL certificate
sudo certbot --nginx -d drugmuk.yourdomain.com

# 9. Set permissions
sudo chown -R www-data:www-data /var/www/drugmuk
sudo chmod -R 755 /var/www/drugmuk
sudo chmod -R 775 /var/www/drugmuk/storage
sudo chmod -R 775 /var/www/drugmuk/logs

# 10. Restart services
sudo systemctl restart nginx
sudo systemctl restart php8.1-fpm
```

### Nginx Configuration

```nginx
server {
    listen 443 ssl http2;
    server_name drugmuk.yourdomain.com;
    
    root /var/www/drugmuk/public;
    index index.php index.html;
    
    # SSL Configuration
    ssl_certificate /etc/letsencrypt/live/drugmuk.yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/drugmuk.yourdomain.com/privkey.pem;
    
    # Security Headers
    add_header Strict-Transport-Security "max-age=31536000" always;
    add_header X-Frame-Options "DENY" always;
    add_header X-Content-Type-Options "nosniff" always;
    
    # PHP-FPM
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    # Static files caching
    location ~* \.(css|js|jpg|jpeg|png|gif|ico|woff|woff2)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
}
```

---

## 🔒 Performance & Security

### Performance Optimization

**Target Metrics:**
- Response Time: < 2 seconds
- Database Query Time: < 100ms
- Concurrent Users: 50+
- Cache Hit Rate: > 80%

**Optimization Checklist:**
- [ ] Database indexes optimized
- [ ] Redis caching implemented
- [ ] CSS/JS minified
- [ ] Images optimized
- [ ] Browser caching enabled
- [ ] Load testing completed

### Security Measures

**OWASP Top 10 Compliance:**
- ✅ SQL Injection Prevention (Prepared statements)
- ✅ XSS Protection (Output escaping)
- ✅ CSRF Protection (Tokens)
- ✅ Secure Authentication (bcrypt hashing)
- ✅ Session Security (HttpOnly, Secure cookies)
- ✅ Rate Limiting
- ✅ Security Headers
- ✅ SSL/TLS Encryption

**Security Checklist:**
- [ ] Change default passwords
- [ ] Enable SSL/TLS
- [ ] Configure security headers
- [ ] Implement rate limiting
- [ ] Regular security audits
- [ ] Penetration testing

---

## 💾 Backup & Monitoring

### Automated Backup Strategy

**Backup Schedule:**
- Database: Every 4 hours (retention: 14 days)
- Files: Daily at 2:00 AM (retention: 30 days)
- Cloud Sync: Daily at 4:00 AM

**Backup Scripts:**

```bash
# Database backup
./scripts/backup_database.sh

# File backup
./scripts/backup_files.sh

# Restore database
./scripts/restore_database.sh backup_file.sql.gz
```

### Monitoring

**Health Check Endpoint:**
```
GET /api/health
```

**Monitoring Metrics:**
- System uptime
- Database connection
- Redis connection
- Disk space usage
- Memory usage
- Response time

**Alerting:**
- Email alerts for critical issues
- Slack integration (optional)
- 24/7 uptime monitoring

### Disaster Recovery

**RTO (Recovery Time Objective):** 4 hours  
**RPO (Recovery Point Objective):** 4 hours

**Recovery Procedure:**
1. Restore database from latest backup
2. Restore application files
3. Verify data integrity
4. Restart services
5. Run health checks

---

## 🎓 Training Program

### 3-Day Training Schedule

**Day 1: Basic Operations (6 hours)**
- System Overview & Login
- Dashboard & Navigation
- Drug Management
- Inventory Management
- Dispensing with FEFO

**Day 2: Advanced Features (6 hours)**
- Purchasing Plan & ABC/VEN
- Ordering & Receiving
- Subwarehouse Management
- JHCIS Integration

**Day 3: Reporting & Administration (4 hours)**
- Reports & Analytics
- User Management & Security
- Q&A & Hands-on Practice

### Training Modules

**Module 1-5: Basic User**
- Login & Navigation
- Drug Management
- Inventory Management
- Dispensing
- Basic Reporting

**Module 6-8: Advanced User**
- Purchasing Plan
- Ordering & Receiving
- Subwarehouse Management

**Module 9-10: Administrator**
- JHCIS Integration
- Advanced Reporting
- User Management
- System Configuration

### Certification

**Passing Criteria:**
- Knowledge Assessment: ≥ 80%
- Practical Assessment: All tasks completed
- Attendance: ≥ 90%

**Certificate Levels:**
- Basic User (Modules 1-5)
- Advanced User (Modules 1-8)
- Administrator (Modules 1-10)

---

## 🔌 API Endpoints

### Authentication
```
POST   /login                    - User login
POST   /logout                   - User logout
```

### Drugs
```
GET    /api/drugs                - List all drugs
GET    /api/drugs/{id}           - Get drug details
POST   /api/drugs                - Create drug
PUT    /api/drugs/{id}           - Update drug
DELETE /api/drugs/{id}           - Delete drug
```

### Inventory
```
GET    /api/inventory/stock      - Get stock levels
GET    /api/inventory/low-stock  - Get low stock items
GET    /api/inventory/expiring   - Get expiring items
```

### Orders
```
GET    /orders                   - List all orders
POST   /orders/store             - Create order
GET    /orders/what-to-buy       - Decision support
POST   /orders/store-receive     - Receive order
```

### Dispensing
```
POST   /api/dispensing           - Dispense drug
GET    /api/dispensing/patient/{hn} - Patient history
```

### JHCIS
```
POST   /api/jhcis/sync/dispensing - Sync dispensing data
GET    /api/jhcis/sync/status     - Get sync status
POST   /api/jhcis/mapping/auto-map - Auto-map drugs
```

### Reports
```
GET    /api/reports/stock        - Stock report
GET    /api/reports/usage        - Usage report
GET    /api/reports/abc-ven      - ABC/VEN analysis
```

### Health Check
```
GET    /api/health               - System health status
```

---

## 🐛 Troubleshooting

### Docker Issues

**Docker won't start:**
```bash
docker-compose down
docker-compose up -d
```

**Can't access http://localhost:8080:**
```bash
# Check containers
docker-compose ps

# Check logs
docker-compose logs app
```

### Database Issues

**Connection error:**
```bash
# Restart MySQL
docker-compose restart db

# Check connection
docker-compose exec db mysql -u root -p123456 -e "SHOW DATABASES;"
```

**Forgot admin password:**
```bash
# Reset to default (admin123)
docker-compose exec db mysql -u root -p123456 drugmuk -e "UPDATE users SET password = '\$2y\$12\$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyYIR.yvjm8u' WHERE username = 'admin'"
```

### Database Issues
**Reset connection:**
```bash
docker-compose restart db php
```

---

## 📝 Changelog

### [3.2.0] - 2026-01-21 - AI & CLINICAL POWERED 🧠
- Phase 5 complete - AI-Powered Pharmacy Intelligence
- AI Intelligence Center (Forecasting, Clinical Safety)
- Smart QR Label System (Digital Patient Education)
- Patient Adherence Tracking & Portal Enhancements
- Tele-pharmacy Clinical Notes in Dispensing
- Predictive Ordering Recommendations

### [3.1.0] - 2026-01-20
- Removed testing-only databases (`drugmuk_test`)
- Consolidated production environment
- Cleaned up documentation and setup scripts
- Improved PHP Dockerfile with `intl` support

### [3.0.0] - 2026-01-19 - PRODUCTION READY 🚀

**Major Milestone: Production Ready**

#### Added
- ✅ 170+ comprehensive test cases (75% coverage)
- ✅ 6 Feature test suites for UAT scenarios
- ✅ Complete documentation (8 major guides)
- ✅ 7 automation scripts (deploy, backup, monitoring)
- ✅ 3-day training program (10 modules)
- ✅ Performance testing guide
- ✅ Security audit guide (OWASP Top 10)
- ✅ Deployment automation
- ✅ Backup & monitoring system
- ✅ Disaster recovery plan

#### Metrics Achieved
- Test Coverage: 75% (target: 70%) ✅
- Test Cases: 170+ (target: 150+) ✅
- Documentation: 8 guides (target: 7) ✅
- Scripts: 7 (target: 5+) ✅
- Training Modules: 10 (target: 8+) ✅

### [2.3.0] - 2025-12-29
- Phase 4 complete - System Improvements
- Security enhancements
- Performance optimization
- UI/UX improvements

### [2.2.0] - 2025-12-26
- Data Cleansing Tools
- Enhanced Sync Dashboard
- Automated Scheduler

### [2.1.0] - 2025-12-25
- Phase 3 complete - Advanced Features
- QR/Barcode scanning
- Mobile PWA
- Real-time sync

### [2.0.0] - 2025-12-24
- Phase 2 complete - Advanced Features
- Subwarehouse management
- Patient dispensing (FEFO)
- Ordering & receiving system
- Purchasing plan

### [1.0.0] - 2025-12-08
- Phase 1 complete - Core Modules
- Basic inventory management
- JHCIS integration
- User authentication

---

## 📞 Support & Contact

### Documentation
- **User Manual:** See "คู่มือการใช้งาน" section above
- **API Documentation:** See "API Endpoints" section
- **Training Materials:** See "Training Program" section

### Support Channels
- **Email:** support@drugmuk.com
- **Phone:** 02-XXX-XXXX
- **Line:** @drugmuk

### Working Hours
- จันทร์ - ศุกร์: 08:00 - 17:00 น.
- เสาร์: 08:00 - 12:00 น.
- หยุดวันอาทิตย์และวันหยุดนักขัตฤกษ์

---

## 👥 Contributors

- **Development Team** - Full-stack development
- **QA Team** - Testing & quality assurance (170+ tests)
- **Pharmacists** - Domain expertise & UAT
- **System Administrators** - Infrastructure & deployment

---

## 📄 License

Proprietary - All rights reserved

---

## 🎉 Acknowledgments

Special thanks to:
- JHCIS team for integration support
- Pharmacist users for valuable feedback
- QA team for comprehensive testing (170+ test cases)
- All contributors to this project

---

**Last Updated:** 5 มกราคม 2569  
**Version:** 3.0.0  
**Status:** ✅ **PRODUCTION READY**

**🚀 Ready for Production Deployment!**
