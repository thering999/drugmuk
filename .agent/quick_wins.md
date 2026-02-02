# 🚀 Quick Wins - ปรับปรุงระบบได้ทันที
**สิ่งที่สามารถทำได้ภายใน 1-4 สัปดาห์**

**วันที่:** 19 มกราคม 2569

---

## 📋 สารบัญ

1. [Week 1: Testing Foundation](#week-1-testing-foundation)
2. [Week 2: Performance Boost](#week-2-performance-boost)
3. [Week 3: UX Improvements](#week-3-ux-improvements)
4. [Week 4: Advanced Features](#week-4-advanced-features)

---

## 🎯 Week 1: Testing Foundation

### Day 1-2: Setup Testing Infrastructure

#### 1. สร้างโครงสร้าง Tests
```bash
# สร้าง folders
mkdir tests
mkdir tests/Unit
mkdir tests/Unit/Models
mkdir tests/Unit/Controllers
mkdir tests/Integration
mkdir tests/Feature

# สร้าง base files
```

#### 2. สร้าง TestCase.php
```php
<?php
// File: tests/TestCase.php

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use App\Core\Database;

abstract class TestCase extends BaseTestCase
{
    protected $db;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Setup test database connection
        $_ENV['DB_NAME'] = 'drugmuk_test';
        $this->db = Database::getInstance()->getConnection();
        
        // Begin transaction
        $this->db->beginTransaction();
    }
    
    protected function tearDown(): void
    {
        // Rollback transaction
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }
        
        parent::tearDown();
    }
    
    /**
     * Helper: Create test drug
     */
    protected function createTestDrug($data = [])
    {
        $defaults = [
            'code' => 'TEST' . rand(1000, 9999),
            'name' => 'Test Drug',
            'unit' => 'tablet',
            'price' => 10.50,
            'min_stock' => 10,
            'max_stock' => 100
        ];
        
        $data = array_merge($defaults, $data);
        
        $sql = "INSERT INTO drugs (code, name, unit, price, min_stock, max_stock) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['code'],
            $data['name'],
            $data['unit'],
            $data['price'],
            $data['min_stock'],
            $data['max_stock']
        ]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Helper: Create test user
     */
    protected function createTestUser($data = [])
    {
        $defaults = [
            'username' => 'test' . rand(1000, 9999),
            'password' => password_hash('password', PASSWORD_BCRYPT),
            'full_name' => 'Test User',
            'role' => 'staff'
        ];
        
        $data = array_merge($defaults, $data);
        
        $sql = "INSERT INTO users (username, password, full_name, role) 
                VALUES (?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['username'],
            $data['password'],
            $data['full_name'],
            $data['role']
        ]);
        
        return $this->db->lastInsertId();
    }
}
```

### Day 3-4: เขียน Unit Tests สำคัญ

#### 3. DrugTest.php
```php
<?php
// File: tests/Unit/Models/DrugTest.php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Drug;

class DrugTest extends TestCase
{
    private $drug;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->drug = new Drug();
    }
    
    public function testCreateDrug()
    {
        $data = [
            'code' => 'TEST001',
            'name' => 'Paracetamol 500mg',
            'unit' => 'tablet',
            'price' => 2.50
        ];
        
        $result = $this->drug->create($data);
        
        $this->assertTrue($result);
    }
    
    public function testFindDrugById()
    {
        $drugId = $this->createTestDrug();
        
        $result = $this->drug->findById($drugId);
        
        $this->assertNotNull($result);
        $this->assertEquals($drugId, $result['id']);
    }
    
    public function testUpdateDrug()
    {
        $drugId = $this->createTestDrug();
        
        $result = $this->drug->update($drugId, [
            'price' => 15.00
        ]);
        
        $this->assertTrue($result);
        
        $updated = $this->drug->findById($drugId);
        $this->assertEquals(15.00, $updated['price']);
    }
    
    public function testDeleteDrug()
    {
        $drugId = $this->createTestDrug();
        
        $result = $this->drug->delete($drugId);
        
        $this->assertTrue($result);
        
        $deleted = $this->drug->findById($drugId);
        $this->assertNull($deleted);
    }
    
    public function testSearchDrugs()
    {
        $this->createTestDrug(['name' => 'Paracetamol 500mg']);
        $this->createTestDrug(['name' => 'Amoxicillin 250mg']);
        
        $results = $this->drug->search('para');
        
        $this->assertGreaterThan(0, count($results));
        $this->assertStringContainsString('Paracetamol', $results[0]['name']);
    }
    
    public function testGetLowStock()
    {
        $drugId = $this->createTestDrug([
            'min_stock' => 50,
            'max_stock' => 100
        ]);
        
        // Add inventory below min_stock
        $this->db->query("INSERT INTO inventory (drug_id, quantity) VALUES (?, ?)", 
            [$drugId, 30]);
        
        $lowStock = $this->drug->getLowStock();
        
        $this->assertGreaterThan(0, count($lowStock));
    }
}
```

#### 4. InventoryTest.php
```php
<?php
// File: tests/Unit/Models/InventoryTest.php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Inventory;

class InventoryTest extends TestCase
{
    private $inventory;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->inventory = new Inventory();
    }
    
    public function testAddStock()
    {
        $drugId = $this->createTestDrug();
        
        $result = $this->inventory->addStock([
            'drug_id' => $drugId,
            'lot_no' => 'LOT001',
            'expire_date' => '2026-12-31',
            'quantity' => 100,
            'cost_price' => 10.00
        ]);
        
        $this->assertTrue($result);
    }
    
    public function testGetStockByDrug()
    {
        $drugId = $this->createTestDrug();
        
        $this->inventory->addStock([
            'drug_id' => $drugId,
            'lot_no' => 'LOT001',
            'expire_date' => '2026-12-31',
            'quantity' => 100,
            'cost_price' => 10.00
        ]);
        
        $stock = $this->inventory->getStockByDrug($drugId);
        
        $this->assertEquals(100, $stock['total_quantity']);
    }
    
    public function testFEFOLogic()
    {
        $drugId = $this->createTestDrug();
        
        // Add 2 lots with different expiry dates
        $this->inventory->addStock([
            'drug_id' => $drugId,
            'lot_no' => 'LOT001',
            'expire_date' => '2026-06-30', // Expires first
            'quantity' => 50,
            'cost_price' => 10.00
        ]);
        
        $this->inventory->addStock([
            'drug_id' => $drugId,
            'lot_no' => 'LOT002',
            'expire_date' => '2026-12-31', // Expires later
            'quantity' => 50,
            'cost_price' => 10.00
        ]);
        
        // Get lot for dispensing (should return LOT001)
        $lot = $this->inventory->getLotForDispensing($drugId, 10);
        
        $this->assertEquals('LOT001', $lot['lot_no']);
    }
    
    public function testGetExpiringDrugs()
    {
        $drugId = $this->createTestDrug();
        
        // Add stock expiring in 2 months
        $expireDate = date('Y-m-d', strtotime('+2 months'));
        $this->inventory->addStock([
            'drug_id' => $drugId,
            'lot_no' => 'LOT001',
            'expire_date' => $expireDate,
            'quantity' => 50,
            'cost_price' => 10.00
        ]);
        
        $expiring = $this->inventory->getExpiringDrugs(3); // Within 3 months
        
        $this->assertGreaterThan(0, count($expiring));
    }
}
```

### Day 5: รัน Tests และแก้ไข

```bash
# รัน all tests
vendor/bin/phpunit

# รัน specific test
vendor/bin/phpunit tests/Unit/Models/DrugTest.php

# รัน with coverage
vendor/bin/phpunit --coverage-html tests/coverage
```

**เป้าหมาย Week 1:**
- ✅ 30+ test cases
- ✅ Coverage 30%+
- ✅ All tests passing

---

## ⚡ Week 2: Performance Boost

### Day 1: Database Optimization

#### 1. เพิ่ม Indexes
```sql
-- File: database/performance_indexes.sql

-- Composite indexes สำหรับ queries ที่ใช้บ่อย
CREATE INDEX idx_inventory_drug_lot ON inventory(drug_id, lot_no);
CREATE INDEX idx_inventory_drug_expire ON inventory(drug_id, expire_date);
CREATE INDEX idx_dispensing_date_hn ON dispensing(dispense_date, hn);
CREATE INDEX idx_orders_status_date ON orders(status, order_date);
CREATE INDEX idx_transactions_drug_date ON transactions(drug_id, transaction_date);

-- Full-text search
ALTER TABLE drugs ADD FULLTEXT INDEX ft_drugs_name (name, generic_name);

-- Covering indexes
CREATE INDEX idx_drugs_active_name ON drugs(is_active, name, code, unit, price);

-- Analyze tables
ANALYZE TABLE drugs, inventory, orders, dispensing, transactions;
```

#### 2. Query Optimization
```php
// File: src/Models/Order.php

// Before (N+1 problem)
public function getAllOrders() {
    $orders = $this->db->query("SELECT * FROM orders")->fetchAll();
    foreach ($orders as &$order) {
        $order['items'] = $this->db->query(
            "SELECT * FROM order_items WHERE order_id = ?", 
            [$order['id']]
        )->fetchAll();
    }
    return $orders;
}

// After (Single query with JOIN)
public function getAllOrders() {
    $sql = "SELECT 
                o.*,
                oi.id as item_id,
                oi.drug_id,
                oi.quantity,
                oi.unit_price,
                d.name as drug_name,
                d.code as drug_code
            FROM orders o
            LEFT JOIN order_items oi ON o.id = oi.order_id
            LEFT JOIN drugs d ON oi.drug_id = d.id
            ORDER BY o.order_date DESC";
    
    $results = $this->db->query($sql)->fetchAll();
    
    // Group by order
    $orders = [];
    foreach ($results as $row) {
        $orderId = $row['id'];
        if (!isset($orders[$orderId])) {
            $orders[$orderId] = [
                'id' => $row['id'],
                'order_no' => $row['order_no'],
                'order_date' => $row['order_date'],
                'status' => $row['status'],
                'items' => []
            ];
        }
        
        if ($row['item_id']) {
            $orders[$orderId]['items'][] = [
                'id' => $row['item_id'],
                'drug_id' => $row['drug_id'],
                'drug_name' => $row['drug_name'],
                'quantity' => $row['quantity'],
                'unit_price' => $row['unit_price']
            ];
        }
    }
    
    return array_values($orders);
}
```

### Day 2: Implement Caching

#### 3. File-based Cache (ไม่ต้องใช้ Redis)
```php
<?php
// File: src/Services/SimpleCacheService.php

namespace App\Services;

class SimpleCacheService
{
    private $cacheDir;
    private $defaultTTL = 3600; // 1 hour
    
    public function __construct()
    {
        $this->cacheDir = __DIR__ . '/../../storage/cache';
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    /**
     * Get cached value
     */
    public function get($key)
    {
        $file = $this->getCacheFile($key);
        
        if (!file_exists($file)) {
            return null;
        }
        
        $data = unserialize(file_get_contents($file));
        
        // Check if expired
        if ($data['expires_at'] < time()) {
            unlink($file);
            return null;
        }
        
        return $data['value'];
    }
    
    /**
     * Set cached value
     */
    public function set($key, $value, $ttl = null)
    {
        $ttl = $ttl ?? $this->defaultTTL;
        $file = $this->getCacheFile($key);
        
        $data = [
            'value' => $value,
            'expires_at' => time() + $ttl
        ];
        
        file_put_contents($file, serialize($data));
    }
    
    /**
     * Remember: Get from cache or execute callback
     */
    public function remember($key, $ttl, $callback)
    {
        $cached = $this->get($key);
        
        if ($cached !== null) {
            return $cached;
        }
        
        $value = $callback();
        $this->set($key, $value, $ttl);
        
        return $value;
    }
    
    /**
     * Delete cached value
     */
    public function delete($key)
    {
        $file = $this->getCacheFile($key);
        if (file_exists($file)) {
            unlink($file);
        }
    }
    
    /**
     * Clear all cache
     */
    public function clear()
    {
        $files = glob($this->cacheDir . '/*.cache');
        foreach ($files as $file) {
            unlink($file);
        }
    }
    
    private function getCacheFile($key)
    {
        return $this->cacheDir . '/' . md5($key) . '.cache';
    }
}
```

#### 4. ใช้ Cache ในระบบ
```php
// File: src/Models/Drug.php

use App\Services\SimpleCacheService;

class Drug
{
    private $cache;
    
    public function __construct()
    {
        $this->cache = new SimpleCacheService();
    }
    
    /**
     * Get all active drugs (with cache)
     */
    public function getAllActive()
    {
        return $this->cache->remember('drugs:all:active', 3600, function() {
            $sql = "SELECT * FROM drugs WHERE is_active = 1 ORDER BY name";
            return $this->db->query($sql)->fetchAll();
        });
    }
    
    /**
     * Update drug (invalidate cache)
     */
    public function update($id, $data)
    {
        $result = parent::update($id, $data);
        
        if ($result) {
            // Invalidate cache
            $this->cache->delete('drugs:all:active');
            $this->cache->delete("drug:$id");
        }
        
        return $result;
    }
}
```

### Day 3-4: Frontend Optimization

#### 5. Minify CSS/JS
```php
<?php
// File: src/Helpers/AssetHelper.php

namespace App\Helpers;

class AssetHelper
{
    /**
     * Minify CSS
     */
    public static function minifyCSS($css)
    {
        // Remove comments
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        
        // Remove whitespace
        $css = str_replace(["\r\n", "\r", "\n", "\t", '  ', '    '], '', $css);
        
        return $css;
    }
    
    /**
     * Minify JS
     */
    public static function minifyJS($js)
    {
        // Remove comments
        $js = preg_replace('!/\*.*?\*/!s', '', $js);
        $js = preg_replace('/\n\s*\n/', "\n", $js);
        
        // Remove whitespace
        $js = preg_replace('/\s+/', ' ', $js);
        
        return $js;
    }
    
    /**
     * Get asset with version (cache busting)
     */
    public static function asset($path)
    {
        $fullPath = __DIR__ . '/../../public/' . $path;
        
        if (!file_exists($fullPath)) {
            return '/' . $path;
        }
        
        $version = filemtime($fullPath);
        return '/' . $path . '?v=' . $version;
    }
}
```

#### 6. Lazy Loading Images
```javascript
// File: public/js/lazy-load.js

document.addEventListener('DOMContentLoaded', function() {
    const images = document.querySelectorAll('img[data-src]');
    
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.removeAttribute('data-src');
                observer.unobserve(img);
            }
        });
    });
    
    images.forEach(img => imageObserver.observe(img));
});
```

### Day 5: Performance Testing

```bash
# Install Apache Bench (if not installed)
# Windows: Download from Apache website

# Test homepage
ab -n 1000 -c 10 http://localhost:8080/

# Test API
ab -n 1000 -c 10 http://localhost:8080/api/drugs

# Test with POST
ab -n 100 -c 10 -p post.txt -T application/json http://localhost:8080/api/drugs
```

**เป้าหมาย Week 2:**
- ✅ Response time < 1s (จาก 2s)
- ✅ Database queries < 50ms
- ✅ Page load < 2s

---

## 🎨 Week 3: UX Improvements

### Day 1-2: Enhanced Search

#### 1. Real-time Search
```javascript
// File: public/js/real-time-search.js

class RealTimeSearch {
    constructor(inputId, resultsId, apiUrl) {
        this.input = document.getElementById(inputId);
        this.results = document.getElementById(resultsId);
        this.apiUrl = apiUrl;
        this.debounceTimer = null;
        
        this.init();
    }
    
    init() {
        this.input.addEventListener('input', (e) => {
            clearTimeout(this.debounceTimer);
            
            const query = e.target.value.trim();
            
            if (query.length < 2) {
                this.results.innerHTML = '';
                return;
            }
            
            // Debounce: wait 300ms after user stops typing
            this.debounceTimer = setTimeout(() => {
                this.search(query);
            }, 300);
        });
    }
    
    async search(query) {
        try {
            const response = await fetch(`${this.apiUrl}?search=${encodeURIComponent(query)}`);
            const data = await response.json();
            
            this.displayResults(data);
        } catch (error) {
            console.error('Search error:', error);
        }
    }
    
    displayResults(data) {
        if (data.length === 0) {
            this.results.innerHTML = '<div class="no-results">ไม่พบข้อมูล</div>';
            return;
        }
        
        let html = '<ul class="search-results">';
        data.forEach(item => {
            html += `
                <li class="search-result-item" data-id="${item.id}">
                    <div class="drug-name">${this.highlight(item.name, query)}</div>
                    <div class="drug-code">${item.code}</div>
                    <div class="drug-stock">คงเหลือ: ${item.stock} ${item.unit}</div>
                </li>
            `;
        });
        html += '</ul>';
        
        this.results.innerHTML = html;
        
        // Add click handlers
        this.results.querySelectorAll('.search-result-item').forEach(item => {
            item.addEventListener('click', () => {
                this.selectItem(item.dataset.id);
            });
        });
    }
    
    highlight(text, query) {
        const regex = new RegExp(`(${query})`, 'gi');
        return text.replace(regex, '<mark>$1</mark>');
    }
    
    selectItem(id) {
        // Handle item selection
        console.log('Selected item:', id);
    }
}

// Usage
const drugSearch = new RealTimeSearch('drug-search', 'search-results', '/api/drugs/search');
```

#### 2. Advanced Filters
```html
<!-- File: src/Views/drugs/index.php -->

<div class="filters-panel">
    <h3>ตัวกรอง</h3>
    
    <div class="filter-group">
        <label>หมวดหมู่</label>
        <select id="filter-category">
            <option value="">ทั้งหมด</option>
            <option value="antibiotic">ยาปฏิชีวนะ</option>
            <option value="analgesic">ยาแก้ปวด</option>
            <option value="antihypertensive">ยาลดความดันโลหิต</option>
        </select>
    </div>
    
    <div class="filter-group">
        <label>VEN Class</label>
        <div class="checkbox-group">
            <label><input type="checkbox" name="ven" value="V"> Vital</label>
            <label><input type="checkbox" name="ven" value="E"> Essential</label>
            <label><input type="checkbox" name="ven" value="N"> Non-essential</label>
        </div>
    </div>
    
    <div class="filter-group">
        <label>ช่วงราคา</label>
        <input type="number" id="price-min" placeholder="ต่ำสุด">
        <input type="number" id="price-max" placeholder="สูงสุด">
    </div>
    
    <div class="filter-group">
        <label>สถานะสต็อก</label>
        <div class="checkbox-group">
            <label><input type="checkbox" name="stock" value="low"> สต็อกต่ำ</label>
            <label><input type="checkbox" name="stock" value="normal"> ปกติ</label>
            <label><input type="checkbox" name="stock" value="high"> สต็อกสูง</label>
        </div>
    </div>
    
    <button onclick="applyFilters()">ค้นหา</button>
    <button onclick="resetFilters()">ล้างตัวกรอง</button>
</div>

<script>
function applyFilters() {
    const filters = {
        category: document.getElementById('filter-category').value,
        ven: Array.from(document.querySelectorAll('input[name="ven"]:checked')).map(cb => cb.value),
        priceMin: document.getElementById('price-min').value,
        priceMax: document.getElementById('price-max').value,
        stock: Array.from(document.querySelectorAll('input[name="stock"]:checked')).map(cb => cb.value)
    };
    
    // Build query string
    const params = new URLSearchParams();
    if (filters.category) params.append('category', filters.category);
    if (filters.ven.length) params.append('ven', filters.ven.join(','));
    if (filters.priceMin) params.append('price_min', filters.priceMin);
    if (filters.priceMax) params.append('price_max', filters.priceMax);
    if (filters.stock.length) params.append('stock', filters.stock.join(','));
    
    // Reload page with filters
    window.location.href = '/drugs?' + params.toString();
}

function resetFilters() {
    window.location.href = '/drugs';
}
</script>
```

### Day 3: Keyboard Shortcuts

```javascript
// File: public/js/keyboard-shortcuts.js

class KeyboardShortcuts {
    constructor() {
        this.shortcuts = {
            // Global shortcuts
            'ctrl+k': () => this.focusSearch(),
            'ctrl+n': () => this.newItem(),
            'ctrl+s': () => this.save(),
            'esc': () => this.closeModal(),
            
            // Navigation
            'g d': () => window.location.href = '/drugs',
            'g i': () => window.location.href = '/inventory',
            'g o': () => window.location.href = '/orders',
            'g r': () => window.location.href = '/reports',
            
            // Quick actions
            'q s': () => this.quickScan(),
            'q d': () => this.quickDispense()
        };
        
        this.init();
    }
    
    init() {
        let keys = [];
        
        document.addEventListener('keydown', (e) => {
            // Build key combination
            let key = '';
            if (e.ctrlKey) key += 'ctrl+';
            if (e.altKey) key += 'alt+';
            if (e.shiftKey) key += 'shift+';
            key += e.key.toLowerCase();
            
            // Check for shortcuts
            if (this.shortcuts[key]) {
                e.preventDefault();
                this.shortcuts[key]();
                return;
            }
            
            // Check for sequence shortcuts (e.g., "g d")
            keys.push(e.key.toLowerCase());
            if (keys.length > 2) keys.shift();
            
            const sequence = keys.join(' ');
            if (this.shortcuts[sequence]) {
                e.preventDefault();
                this.shortcuts[sequence]();
                keys = [];
            }
        });
        
        // Show shortcuts help
        this.createHelpModal();
    }
    
    focusSearch() {
        const searchInput = document.querySelector('input[type="search"]');
        if (searchInput) searchInput.focus();
    }
    
    newItem() {
        const newButton = document.querySelector('.btn-new');
        if (newButton) newButton.click();
    }
    
    save() {
        const saveButton = document.querySelector('.btn-save');
        if (saveButton) saveButton.click();
    }
    
    closeModal() {
        const modal = document.querySelector('.modal.show');
        if (modal) modal.classList.remove('show');
    }
    
    createHelpModal() {
        // Show shortcuts when user presses '?'
        document.addEventListener('keydown', (e) => {
            if (e.key === '?' && !e.ctrlKey && !e.altKey) {
                this.showHelp();
            }
        });
    }
    
    showHelp() {
        alert(`
Keyboard Shortcuts:
        
Global:
  Ctrl+K - ค้นหา
  Ctrl+N - สร้างใหม่
  Ctrl+S - บันทึก
  Esc - ปิด
  ? - แสดงความช่วยเหลือ
  
Navigation:
  G D - ไปหน้ายา
  G I - ไปหน้าคลัง
  G O - ไปหน้าสั่งซื้อ
  G R - ไปหน้ารายงาน
  
Quick Actions:
  Q S - สแกนด่วน
  Q D - จ่ายยาด่วน
        `);
    }
}

// Initialize
const shortcuts = new KeyboardShortcuts();
```

### Day 4-5: Notifications & Alerts

```javascript
// File: public/js/notification-system.js

class NotificationSystem {
    constructor() {
        this.container = this.createContainer();
        this.queue = [];
        this.maxNotifications = 5;
    }
    
    createContainer() {
        const container = document.createElement('div');
        container.className = 'notification-container';
        container.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            max-width: 400px;
        `;
        document.body.appendChild(container);
        return container;
    }
    
    show(message, type = 'info', duration = 5000) {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <div class="notification-icon">${this.getIcon(type)}</div>
                <div class="notification-message">${message}</div>
                <button class="notification-close" onclick="this.parentElement.parentElement.remove()">×</button>
            </div>
        `;
        
        this.container.appendChild(notification);
        
        // Auto-remove after duration
        if (duration > 0) {
            setTimeout(() => {
                notification.classList.add('fade-out');
                setTimeout(() => notification.remove(), 300);
            }, duration);
        }
        
        // Limit number of notifications
        const notifications = this.container.querySelectorAll('.notification');
        if (notifications.length > this.maxNotifications) {
            notifications[0].remove();
        }
    }
    
    success(message, duration) {
        this.show(message, 'success', duration);
    }
    
    error(message, duration) {
        this.show(message, 'error', duration);
    }
    
    warning(message, duration) {
        this.show(message, 'warning', duration);
    }
    
    info(message, duration) {
        this.show(message, 'info', duration);
    }
    
    getIcon(type) {
        const icons = {
            success: '✓',
            error: '✕',
            warning: '⚠',
            info: 'ℹ'
        };
        return icons[type] || icons.info;
    }
}

// Global instance
window.notify = new NotificationSystem();

// Usage examples:
// notify.success('บันทึกสำเร็จ');
// notify.error('เกิดข้อผิดพลาด');
// notify.warning('สต็อกต่ำกว่าจุดสั่งซื้อ');
// notify.info('กำลังประมวลผล...');
```

**เป้าหมาย Week 3:**
- ✅ Real-time search
- ✅ Advanced filters
- ✅ Keyboard shortcuts
- ✅ Better notifications

---

## 🚀 Week 4: Advanced Features

### Day 1-2: Bulk Operations

```javascript
// File: public/js/bulk-operations.js

class BulkOperations {
    constructor(tableId) {
        this.table = document.getElementById(tableId);
        this.selectedIds = new Set();
        this.init();
    }
    
    init() {
        // Add "Select All" checkbox
        const thead = this.table.querySelector('thead tr');
        const selectAllTh = document.createElement('th');
        selectAllTh.innerHTML = '<input type="checkbox" id="select-all">';
        thead.insertBefore(selectAllTh, thead.firstChild);
        
        // Add checkboxes to each row
        const tbody = this.table.querySelector('tbody');
        tbody.querySelectorAll('tr').forEach(row => {
            const id = row.dataset.id;
            const td = document.createElement('td');
            td.innerHTML = `<input type="checkbox" class="row-checkbox" value="${id}">`;
            row.insertBefore(td, row.firstChild);
        });
        
        // Event listeners
        document.getElementById('select-all').addEventListener('change', (e) => {
            this.selectAll(e.target.checked);
        });
        
        tbody.querySelectorAll('.row-checkbox').forEach(cb => {
            cb.addEventListener('change', (e) => {
                this.toggleSelection(e.target.value, e.target.checked);
            });
        });
        
        // Create bulk actions toolbar
        this.createToolbar();
    }
    
    selectAll(checked) {
        this.table.querySelectorAll('.row-checkbox').forEach(cb => {
            cb.checked = checked;
            this.toggleSelection(cb.value, checked);
        });
    }
    
    toggleSelection(id, selected) {
        if (selected) {
            this.selectedIds.add(id);
        } else {
            this.selectedIds.delete(id);
        }
        
        this.updateToolbar();
    }
    
    createToolbar() {
        const toolbar = document.createElement('div');
        toolbar.id = 'bulk-toolbar';
        toolbar.className = 'bulk-toolbar hidden';
        toolbar.innerHTML = `
            <span class="selected-count">เลือก <strong>0</strong> รายการ</span>
            <button onclick="bulkOps.exportSelected()">ส่งออก Excel</button>
            <button onclick="bulkOps.deleteSelected()">ลบ</button>
            <button onclick="bulkOps.clearSelection()">ยกเลิก</button>
        `;
        
        this.table.parentElement.insertBefore(toolbar, this.table);
    }
    
    updateToolbar() {
        const toolbar = document.getElementById('bulk-toolbar');
        const count = this.selectedIds.size;
        
        if (count > 0) {
            toolbar.classList.remove('hidden');
            toolbar.querySelector('.selected-count strong').textContent = count;
        } else {
            toolbar.classList.add('hidden');
        }
    }
    
    async exportSelected() {
        if (this.selectedIds.size === 0) {
            notify.warning('กรุณาเลือกรายการที่ต้องการส่งออก');
            return;
        }
        
        const ids = Array.from(this.selectedIds);
        
        try {
            const response = await fetch('/api/drugs/export', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ ids })
            });
            
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `drugs_${Date.now()}.xlsx`;
            a.click();
            
            notify.success('ส่งออกสำเร็จ');
        } catch (error) {
            notify.error('เกิดข้อผิดพลาดในการส่งออก');
        }
    }
    
    async deleteSelected() {
        if (this.selectedIds.size === 0) {
            notify.warning('กรุณาเลือกรายการที่ต้องการลบ');
            return;
        }
        
        if (!confirm(`ต้องการลบ ${this.selectedIds.size} รายการ?`)) {
            return;
        }
        
        const ids = Array.from(this.selectedIds);
        
        try {
            const response = await fetch('/api/drugs/bulk-delete', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ ids })
            });
            
            const data = await response.json();
            
            if (data.success) {
                notify.success(`ลบสำเร็จ ${data.deleted} รายการ`);
                window.location.reload();
            }
        } catch (error) {
            notify.error('เกิดข้อผิดพลาดในการลบ');
        }
    }
    
    clearSelection() {
        this.selectedIds.clear();
        this.table.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = false);
        document.getElementById('select-all').checked = false;
        this.updateToolbar();
    }
}

// Initialize
const bulkOps = new BulkOperations('drugs-table');
```

### Day 3: Export to Excel (Real Implementation)

```php
<?php
// File: src/Services/ExcelExportService.php

namespace App\Services;

class ExcelExportService
{
    /**
     * Export data to Excel (CSV format)
     */
    public function export($data, $headers, $filename)
    {
        // Set headers for download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Open output stream
        $output = fopen('php://output', 'w');
        
        // Add BOM for Excel UTF-8 support
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Write headers
        fputcsv($output, $headers);
        
        // Write data
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Export drugs to Excel
     */
    public function exportDrugs($drugIds = [])
    {
        $db = \App\Core\Database::getInstance()->getConnection();
        
        $sql = "SELECT code, name, generic_name, unit, price, min_stock, max_stock, category
                FROM drugs";
        
        if (!empty($drugIds)) {
            $placeholders = str_repeat('?,', count($drugIds) - 1) . '?';
            $sql .= " WHERE id IN ($placeholders)";
            $stmt = $db->prepare($sql);
            $stmt->execute($drugIds);
        } else {
            $stmt = $db->query($sql);
        }
        
        $data = $stmt->fetchAll(\PDO::FETCH_NUM);
        
        $headers = ['รหัสยา', 'ชื่อยา', 'ชื่อสามัญ', 'หน่วย', 'ราคา', 'สต็อกต่ำสุด', 'สต็อกสูงสุด', 'หมวดหมู่'];
        
        $this->export($data, $headers, 'drugs_' . date('Ymd_His') . '.csv');
    }
}
```

### Day 4-5: Dashboard Widgets

```php
<!-- File: src/Views/dashboard/widgets.php -->

<div class="dashboard-grid">
    <!-- Stock Alert Widget -->
    <div class="widget widget-alert">
        <h3>🚨 แจ้งเตือนสต็อก</h3>
        <div class="widget-content">
            <?php
            $lowStock = $inventoryModel->getLowStock();
            $expiring = $inventoryModel->getExpiringDrugs(3);
            ?>
            
            <div class="alert-item">
                <span class="alert-label">สต็อกต่ำ:</span>
                <span class="alert-value <?= count($lowStock) > 0 ? 'text-danger' : '' ?>">
                    <?= count($lowStock) ?> รายการ
                </span>
            </div>
            
            <div class="alert-item">
                <span class="alert-label">ใกล้หมดอายุ (3 เดือน):</span>
                <span class="alert-value <?= count($expiring) > 0 ? 'text-warning' : '' ?>">
                    <?= count($expiring) ?> รายการ
                </span>
            </div>
            
            <?php if (count($lowStock) > 0): ?>
            <a href="/inventory?filter=low-stock" class="widget-link">ดูรายละเอียด →</a>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Quick Stats Widget -->
    <div class="widget widget-stats">
        <h3>📊 สถิติวันนี้</h3>
        <div class="widget-content">
            <?php
            $todayStats = $dashboardModel->getTodayStats();
            ?>
            
            <div class="stat-item">
                <div class="stat-value"><?= number_format($todayStats['dispensing_count']) ?></div>
                <div class="stat-label">ครั้งจ่ายยา</div>
            </div>
            
            <div class="stat-item">
                <div class="stat-value"><?= number_format($todayStats['patients_count']) ?></div>
                <div class="stat-label">ผู้ป่วย</div>
            </div>
            
            <div class="stat-item">
                <div class="stat-value">฿<?= number_format($todayStats['total_value'], 2) ?></div>
                <div class="stat-label">มูลค่ารวม</div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions Widget -->
    <div class="widget widget-actions">
        <h3>⚡ Quick Actions</h3>
        <div class="widget-content">
            <button onclick="window.location.href='/scan'" class="quick-action-btn">
                📷 สแกนบาร์โค้ด
            </button>
            <button onclick="window.location.href='/dispensing/new'" class="quick-action-btn">
                💊 จ่ายยาด่วน
            </button>
            <button onclick="window.location.href='/orders/what-to-buy'" class="quick-action-btn">
                🛒 ซื้ออะไร?
            </button>
            <button onclick="window.location.href='/reports'" class="quick-action-btn">
                📊 รายงาน
            </button>
        </div>
    </div>
    
    <!-- Recent Activity Widget -->
    <div class="widget widget-activity">
        <h3>🕒 กิจกรรมล่าสุด</h3>
        <div class="widget-content">
            <?php
            $recentActivities = $dashboardModel->getRecentActivities(5);
            foreach ($recentActivities as $activity):
            ?>
            <div class="activity-item">
                <div class="activity-icon"><?= $activity['icon'] ?></div>
                <div class="activity-details">
                    <div class="activity-description"><?= $activity['description'] ?></div>
                    <div class="activity-time"><?= timeAgo($activity['created_at']) ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<style>
.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.widget {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    padding: 20px;
}

.widget h3 {
    margin: 0 0 15px 0;
    font-size: 18px;
}

.alert-item, .stat-item {
    padding: 10px 0;
    border-bottom: 1px solid #eee;
}

.alert-item:last-child, .stat-item:last-child {
    border-bottom: none;
}

.stat-value {
    font-size: 32px;
    font-weight: bold;
    color: #2196F3;
}

.stat-label {
    font-size: 14px;
    color: #666;
}

.quick-action-btn {
    display: block;
    width: 100%;
    padding: 12px;
    margin: 8px 0;
    background: #f5f5f5;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s;
}

.quick-action-btn:hover {
    background: #2196F3;
    color: white;
    transform: translateY(-2px);
}

.activity-item {
    display: flex;
    gap: 12px;
    padding: 10px 0;
    border-bottom: 1px solid #eee;
}

.activity-icon {
    font-size: 24px;
}

.activity-time {
    font-size: 12px;
    color: #999;
}
</style>
```

**เป้าหมาย Week 4:**
- ✅ Bulk operations
- ✅ Excel export
- ✅ Dashboard widgets
- ✅ Better UX

---

## 📊 สรุป Quick Wins (4 สัปดาห์)

### ผลลัพธ์ที่คาดหวัง

| สัปดาห์ | ฟีเจอร์ | ผลลัพธ์ |
|---------|---------|---------|
| Week 1 | Testing | 30+ tests, Coverage 30% |
| Week 2 | Performance | Response < 1s, Queries < 50ms |
| Week 3 | UX | Real-time search, Shortcuts |
| Week 4 | Features | Bulk ops, Excel export, Widgets |

### งบประมาณรวม
```
Week 1: $1,000 (Testing)
Week 2: $800 (Performance)
Week 3: $600 (UX)
Week 4: $800 (Features)

รวม: $3,200
```

### ROI
```
ลดเวลาการทำงาน: 20%
เพิ่มประสิทธิภาพ: 50%
ลดข้อผิดพลาด: 30%

Break-even: 2 เดือน
```

---

**ผู้จัดทำ:** Antigravity AI  
**วันที่:** 19 มกราคม 2569  
**เวอร์ชัน:** 1.0
