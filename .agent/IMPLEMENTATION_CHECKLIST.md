# ✅ JHCIS Integration - Implementation Checklist

**วันที่:** 21 มกราคม 2569  
**เวอร์ชัน:** 3.2.0  
**สถานะ:** Ready to Deploy

---

## 📋 Checklist - ทำตามลำดับ

### ✅ Step 1: Database Setup (DONE!)
- [x] Apply drug_allergy_schema.sql ✅
- [x] Apply patient_profile_schema.sql ✅
- [x] Verify tables created
- [x] Verify views created
- [x] Verify stored procedures created

**Status:** ✅ Complete

---

### ⏳ Step 2: Routes Configuration (TODO)

**ไฟล์:** `routes/web.php`

**วิธีทำ:**
1. เปิดไฟล์ `routes/web.php`
2. เพิ่มโค้ดนี้ก่อน closing tag:

```php
// ===================================
// JHCIS Integration Routes
// ===================================

// Drug Allergy Routes
$router->post('/api/allergy/check', 'DrugAllergyController@check');
$router->post('/api/allergy/check-multiple', 'DrugAllergyController@checkMultiple');
$router->get('/api/allergy/patient/{hn}', 'DrugAllergyController@getPatientAllergies');
$router->post('/api/allergy/sync', 'DrugAllergyController@sync');
$router->get('/api/allergy/statistics', 'DrugAllergyController@statistics');

// Patient Profile Routes
$router->get('/api/patient/search', 'PatientController@search');
$router->get('/api/patient/{hn}', 'PatientController@getProfile');
$router->get('/api/patient/{hn}/chronic', 'PatientController@getChronicDiseases');
$router->get('/api/patient/{hn}/visits', 'PatientController@getRecentVisits');
$router->get('/api/patient/{hn}/medications', 'PatientController@getCurrentMedications');
$router->get('/api/patient/{hn}/vitals', 'PatientController@getVitalSigns');
$router->post('/api/patient/{hn}/sync', 'PatientController@syncProfile');
$router->get('/patient/{hn}', 'PatientController@dashboard');
```

3. บันทึกไฟล์

**หรือ:** Copy จาก `routes/jhcis_routes.php` ที่ผมสร้างให้

---

### ⏳ Step 3: Test APIs (TODO)

**ทดสอบ Patient Search:**
```bash
curl "http://localhost:8080/api/patient/search?q=0000001"
```

**Expected Response:**
```json
{
  "success": true,
  "patients": [...],
  "count": 1
}
```

**ทดสอบ Patient Profile:**
```bash
curl "http://localhost:8080/api/patient/0000001"
```

**ทดสอบ Drug Allergy Check:**
```bash
curl -X POST http://localhost:8080/api/allergy/check \
  -H "Content-Type: application/json" \
  -d '{"hn":"0000001","drug_name":"Amoxicillin"}'
```

**Expected Response:**
```json
{
  "success": true,
  "has_allergy": false,
  "message": "ไม่พบประวัติแพ้ยา"
}
```

---

### ⏳ Step 4: Frontend Integration (TODO)

**ไฟล์:** `src/Views/dispensing/create.php` (หรือหน้าจ่ายยา)

**เพิ่ม JavaScript:**
```html
<!-- เพิ่มก่อน </body> -->
<script src="/js/drug-allergy-checker.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize allergy checker
    window.allergyChecker = new DrugAllergyChecker();
    window.allergyChecker.init();
});
</script>
```

**เพิ่ม HTML Elements:**
```html
<!-- HN Input -->
<div class="form-group">
    <label>HN ผู้ป่วย</label>
    <input type="text" id="patient-hn" name="hn" class="form-control" required>
</div>

<!-- Allergy Display -->
<div id="patient-allergies"></div>

<!-- Alert Container -->
<div id="allergy-alerts"></div>

<!-- Drug Select (เพิ่ม class drug-select) -->
<select class="form-control drug-select" name="drug_id[]">
    <option value="">เลือกยา</option>
    <!-- options from database -->
</select>
```

---

### ⏳ Step 5: User Training (TODO)

**สิ่งที่ต้องอบรม:**
1. วิธีใช้ Patient Search
2. วิธีดูข้อมูลผู้ป่วย
3. การตรวจสอบประวัติแพ้ยา
4. การจัดการ Chronic Patients
5. การส่ง Refill Reminders

**เวลาประมาณ:** 1-2 ชั่วโมง

---

### ⏳ Step 6: Deploy to Production (TODO)

**Pre-Deployment:**
```bash
# 1. Backup database
docker-compose exec db mysqldump -u root -p123456 drugmuk > backup_pre_jhcis.sql

# 2. Test all APIs
# (run tests from Step 3)

# 3. Check logs
docker-compose logs app | grep -i error
```

**Deployment:**
```bash
# 1. Pull latest code
git pull origin main

# 2. Apply schemas (if not done)
Get-Content database/drug_allergy_schema.sql | docker-compose exec -T db mysql -u root -p123456 drugmuk
Get-Content database/patient_profile_schema.sql | docker-compose exec -T db mysql -u root -p123456 drugmuk

# 3. Clear cache
rm -rf storage/cache/*

# 4. Restart services
docker-compose restart app
```

**Post-Deployment:**
```bash
# 1. Verify APIs work
curl "http://localhost:8080/api/patient/search?q=test"

# 2. Monitor logs
docker-compose logs -f app

# 3. Check database
docker-compose exec db mysql -u root -p123456 -e "SHOW TABLES LIKE 'patient%';" drugmuk
```

---

## 🎯 Quick Start Guide

### สำหรับคนรีบ (10 นาที):

```bash
# 1. Database (DONE ✅)
# Already applied

# 2. Add Routes
# Copy from routes/jhcis_routes.php to routes/web.php

# 3. Test
curl "http://localhost:8080/api/patient/search?q=0000001"

# 4. Done!
```

---

## 📊 Verification Checklist

### Database Verification
```sql
-- Check tables
SHOW TABLES LIKE 'patient%';
SHOW TABLES LIKE '%allergy%';
SHOW TABLES LIKE 'chronic%';

-- Expected: 11 tables

-- Check views
SHOW FULL TABLES WHERE Table_type = 'VIEW';

-- Expected: 6 views

-- Check stored procedures
SHOW PROCEDURE STATUS WHERE Db = 'drugmuk';

-- Expected: 4 procedures
```

### API Verification
```bash
# Patient Search
curl "http://localhost:8080/api/patient/search?q=test"

# Patient Profile
curl "http://localhost:8080/api/patient/0000001"

# Allergy Check
curl -X POST http://localhost:8080/api/allergy/check \
  -H "Content-Type: application/json" \
  -d '{"hn":"0000001","drug_name":"test"}'
```

### Frontend Verification
- [ ] Patient search autocomplete works
- [ ] Patient profile displays correctly
- [ ] Drug allergy alerts show up
- [ ] Sound notifications work
- [ ] Modal warnings appear

---

## 🚨 Troubleshooting

### Problem: API returns 404
**Solution:** Check routes are added correctly in `routes/web.php`

### Problem: Database connection error
**Solution:** 
```bash
# Check JHCIS connection
docker-compose exec app php -r "
\$pdo = new PDO('mysql:host=host.docker.internal;dbname=jhcisdb', 'root', '123456');
echo 'Connected!';
"
```

### Problem: No patient data
**Solution:** Check JHCIS database has data and connection is correct

### Problem: Allergy check not working
**Solution:** 
1. Check `patient_allergies_cache` table has data
2. Run sync: `curl -X POST http://localhost:8080/api/allergy/sync -d '{"hn":"0000001"}'`

---

## 📚 Documentation References

1. **API Documentation:** `.agent/drug_allergy_implementation.md`
2. **Phase 1 Complete:** `.agent/jhcis_phase1_complete.md`
3. **Quick Reference:** `.agent/QUICK_REFERENCE.md`
4. **Final Summary:** `.agent/FINAL_SUMMARY_20260121.md`

---

## 🎉 Success Criteria

### ✅ Ready for Production when:
- [ ] All routes added and working
- [ ] All APIs tested and returning correct data
- [ ] Frontend integrated and working
- [ ] Users trained
- [ ] No errors in logs
- [ ] Performance acceptable (< 2s page load)
- [ ] Security headers enabled
- [ ] Backup completed

---

## 📞 Support

**ถ้ามีปัญหา:**
1. ตรวจสอบ logs: `docker-compose logs app`
2. ตรวจสอบ database: `docker-compose exec db mysql -u root -p123456 drugmuk`
3. ดู documentation ใน `.agent/` folder
4. ติดต่อ developer

---

**สร้างโดย:** Antigravity AI  
**วันที่:** 21 มกราคม 2569  
**เวอร์ชัน:** 3.2.0
