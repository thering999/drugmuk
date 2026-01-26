<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการหลาย รพ.สต. - Drugmuk</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Sarabun', sans-serif;
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            min-height: 100vh; padding: 2rem;
        }
        .container { max-width: 1200px; margin: 0 auto; }
        .header {
            background: rgba(255, 255, 255, 0.15); backdrop-filter: blur(20px);
            border-radius: 20px; padding: 2rem; margin-bottom: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.3); color: white;
        }
        .card {
            background: white; border-radius: 15px; padding: 2rem;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1); margin-bottom: 2rem;
        }
        .btn {
            padding: 0.75rem 1.5rem; border: none; border-radius: 8px;
            font-size: 1rem; font-weight: 600; cursor: pointer;
            transition: all 0.2s; display: inline-flex; align-items: center; gap: 0.5rem;
        }
        .btn-primary { background: #3b82f6; color: white; }
        .btn-primary:hover { background: #2563eb; }
        .btn-success { background: #10b981; color: white; }
        .btn-success:hover { background: #059669; }
        .btn-danger { background: #ef4444; color: white; }
        .btn-danger:hover { background: #dc2626; }
        .btn-secondary { background: #6b7280; color: white; }
        .btn-secondary:hover { background: #4b5563; }
        .hospital-list { margin-top: 1rem; }
        .hospital-item {
            padding: 1.5rem; border: 2px solid #e5e7eb; border-radius: 10px;
            margin-bottom: 1rem; display: flex; justify-content: space-between;
            align-items: center; transition: all 0.2s;
        }
        .hospital-item:hover { border-color: #3b82f6; background: #f9fafb; }
        .hospital-info h3 { margin-bottom: 0.5rem; color: #374151; }
        .hospital-info p { color: #6b7280; font-size: 0.9rem; }
        .hospital-actions { display: flex; gap: 0.5rem; }
        .badge {
            display: inline-block; padding: 0.25rem 0.75rem;
            border-radius: 12px; font-size: 0.85rem; font-weight: 600;
        }
        .badge-active { background: #d1fae5; color: #065f46; }
        .badge-inactive { background: #fee2e2; color: #991b1b; }
        .modal {
            display: none; position: fixed; z-index: 1000; left: 0; top: 0;
            width: 100%; height: 100%; background: rgba(0,0,0,0.5);
        }
        .modal-content {
            background: white; margin: 5% auto; padding: 2rem;
            border-radius: 15px; max-width: 600px; position: relative;
        }
        .close { position: absolute; right: 1rem; top: 1rem; font-size: 2rem;
            cursor: pointer; color: #6b7280; }
        .form-group { margin-bottom: 1rem; }
        .label { display: block; margin-bottom: 0.5rem; font-weight: 600; color: #374151; }
        .input {
            width: 100%; padding: 0.75rem; border: 2px solid #e5e7eb;
            border-radius: 8px; font-size: 1rem;
        }
        .input:focus { outline: none; border-color: #3b82f6; }
        .back-link {
            display: inline-block; margin-bottom: 20px; color: white;
            text-decoration: none; background: rgba(255,255,255,0.2);
            padding: 8px 16px; border-radius: 8px;
        }
        .alert {
            padding: 1rem; border-radius: 8px; margin-bottom: 1rem; display: none;
        }
        .alert-success { background: #d1fae5; color: #065f46; }
        .alert-error { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    <div class="container">
        <a href="/admin/jhcis/dashboard" class="back-link">← กลับไป JHCIS Dashboard</a>

        <div class="header">
            <h1>🏥 จัดการหลาย รพ.สต.</h1>
            <p>เชื่อมต่อและ Sync ข้อมูลจากหลาย รพ.สต. พร้อมกัน</p>
        </div>

        <div id="alertBox" class="alert"></div>

        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h2 style="color: #374151;">รายการ รพ.สต.</h2>
                <div style="display: flex; gap: 1rem;">
                    <button class="btn btn-success" onclick="syncAllHospitals()">
                        <span>🔄</span><span>Sync ทั้งหมด</span>
                    </button>
                    <button class="btn btn-primary" onclick="showAddModal()">
                        <span>➕</span><span>เพิ่ม รพ.สต.</span>
                    </button>
                </div>
            </div>

            <div class="hospital-list" id="hospitalList">
                <p style="text-align: center; color: #999;">กำลังโหลด...</p>
            </div>
        </div>
    </div>

    <!-- Modal เพิ่ม/แก้ไข รพ.สต. -->
    <div id="hospitalModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2 style="margin-bottom: 1.5rem;" id="modalTitle">เพิ่ม รพ.สต.</h2>
            
            <form id="hospitalForm">
                <input type="hidden" id="hospitalId">
                
                <div class="form-group">
                    <label class="label">ชื่อ รพ.สต.</label>
                    <input type="text" class="input" id="hospitalName" placeholder="รพ.สต.บ้านนา" required>
                </div>

                <div class="form-group">
                    <label class="label">รหัส รพ.สต.</label>
                    <input type="text" class="input" id="hospitalCode" placeholder="12345">
                </div>

                <div class="form-group">
                    <label class="label">Host / IP</label>
                    <input type="text" class="input" id="hospitalHost" value="localhost" required>
                </div>

                <div class="form-group">
                    <label class="label">Port</label>
                    <input type="text" class="input" id="hospitalPort" value="3306" required>
                </div>

                <div class="form-group">
                    <label class="label">Database Name</label>
                    <input type="text" class="input" id="hospitalDbname" value="jhcisdb" required>
                </div>

                <div class="form-group">
                    <label class="label">Username</label>
                    <input type="text" class="input" id="hospitalUser" value="root" required>
                </div>

                <div class="form-group">
                    <label class="label">Password</label>
                    <input type="password" class="input" id="hospitalPass">
                </div>

                <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                    <button type="button" class="btn btn-secondary" onclick="testHospitalConnection()">
                        <span>🔍</span><span>ทดสอบการเชื่อมต่อ</span>
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <span>💾</span><span>บันทึก</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let hospitals = [];

        // โหลดรายการ รพ.สต.
        async function loadHospitals() {
            try {
                const response = await fetch('/api/multi-jhcis/hospitals');
                const data = await response.json();
                
                if (data.success) {
                    hospitals = data.hospitals;
                    renderHospitals();
                }
            } catch (e) {
                console.error(e);
            }
        }

        // แสดงรายการ รพ.สต.
        function renderHospitals() {
            const list = document.getElementById('hospitalList');
            
            if (hospitals.length === 0) {
                list.innerHTML = '<p style="text-align: center; color: #999;">ยังไม่มี รพ.สต.</p>';
                return;
            }

            let html = '';
            hospitals.forEach(hospital => {
                const statusBadge = hospital.active 
                    ? '<span class="badge badge-active">✓ Active</span>'
                    : '<span class="badge badge-inactive">✗ Inactive</span>';

                html += `
                    <div class="hospital-item">
                        <div class="hospital-info">
                            <h3>${hospital.name} ${statusBadge}</h3>
                            <p>รหัส: ${hospital.code || '-'} | Host: ${hospital.host}:${hospital.port} | DB: ${hospital.dbname}</p>
                        </div>
                        <div class="hospital-actions">
                            <button class="btn btn-secondary" onclick="editHospital('${hospital.id}')">
                                <span>✏️</span><span>แก้ไข</span>
                            </button>
                            <button class="btn btn-danger" onclick="deleteHospital('${hospital.id}', '${hospital.name}')">
                                <span>🗑️</span><span>ลบ</span>
                            </button>
                        </div>
                    </div>
                `;
            });

            list.innerHTML = html;
        }

        // แสดง Modal เพิ่ม
        function showAddModal() {
            document.getElementById('modalTitle').textContent = 'เพิ่ม รพ.สต.';
            document.getElementById('hospitalForm').reset();
            document.getElementById('hospitalId').value = '';
            document.getElementById('hospitalModal').style.display = 'block';
        }

        // แก้ไข รพ.สต.
        function editHospital(id) {
            const hospital = hospitals.find(h => h.id === id);
            if (!hospital) return;

            document.getElementById('modalTitle').textContent = 'แก้ไข รพ.สต.';
            document.getElementById('hospitalId').value = hospital.id;
            document.getElementById('hospitalName').value = hospital.name;
            document.getElementById('hospitalCode').value = hospital.code || '';
            document.getElementById('hospitalHost').value = hospital.host;
            document.getElementById('hospitalPort').value = hospital.port;
            document.getElementById('hospitalDbname').value = hospital.dbname;
            document.getElementById('hospitalUser').value = hospital.user;
            document.getElementById('hospitalPass').value = hospital.pass;
            document.getElementById('hospitalModal').style.display = 'block';
        }

        // ปิด Modal
        function closeModal() {
            document.getElementById('hospitalModal').style.display = 'none';
        }

        // บันทึก รพ.สต.
        document.getElementById('hospitalForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const hospitalId = document.getElementById('hospitalId').value;
            const data = {
                id: hospitalId,
                name: document.getElementById('hospitalName').value,
                code: document.getElementById('hospitalCode').value,
                host: document.getElementById('hospitalHost').value,
                port: document.getElementById('hospitalPort').value,
                dbname: document.getElementById('hospitalDbname').value,
                user: document.getElementById('hospitalUser').value,
                pass: document.getElementById('hospitalPass').value
            };

            const url = hospitalId ? '/api/multi-jhcis/update' : '/api/multi-jhcis/add';

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await response.json();

                if (result.success) {
                    showAlert('success', '✅ ' + result.message);
                    closeModal();
                    loadHospitals();
                } else {
                    showAlert('error', '❌ ' + result.message);
                }
            } catch (e) {
                showAlert('error', '❌ เกิดข้อผิดพลาด: ' + e.message);
            }
        });

        // ลบ รพ.สต.
        async function deleteHospital(id, name) {
            if (!confirm(`ต้องการลบ ${name} หรือไม่?`)) return;

            try {
                const response = await fetch('/api/multi-jhcis/delete', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id })
                });
                const result = await response.json();

                if (result.success) {
                    showAlert('success', '✅ ' + result.message);
                    loadHospitals();
                } else {
                    showAlert('error', '❌ ' + result.message);
                }
            } catch (e) {
                showAlert('error', '❌ เกิดข้อผิดพลาด: ' + e.message);
            }
        }

        // ทดสอบการเชื่อมต่อ
        async function testHospitalConnection() {
            const data = {
                host: document.getElementById('hospitalHost').value,
                port: document.getElementById('hospitalPort').value,
                dbname: document.getElementById('hospitalDbname').value,
                user: document.getElementById('hospitalUser').value,
                pass: document.getElementById('hospitalPass').value
            };

            try {
                const response = await fetch('/api/multi-jhcis/test', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await response.json();

                if (result.success) {
                    alert(`✅ ${result.message}\nพบตาราง: ${result.tables_count} ตาราง`);
                } else {
                    alert(`❌ ${result.message}`);
                }
            } catch (e) {
                alert('❌ เกิดข้อผิดพลาด: ' + e.message);
            }
        }

        // Sync ทั้งหมด
        async function syncAllHospitals() {
            if (!confirm('ต้องการ Sync ข้อมูลจากทุก รพ.สต. หรือไม่?')) return;

            showAlert('success', '🔄 กำลัง Sync...');

            try {
                const response = await fetch('/api/multi-jhcis/sync-all', {
                    method: 'POST'
                });
                const result = await response.json();

                if (result.success) {
                    let message = 'Sync เสร็จสิ้น:\n\n';
                    result.results.forEach(r => {
                        message += `${r.hospital_name}: ${r.success ? '✅' : '❌'} ${r.message} (${r.records} records)\n`;
                    });
                    alert(message);
                    showAlert('success', '✅ Sync เสร็จสิ้น!');
                } else {
                    showAlert('error', '❌ เกิดข้อผิดพลาด');
                }
            } catch (e) {
                showAlert('error', '❌ เกิดข้อผิดพลาด: ' + e.message);
            }
        }

        function showAlert(type, message) {
            const alertBox = document.getElementById('alertBox');
            alertBox.className = `alert alert-${type}`;
            alertBox.textContent = message;
            alertBox.style.display = 'block';
            setTimeout(() => alertBox.style.display = 'none', 5000);
        }

        // โหลดข้อมูลเมื่อเริ่มต้น
        loadHospitals();
    </script>
</body>
</html>
