<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unmapped Drugs - รายการยาที่ยังไม่ได้จับคู่</title>
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
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            padding: 20px 30px;
            border-radius: 15px;
            margin-bottom: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .header h1 {
            color: #d97706;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .header p {
            color: #666;
            font-size: 14px;
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
            border-color: #d97706;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
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
            background: #fef3c7;
        }

        .badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
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

        .loading {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .spinner {
            border: 3px solid #f3f4f6;
            border-top: 3px solid #d97706;
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

        .alert-warning {
            background: #fef3c7;
            color: #92400e;
            border-left: 4px solid #f59e0b;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .empty-state h3 {
            font-size: 24px;
            margin-bottom: 10px;
            color: #333;
        }

        .empty-state p {
            font-size: 16px;
            margin-bottom: 20px;
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
            border-color: #d97706;
        }

        select.form-control {
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1>⚠️ ยาที่ยังไม่ได้จับคู่ (Unmapped)</h1>
                    <p>รายการยาในระบบ Drugmuk ที่ยังไม่ได้จับคู่รหัสกับ JHCIS</p>
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

        <!-- Actions -->
        <div class="actions">
            <button class="btn btn-success" onclick="autoMapAll()">
                ⚡ จับคู่อัตโนมัติทั้งหมด (AI)
            </button>
            <a href="/admin/jhcis/mapping" class="btn btn-primary">
                ← กลับไปหน้า Mapping
            </a>
        </div>

        <!-- Alert -->
        <div id="alert-container"></div>

        <!-- Table -->
        <div class="table-container">
            <div class="table-header">
                <h2>รายการยาที่ยังไม่ถูกจับคู่</h2>
                <input type="text" class="search-box" id="search" placeholder="🔍 ค้นหาชื่อยาหรือรหัส..." onkeyup="filterTable()">
            </div>

            <div id="loading" class="loading">
                <div class="spinner"></div>
                <p>กำลังโหลดข้อมูล...</p>
            </div>

            <div id="empty-state" class="empty-state" style="display: none;">
                <h3>🎉 ยอดเยี่ยม!</h3>
                <p>ไม่มียาที่ยังไม่ได้ map แล้ว</p>
                <a href="/admin/jhcis/mapping" class="btn btn-primary">กลับไปหน้า Mapping</a>
            </div>

            <table id="unmapped-table" style="display: none;">
                <thead>
                    <tr>
                        <th>รหัสยา</th>
                        <th>ชื่อยา</th>
                        <th>ชื่อสามัญ</th>
                        <th>หน่วย</th>
                        <th>สถานะ</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody id="unmapped-tbody">
                </tbody>
            </table>
        </div>
    </div>

    <!-- Quick Map Modal -->
    <div class="modal" id="quick-map-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>จับคู่ชื่อยาด่วน</h3>
            </div>
            <form id="quick-map-form" onsubmit="submitQuickMap(event)">
                <input type="hidden" id="drug-id">
                <div class="form-group">
                    <label>ชื่อยา</label>
                    <input type="text" class="form-control" id="drug-name" readonly>
                </div>
                <div class="form-group">
                    <label for="jhcis-code">รหัสยา JHCIS *</label>
                    <input type="text" class="form-control" id="jhcis-code" required placeholder="ป้อนรหัสยาจาก JHCIS">
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
                    <button type="submit" class="btn btn-success">บันทึกการจับคู่</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Load data on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadStats();
            loadUnmappedDrugs();
        });

        // Load statistics and connection status
        async function loadStats() {
            try {
                const urlParams = new URLSearchParams(window.location.search);
                const hospitalId = urlParams.get('hospital_id');
                const apiUrl = hospitalId ? `/api/jhcis/mapping/stats?hospital_id=${hospitalId}` : '/api/jhcis/mapping/stats';
                
                const response = await fetch(apiUrl);
                const data = await response.json();
                
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
                    
                    if (!hospitalId && data.connection.hospital_id) {
                        const newUrl = window.location.pathname + '?hospital_id=' + data.connection.hospital_id;
                        window.history.replaceState({path: newUrl}, '', newUrl);
                    }
                }
            } catch (error) {
                console.error('Error loading stats:', error);
            }
        }

        // Load unmapped drugs
        async function loadUnmappedDrugs() {
            try {
                const response = await fetch('/api/jhcis/unmapped-drugs');
                const data = await response.json();
                
                document.getElementById('loading').style.display = 'none';
                
                if (data.length === 0) {
                    document.getElementById('empty-state').style.display = 'block';
                    return;
                }
                
                const tbody = document.getElementById('unmapped-tbody');
                tbody.innerHTML = '';
                
                data.forEach(drug => {
                    const row = `
                        <tr>
                            <td><strong>${drug.code || '-'}</strong></td>
                            <td>${drug.name}</td>
                            <td>${drug.generic_name || '-'}</td>
                            <td>${drug.unit || '-'}</td>
                            <td><span class="badge badge-unmapped">ยังไม่จับคู่</span></td>
                            <td>
                                <button class="btn btn-sm btn-success" onclick="quickMap(${drug.id}, '${drug.name}')">
                                    จับคู่ด่วน
                                </button>
                            </td>
                        </tr>
                    `;
                    tbody.innerHTML += row;
                });
                
                document.getElementById('unmapped-table').style.display = 'table';
            } catch (error) {
                console.error('Error loading unmapped drugs:', error);
                showAlert('Error loading data', 'error');
            }
        }

        // Auto-map all
        async function autoMapAll() {
            if (!confirm('ต้องการทำ Auto-Mapping ทั้งหมดหรือไม่?')) {
                return;
            }
            
            showAlert('กำลังทำ Auto-Mapping...', 'warning');
            
            try {
                const response = await fetch('/api/jhcis/mapping/auto-map', {
                    method: 'POST'
                });
                const result = await response.json();
                
                if (result.success) {
                    showAlert(`Auto-Mapping สำเร็จ! จับคู่ได้ ${result.mapped_count} รายการ`, 'success');
                    setTimeout(() => {
                        loadUnmappedDrugs();
                    }, 1500);
                } else {
                    showAlert('Auto-Mapping ล้มเหลว: ' + result.message, 'error');
                }
            } catch (error) {
                console.error('Error auto-mapping:', error);
                showAlert('เกิดข้อผิดพลาดในการ Auto-Mapping', 'error');
            }
        }

        // Quick map
        function quickMap(drugId, drugName) {
            document.getElementById('drug-id').value = drugId;
            document.getElementById('drug-name').value = drugName;
            document.getElementById('quick-map-modal').classList.add('active');
        }

        // Close modal
        function closeModal() {
            document.getElementById('quick-map-modal').classList.remove('active');
            document.getElementById('quick-map-form').reset();
        }

        // Submit quick map
        async function submitQuickMap(event) {
            event.preventDefault();
            
            const formData = {
                jhcis_drug_code: document.getElementById('jhcis-code').value,
                drugmuk_drug_id: document.getElementById('drug-id').value,
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
                    loadUnmappedDrugs();
                } else {
                    showAlert('บันทึกล้มเหลว: ' + result.message, 'error');
                }
            } catch (error) {
                console.error('Error saving mapping:', error);
                showAlert('เกิดข้อผิดพลาดในการบันทึก', 'error');
            }
        }

        // Filter table
        function filterTable() {
            const input = document.getElementById('search');
            const filter = input.value.toUpperCase();
            const table = document.getElementById('unmapped-table');
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
    </script>
</body>
</html>
