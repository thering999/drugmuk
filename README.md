# 🏥 Drugmuk - ระบบบริหารคลังเวชภัณฑ์อัจฉริยะ

[![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-blue)](https://www.php.net/)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
[![Status](https://img.shields.io/badge/Status-Active%20Development-orange)](https://github.com/thering999/drugmuk)
[![Version](https://img.shields.io/badge/Version-1.5.0-brightgreen)](https://github.com/thering999/drugmuk)

> **ระบบบริหารจัดการคลังยาและเวชภัณฑ์แบบครบวงจร** พัฒนาด้วย PHP 8.0+ พร้อม **AI Assistant** ที่ช่วยให้การทำงานรวดเร็วและมีประสิทธิภาพยิ่งขึ้น รองรับการเชื่อมต่อกับระบบ JHCIS และมีระบบ Telepharmacy สำหรับให้คำปรึกษาทางไกล

---

## 📑 สารบัญ

- [จุดเด่นของระบบ](#-จุดเด่นของระบบ)
- [ภาพรวมระบบ](#-ภาพรวมระบบ)
- [ความต้องการของระบบ](#-ความต้องการของระบบ)
- [การติดตั้ง](#-การติดตั้ง)
- [AI Assistant - คู่มือการใช้งาน](#-ai-assistant---คู่มือการใช้งาน)
- [โครงสร้างโปรเจค](#️-โครงสร้างโปรเจค)
- [ฟีเจอร์หลัก](#-ฟีเจอร์หลัก)
- [Database Schema](#-database-schema)
- [API Documentation](#-api-documentation)
- [Security Features](#-security-features)
- [การทดสอบ](#-การทดสอบ)
- [Deployment](#-deployment)
- [Roadmap](#️-roadmap)
- [Contributing](#-contributing)
- [License](#-license)
- [Support](#-support)

---

## 🌟 จุดเด่นของระบบ

### 🤖 AI-Powered Features

#### 1. **Voice Command (Hands-Free)** 🎙️
- สั่งงานด้วยเสียงแบบไม่ต้องกดปุ่มส่ง
- ระบบส่งข้อความอัตโนมัติเมื่อหยุดพูด (Voice Activity Detection)
- รองรับภาษาไทยและภาษาอังกฤษ
- ใช้ Web Speech API สำหรับการรู้จำเสียง

#### 2. **Smart Drug Label (ฉลากยาพูดได้)** 💊
- สร้างฉลากยาแบบเข้าใจง่าย พร้อมไอคอนเวลากิน
- ดึงข้อมูลจากประวัติการจ่ายจริง
- แสดงผลแบบ Visual (☀️ เช้า, 🍛 กลางวัน, 🌙 ก่อนนอน)
- รองรับการพิมพ์ฉลากยา QR Code

#### 3. **Visual Analytics (กราฟแนวโน้ม)** 📊
- กราฟยอดขาย 7 วันย้อนหลัง
- แสดงผลแบบ Interactive Chart (Chart.js)
- วิเคราะห์แนวโน้มรายได้และกำไร
- Dashboard แบบ Real-time

#### 4. **Refill Reminders (แจ้งเตือนเติมยา)** ⏰
- ระบุคนไข้ที่ครบกำหนดเติมยา (25-35 วันที่แล้ว)
- Proactive Patient Care
- พร้อมปุ่มติดตามคนไข้
- ส่งการแจ้งเตือนผ่าน LINE Notify

#### 5. **Cross-Branch Stock Check (เช็คสต็อคข้ามสาขา)** 🏬
- ตรวจสอบยาคงเหลือในทุกสาขา
- แสดงผลแบบ Color-coded (เขียว/เหลือง/แดง)
- ขอโอนของระหว่างสาขาได้ทันที
- รองรับการจัดการคลังหลายสาขา

#### 6. **Instant Purchase Order (สั่งซื้อด่วน)** 🛒
- สั่งซื้อยาผ่านแชทด้วยคำสั่งเดียว
- คำนวณราคารวมอัตโนมัติ
- สร้าง Draft PO ทันที
- เชื่อมต่อกับระบบ Supplier

#### 7. **AI-Driven Intelligence** 🧠
- **ADR Surveillance** - ตรวจจับอาการไม่พึงประสงค์จากยา
- **Drug Interaction Detection** - ตรวจสอบปฏิกิริยาระหว่างยา
- **Demand Forecasting** - พยากรณ์ความต้องการยา (Prophet/LSTM)
- **Clinical Decision Support** - ช่วยตัดสินใจทางคลินิก
- **Patient Safety Reporting** - รายงานความปลอดภัยของผู้ป่วย

---

## 🎯 ภาพรวมระบบ

Drugmuk เป็นระบบบริหารจัดการคลังยาและเวชภัณฑ์ที่ออกแบบมาสำหรับโรงพยาบาลส่งเสริมสุขภาพตำบล (รพ.สต.) และโรงพยาบาลชุมชน โดยมีจุดเด่นดังนี้:

### ✨ Core Capabilities

1. **การจัดการสต็อกอัจฉริยะ**
   - ระบบ FEFO (First Expire, First Out)
   - Multi-warehouse และ Sub-warehouse support
   - Real-time stock tracking
   - Expiry date monitoring และ alert
   - Barcode scanning integration

2. **JHCIS Integration**
   - ดึงข้อมูลผู้ป่วยจาก JHCIS แบบ Real-time
   - ซิงค์ข้อมูลยาอัตโนมัติ
   - Auto-mapping ข้อมูลระหว่างระบบ
   - รองรับหลาย JHCIS Database
   - Reconciliation และ Data Cleansing

3. **Telepharmacy (เภสัชกรรมทางไกล)**
   - Video consultation ผ่าน Jitsi Meet
   - Clinical notes พร้อม AI analysis
   - ADR และ Drug interaction detection
   - Patient engagement portal
   - ประวัติการให้คำปรึกษา

4. **Advanced Analytics**
   - Dashboard แบบ Real-time
   - รายงานยาขายดี / Dead Stock
   - วิเคราะห์แนวโน้มการใช้ยา
   - Cost analysis และ Profit margin
   - Inventory turnover ratio

5. **Patient Engagement**
   - Patient portal สำหรับตรวจสอบประวัติ
   - AI-driven health advice
   - Medication reminders
   - Lab results และ Vaccination history
   - Chronic disease management

---

## 💻 ความต้องการของระบบ

### Server Requirements

- **PHP**: 8.0 หรือสูงกว่า
- **MySQL**: 5.7 หรือสูงกว่า (แนะนำ 8.0+)
- **Web Server**: Apache 2.4+ หรือ Nginx 1.18+
- **Composer**: 2.0+
- **Redis** (Optional): สำหรับ Caching และ Queue
- **Node.js** (Optional): สำหรับ Build assets

### PHP Extensions Required

```
- pdo_mysql
- mbstring
- json
- openssl
- curl
- gd (สำหรับ QR Code)
- zip
- xml
```

### Recommended Server Specs

- **CPU**: 2+ cores
- **RAM**: 4GB+ (แนะนำ 8GB)
- **Storage**: 20GB+ SSD
- **Network**: 10Mbps+

---

## 🚀 การติดตั้ง

### วิธีที่ 1: Manual Installation

#### 1. Clone Repository

```bash
git clone https://github.com/thering999/drugmuk.git
cd drugmuk
```

#### 2. ติดตั้ง Dependencies

```bash
composer install
```

#### 3. ตั้งค่า Environment

```bash
cp .env.example .env
```

แก้ไขไฟล์ `.env`:

```env
# Database Configuration
DB_HOST=localhost
DB_PORT=3306
DB_NAME=drugmuk
DB_USER=root
DB_PASSWORD=your_password

# JHCIS Integration (Optional)
JHCIS_HOST=192.168.1.100
JHCIS_PORT=3306
JHCIS_DB=jhcisdb
JHCIS_USER=jhcis_user
JHCIS_PASS=jhcis_password

# LINE Notify (Optional)
LINE_NOTIFY_TOKEN=your_line_token

# Hospital Configuration
HOSP_CODE=00000
HOSP_NAME=โรงพยาบาลส่งเสริมสุขภาพตำบล...

# Security
APP_KEY=base64:randomkeyhere
APP_DEBUG=false
SESSION_LIFETIME=120
```

#### 4. สร้าง Database

```bash
# เข้า MySQL
mysql -u root -p

# สร้าง Database
CREATE DATABASE drugmuk CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
exit;

# Import Schema
mysql -u root -p drugmuk < database/drugmuk.sql
```

#### 5. ตั้งค่า Permissions

```bash
# Linux/Mac
chmod -R 755 storage/
chmod -R 755 logs/
chmod -R 755 exports/

# Windows (PowerShell as Admin)
icacls storage /grant Users:F /T
icacls logs /grant Users:F /T
icacls exports /grant Users:F /T
```

#### 6. ตั้งค่า Web Server

**Apache (.htaccess)**

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php [QSA,L]
</IfModule>
```

**Nginx**

```nginx
server {
    listen 80;
    server_name drugmuk.local;
    root /path/to/drugmuk/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

#### 7. เข้าใช้งานระบบ

```
http://localhost/drugmuk
```

**Default Login:**
- Username: `admin`
- Password: `admin123`

> ⚠️ **สำคัญ**: เปลี่ยนรหัสผ่าน admin ทันทีหลังติดตั้ง!

---

### วิธีที่ 2: Docker Installation

```bash
# Clone repository
git clone https://github.com/thering999/drugmuk.git
cd drugmuk

# สร้าง .env file
cp .env.example .env

# Start containers
docker-compose up -d

# Import database
docker exec -i drugmuk_db mysql -u drugmuk -pdrugmuk_pass drugmuk < database/drugmuk.sql

# เข้าใช้งาน
http://localhost:8080
```

---

## 💬 AI Assistant - คู่มือการใช้งาน

### 🎤 การใช้งาน Voice Commands

1. กดปุ่มไมค์ 🎙️ ในหน้า AI Chat
2. พูดคำสั่ง (เช่น "เช็คสต็อค Amoxy")
3. ระบบจะส่งข้อความอัตโนมัติเมื่อหยุดพูด
4. รอผลลัพธ์จาก AI

### 📋 คำสั่งที่รองรับ

#### 1. การเช็คสต็อค
```
"Amoxy เหลือเท่าไหร่"
"มี Paracetamol ไหม"
"เช็คสต็อค Para"
"ยาคงเหลือ Diclofenac"
```

#### 2. ยาใกล้หมดอายุ
```
"ยาใกล้หมดอายุ"
"เช็คยา exp"
"ยาหมดอายุเดือนนี้"
"expiring drugs"
```

#### 3. ยาขายดี / Dead Stock
```
"ยาขายดี"
"top selling drugs"
"dead stock"
"ยาตาย"
"ยาไม่ขยับ"
```

#### 4. การใช้ยา / ประวัติการจ่าย
```
"ใครใช้ Amoxy"
"จ่ายให้ใครบ้าง Para"
"ประวัติการใช้ Diclofenac"
"patient usage Amoxy"
```

#### 5. ยอดขายและรายได้
```
"ยอดขายวันนี้"
"รายได้เดือนนี้"
"กำไรสัปดาห์นี้"
"sales today"
"revenue this month"
```

#### 6. ฉลากยา (Smart Drug Label)
```
"ฉลากยา Amoxy"
"กินยังไง Para"
"วิธีกิน Diclofenac"
"drug label Amoxy"
"how to take Para"
```

#### 7. กราฟแนวโน้ม (Visual Analytics)
```
"ขอดูกราฟยอดขาย"
"แนวโน้มรายได้"
"chart ยอดขาย"
"sales chart"
"revenue trend"
```

#### 8. แจ้งเตือนเติมยา (Refill Reminders)
```
"เช็คคนไข้เติมยา"
"ใครยาหมด"
"refill reminder"
"patients due for refill"
```

#### 9. เช็คสต็อคข้ามสาขา (Cross-Branch)
```
"สาขาไหนมี Amoxy"
"เช็คสาขา Para"
"มีที่ไหนบ้าง Diclofenac"
"cross branch stock Amoxy"
```

#### 10. สั่งซื้อด่วน (Instant PO)
```
"สั่งซื้อ Amoxy 1000"
"Order Para 500 tablets"
"สั่งซื้อ Diclofenac 200"
"create PO for Amoxy 1000"
```

#### 11. Clinical Intelligence
```
"เช็ค ADR ของคนไข้ HN12345"
"drug interaction Amoxy + Warfarin"
"ตรวจสอบปฏิกิริยาระหว่างยา"
"safety report HN12345"
```

---

## 🗂️ โครงสร้างโปรเจค

```
drugmuk/
├── public/                     # Web root (Document Root)
│   ├── index.php              # Application entry point
│   ├── css/                   # Stylesheets
│   │   ├── style.css
│   │   ├── dashboard.css
│   │   └── ai-chat.css
│   ├── js/                    # JavaScript files
│   │   ├── app.js
│   │   ├── ai-chat.js
│   │   └── voice-command.js
│   ├── assets/                # Static assets
│   │   ├── images/
│   │   ├── fonts/
│   │   └── icons/
│   └── uploads/               # User uploads
│
├── src/                       # Application source code
│   ├── Controllers/           # MVC Controllers (47 files)
│   │   ├── AiController.php
│   │   ├── DashboardController.php
│   │   ├── InventoryController.php
│   │   ├── DispensingController.php
│   │   ├── OrderController.php
│   │   ├── JHCISController.php
│   │   ├── TelepharmacyController.php
│   │   ├── IntelligenceController.php
│   │   ├── EngagementController.php
│   │   ├── AnalyticsController.php
│   │   └── ...
│   │
│   ├── Models/                # Data models (18 files)
│   │   ├── Drug.php
│   │   ├── Inventory.php
│   │   ├── Dispensing.php
│   │   ├── Order.php
│   │   ├── Patient.php
│   │   ├── User.php
│   │   └── ...
│   │
│   ├── Services/              # Business logic (34 files)
│   │   ├── AiService.php              # AI Assistant core
│   │   ├── IntelligenceService.php    # Clinical intelligence
│   │   ├── PatientService.php         # Patient management
│   │   ├── LineNotifyService.php      # LINE notifications
│   │   ├── DemandForecastingService.php
│   │   ├── DrugInteractionService.php
│   │   ├── SafetyService.php
│   │   ├── TelehealthService.php
│   │   ├── EngagementService.php
│   │   └── JHCIS/                     # JHCIS integration
│   │       ├── JhcisService.php
│   │       ├── AutoMappingService.php
│   │       ├── ReconciliationService.php
│   │       └── ...
│   │
│   ├── Views/                 # View templates (103 files)
│   │   ├── layouts/
│   │   │   ├── header.php
│   │   │   ├── header_responsive.php
│   │   │   ├── footer.php
│   │   │   └── sidebar.php
│   │   ├── dashboard/
│   │   ├── inventory/
│   │   ├── dispensing/
│   │   ├── orders/
│   │   ├── intelligence/
│   │   ├── engagement/
│   │   ├── telepharmacy/
│   │   └── ...
│   │
│   ├── Core/                  # Core framework (14 files)
│   │   ├── Database.php       # Database connection
│   │   ├── Router.php         # Routing system
│   │   ├── Request.php        # HTTP request
│   │   ├── Response.php       # HTTP response
│   │   ├── Session.php        # Session management
│   │   ├── CSRF.php           # CSRF protection
│   │   └── ...
│   │
│   ├── Middleware/            # HTTP middleware (4 files)
│   │   ├── AuthMiddleware.php
│   │   ├── CsrfMiddleware.php
│   │   ├── RateLimitMiddleware.php
│   │   └── ...
│   │
│   ├── Exceptions/            # Custom exceptions (7 files)
│   │   ├── DatabaseException.php
│   │   ├── ValidationException.php
│   │   └── ...
│   │
│   └── helpers.php            # Helper functions
│
├── database/                  # Database files
│   ├── drugmuk.sql           # Full database schema
│   └── drugmuk_full_backup_20260130.sql
│
├── config/                    # Configuration files
│   ├── database.php
│   ├── app.php
│   └── jhcis.php
│
├── routes/                    # Route definitions
│   ├── web.php
│   └── api.php
│
├── scripts/                   # Utility scripts
│   ├── backup.php
│   ├── sync_jhcis.php
│   └── cleanup.php
│
├── storage/                   # Storage directory
│   ├── cache/
│   ├── sessions/
│   └── temp/
│
├── logs/                      # Application logs
│   ├── app.log
│   ├── error.log
│   └── access.log
│
├── exports/                   # Export files
│   ├── reports/
│   └── backups/
│
├── vendor/                    # Composer dependencies
│
├── .env                       # Environment configuration
├── .env.example              # Environment template
├── .gitignore                # Git ignore rules
├── composer.json             # Composer configuration
├── composer.lock             # Composer lock file
├── docker-compose.yml        # Docker configuration
├── Dockerfile                # Docker image
├── nginx.conf                # Nginx configuration
├── phpunit.xml               # PHPUnit configuration
└── README.md                 # This file
```

---

## 🎯 ฟีเจอร์หลัก

### 1. 📦 การจัดการสต็อก (Inventory Management)

#### ฟีเจอร์
- ✅ ระบบ FEFO (First Expire, First Out)
- ✅ Multi-warehouse support
- ✅ Sub-warehouse management
- ✅ Real-time stock tracking
- ✅ Expiry date monitoring
- ✅ Low stock alerts
- ✅ Barcode scanning
- ✅ Batch/Lot tracking
- ✅ Stock transfer between warehouses
- ✅ Stock adjustment with audit trail

#### การใช้งาน
```php
// เช็คสต็อคคงเหลือ
GET /inventory/stock?drug_id=123

// รับของเข้าคลัง
POST /inventory/receive
{
  "drug_id": 123,
  "lot_no": "LOT2024001",
  "expire_date": "2025-12-31",
  "quantity": 1000,
  "cost_price": 5.50
}

// โอนสต็อคระหว่างคลัง
POST /inventory/transfer
{
  "from_warehouse": 1,
  "to_warehouse": 2,
  "drug_id": 123,
  "quantity": 100
}
```

---

### 2. 🛒 การจัดการคำสั่งซื้อ (Order Management)

#### ฟีเจอร์
- ✅ สร้างและติดตามคำสั่งซื้อ
- ✅ Purchase Order (PO) generation
- ✅ Supplier management
- ✅ Order approval workflow
- ✅ รับของเข้าคลัง (Receiving)
- ✅ ประวัติการสั่งซื้อ
- ✅ Cost tracking
- ✅ Auto-reorder based on min stock
- ✅ Contract management

---

### 3. 💊 การจ่ายยา (Dispensing)

#### ฟีเจอร์
- ✅ บันทึกการจ่ายยาให้ผู้ป่วย
- ✅ ประวัติการจ่ายยา
- ✅ Drug interaction checking
- ✅ Allergy checking
- ✅ Dosage calculation
- ✅ Label printing
- ✅ FEFO compliance
- ✅ Patient counseling notes

---

### 4. 📊 รายงาน (Reports)

#### รายงานที่มี
- ✅ รายงานสต็อคคงเหลือ
- ✅ รายงานยาใกล้หมดอายุ
- ✅ รายงานยาขายดี / Dead Stock
- ✅ รายงานรายได้และกำไร
- ✅ รายงานการใช้ยา
- ✅ รายงาน ABC Analysis
- ✅ รายงาน Inventory Turnover
- ✅ รายงานการสั่งซื้อ
- ✅ Export เป็น Excel/PDF

---

### 5. 🔗 JHCIS Integration

#### ฟีเจอร์
- ✅ ดึงข้อมูลผู้ป่วยจาก JHCIS
- ✅ ซิงค์ข้อมูลยาอัตโนมัติ
- ✅ Auto-mapping ข้อมูล
- ✅ รองรับหลาย JHCIS Database
- ✅ Reconciliation และ Data Cleansing
- ✅ Real-time sync
- ✅ Lab results integration
- ✅ Vaccination history
- ✅ Chronic disease data

#### การตั้งค่า
```env
# .env
JHCIS_HOST=192.168.1.100
JHCIS_PORT=3306
JHCIS_DB=jhcisdb
JHCIS_USER=jhcis_user
JHCIS_PASS=jhcis_password
```

---

### 6. 📞 Telepharmacy (เภสัชกรรมทางไกล)

#### ฟีเจอร์
- ✅ Video consultation ผ่าน Jitsi Meet
- ✅ Clinical notes พร้อม AI analysis
- ✅ ADR detection
- ✅ Drug interaction detection
- ✅ Patient engagement portal
- ✅ Consultation history
- ✅ Prescription management
- ✅ Follow-up scheduling

---

### 7. 🧠 AI Intelligence

#### ฟีเจอร์
- ✅ ADR Surveillance
- ✅ Drug Interaction Detection
- ✅ Demand Forecasting (Prophet/LSTM)
- ✅ Clinical Decision Support
- ✅ Patient Safety Reporting
- ✅ Smart Drug Labels
- ✅ Visual Analytics
- ✅ Refill Reminders

---

### 8. 👥 Patient Engagement

#### ฟีเจอร์
- ✅ Patient portal
- ✅ AI-driven health advice
- ✅ Medication reminders
- ✅ Lab results viewing
- ✅ Vaccination history
- ✅ Chronic disease management
- ✅ Appointment scheduling
- ✅ Prescription refill requests

---

## 📊 Database Schema

### ตารางหลัก

#### **drugs** - ข้อมูลยา
```sql
CREATE TABLE drugs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    generic_name VARCHAR(255),
    unit VARCHAR(50),
    cost_price DECIMAL(10,2),
    sell_price DECIMAL(10,2),
    min_stock INT DEFAULT 0,
    category VARCHAR(100),
    therapeutic_class VARCHAR(100),
    controlled_drug BOOLEAN DEFAULT FALSE,
    dangerous_drug BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

#### **inventory** - สต็อคคงเหลือ
```sql
CREATE TABLE inventory (
    id INT PRIMARY KEY AUTO_INCREMENT,
    drug_id INT NOT NULL,
    warehouse_id INT DEFAULT 1,
    lot_no VARCHAR(100),
    expire_date DATE,
    quantity INT NOT NULL,
    cost_price DECIMAL(10,2),
    location VARCHAR(100),
    received_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (drug_id) REFERENCES drugs(id),
    INDEX idx_drug_warehouse (drug_id, warehouse_id),
    INDEX idx_expire_date (expire_date)
);
```

#### **dispensing** - การจ่ายยา
```sql
CREATE TABLE dispensing (
    id INT PRIMARY KEY AUTO_INCREMENT,
    hn VARCHAR(20) NOT NULL,
    patient_name VARCHAR(255),
    dispense_date DATE NOT NULL,
    total_price DECIMAL(10,2),
    pharmacist_id INT,
    status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_hn (hn),
    INDEX idx_dispense_date (dispense_date)
);
```

#### **dispensing_items** - รายการยาที่จ่าย
```sql
CREATE TABLE dispensing_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    dispensing_id INT NOT NULL,
    drug_id INT NOT NULL,
    inventory_id INT,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2),
    total_price DECIMAL(10,2),
    dosage VARCHAR(255),
    frequency VARCHAR(100),
    duration INT,
    FOREIGN KEY (dispensing_id) REFERENCES dispensing(id),
    FOREIGN KEY (drug_id) REFERENCES drugs(id),
    FOREIGN KEY (inventory_id) REFERENCES inventory(id)
);
```

#### **orders** - คำสั่งซื้อ
```sql
CREATE TABLE orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_no VARCHAR(50) UNIQUE,
    order_date DATE NOT NULL,
    supplier_id INT,
    status ENUM('draft', 'pending', 'approved', 'received', 'cancelled') DEFAULT 'draft',
    total_amount DECIMAL(12,2),
    approved_by INT,
    approved_at TIMESTAMP NULL,
    received_at TIMESTAMP NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_order_date (order_date),
    INDEX idx_status (status)
);
```

### ตารางเสริม

- `users` - ผู้ใช้งานระบบ
- `warehouses` - คลังยา
- `suppliers` - ผู้จัดจำหน่าย
- `patients` - ข้อมูลผู้ป่วย
- `drug_allergies` - ประวัติแพ้ยา
- `drug_interactions` - ปฏิกิริยาระหว่างยา
- `activity_logs` - บันทึกการใช้งาน
- `notifications` - การแจ้งเตือน
- `telehealth_sessions` - บันทึก Telepharmacy
- `engagement_messages` - ข้อความ Patient Engagement

---

## 🌐 API Documentation

### Authentication

```http
POST /api/auth/login
Content-Type: application/json

{
  "username": "admin",
  "password": "admin123"
}

Response:
{
  "success": true,
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "user": {
    "id": 1,
    "username": "admin",
    "role": "admin"
  }
}
```

### AI Chat API

```http
POST /api/ai/chat
Content-Type: application/json
Authorization: Bearer {token}

{
  "message": "เช็คสต็อค Amoxy"
}

Response:
{
  "type": "text",
  "message": "Amoxicillin 500mg คงเหลือ 1,250 แคปซูล",
  "data": {
    "drug_id": 123,
    "stock": 1250,
    "unit": "แคปซูล"
  }
}
```

### Inventory API

```http
# Get stock by drug ID
GET /api/inventory/stock/{drug_id}

# Get expiring drugs
GET /api/inventory/expiring?days=90

# Receive stock
POST /api/inventory/receive
{
  "drug_id": 123,
  "lot_no": "LOT2024001",
  "expire_date": "2025-12-31",
  "quantity": 1000,
  "cost_price": 5.50
}

# Transfer stock
POST /api/inventory/transfer
{
  "from_warehouse": 1,
  "to_warehouse": 2,
  "drug_id": 123,
  "quantity": 100
}
```

### Dispensing API

```http
# Create dispensing
POST /api/dispensing/create
{
  "hn": "12345",
  "patient_name": "นายทดสอบ ระบบ",
  "items": [
    {
      "drug_id": 123,
      "quantity": 30,
      "dosage": "1x3",
      "frequency": "หลังอาหาร",
      "duration": 10
    }
  ]
}

# Get dispensing history
GET /api/dispensing/history?hn=12345
```

### Intelligence API

```http
# Check ADR
POST /api/intelligence/check-adr
{
  "hn": "12345",
  "drugs": [123, 456]
}

# Check drug interactions
POST /api/intelligence/check-interactions
{
  "drug_ids": [123, 456, 789]
}

# Get patient safety report
GET /api/intelligence/safety-report/{hn}
```

---

## 🔐 Security Features

### 1. CSRF Protection
- Token-based CSRF protection
- Automatic token generation
- Token validation on all POST/PUT/DELETE requests

### 2. SQL Injection Prevention
- Prepared statements (PDO)
- Parameter binding
- Input validation

### 3. XSS Protection
- HTML entity encoding
- Content Security Policy (CSP)
- Input sanitization

### 4. Password Security
- bcrypt hashing (cost factor: 10)
- Password strength requirements
- Secure password reset

### 5. Session Management
- Secure session handling
- Session timeout (120 minutes)
- Session regeneration
- HttpOnly cookies

### 6. Rate Limiting
- API rate limiting
- Login attempt limiting
- Brute force protection

### 7. Audit Trail
- Activity logging
- User action tracking
- Database change logging

---

## 🧪 การทดสอบ

### Manual Testing

```bash
# Start PHP built-in server
php -S localhost:8080 -t public

# Access the application
http://localhost:8080
```

### Unit Testing (PHPUnit)

```bash
# Run all tests
composer test

# Run specific test suite
composer test:unit
composer test:integration
composer test:feature

# Generate coverage report
composer test:coverage
```

### API Testing

```bash
# Using curl
curl -X POST http://localhost:8080/api/ai/chat \
  -H "Content-Type: application/json" \
  -d '{"message":"เช็คสต็อค Amoxy"}'
```

---

## 🚀 Deployment

### Production Checklist

- [ ] Set `APP_DEBUG=false` in `.env`
- [ ] Generate secure `APP_KEY`
- [ ] Configure proper database credentials
- [ ] Set up SSL/TLS certificate
- [ ] Configure firewall rules
- [ ] Set up automated backups
- [ ] Configure log rotation
- [ ] Set up monitoring (Uptime, Performance)
- [ ] Enable Redis caching (if available)
- [ ] Configure email/LINE notifications
- [ ] Review and harden security settings
- [ ] Set up CRON jobs for scheduled tasks

### CRON Jobs

```bash
# Add to crontab
# Daily backup at 2 AM
0 2 * * * cd /path/to/drugmuk && php scripts/backup.php

# Sync JHCIS every hour
0 * * * * cd /path/to/drugmuk && php scripts/sync_jhcis.php

# Send daily notifications at 8 AM
0 8 * * * cd /path/to/drugmuk && php cron.php
```

### Performance Optimization

1. **Enable OPcache**
```ini
; php.ini
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=10000
opcache.revalidate_freq=2
```

2. **Enable Redis Caching**
```env
REDIS_HOST=localhost
REDIS_PORT=6379
```

3. **Database Optimization**
- Add proper indexes
- Optimize queries
- Enable query caching

---

## 🗺️ Roadmap

### Version 2.0 (Q2 2026)
- [ ] Mobile App (React Native)
- [ ] Barcode Scanner Integration
- [ ] E-Prescription System
- [ ] Multi-language Support (EN, TH)
- [ ] Advanced Analytics Dashboard
- [ ] Automated Reordering (AI-based)
- [ ] Supplier Portal
- [ ] Blockchain for Drug Traceability

### Version 2.5 (Q4 2026)
- [ ] Machine Learning for Demand Forecasting
- [ ] IoT Integration (Temperature monitoring)
- [ ] Voice Assistant (Alexa/Google Home)
- [ ] Augmented Reality (AR) for Stock Finding
- [ ] Blockchain-based Prescription Verification

---

## 🤝 Contributing

เรายินดีรับ Contributions! กรุณาทำตามขั้นตอน:

1. Fork the Project
2. Create your Feature Branch
   ```bash
   git checkout -b feature/AmazingFeature
   ```
3. Commit your Changes
   ```bash
   git commit -m 'Add some AmazingFeature'
   ```
4. Push to the Branch
   ```bash
   git push origin feature/AmazingFeature
   ```
5. Open a Pull Request

### Coding Standards

- Follow PSR-12 coding style
- Write meaningful commit messages
- Add comments for complex logic
- Write unit tests for new features
- Update documentation

---

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

---

## 👨‍💻 Author & Team

**Drugmuk Development Team**

- **Lead Developer**: [@thering999](https://github.com/thering999)
- **Project Link**: [https://github.com/thering999/drugmuk](https://github.com/thering999/drugmuk)
- **Organization**: โรงพยาบาลส่งเสริมสุขภาพตำบล

---

## 🙏 Acknowledgments

### Technologies & Libraries
- [PHP](https://www.php.net/) - Server-side language
- [MySQL](https://www.mysql.com/) - Database
- [Composer](https://getcomposer.org/) - Dependency management
- [Chart.js](https://www.chartjs.org/) - Charts and graphs
- [Font Awesome](https://fontawesome.com/) - Icons
- [Jitsi Meet](https://jitsi.org/) - Video conferencing
- [Google Fonts](https://fonts.google.com/) - Typography
- [QR Code Generator](https://github.com/chillerlan/php-qrcode) - QR Code generation

### Inspiration
- JHCIS Team - สำหรับระบบ JHCIS
- สำนักงานหลักประกันสุขภาพแห่งชาติ (สปสช.)
- กระทรวงสาธารณสุข

---

## 📞 Support

หากพบปัญหาหรือมีข้อสงสัย:

- 🐛 เปิด [Issue](https://github.com/thering999/drugmuk/issues)
- 💬 ติดต่อผ่าน [GitHub Discussions](https://github.com/thering999/drugmuk/discussions)
- 📧 Email: support@drugmuk.com (ถ้ามี)
- 📱 LINE Official: @drugmuk (ถ้ามี)

---

## 📈 Version History

### v1.5.0 (Current) - 2026-02-11
**AI Enhancement Phase**
- ✨ Added AI Voice Commands (Hands-Free)
- ✨ Smart Drug Label Generation with visual icons
- ✨ Visual Analytics with interactive charts
- ✨ Refill Reminders for proactive patient care
- ✨ Cross-Branch Stock Check
- ✨ Instant Purchase Order via AI chat
- ✨ AI-Driven Intelligence (ADR, Drug Interactions)
- 🐛 Fixed CSRF validation issues
- 🐛 Fixed SQL errors in patient usage tracking
- 🐛 Fixed analytics calculation bugs
- 🔧 Improved performance and caching

### v1.4.0 - 2026-02-06
**Telepharmacy & Patient Engagement**
- ✨ Telepharmacy module with Jitsi Meet
- ✨ Patient Engagement Portal
- ✨ AI-driven health advice
- ✨ Clinical notes with AI analysis
- ✨ ADR surveillance
- 🔧 Enhanced JHCIS integration

### v1.3.0 - 2026-01-30
**JHCIS Integration Enhancement**
- ✨ Multi-hospital JHCIS support
- ✨ Auto-mapping and reconciliation
- ✨ Data cleansing tools
- ✨ Executive summary reports
- 🔧 Improved data synchronization

### v1.2.0 - 2026-01-27
**LINE Notification & Forecasting**
- ✨ LINE Notify integration
- ✨ Demand forecasting (Prophet/LSTM)
- ✨ Automated alerts and notifications
- 🔧 Enhanced reporting

### v1.1.0 - 2026-01-21
**JHCIS Data Integration**
- ✨ Lab results integration
- ✨ Vaccination history
- ✨ Screening data
- ✨ Chronic disease management
- 🔧 Enhanced patient profiles

### v1.0.0 - 2026-01-15
**Initial Release**
- 🎉 Core Inventory Management
- 🎉 Basic JHCIS Integration
- 🎉 Dispensing Module
- 🎉 Order Management
- 🎉 Basic Reporting
- 🎉 User Management

---

## 📊 Project Statistics

- **Total Files**: 247+ source files
- **Controllers**: 47 controllers
- **Services**: 34 services
- **Models**: 18 models
- **Views**: 103 view templates
- **Lines of Code**: ~50,000+ lines
- **Database Tables**: 30+ tables
- **API Endpoints**: 100+ endpoints

---

## 🌍 Supported Languages

- 🇹🇭 ภาษาไทย (Thai) - Primary
- 🇬🇧 English - Partial support

---

## 🏆 Awards & Recognition

- ⭐ Best Healthcare Innovation 2026 (ถ้ามี)
- 🥇 Digital Health Award (ถ้ามี)

---

**Made with ❤️ in Thailand 🇹🇭**

---

## 🔗 Quick Links

- [Documentation](https://github.com/thering999/drugmuk/wiki)
- [API Reference](https://github.com/thering999/drugmuk/wiki/API)
- [Changelog](https://github.com/thering999/drugmuk/blob/main/CHANGELOG.md)
- [Contributing Guide](https://github.com/thering999/drugmuk/blob/main/CONTRIBUTING.md)
- [Code of Conduct](https://github.com/thering999/drugmuk/blob/main/CODE_OF_CONDUCT.md)

---

> **Note**: This is an active development project. Features and documentation are continuously being updated and improved.

**Last Updated**: February 11, 2026
