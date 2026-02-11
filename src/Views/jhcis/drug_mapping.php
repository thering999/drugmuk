<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Drug Code Mapping - ระบบบริหารคลังยา</title>
    <?= \App\Core\CSRF::metaTag() ?>
    <script>
        // CSRF Interceptor - Must be early in the head
        (function() {
            const originalFetch = window.fetch;
            window.fetch = function(url, options = {}) {
                const method = (options.method || 'GET').toUpperCase();
                if (method !== 'GET') {
                    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                    if (token) {
                        options.headers = options.headers || {};
                        options.headers['X-CSRF-Token'] = token;
                    }
                }
                return originalFetch(url, options);
            };
        })();
    </script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
            font-weight: 600;
        }

        .back-button:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateX(-5px);
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            padding: 20px 30px;
            border-radius: 15px;
            margin-bottom: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .header h1 {
            color: #667eea;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .header p {
            color: #666;
            font-size: 14px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card h3 {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .stat-card .value {
            font-size: 36px;
            font-weight: bold;
            color: #667eea;
        }

        .stat-card.warning .value {
            color: #f59e0b;
        }

        .stat-card.success .value {
            color: #10b981;
        }

        .actions {
            background: rgba(255, 255, 255, 0.95);
            padding: 20px 30px;
            border-radius: 15px;
            margin-bottom: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
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
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(16, 185, 129, 0.4);
        }

        .btn-warning {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
        }

        .btn-warning:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(245, 158, 11, 0.4);
        }

        .table-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            overflow-x: auto;
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .table-header h2 {
            color: #333;
            font-size: 20px;
        }

        .search-box {
            padding: 10px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            width: 300px;
            transition: border-color 0.3s;
        }

        .search-box:focus {
            outline: none;
            border-color: #667eea;
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
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 14px;
        }

        tbody tr:hover {
            background: #f9fafb;
        }

        .badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }

        .badge-exact {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-equivalent {
            background: #dbeafe;
            color: #1e40af;
        }

        .badge-manual {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-unmapped {
            background: #fee2e2;
            color: #991b1b;
        }

        .status-card {
            display: flex;
            align-items: center;
            gap: 15px;
            background: rgba(255, 255, 255, 0.95);
            padding: 15px 25px;
            border-radius: 15px;
            margin-bottom: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .status-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
        }

        .status-dot.online { background: #10b981; box-shadow: 0 0 10px #10b981; }
        .status-dot.offline { background: #ef4444; box-shadow: 0 0 10px #ef4444; }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 15px;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            margin-bottom: 20px;
        }

        .modal-header h3 {
            color: #333;
            font-size: 24px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #374151;
            font-weight: 600;
            font-size: 14px;
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
        }

        select.form-control {
            cursor: pointer;
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .spinner {
            border: 3px solid #f3f4f6;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }

        .alert-info {
            background: #dbeafe;
            color: #1e40af;
            border-left: 4px solid #3b82f6;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Back Button -->
        <a href="/admin/jhcis/dashboard" class="back-button">
            <span style="font-size: 1.2rem;">←</span>
            <span>กลับไปหน้า Dashboard</span>
        </a>
        
        <!-- Header -->
        <div class="header">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1>🔗 Drug Code Mapping</h1>
                    <p>จัดการการจับคู่รหัสยาระหว่างระบบ Drugmuk และ JHCIS</p>
                </div>
                <div id="connection-status-widget" class="status-card" style="margin-bottom: 0;">
                    <div class="status-dot offline" id="conn-dot"></div>
                    <div>
                        <div id="conn-text" style="font-weight: bold; font-size: 14px; color: #ef4444;">JHCIS: กำลังตรวจสอบ...</div>
                        <div id="hospital-name" style="font-size: 12px; color: #666;">-</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card success">
                <h3>ยาที่จับคู่แล้ว</h3>
                <div class="value" id="stat-mapped">-</div>
            </div>
            <div class="stat-card warning">
                <h3>ยาที่ยังไม่ได้จับคู่</h3>
                <div class="value" id="stat-unmapped">-</div>
            </div>
            <div class="stat-card">
                <h3>อัตราการจับคู่</h3>
                <div class="value" id="stat-rate">-</div>
            </div>
            <div class="stat-card">
                <h3>ยาทั้งหมดในระบบ</h3>
                <div class="value" id="stat-total">-</div>
            </div>
        </div>

        <!-- Actions -->
        <div class="actions">
            <button class="btn btn-success" onclick="autoMap()">
                ⚡ จับคู่อัตโนมัติ (AI)
            </button>
            <button class="btn btn-primary" onclick="showManualMapModal()">
                ➕ จับคู่ด้วยตนเอง
            </button>
            <button class="btn btn-warning" onclick="showUnmappedDrugs()">
                ⚠️ ดูยาที่ยังไม่ได้จับคู่
            </button>
            <a href="/admin/jhcis/dashboard" class="btn btn-primary">
                ← กลับหน้า Dashboard
            </a>
        </div>

        <!-- Alert -->
        <div id="alert-container"></div>

        <!-- Table -->
        <div class="table-container">
            <div class="table-header">
                <h2>รายการการจับคู่ยา</h2>
                <input type="text" class="search-box" id="search" placeholder="🔍 ค้นหาชื่อยาหรือรหัส..." onkeyup="filterTable()">
            </div>

            <div id="loading" class="loading">
                <div class="spinner"></div>
                <p>กำลังโหลดข้อมูล...</p>
            </div>

            <table id="mapping-table" style="display: none;">
                <thead>
                    <tr>
                        <th>รหัสยา JHCIS</th>
                        <th>ยาในระบบ Drugmuk</th>
                        <th>ประเภทการจับคู่</th>
                        <th>ความเชื่อมั่น</th>
                        <th>ผู้จับคู่</th>
                        <th>วันที่จับคู่</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody id="mapping-tbody">
                </tbody>
            </table>
        </div>
    </div>

    <!-- Manual Mapping Modal -->
    <div class="modal" id="manual-map-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>จับคู่ยาด้วยตนเอง</h3>
            </div>
            <form id="manual-map-form" onsubmit="submitManualMap(event)">
                <div class="form-group">
                    <label for="jhcis-drugcode">รหัสยา JHCIS *</label>
                    <input type="text" class="form-control" id="jhcis-drugcode" required placeholder="ป้อนรหัสยาจาก JHCIS">
                </div>
                <div class="form-group">
                    <label for="drugmuk-drug-id">ยาในระบบ Drugmuk *</label>
                    <select class="form-control" id="drugmuk-drug-id" required>
                        <option value="">-- เลือกรายการยาในระบบ --</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="mapping-type">ประเภทการจับคู่ *</label>
                    <select class="form-control" id="mapping-type" required>
                        <option value="exact">ตรงกันทุกประการ (Exact)</option>
                        <option value="equivalent">เทียบเท่า (Equivalent)</option>
                        <option value="manual">กำหนดเอง (Manual)</option>
                    </select>
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="btn" onclick="closeModal()">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary">บันทึกการจับคู่</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Load data on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadStats();
            loadMappings();
            loadDrugsList();
        });

        // Load statistics
        async function loadStats() {
            try {
                // Get hospital_id from URL
                const urlParams = new URLSearchParams(window.location.search);
                const hospitalId = urlParams.get('hospital_id');
                const apiUrl = hospitalId ? `/api/jhcis/mapping/stats?hospital_id=${hospitalId}` : '/api/jhcis/mapping/stats';
                
                const response = await fetch(apiUrl);
                const data = await response.json();
                
                document.getElementById('stat-mapped').textContent = data.mapped || 0;
                document.getElementById('stat-unmapped').textContent = data.unmapped || 0;
                document.getElementById('stat-total').textContent = data.total || 0;
                
                const rate = data.total > 0 ? Number((data.mapped / data.total) * 100).toFixed(1) : 0;
                document.getElementById('stat-rate').textContent = rate + '%';

                // Update connection status
                if (data.connection) {
                    const dot = document.getElementById('conn-dot');
                    const text = document.getElementById('conn-text');
                    const hosp = document.getElementById('hospital-name');
                    
                    if (data.connection.status) {
                        dot.className = 'status-dot online';
                        text.textContent = 'JHCIS: เชื่อมต่อแล้ว';
                        text.style.color = '#10b981';
                    } else {
                        dot.className = 'status-dot offline';
                        text.textContent = 'JHCIS: การเชื่อมต่อขัดข้อง';
                        text.style.color = '#ef4444';
                    }
                    hosp.textContent = data.connection.hospital_name || '-';
                    
                    // If hospital_id wasn't in URL, add it
                    if (!hospitalId && data.connection.hospital_id) {
                        const newUrl = window.location.pathname + '?hospital_id=' + data.connection.hospital_id;
                        window.history.replaceState({path: newUrl}, '', newUrl);
                    }
                }
            } catch (error) {
                console.error('Error loading stats:', error);
            }
        }

        // Load mappings
        async function loadMappings() {
            try {
                const response = await fetch('/api/jhcis/mapping/drugs');
                const data = await response.json();
                
                const tbody = document.getElementById('mapping-tbody');
                tbody.innerHTML = '';
                
                data.forEach(mapping => {
                    let typeLabel = mapping.mapping_type;
                    if (mapping.mapping_type === 'exact') typeLabel = 'ตรงกันทุกประการ';
                    if (mapping.mapping_type === 'equivalent') typeLabel = 'เทียบเท่า';
                    if (mapping.mapping_type === 'manual') typeLabel = 'กำหนดเอง';

                    const row = `
                        <tr>
                            <td><strong>${mapping.jhcis_drug_code}</strong></td>
                            <td>${mapping.drug_name} (${mapping.drug_code})</td>
                            <td><span class="badge badge-${mapping.mapping_type}">${typeLabel}</span></td>
                            <td>${Number(mapping.confidence_score * 100).toFixed(0)}%</td>
                            <td>${mapping.mapped_by_name || '-'}</td>
                            <td>${formatDate(mapping.mapped_at)}</td>
                            <td>
                                <button class="btn btn-sm btn-warning" onclick="editMapping(${mapping.id})">แก้ไข</button>
                                <button class="btn btn-sm" onclick="deleteMapping(${mapping.id})" style="background: #ef4444; color: white;">ลบ</button>
                            </td>
                        </tr>
                    `;
                    tbody.innerHTML += row;
                });
                
                document.getElementById('loading').style.display = 'none';
                document.getElementById('mapping-table').style.display = 'table';
            } catch (error) {
                console.error('Error loading mappings:', error);
                showAlert('Error loading mappings', 'error');
            }
        }

        // Load drugs list for dropdown
        async function loadDrugsList() {
            try {
                const response = await fetch('/api/drugs');
                const drugs = await response.json();
                
                const select = document.getElementById('drugmuk-drug-id');
                drugs.forEach(drug => {
                    const option = document.createElement('option');
                    option.value = drug.id;
                    option.textContent = `${drug.name} (${drug.code})`;
                    select.appendChild(option);
                });
            } catch (error) {
                console.error('Error loading drugs:', error);
            }
        }

        // Auto-mapping
        async function autoMap() {
            if (!confirm('ต้องการทำ Auto-Mapping หรือไม่? ระบบจะจับคู่รหัสยาที่ตรงกันโดยอัตโนมัติ')) {
                return;
            }
            
            showAlert('กำลังทำ Auto-Mapping...', 'info');
            
            try {
                const response = await fetch('/api/jhcis/mapping/auto-map', {
                    method: 'POST'
                });
                const result = await response.json();
                
                if (result.success) {
                    showAlert(`Auto-Mapping สำเร็จ! จับคู่ได้ ${result.mapped_count} รายการ`, 'success');
                    loadStats();
                    loadMappings();
                } else {
                    showAlert('Auto-Mapping ล้มเหลว: ' + result.message, 'error');
                }
            } catch (error) {
                console.error('Error auto-mapping:', error);
                showAlert('เกิดข้อผิดพลาดในการ Auto-Mapping', 'error');
            }
        }

        // Show manual mapping modal
        function showManualMapModal() {
            document.getElementById('manual-map-modal').classList.add('active');
        }

        // Close modal
        function closeModal() {
            document.getElementById('manual-map-modal').classList.remove('active');
            document.getElementById('manual-map-form').reset();
            
            // Reset modal title
            document.querySelector('#manual-map-modal .modal-header h3').textContent = 'จับคู่ยาด้วยตนเอง';
            
            // Reset form submit handler
            document.getElementById('manual-map-form').onsubmit = submitManualMap;
        }

        // Submit manual mapping
        async function submitManualMap(event) {
            event.preventDefault();
            
            const formData = {
                jhcis_drug_code: document.getElementById('jhcis-drugcode').value,
                drugmuk_drug_id: document.getElementById('drugmuk-drug-id').value,
                mapping_type: document.getElementById('mapping-type').value
            };
            
            try {
                const response = await fetch('/api/jhcis/mapping/drugs', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('บันทึก Mapping สำเร็จ!', 'success');
                    closeModal();
                    loadStats();
                    loadMappings();
                } else {
                    showAlert('บันทึกล้มเหลว: ' + result.message, 'error');
                }
            } catch (error) {
                console.error('Error saving mapping:', error);
                showAlert('เกิดข้อผิดพลาดในการบันทึก', 'error');
            }
        }

        // Show unmapped drugs
        function showUnmappedDrugs() {
            window.location.href = '/admin/jhcis/unmapped-drugs';
        }

        // Filter table
        function filterTable() {
            const input = document.getElementById('search');
            const filter = input.value.toUpperCase();
            const table = document.getElementById('mapping-table');
            const tr = table.getElementsByTagName('tr');
            
            for (let i = 1; i < tr.length; i++) {
                const td = tr[i].getElementsByTagName('td');
                let found = false;
                
                for (let j = 0; j < td.length; j++) {
                    if (td[j]) {
                        const txtValue = td[j].textContent || td[j].innerText;
                        if (txtValue.toUpperCase().indexOf(filter) > -1) {
                            found = true;
                            break;
                        }
                    }
                }
                
                tr[i].style.display = found ? '' : 'none';
            }
        }

        // Show alert
        function showAlert(message, type = 'info') {
            const container = document.getElementById('alert-container');
            const alert = document.createElement('div');
            alert.className = `alert alert-${type}`;
            alert.textContent = message;
            container.innerHTML = '';
            container.appendChild(alert);
            
            setTimeout(() => {
                alert.remove();
            }, 5000);
        }

        // Format date
        function formatDate(dateString) {
            if (!dateString) return '-';
            const date = new Date(dateString);
            return date.toLocaleDateString('th-TH', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        // Edit mapping
        async function editMapping(id) {
            try {
                // Fetch mapping details
                const response = await fetch(`/api/jhcis/mapping/drugs/${id}`);
                const mapping = await response.json();
                
                if (mapping.success === false) {
                    showAlert('ไม่พบข้อมูล Mapping', 'error');
                    return;
                }
                
                // Populate form with existing data
                document.getElementById('jhcis-drugcode').value = mapping.jhcis_drug_code || '';
                document.getElementById('drugmuk-drug-id').value = mapping.drugmuk_drug_id || '';
                document.getElementById('mapping-type').value = mapping.mapping_type || 'manual';
                
                // Change form submit to update instead of create
                const form = document.getElementById('manual-map-form');
                form.onsubmit = async function(event) {
                    event.preventDefault();
                    
                    const formData = {
                        jhcis_drug_code: document.getElementById('jhcis-drugcode').value,
                        drugmuk_drug_id: document.getElementById('drugmuk-drug-id').value,
                        mapping_type: document.getElementById('mapping-type').value
                    };
                    
                    try {
                        const updateResponse = await fetch(`/api/jhcis/mapping/drugs/${id}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({...formData, _method: 'PUT'})
                        });
                        
                        const result = await updateResponse.json();
                        
                        if (result.success) {
                            showAlert('อัปเดต Mapping สำเร็จ!', 'success');
                            closeModal();
                            loadStats();
                            loadMappings();
                            
                            // Reset form submit handler
                            form.onsubmit = submitManualMap;
                        } else {
                            showAlert('อัปเดตล้มเหลว: ' + result.message, 'error');
                        }
                    } catch (error) {
                        console.error('Error updating mapping:', error);
                        showAlert('เกิดข้อผิดพลาดในการอัปเดต', 'error');
                    }
                };
                
                // Show modal
                document.getElementById('manual-map-modal').classList.add('active');
                
                // Change modal title
                document.querySelector('#manual-map-modal .modal-header h3').textContent = 'แก้ไขการจับคู่ยา';
                
            } catch (error) {
                console.error('Error loading mapping for edit:', error);
                showAlert('เกิดข้อผิดพลาดในการโหลดข้อมูล', 'error');
            }
        }

        // Delete mapping
        async function deleteMapping(id) {
            if (!confirm('ต้องการลบ Mapping นี้หรือไม่?')) {
                return;
            }
            
            try {
                const response = await fetch(`/api/jhcis/mapping/drugs/${id}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: '_method=DELETE'
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('ลบ Mapping สำเร็จ!', 'success');
                    loadStats();
                    loadMappings();
                } else {
                    showAlert('ลบล้มเหลว: ' + result.message, 'error');
                }
            } catch (error) {
                console.error('Error deleting mapping:', error);
                showAlert('เกิดข้อผิดพลาดในการลบ', 'error');
            }
        }
    </script>
</body>
</html>
