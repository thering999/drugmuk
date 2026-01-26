<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการ รพ.สต. - JHCIS</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Sarabun', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 1200px; margin: 0 auto; }
        .header {
            background: white;
            padding: 20px 30px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary { background: #667eea; color: white; }
        .btn-success { background: #10b981; color: white; }
        .btn-danger { background: #ef4444; color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        .card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #f8f9fa;
            font-weight: bold;
        }
        .badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }
        .modal-content {
            background: white;
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            border-radius: 10px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏥 จัดการ รพ.สต.</h1>
            <div>
                <button onclick="showAddModal()" class="btn btn-success">+ เพิ่ม รพ.สต.</button>
                <a href="/admin/jhcis/dashboard" class="btn btn-secondary">← กลับ</a>
            </div>
        </div>

        <div class="card">
            <h2 style="margin-bottom: 20px;">รายการ รพ.สต. ทั้งหมด</h2>
            <table>
                <thead>
                    <tr>
                        <th>รหัส</th>
                        <th>ชื่อ</th>
                        <th>Database</th>
                        <th>สถานะ</th>
                        <th>Sync ล่าสุด</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($hospitals as $hospital): ?>
                    <tr>
                        <td><?= htmlspecialchars($hospital['code']) ?></td>
                        <td><?= htmlspecialchars($hospital['name']) ?></td>
                        <td><?= htmlspecialchars($hospital['db_host']) ?>:<?= $hospital['db_port'] ?>/<?= htmlspecialchars($hospital['db_name']) ?></td>
                        <td>
                            <span class="badge <?= $hospital['is_active'] ? 'badge-success' : 'badge-danger' ?>">
                                <?= $hospital['is_active'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                        <td><?= $hospital['last_sync_at'] ? date('d/m/Y H:i', strtotime($hospital['last_sync_at'])) : '-' ?></td>
                        <td>
                            <button onclick='editHospital(<?= json_encode($hospital) ?>)' class="btn btn-primary">แก้ไข</button>
                            <button onclick="deleteHospital(<?= $hospital['id'] ?>)" class="btn btn-danger">ลบ</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div id="hospitalModal" class="modal">
        <div class="modal-content">
            <h2 id="modalTitle">เพิ่ม รพ.สต.</h2>
            <form id="hospitalForm" onsubmit="saveHospital(event)">
                <input type="hidden" id="hospitalId">
                
                <div class="form-group">
                    <label>รหัส *</label>
                    <input type="text" id="code" required>
                </div>
                
                <div class="form-group">
                    <label>ชื่อ *</label>
                    <input type="text" id="name" required>
                </div>
                
                <div class="form-group">
                    <label>Database Host</label>
                    <input type="text" id="db_host" value="127.0.0.1" placeholder="127.0.0.1 (สำหรับ Docker) หรือ localhost">
                </div>
                
                <div class="form-group">
                    <label>Database Port</label>
                    <input type="number" id="db_port" value="3306">
                </div>
                
                <div class="form-group">
                    <label>Database Name *</label>
                    <input type="text" id="db_name" required>
                </div>
                
                <div class="form-group">
                    <label>Database User</label>
                    <input type="text" id="db_user" value="root">
                </div>
                
                <div class="form-group">
                    <label>Database Password</label>
                    <input type="password" id="db_pass">
                </div>
                
                <div class="form-group">
                    <label>สถานะ</label>
                    <select id="is_active">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" class="btn btn-success">บันทึก</button>
                    <button type="button" onclick="closeModal()" class="btn btn-secondary">ยกเลิก</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showAddModal() {
            document.getElementById('modalTitle').textContent = 'เพิ่ม รพ.สต.';
            document.getElementById('hospitalForm').reset();
            document.getElementById('hospitalId').value = '';
            document.getElementById('hospitalModal').style.display = 'block';
        }

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
            document.getElementById('is_active').value = hospital.is_active;
            document.getElementById('hospitalModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('hospitalModal').style.display = 'none';
        }

        async function saveHospital(event) {
            event.preventDefault();
            
            const id = document.getElementById('hospitalId').value;
            const url = id ? '/admin/jhcis/hospitals/update' : '/admin/jhcis/hospitals/add';
            
            const formData = new FormData();
            if (id) formData.append('id', id);
            formData.append('code', document.getElementById('code').value);
            formData.append('name', document.getElementById('name').value);
            formData.append('db_host', document.getElementById('db_host').value);
            formData.append('db_port', document.getElementById('db_port').value);
            formData.append('db_name', document.getElementById('db_name').value);
            formData.append('db_user', document.getElementById('db_user').value);
            formData.append('db_pass', document.getElementById('db_pass').value);
            formData.append('is_active', document.getElementById('is_active').value);
            
            try {
                const response = await fetch(url, {
                    method: 'POST',
                    body: formData
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
            }
        }

        async function deleteHospital(id) {
            if (!confirm('ต้องการลบ รพ.สต. นี้?')) return;
            
            const formData = new FormData();
            formData.append('id', id);
            
            try {
                const response = await fetch('/admin/jhcis/hospitals/delete', {
                    method: 'POST',
                    body: formData
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
            }
        }
    </script>
</body>
</html>
