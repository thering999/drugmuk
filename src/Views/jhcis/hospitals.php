<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการ รพ.สต. - JHCIS Multi-Hospital Management</title>
    <?= \App\Core\CSRF::metaTag() ?>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
        
        /* Header Styles */
        .page-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        
        .page-header h1 {
            font-size: 32px;
            color: #1f2937;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .page-header p {
            color: #6b7280;
            font-size: 16px;
            margin-bottom: 20px;
        }
        
        .header-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        
        /* Button Styles */
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .btn-success { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; }
        .btn-danger { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; }
        .btn-warning { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; }
        .btn-secondary { background: #6b7280; color: white; }
        .btn-info { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; }
        
        .btn-sm {
            padding: 8px 16px;
            font-size: 13px;
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }
        
        .stat-card .icon {
            font-size: 36px;
            margin-bottom: 12px;
        }
        
        .stat-card .value {
            font-size: 32px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 8px;
        }
        
        .stat-card .label {
            font-size: 14px;
            color: #6b7280;
            font-weight: 500;
        }
        
        /* Card Styles */
        .card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }
        
        .card h2 {
            font-size: 22px;
            color: #1f2937;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f3f4f6;
        }
        
        /* Table Styles */
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 15px 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        
        th {
            background: #f9fafb;
            font-weight: 600;
            color: #374151;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        td {
            color: #4b5563;
            font-size: 15px;
        }
        
        tr:hover {
            background: #f9fafb;
        }
        
        /* Badge Styles */
        .badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            display: inline-block;
        }
        
        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-info { background: #dbeafe; color: #1e40af; }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.6);
            backdrop-filter: blur(4px);
            z-index: 1000;
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .modal-content {
            background: white;
            max-width: 650px;
            margin: 50px auto;
            padding: 0;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: slideDown 0.3s ease;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        @keyframes slideDown {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 25px 30px;
            border-bottom: 2px solid #f3f4f6;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px 15px 0 0;
        }
        
        .modal-header h2 {
            margin: 0;
            color: white;
            font-size: 24px;
            border: none;
            padding: 0;
        }
        
        .close-btn {
            background: rgba(255,255,255,0.2);
            border: none;
            font-size: 28px;
            cursor: pointer;
            color: white;
            padding: 0;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s;
        }
        
        .close-btn:hover {
            background: rgba(255,255,255,0.3);
            transform: rotate(90deg);
        }
        
        .modal-body {
            padding: 30px;
        }
        
        /* Form Styles */
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #374151;
            font-size: 14px;
        }
        
        .form-group input, 
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s;
            font-family: 'Sarabun', sans-serif;
        }
        
        .form-group input:focus, 
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #f3f4f6;
        }
        
        /* Loading Spinner */
        .spinner {
            display: inline-block;
            width: 18px;
            height: 18px;
            border: 3px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 0.8s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Alert Styles */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from { transform: translateX(-20px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        .alert-success { background: #d1fae5; color: #065f46; border-left: 4px solid #10b981; }
        .alert-error { background: #fee2e2; color: #991b1b; border-left: 4px solid #ef4444; }
        .alert-info { background: #dbeafe; color: #1e40af; border-left: 4px solid #3b82f6; }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #9ca3af;
        }
        
        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .empty-state h3 {
            font-size: 20px;
            color: #6b7280;
            margin-bottom: 10px;
        }
        
        .empty-state p {
            font-size: 15px;
            margin-bottom: 20px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .header-actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
            
            table {
                font-size: 13px;
            }
            
            th, td {
                padding: 10px 8px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <h1>🏥 จัดการ รพ.สต. (Multi-Hospital Management)</h1>
            <p>ระบบบริหารจัดการการเชื่อมต่อกับฐานข้อมูล JHCIS จากหลาย รพ.สต. พร้อมกัน</p>
            <div class="header-actions">
                <button onclick="syncAll()" class="btn btn-primary">
                    <span>🔄</span>
                    <span>Sync ทั้งหมด</span>
                </button>
                <button onclick="showAddModal()" class="btn btn-success">
                    <span>➕</span>
                    <span>เพิ่ม รพ.สต.</span>
                </button>
                <a href="/admin/jhcis/reports" class="btn btn-info">
                    <span>📊</span>
                    <span>รายงานรวม</span>
                </a>
                <a href="/admin/jhcis/dashboard" class="btn btn-secondary">
                    <span>←</span>
                    <span>กลับ Dashboard</span>
                </a>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon">🏥</div>
                <div class="value" id="totalHospitals"><?= count($hospitals) ?></div>
                <div class="label">รพ.สต. ทั้งหมด</div>
            </div>
            <div class="stat-card">
                <div class="icon">✅</div>
                <div class="value" id="activeHospitals">
                    <?= count(array_filter($hospitals, fn($h) => $h['is_active'])) ?>
                </div>
                <div class="label">เปิดใช้งาน</div>
            </div>
            <div class="stat-card">
                <div class="icon">🔄</div>
                <div class="value" id="syncedToday">
                    <?= count(array_filter($hospitals, fn($h) => $h['last_sync_at'] && date('Y-m-d', strtotime($h['last_sync_at'])) === date('Y-m-d'))) ?>
                </div>
                <div class="label">ซิงค์วันนี้</div>
            </div>
            <div class="stat-card">
                <div class="icon">⚠️</div>
                <div class="value" id="needsSync">
                    <?= count(array_filter($hospitals, fn($h) => !$h['last_sync_at'] || strtotime($h['last_sync_at']) < strtotime('-7 days'))) ?>
                </div>
                <div class="label">ต้องซิงค์</div>
            </div>
        </div>

        <!-- Hospitals Table -->
        <div class="card">
            <h2>📋 รายการ รพ.สต. ทั้งหมด</h2>
            
            <?php if (empty($hospitals)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">🏥</div>
                    <h3>ยังไม่มี รพ.สต. ในระบบ</h3>
                    <p>เริ่มต้นโดยการเพิ่ม รพ.สต. แรกของคุณ</p>
                    <button onclick="showAddModal()" class="btn btn-success">
                        <span>➕</span>
                        <span>เพิ่ม รพ.สต. แรก</span>
                    </button>
                </div>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>รหัส</th>
                                <th>ชื่อ รพ.สต.</th>
                                <th>Database</th>
                                <th>PCU Code</th>
                                <th>สถานะ</th>
                                <th>Sync ล่าสุด</th>
                                <th style="text-align: center;">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($hospitals as $hospital): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($hospital['code']) ?></strong></td>
                                <td><?= htmlspecialchars($hospital['name']) ?></td>
                                <td>
                                    <small style="color: #6b7280;">
                                        <?= htmlspecialchars($hospital['db_host']) ?>:<?= $hospital['db_port'] ?>/<?= htmlspecialchars($hospital['db_name']) ?>
                                    </small>
                                </td>
                                <td>
                                    <?php if ($hospital['pcucode']): ?>
                                        <span class="badge badge-info"><?= htmlspecialchars($hospital['pcucode']) ?></span>
                                    <?php else: ?>
                                        <span style="color: #9ca3af;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?= $hospital['is_active'] ? 'badge-success' : 'badge-danger' ?>">
                                        <?= $hospital['is_active'] ? '✓ Active' : '✗ Inactive' ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($hospital['last_sync_at']): ?>
                                        <?php 
                                            $syncTime = strtotime($hospital['last_sync_at']);
                                            $isRecent = $syncTime > strtotime('-24 hours');
                                        ?>
                                        <span style="color: <?= $isRecent ? '#10b981' : '#6b7280' ?>;">
                                            <?= date('d/m/Y H:i', $syncTime) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">ยังไม่เคยซิงค์</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 8px; justify-content: center; flex-wrap: wrap;">
                                        <button onclick="testConnection(<?= $hospital['id'] ?>)" class="btn btn-warning btn-sm" title="ทดสอบการเชื่อมต่อ">
                                            ⚡ ทดสอบ
                                        </button>
                                        <button onclick="syncHospital(<?= $hospital['id'] ?>)" class="btn btn-info btn-sm" title="ซิงค์ข้อมูล">
                                            🔄 Sync
                                        </button>
                                        <button 
                                            data-hospital='<?= htmlspecialchars(json_encode($hospital), ENT_QUOTES, 'UTF-8') ?>'
                                            onclick="editHospital(JSON.parse(this.getAttribute('data-hospital')))" 
                                            class="btn btn-primary btn-sm"
                                            title="แก้ไข">
                                            ✏️ แก้ไข
                                        </button>
                                        <button onclick="deleteHospital(<?= $hospital['id'] ?>, '<?= htmlspecialchars($hospital['name']) ?>')" class="btn btn-danger btn-sm" title="ลบ">
                                            🗑️ ลบ
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div id="hospitalModal" class="modal" onclick="if(event.target === this) closeModal()">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">เพิ่ม รพ.สต.</h2>
                <button class="close-btn" onclick="closeModal()" title="ปิด">&times;</button>
            </div>
            <div class="modal-body">
                <form id="hospitalForm" onsubmit="saveHospital(event)">
                    <input type="hidden" id="hospitalId">
                    
                    <div class="form-group">
                        <label>รหัส *</label>
                        <input type="text" id="code" required placeholder="เช่น HPHO01">
                    </div>
                    
                    <div class="form-group">
                        <label>ชื่อ รพ.สต. *</label>
                        <input type="text" id="name" required placeholder="เช่น รพ.สต. บ้านหนองบัว">
                    </div>
                    
                    <div class="form-group">
                        <label>Database Host</label>
                        <input type="text" id="db_host" value="127.0.0.1" placeholder="127.0.0.1 (Docker) หรือ localhost">
                    </div>
                    
                    <div class="form-group">
                        <label>Database Port</label>
                        <input type="number" id="db_port" value="3306" placeholder="3306">
                    </div>
                    
                    <div class="form-group">
                        <label>Database Name *</label>
                        <input type="text" id="db_name" required placeholder="jhcis">
                    </div>
                    
                    <div class="form-group">
                        <label>Database User</label>
                        <input type="text" id="db_user" value="root" placeholder="root">
                    </div>
                    
                    <div class="form-group">
                        <label>Database Password</label>
                        <input type="password" id="db_pass" placeholder="••••••">
                    </div>
                    
                    <div class="form-group">
                        <label>PCU Code (ถ้ามี)</label>
                        <input type="text" id="pcucode" placeholder="ระบุ pcucode หากเป็นฐานรวม (5 หรือ 9 หลัก)">
                        <small style="color: #6b7280; display: block; margin-top: 5px;">
                            💡 ใช้สำหรับกรองข้อมูลในกรณีที่ฐานข้อมูลมีหลาย รพ.สต. รวมกัน
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label>สถานะ</label>
                        <select id="is_active">
                            <option value="1">✓ Active (เปิดใช้งาน)</option>
                            <option value="0">✗ Inactive (ปิดใช้งาน)</option>
                        </select>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-success" id="saveBtn">
                            <span>💾</span>
                            <span>บันทึก</span>
                        </button>
                        <button type="button" onclick="closeModal()" class="btn btn-secondary">
                            <span>✕</span>
                            <span>ยกเลิก</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Show Add Modal
        function showAddModal() {
            document.getElementById('modalTitle').textContent = 'เพิ่ม รพ.สต.';
            document.getElementById('hospitalForm').reset();
            document.getElementById('hospitalId').value = '';
            document.getElementById('db_host').value = '127.0.0.1';
            document.getElementById('db_port').value = '3306';
            document.getElementById('db_user').value = 'root';
            document.getElementById('is_active').value = '1';
            document.getElementById('hospitalModal').style.display = 'block';
        }

        // Edit Hospital
        function editHospital(hospital) {
            document.getElementById('modalTitle').textContent = 'แก้ไข รพ.สต.';
            document.getElementById('hospitalId').value = hospital.id;
            document.getElementById('code').value = hospital.code;
            document.getElementById('name').value = hospital.name;
            document.getElementById('db_host').value = hospital.db_host;
            document.getElementById('db_port').value = hospital.db_port;
            document.getElementById('db_name').value = hospital.db_name;
            document.getElementById('db_user').value = hospital.db_user;
            document.getElementById('db_pass').value = hospital.db_pass;
            document.getElementById('pcucode').value = hospital.pcucode || '';
            document.getElementById('is_active').value = hospital.is_active;
            document.getElementById('hospitalModal').style.display = 'block';
        }

        // Close Modal
        function closeModal() {
            document.getElementById('hospitalModal').style.display = 'none';
        }

        // Save Hospital
        async function saveHospital(event) {
            event.preventDefault();
            
            const id = document.getElementById('hospitalId').value;
            const url = id ? '/admin/jhcis/hospitals/update' : '/admin/jhcis/hospitals/add';
            const saveBtn = document.getElementById('saveBtn');
            
            // Disable button and show loading
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<span class="spinner"></span><span>กำลังบันทึก...</span>';
            
            const formData = new FormData();
            if (id) formData.append('id', id);
            formData.append('code', document.getElementById('code').value);
            formData.append('name', document.getElementById('name').value);
            formData.append('db_host', document.getElementById('db_host').value);
            formData.append('db_port', document.getElementById('db_port').value);
            formData.append('db_name', document.getElementById('db_name').value);
            formData.append('db_user', document.getElementById('db_user').value);
            formData.append('db_pass', document.getElementById('db_pass').value);
            formData.append('pcucode', document.getElementById('pcucode').value);
            formData.append('is_active', document.getElementById('is_active').value);
            
            try {
                const response = await fetch(url, {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                
                if (data.success) {
                    showAlert('success', '✅ ' + data.message);
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showAlert('error', '❌ ' + data.message);
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = '<span>💾</span><span>บันทึก</span>';
                }
            } catch (error) {
                showAlert('error', '❌ เกิดข้อผิดพลาด: ' + error.message);
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<span>💾</span><span>บันทึก</span>';
            }
        }

        // Delete Hospital
        async function deleteHospital(id, name) {
            if (!confirm(`ต้องการลบ "${name}" ใช่หรือไม่?\n\nการลบจะไม่สามารถกู้คืนได้`)) return;
            
            const formData = new FormData();
            formData.append('id', id);
            
            try {
                const response = await fetch('/admin/jhcis/hospitals/delete', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                
                if (data.success) {
                    showAlert('success', '✅ ' + data.message);
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showAlert('error', '❌ ' + data.message);
                }
            } catch (error) {
                showAlert('error', '❌ เกิดข้อผิดพลาด: ' + error.message);
            }
        }

        // Test Connection
        async function testConnection(id) {
            const btn = event.target;
            const originalHTML = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner"></span> กำลังทดสอบ...';
            
            try {
                const response = await fetch(`/api/jhcis/test-connection/${id}`);
                const data = await response.json();
                
                if (data.success) {
                    alert(`✅ เชื่อมต่อสำเร็จ!\n\n📊 ข้อมูลในฐาน:\n- ยา: ${data.total_drugs.toLocaleString()} รายการ\n- ตาราง: ${data.total_tables} ตาราง\n- ตาราง Dispensing: ${data.dispensing_tables.join(', ') || 'ไม่พบ'}`);
                } else {
                    alert('❌ เชื่อมต่อไม่สำเร็จ\n\n' + data.message);
                }
            } catch (error) {
                alert('❌ เกิดข้อผิดพลาด: ' + error.message);
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalHTML;
            }
        }

        // Sync Single Hospital
        async function syncHospital(id) {
            if (!confirm('ต้องการซิงค์ข้อมูลจาก รพ.สต. นี้หรือไม่?')) return;
            
            const btn = event.target;
            const originalHTML = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner"></span> กำลังซิงค์...';
            
            try {
                const response = await fetch(`/api/jhcis/sync-now/${id}`, {
                    method: 'POST'
                });
                const data = await response.json();
                
                if (data.success) {
                    showAlert('success', '✅ เริ่มการซิงค์สำเร็จ! กรุณารอสักครู่...');
                    setTimeout(() => location.reload(), 2000);
                } else {
                    showAlert('error', '❌ ' + data.message);
                    btn.disabled = false;
                    btn.innerHTML = originalHTML;
                }
            } catch (error) {
                showAlert('error', '❌ เกิดข้อผิดพลาด: ' + error.message);
                btn.disabled = false;
                btn.innerHTML = originalHTML;
            }
        }

        // Sync All Hospitals
        async function syncAll() {
            if (!confirm('ต้องการเริ่มซิงค์ข้อมูลจากทุก รพ.สต. ที่เปิดใช้งานอยู่หรือไม่?\n\nกระบวนการนี้อาจใช้เวลาสักครู่')) return;
            
            const btn = event.target;
            const originalHTML = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner"></span><span>กำลังซิงค์...</span>';
            
            try {
                const response = await fetch('/admin/jhcis/hospitals/sync-all', {
                    method: 'POST'
                });
                const data = await response.json();
                
                if (data.success) {
                    let message = '✅ ซิงค์ข้อมูลสำเร็จ!\n\n';
                    for (const [name, res] of Object.entries(data.results)) {
                        message += `📍 ${name}: ${res.success ? '✓ สำเร็จ ' + res.imported + ' รายการ' : '✗ ล้มเหลว: ' + res.error}\n`;
                    }
                    alert(message);
                    location.reload();
                } else {
                    showAlert('error', '❌ ' + data.message);
                    btn.disabled = false;
                    btn.innerHTML = originalHTML;
                }
            } catch (error) {
                showAlert('error', '❌ เกิดข้อผิดพลาด: ' + error.message);
                btn.disabled = false;
                btn.innerHTML = originalHTML;
            }
        }

        // Show Alert
        function showAlert(type, message) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type}`;
            alertDiv.innerHTML = `<strong>${message}</strong>`;
            
            const container = document.querySelector('.container');
            container.insertBefore(alertDiv, container.firstChild);
            
            setTimeout(() => {
                alertDiv.style.opacity = '0';
                setTimeout(() => alertDiv.remove(), 300);
            }, 5000);
        }

        // Auto-include CSRF token in all AJAX requests
        document.addEventListener('DOMContentLoaded', function() {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            
            if (csrfToken) {
                const originalFetch = window.fetch;
                window.fetch = function(url, options = {}) {
                    const method = (options.method || 'GET').toUpperCase();
                    if (method !== 'GET') {
                        options.headers = options.headers || {};
                        options.headers['X-CSRF-Token'] = csrfToken;
                    }
                    return originalFetch(url, options);
                };
            }
        });

        // Close modal on ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });
    </script>
</body>
</html>
