<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายการยาจาก JHCIS - Drugmuk</title>
    <?= \App\Core\CSRF::metaTag() ?>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Sarabun', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            font-size: 32px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-value {
            font-size: 48px;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
        }
        
        .stat-label {
            color: #666;
            font-size: 16px;
        }
        
        .controls {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .controls-row {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
        }
        
        .btn-info {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
        }
        
        .btn-warning {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
            color: white;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .advanced-filters {
            display: none;
            margin-top: 15px;
            padding: 20px;
            background: #f8f9ff;
            border-radius: 10px;
            border: 2px dashed #667eea;
        }
        
        .advanced-filters.show {
            display: block;
            animation: slideDown 0.3s ease-out;
        }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .filter-group {
            display: flex;
            gap: 15px;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .filter-group label {
            font-weight: 600;
            color: #333;
            min-width: 100px;
        }
        
        .filter-group input, .filter-group select {
            padding: 8px 12px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .quick-actions {
            position: fixed;
            bottom: 30px;
            right: 30px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            z-index: 100;
        }
        
        .quick-action-btn {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            border: none;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-size: 24px;
            cursor: pointer;
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
            transition: all 0.3s;
        }
        
        .quick-action-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.6);
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.show {
            display: flex;
        }
        
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 15px;
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            animation: modalSlideIn 0.3s ease-out;
        }
        
        @keyframes modalSlideIn {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .modal-header h3 {
            font-size: 24px;
            color: #333;
        }
        
        .close-btn {
            font-size: 28px;
            cursor: pointer;
            color: #999;
            transition: color 0.3s;
        }
        
        .close-btn:hover {
            color: #333;
        }
        
        .selection-info {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 15px;
            display: none;
            align-items: center;
            justify-content: space-between;
            animation: slideDown 0.3s ease-out;
        }
        
        .selection-info.show {
            display: flex;
        }
        
        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
            margin: 10px 0;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #11998e 0%, #38ef7d 100%);
            transition: width 0.3s ease;
        }
        
        .search-box {
            flex: 1;
            min-width: 300px;
        }
        
        .search-box input {
            width: 100%;
            padding: 12px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .search-box input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .filter-tabs {
            display: flex;
            gap: 10px;
        }
        
        .filter-tab {
            padding: 10px 20px;
            border: 2px solid #e0e0e0;
            background: white;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 600;
            font-size: 14px;
        }
        
        .filter-tab.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: transparent;
        }
        
        .table-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
        }
        
        th input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        
        tbody tr {
            border-bottom: 1px solid #f0f0f0;
            transition: background 0.2s;
        }
        
        tbody tr:hover {
            background: #f8f9ff;
        }
        
        td {
            padding: 15px;
            font-size: 14px;
        }
        
        .drug-code {
            color: #667eea;
            font-weight: 600;
        }
        
        .drug-name {
            font-weight: 500;
            color: #333;
        }
        
        .drug-generic {
            color: #666;
            font-size: 13px;
        }
        
        .price {
            color: #11998e;
            font-weight: 600;
        }
        
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        
        .status-imported {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-not-imported {
            background: #fef3c7;
            color: #92400e;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            padding: 20px;
        }
        
        .pagination button {
            padding: 8px 16px;
            border: 2px solid #e0e0e0;
            background: white;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .pagination button:hover:not(:disabled) {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        .pagination button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .pagination button.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        .back-btn {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
        }
        
        .back-btn:hover {
            background: #667eea;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>
                📋 รายการยาจาก JHCIS
            </h1>
            <a href="/admin/jhcis/dashboard" class="btn back-btn">
                ← กลับหน้าหลัก
            </a>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?= number_format($totalDrugs ?? 0) ?></div>
                <div class="stat-label">รายการทั้งหมด</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= number_format(count($mappedDrugs ?? [])) ?></div>
                <div class="stat-label">ยาที่ Import แล้ว</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">เก่า</div>
                <div class="stat-label">JHCIS เวอร์ชัน</div>
            </div>
        </div>

        <!-- Selection Info Bar -->
        <div class="selection-info" id="selectionInfo">
            <div>
                <strong id="selectedCount">0</strong> รายการที่เลือก
            </div>
            <div style="display: flex; gap: 10px;">
                <button class="btn btn-success" onclick="importSelected()">✅ Import</button>
                <button class="btn btn-warning" onclick="showBatchPriceUpdate()">💰 อัพเดทราคา</button>
                <button class="btn btn-danger" onclick="clearSelection()">✖ ยกเลิก</button>
            </div>
        </div>

        <!-- Controls -->
        <div class="controls">
            <div class="controls-row">
                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="🔍 ค้นหา (ชื่อยา, ชื่อสามัญ, รหัสยา)" value="<?= htmlspecialchars($search ?? '') ?>">
                </div>
                <button class="btn btn-primary" onclick="searchDrugs()">ค้นหา</button>
                <button class="btn btn-secondary" onclick="toggleAdvancedFilters()">🔧 ตัวกรองขั้นสูง</button>
                <button class="btn btn-success" onclick="importSelected()">✅ Import ที่เลือก</button>
                <button class="btn btn-info" onclick="exportExcel()">📊 Export Excel</button>
                <button class="btn btn-warning" onclick="autoMapping()">🤖 Auto Mapping</button>
            </div>
            
            <!-- Advanced Filters -->
            <div class="advanced-filters" id="advancedFilters">
                <div class="filter-group">
                    <label>ช่วงราคา:</label>
                    <input type="number" id="minPrice" placeholder="ราคาต่ำสุด" style="width: 120px;">
                    <span>-</span>
                    <input type="number" id="maxPrice" placeholder="ราคาสูงสุด" style="width: 120px;">
                    <span>บาท</span>
                </div>
                <div class="filter-group">
                    <label>หน่วย:</label>
                    <select id="unitFilter" style="width: 200px;">
                        <option value="">ทั้งหมด</option>
                        <option value="เม็ด">เม็ด</option>
                        <option value="แคปซูล">แคปซูล</option>
                        <option value="ขวด">ขวด</option>
                        <option value="หลอด">หลอด</option>
                        <option value="ซอง">ซอง</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>เรียงตาม:</label>
                    <select id="sortBy" style="width: 150px;">
                        <option value="name">ชื่อยา</option>
                        <option value="code">รหัสยา</option>
                        <option value="price-asc">ราคา (น้อย-มาก)</option>
                        <option value="price-desc">ราคา (มาก-น้อย)</option>
                    </select>
                </div>
                <div class="filter-group">
                    <button class="btn btn-primary" onclick="applyAdvancedFilters()">✓ ใช้ตัวกรอง</button>
                    <button class="btn btn-secondary" onclick="resetFilters()">↺ รีเซ็ต</button>
                </div>
            </div>
            
            <div class="controls-row" style="margin-top: 15px;">
                <div class="filter-tabs">
                    <div class="filter-tab active" onclick="filterDrugs('all')">ทั้งหมด (<?= number_format($totalDrugs ?? 0) ?>)</div>
                    <div class="filter-tab" onclick="filterDrugs('imported')">Import แล้ว (<?= number_format(count($mappedDrugs ?? [])) ?>)</div>
                    <div class="filter-tab" onclick="filterDrugs('not-imported')">ยังไม่ Import (<?= number_format(($totalDrugs ?? 0) - count($mappedDrugs ?? [])) ?>)</div>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th width="50">
                            <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                        </th>
                        <th>รหัสยา ↕</th>
                        <th>ชื่อยา ↕</th>
                        <th>ชื่อสามัญ ↕</th>
                        <th>หน่วย ↕</th>
                        <th>ราคา ↕</th>
                        <th>สถานะ</th>
                    </tr>
                </thead>
                <tbody id="drugTableBody">
                    <?php if (!empty($jhcisDrugs)): ?>
                        <?php foreach ($jhcisDrugs as $drug): ?>
                            <?php $isImported = isset($mappedDrugs[$drug['drugcode']]); ?>
                            <tr data-imported="<?= $isImported ? '1' : '0' ?>">
                                <td>
                                    <input type="checkbox" class="drug-checkbox" value="<?= htmlspecialchars($drug['drugcode']) ?>" 
                                           data-name="<?= htmlspecialchars($drug['name'] ?? '') ?>"
                                           data-generic="<?= htmlspecialchars($drug['genericname'] ?? '') ?>"
                                           data-unit="<?= htmlspecialchars($drug['units'] ?? '') ?>"
                                           data-price="<?= htmlspecialchars($drug['unitprice'] ?? 0) ?>">
                                </td>
                                <td class="drug-code"><?= htmlspecialchars($drug['drugcode']) ?></td>
                                <td class="drug-name"><?= htmlspecialchars($drug['name'] ?? '-') ?></td>
                                <td class="drug-generic"><?= htmlspecialchars($drug['genericname'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($drug['units'] ?? '-') ?></td>
                                <td class="price"><?= number_format($drug['unitprice'] ?? 0, 2) ?> บาท</td>
                                <td>
                                    <?php if ($isImported): ?>
                                        <span class="status-badge status-imported">✓ ยัง Import</span>
                                    <?php else: ?>
                                        <span class="status-badge status-not-imported">⚠ ยัง Import</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px; color: #999;">
                                ไม่พบข้อมูลยา
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <?php if (($totalPages ?? 0) > 1): ?>
                <div class="pagination">
                    <button onclick="goToPage(<?= max(1, ($page ?? 1) - 1) ?>)" <?= ($page ?? 1) <= 1 ? 'disabled' : '' ?>>
                        ← ก่อนหน้า
                    </button>
                    
                    <?php 
                    $startPage = max(1, ($page ?? 1) - 2);
                    $endPage = min($totalPages, $startPage + 4);
                    if ($endPage - $startPage < 4) {
                        $startPage = max(1, $endPage - 4);
                    }
                    
                    if ($startPage > 1): ?>
                        <button onclick="goToPage(1)">1</button>
                        <?php if ($startPage > 2): ?><span>...</span><?php endif; ?>
                    <?php endif; ?>

                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <button onclick="goToPage(<?= $i ?>)" class="<?= $i == ($page ?? 1) ? 'active' : '' ?>">
                            <?= $i ?>
                        </button>
                    <?php endfor; ?>
                    
                    <?php if ($endPage < $totalPages): ?>
                        <?php if ($endPage < $totalPages - 1): ?><span>...</span><?php endif; ?>
                        <button onclick="goToPage(<?= $totalPages ?>)"><?= $totalPages ?></button>
                    <?php endif; ?>
                    
                    <button onclick="goToPage(<?= min($totalPages, ($page ?? 1) + 1) ?>)" <?= ($page ?? 1) >= $totalPages ? 'disabled' : '' ?>>
                        ถัดไป →
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Batch Price Update Modal -->
    <div class="modal" id="batchPriceModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>💰 อัพเดทราคาแบบกลุ่ม</h3>
                <span class="close-btn" onclick="closeBatchPriceModal()">×</span>
            </div>
            <div>
                <p style="margin-bottom: 15px;">อัพเดทราคาสำหรับ <strong id="batchCount">0</strong> รายการที่เลือก</p>
                <div class="filter-group">
                    <label>วิธีการ:</label>
                    <select id="priceMethod" style="width: 200px;">
                        <option value="set">กำหนดราคาใหม่</option>
                        <option value="increase">เพิ่มราคา (%)</option>
                        <option value="decrease">ลดราคา (%)</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>ค่า:</label>
                    <input type="number" id="priceValue" placeholder="ใส่ค่า" style="width: 200px;">
                </div>
                <div style="margin-top: 20px; display: flex; gap: 10px;">
                    <button class="btn btn-success" onclick="applyBatchPrice()">✓ ยืนยัน</button>
                    <button class="btn btn-secondary" onclick="closeBatchPriceModal()">✖ ยกเลิก</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Preview Modal -->
    <div class="modal" id="previewModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>👁️ ดูรายละเอียดยา</h3>
                <span class="close-btn" onclick="closePreviewModal()">×</span>
            </div>
            <div id="previewContent">
                <!-- Content will be loaded dynamically -->
            </div>
        </div>
    </div>

    <!-- Auto Mapping Progress Modal -->
    <div class="modal" id="autoMappingModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>🤖 Auto Mapping กำลังทำงาน...</h3>
            </div>
            <div>
                <p>กำลังวิเคราะห์และแนะนำการ mapping อัตโนมัติ</p>
                <div class="progress-bar">
                    <div class="progress-fill" id="mappingProgress" style="width: 0%"></div>
                </div>
                <p id="mappingStatus">กำลังเริ่มต้น...</p>
            </div>
        </div>
    </div>

    <!-- Quick Actions Floating Buttons -->
    <div class="quick-actions">
        <button class="quick-action-btn" onclick="scrollToTop()" title="กลับด้านบน">
            ↑
        </button>
        <button class="quick-action-btn" onclick="refreshData()" title="รีเฟรชข้อมูล">
            ↻
        </button>
        <button class="quick-action-btn" onclick="showHelp()" title="ช่วยเหลือ">
            ?
        </button>
    </div>

    <script>
        let currentFilter = 'all';
        let selectedDrugs = new Set();

        // Update selection info
        function updateSelectionInfo() {
            const count = selectedDrugs.size;
            document.getElementById('selectedCount').textContent = count;
            document.getElementById('selectionInfo').classList.toggle('show', count > 0);
        }

        // Toggle select all
        function toggleSelectAll() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.drug-checkbox');
            
            checkboxes.forEach(cb => {
                const row = cb.closest('tr');
                if (row.style.display !== 'none') {
                    cb.checked = selectAll.checked;
                    if (selectAll.checked) {
                        selectedDrugs.add(cb.value);
                    } else {
                        selectedDrugs.delete(cb.value);
                    }
                }
            });
            updateSelectionInfo();
        }

        // Handle individual checkbox change
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('drug-checkbox')) {
                if (e.target.checked) {
                    selectedDrugs.add(e.target.value);
                } else {
                    selectedDrugs.delete(e.target.value);
                }
                updateSelectionInfo();
            }
        });

        // Search drugs
        function searchDrugs() {
            const search = document.getElementById('searchInput').value;
            window.location.href = `/jhcis-drugs?search=${encodeURIComponent(search)}`;
        }

        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchDrugs();
            }
        });

        // Toggle advanced filters
        function toggleAdvancedFilters() {
            document.getElementById('advancedFilters').classList.toggle('show');
        }

        // Apply advanced filters
        function applyAdvancedFilters() {
            const minPrice = parseFloat(document.getElementById('minPrice').value) || 0;
            const maxPrice = parseFloat(document.getElementById('maxPrice').value) || Infinity;
            const unit = document.getElementById('unitFilter').value;
            const sortBy = document.getElementById('sortBy').value;

            const rows = Array.from(document.querySelectorAll('#drugTableBody tr'));
            
            // Filter
            rows.forEach(row => {
                const price = parseFloat(row.cells[5].textContent.replace(/[^0-9.]/g, '')) || 0;
                const rowUnit = row.cells[4].textContent.trim();
                
                const priceMatch = price >= minPrice && price <= maxPrice;
                const unitMatch = !unit || rowUnit.includes(unit);
                
                if (priceMatch && unitMatch && row.style.display !== 'none') {
                    row.style.display = '';
                } else if (!priceMatch || !unitMatch) {
                    row.style.display = 'none';
                }
            });

            // Sort
            const tbody = document.getElementById('drugTableBody');
            const sortedRows = rows.sort((a, b) => {
                if (a.style.display === 'none' || b.style.display === 'none') return 0;
                
                switch(sortBy) {
                    case 'name':
                        return a.cells[2].textContent.localeCompare(b.cells[2].textContent, 'th');
                    case 'code':
                        return a.cells[1].textContent.localeCompare(b.cells[1].textContent);
                    case 'price-asc':
                        return parseFloat(a.cells[5].textContent.replace(/[^0-9.]/g, '')) - 
                               parseFloat(b.cells[5].textContent.replace(/[^0-9.]/g, ''));
                    case 'price-desc':
                        return parseFloat(b.cells[5].textContent.replace(/[^0-9.]/g, '')) - 
                               parseFloat(a.cells[5].textContent.replace(/[^0-9.]/g, ''));
                    default:
                        return 0;
                }
            });

            sortedRows.forEach(row => tbody.appendChild(row));
        }

        // Reset filters
        function resetFilters() {
            document.getElementById('minPrice').value = '';
            document.getElementById('maxPrice').value = '';
            document.getElementById('unitFilter').value = '';
            document.getElementById('sortBy').value = 'name';
            
            document.querySelectorAll('#drugTableBody tr').forEach(row => {
                row.style.display = '';
            });
            
            filterDrugs(currentFilter);
        }

        // Filter drugs by status
        function filterDrugs(filter) {
            currentFilter = filter;
            const tabs = document.querySelectorAll('.filter-tab');
            tabs.forEach((tab, index) => {
                tab.classList.remove('active');
                if ((filter === 'all' && index === 0) ||
                    (filter === 'imported' && index === 1) ||
                    (filter === 'not-imported' && index === 2)) {
                    tab.classList.add('active');
                }
            });

            const rows = document.querySelectorAll('#drugTableBody tr');
            rows.forEach(row => {
                const isImported = row.dataset.imported === '1';
                if (filter === 'all') {
                    row.style.display = '';
                } else if (filter === 'imported' && isImported) {
                    row.style.display = '';
                } else if (filter === 'not-imported' && !isImported) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Import selected drugs
        async function importSelected() {
            const checkboxes = document.querySelectorAll('.drug-checkbox:checked');
            if (checkboxes.length === 0) {
                alert('กรุณาเลือกยาที่ต้องการ Import');
                return;
            }

            if (!confirm(`ต้องการ Import ยา ${checkboxes.length} รายการ?`)) {
                return;
            }

            const drugs = Array.from(checkboxes).map(cb => ({
                drugcode: cb.value,
                name: cb.dataset.name,
                genericname: cb.dataset.generic,
                units: cb.dataset.unit,
                unitprice: cb.dataset.price
            }));

            try {
                const response = await fetch('/jhcis-import/bulk-import', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    },
                    body: JSON.stringify({ drugs })
                });

                const data = await response.json();
                if (data.success) {
                    alert(`✅ ${data.message}`);
                    location.reload();
                } else {
                    alert(`❌ ${data.message}`);
                }
            } catch (error) {
                alert('❌ เกิดข้อผิดพลาด: ' + error.message);
            }
        }

        // Clear selection
        function clearSelection() {
            document.querySelectorAll('.drug-checkbox').forEach(cb => cb.checked = false);
            document.getElementById('selectAll').checked = false;
            selectedDrugs.clear();
            updateSelectionInfo();
        }

        // Show batch price update modal
        function showBatchPriceUpdate() {
            if (selectedDrugs.size === 0) {
                alert('กรุณาเลือกยาที่ต้องการอัพเดทราคา');
                return;
            }
            document.getElementById('batchCount').textContent = selectedDrugs.size;
            document.getElementById('batchPriceModal').classList.add('show');
        }

        function closeBatchPriceModal() {
            document.getElementById('batchPriceModal').classList.remove('show');
        }

        async function applyBatchPrice() {
            const method = document.getElementById('priceMethod').value;
            const value = parseFloat(document.getElementById('priceValue').value);
            
            if (isNaN(value)) {
                alert('กรุณาใส่ค่าที่ถูกต้อง');
                return;
            }

            if (!confirm(`ยืนยันการอัพเดทราคา ${selectedDrugs.size} รายการ?`)) {
                return;
            }

            const btn = document.querySelector('#batchPriceModal .btn-success');
            btn.disabled = true;
            btn.textContent = '⏳ กำลังอัพเดท...';

            try {
                const response = await fetch('/jhcis-drugs/batch-price', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    },
                    body: JSON.stringify({
                        drug_codes: Array.from(selectedDrugs),
                        method: method,
                        value: value
                    })
                });

                const data = await response.json();
                if (data.success) {
                    alert('✅ ' + data.message);
                    location.reload();
                } else {
                    alert('❌ ' + data.message);
                }
            } catch (error) {
                alert('❌ เกิดข้อผิดพลาด: ' + error.message);
            } finally {
                btn.disabled = false;
                btn.textContent = '✓ ยืนยัน';
                closeBatchPriceModal();
            }
        }

        // Auto mapping
        async function autoMapping() {
            document.getElementById('autoMappingModal').classList.add('show');
            const progress = document.getElementById('mappingProgress');
            const status = document.getElementById('mappingStatus');
            
            progress.style.width = '30%';
            status.textContent = '🔍 กำลังวิเคราะห์ข้อมูลยา...';

            try {
                const response = await fetch('/jhcis-drugs/auto-mapping');
                const data = await response.json();

                progress.style.width = '80%';
                status.textContent = '✨ คำนวณความคล้ายคลึงของชื่อ...';

                if (data.success && data.suggestions.length > 0) {
                    let matchedCount = 0;
                    data.suggestions.forEach(suggestion => {
                        const checkbox = document.querySelector(`.drug-checkbox[value="${suggestion.jhcis_code}"]`);
                        if (checkbox) {
                            checkbox.checked = true;
                            selectedDrugs.add(suggestion.jhcis_code);
                            const row = checkbox.closest('tr');
                            row.style.background = '#f0fff4'; // Light green for matches
                            matchedCount++;
                        }
                    });

                    progress.style.width = '100%';
                    status.textContent = '✅ วิเคราะห์เสร็จสิ้น!';
                    
                    setTimeout(() => {
                        document.getElementById('autoMappingModal').classList.remove('show');
                        updateSelectionInfo();
                        alert(`🤖 Auto Mapping เสร็จสิ้น!\n\nพบรายการที่ตรงกัน ${matchedCount} รายการ\nกรุณาตรวจสอบและกด "Import" เพื่อบันทึกเข้าสู่ระบบ`);
                    }, 800);
                } else {
                    document.getElementById('autoMappingModal').classList.remove('show');
                    alert('🤖 ไม่พบรายการที่สามารถ Map อัตโนมัติได้เพิ่มเติม');
                }
            } catch (error) {
                document.getElementById('autoMappingModal').classList.remove('show');
                alert('❌ เกิดข้อผิดพลาด: ' + error.message);
            }
        }

        // Export Excel
        function exportExcel() {
            const search = document.getElementById('searchInput').value;
            window.location.href = `/jhcis-drugs/export?search=${encodeURIComponent(search)}`;
        }

        // Go to page
        function goToPage(page) {
            const search = document.getElementById('searchInput').value;
            window.location.href = `/jhcis-drugs?page=${page}&search=${encodeURIComponent(search)}`;
        }

        // Quick actions
        function scrollToTop() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function refreshData() {
            location.reload();
        }

        function showHelp() {
            alert(`📚 คู่มือการใช้งาน

🔍 ค้นหา: ค้นหายาด้วยชื่อ, ชื่อสามัญ, หรือรหัสยา
🔧 ตัวกรองขั้นสูง: กรองตามราคา, หน่วย, และเรียงลำดับ
✅ Import: เลือกยาแล้วกด Import เพื่อนำเข้าสู่ระบบ
💰 อัพเดทราคา: อัพเดทราคาหลายรายการพร้อมกัน
🤖 Auto Mapping: ให้ระบบแนะนำการ mapping อัตโนมัติ
📊 Export: ส่งออกข้อมูลเป็น Excel`);
        }

        function closePreviewModal() {
            document.getElementById('previewModal').classList.remove('show');
        }
    </script>
</body>
</html>
