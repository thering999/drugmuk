<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?= \App\Core\CSRF::metaTag() ?>
    <title>Import ข้อมูลจาก JHCIS - Drugmuk</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Sarabun', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            background: white;
            padding: 20px 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            color: #667eea;
            font-size: 28px;
        }

        .card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 20px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }

        .stat-value {
            font-size: 36px;
            font-weight: bold;
            margin: 10px 0;
        }

        .stat-label {
            font-size: 14px;
            opacity: 0.9;
        }

        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: all 0.3s;
            margin-right: 10px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
        }

        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
        }

        .progress {
            width: 100%;
            height: 30px;
            background: #f0f0f0;
            border-radius: 15px;
            overflow: hidden;
            margin: 20px 0;
            display: none;
        }

        .progress-bar {
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transition: width 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }

        .connection-status {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
        }

        .status-connected {
            background: #d4edda;
            color: #155724;
        }

        .status-disconnected {
            background: #f8d7da;
            color: #721c24;
        }

        .status-testing {
            background: #fff3cd;
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔄 Import ข้อมูลจาก JHCIS</h1>
            <a href="/dashboard" class="btn btn-secondary">← กลับหน้าหลัก</a>
        </div>

        <div id="alertContainer"></div>

        <!-- Connection Status -->
        <div class="card">
            <h2 style="margin-bottom: 20px;">สถานะการเชื่อมต่อ</h2>
            <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                <span id="connectionStatus" class="connection-status status-disconnected">
                    ยังไม่ได้ทดสอบ
                </span>
                <button onclick="testConnection()" class="btn btn-primary" id="btnTest">
                    🔍 ทดสอบการเชื่อมต่อ
                </button>
                <a href="/settings/database" class="btn btn-secondary">
                    ⚙️ ตั้งค่าการเชื่อมต่อ
                </a>
            </div>
            
            <!-- แสดงเมื่อเชื่อมต่อไม่ได้ -->
            <div id="connectionHelp" style="display: none; margin-top: 20px;">
                <div class="alert alert-info">
                    💡 <strong>ไม่มี JHCIS Database?</strong> ไม่เป็นไร!<br>
                    คุณสามารถใช้ <strong>ข้อมูลตัวอย่าง</strong> เพื่อทดสอบระบบได้<br><br>
                    <a href="/mock-data" class="btn btn-success" style="margin-top: 10px;">
                        🎲 สร้างข้อมูลตัวอย่าง
                    </a>
                    <a href="/dispensing/create" class="btn btn-primary" style="margin-top: 10px;">
                        💊 เริ่มจ่ายยาเลย
                    </a>
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="card">
            <h2 style="margin-bottom: 20px;">สถิติข้อมูลใน JHCIS</h2>
            <div class="stats-grid" id="statsGrid">
                <div class="stat-card">
                    <div class="stat-label">รายการยาทั้งหมด</div>
                    <div class="stat-value" id="statDrugs">-</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">ผู้ป่วยทั้งหมด</div>
                    <div class="stat-value" id="statPatients">-</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">การจ่ายยาเดือนนี้</div>
                    <div class="stat-value" id="statDispensing">-</div>
                </div>
            </div>
        </div>

        <!-- Import Drugs -->
        <div class="card">
            <h2 style="margin-bottom: 20px;">📦 Import ข้อมูลยา</h2>
            <p style="margin-bottom: 20px; color: #666;">
                ดึงข้อมูลยาจาก JHCIS มาใส่ในระบบ Drugmuk
            </p>
            
            <div class="form-group">
                <label>จำนวนรายการที่ต้องการดึง:</label>
                <input type="number" id="drugLimit" value="1000" min="1" max="10000" step="100" 
                       style="width: 200px;" placeholder="เช่น 1000">
                <small style="display: block; margin-top: 5px; color: #666;">
                    (ขั้นต่ำ 1 รายการ, สูงสุด 10,000 รายการ)
                </small>
            </div>
            
            <button onclick="importDrugs()" class="btn btn-success" id="btnImportDrugs">
                ⬇️ Import ข้อมูลยา
            </button>
            <div class="progress" id="progressDrugs">
                <div class="progress-bar" id="progressBarDrugs">0%</div>
            </div>
        </div>

        <!-- Import Dispensing -->
        <div class="card">
            <h2 style="margin-bottom: 20px;">💊 Import ประวัติการจ่ายยา</h2>
            <p style="margin-bottom: 20px; color: #666;">
                ดึงประวัติการจ่ายยาย้อนหลังจาก JHCIS
            </p>
            
            <div class="form-group">
                <label>วันที่เริ่มต้น:</label>
                <input type="date" id="startDate" value="<?= date('Y-m-d', strtotime('-30 days')) ?>">
            </div>

            <div class="form-group">
                <label>วันที่สิ้นสุด:</label>
                <input type="date" id="endDate" value="<?= date('Y-m-d') ?>">
            </div>

            <div class="form-group">
                <label>จำนวนรายการที่ต้องการดึง:</label>
                <input type="number" id="dispensingLimit" value="500" min="1" max="10000" step="100" 
                       style="width: 200px;" placeholder="เช่น 500">
                <small style="display: block; margin-top: 5px; color: #666;">
                    (ขั้นต่ำ 1 รายการ, สูงสุด 10,000 รายการ)
                </small>
            </div>

            <button onclick="importDispensing()" class="btn btn-success" id="btnImportDispensing">
                ⬇️ Import ประวัติการจ่ายยา
            </button>
            <div class="progress" id="progressDispensing">
                <div class="progress-bar" id="progressBarDispensing">0%</div>
            </div>
        </div>
    </div>

    <script>
        let isConnected = false;

        function getCSRFToken() {
            return document.querySelector('meta[name="csrf-token"]').content;
        }

        // ทดสอบการเชื่อมต่อ
        async function testConnection() {
            const btn = document.getElementById('btnTest');
            const status = document.getElementById('connectionStatus');
            const helpDiv = document.getElementById('connectionHelp');
            
            btn.disabled = true;
            status.className = 'connection-status status-testing';
            status.textContent = 'กำลังทดสอบ...';
            helpDiv.style.display = 'none';

            try {
                const response = await fetch('/jhcis-import/test-connection', {
                    headers: { 'X-CSRF-TOKEN': getCSRFToken() }
                });
                const data = await response.json();

                if (data.success) {
                    status.className = 'connection-status status-connected';
                    status.textContent = `เชื่อมต่อสำเร็จ (พบยา ${data.total_drugs.toLocaleString()} รายการ)`;
                    isConnected = true;
                    helpDiv.style.display = 'none';
                    
                    // โหลดสถิติ
                    loadStatistics();
                    
                    showAlert('success', data.message);
                } else {
                    status.className = 'connection-status status-disconnected';
                    status.textContent = 'เชื่อมต่อไม่สำเร็จ';
                    helpDiv.style.display = 'block'; // แสดงข้อความช่วยเหลือ
                    
                    // แสดงข้อความที่มีประโยชน์
                    let message = data.message;
                    if (data.suggestion) {
                        message += '\n\n' + data.suggestion;
                    }
                    
                    // ถ้าเป็นกรณีไม่พบตาราง ให้แสดงเป็น info แทน error
                    const alertType = data.message.includes('ไม่พบตาราง') ? 'info' : 'error';
                    showAlert(alertType, message);
                }
            } catch (error) {
                status.className = 'connection-status status-disconnected';
                status.textContent = 'เชื่อมต่อไม่สำเร็จ';
                helpDiv.style.display = 'block'; // แสดงข้อความช่วยเหลือ
                showAlert('error', 'เกิดข้อผิดพลาด: ' + error.message);
            }

            btn.disabled = false;
        }

        // โหลดสถิติ
        async function loadStatistics() {
            try {
                const response = await fetch('/jhcis-import/statistics', {
                    headers: { 'X-CSRF-TOKEN': getCSRFToken() }
                });
                const data = await response.json();

                if (data.success) {
                    document.getElementById('statDrugs').textContent = 
                        data.statistics.total_drugs.toLocaleString();
                    document.getElementById('statPatients').textContent = 
                        data.statistics.total_patients.toLocaleString();
                    document.getElementById('statDispensing').textContent = 
                        data.statistics.dispensing_this_month.toLocaleString();
                }
            } catch (error) {
                console.error('Error loading statistics:', error);
            }
        }

        // Import ข้อมูลยา
        async function importDrugs() {
            if (!isConnected) {
                showAlert('error', 'กรุณาทดสอบการเชื่อมต่อก่อน');
                return;
            }

            const limit = parseInt(document.getElementById('drugLimit').value);
            
            // Validate limit
            if (isNaN(limit) || limit < 1 || limit > 10000) {
                showAlert('error', 'กรุณาระบุจำนวนรายการระหว่าง 1-10,000');
                return;
            }

            if (!confirm(`ต้องการ Import ข้อมูลยาจาก JHCIS จำนวน ${limit.toLocaleString()} รายการ?`)) {
                return;
            }

            const btn = document.getElementById('btnImportDrugs');
            const progress = document.getElementById('progressDrugs');
            const progressBar = document.getElementById('progressBarDrugs');

            btn.disabled = true;
            progress.style.display = 'block';
            progressBar.style.width = '50%';
            progressBar.textContent = 'กำลัง Import...';

            try {
                const params = new URLSearchParams();
                params.append('limit', limit);
                params.append('csrf_token', getCSRFToken());
                
                const response = await fetch('/jhcis-import/import-drugs', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-CSRF-TOKEN': getCSRFToken() 
                    },
                    body: params
                });
                const data = await response.json();

                progressBar.style.width = '100%';
                progressBar.textContent = 'เสร็จสิ้น!';

                if (data.success) {
                    showAlert('success', 
                        `Import สำเร็จ! เพิ่มใหม่ ${data.imported} รายการ, อัพเดท ${data.updated} รายการ`
                    );
                } else {
                    showAlert('error', data.message);
                }

                setTimeout(() => {
                    progress.style.display = 'none';
                    progressBar.style.width = '0%';
                }, 2000);

            } catch (error) {
                showAlert('error', 'เกิดข้อผิดพลาด: ' + error.message);
                progress.style.display = 'none';
            }

            btn.disabled = false;
        }

        // Import ประวัติการจ่ายยา
        async function importDispensing() {
            if (!isConnected) {
                showAlert('error', 'กรุณาทดสอบการเชื่อมต่อก่อน');
                return;
            }

            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            const limit = parseInt(document.getElementById('dispensingLimit').value);

            if (!startDate || !endDate) {
                showAlert('error', 'กรุณาเลือกวันที่');
                return;
            }

            // Validate limit
            if (isNaN(limit) || limit < 1 || limit > 10000) {
                showAlert('error', 'กรุณาระบุจำนวนรายการระหว่าง 1-10,000');
                return;
            }

            if (!confirm(`ต้องการ Import ประวัติการจ่ายยาตั้งแต่ ${startDate} ถึง ${endDate} จำนวน ${limit.toLocaleString()} รายการ?`)) {
                return;
            }

            const btn = document.getElementById('btnImportDispensing');
            const progress = document.getElementById('progressDispensing');
            const progressBar = document.getElementById('progressBarDispensing');

            btn.disabled = true;
            progress.style.display = 'block';
            progressBar.style.width = '50%';
            progressBar.textContent = 'กำลัง Import...';

            try {
                const params = new URLSearchParams();
                params.append('start_date', startDate);
                params.append('end_date', endDate);
                params.append('limit', limit);
                params.append('csrf_token', getCSRFToken());

                const response = await fetch('/jhcis-import/import-dispensing', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-CSRF-TOKEN': getCSRFToken() 
                    },
                    body: params
                });
                const data = await response.json();

                progressBar.style.width = '100%';
                progressBar.textContent = 'เสร็จสิ้น!';

                if (data.success) {
                    let message = `Import สำเร็จ! ${data.imported} รายการ จากทั้งหมด ${data.total_records} records`;
                    if (data.errors && data.errors.length > 0) {
                        message += `\n\nมีข้อผิดพลาด ${data.errors.length} รายการ`;
                    }
                    showAlert('success', message);
                } else {
                    showAlert('error', data.message);
                }

                setTimeout(() => {
                    progress.style.display = 'none';
                    progressBar.style.width = '0%';
                }, 2000);

            } catch (error) {
                showAlert('error', 'เกิดข้อผิดพลาด: ' + error.message);
                progress.style.display = 'none';
            }

            btn.disabled = false;
        }

        // แสดง Alert
        function showAlert(type, message) {
            const container = document.getElementById('alertContainer');
            
            let alertClass, icon;
            if (type === 'success') {
                alertClass = 'alert-success';
                icon = '✅';
            } else if (type === 'info') {
                alertClass = 'alert-info';
                icon = 'ℹ️';
            } else {
                alertClass = 'alert-error';
                icon = '❌';
            }
            
            const alert = document.createElement('div');
            alert.className = `alert ${alertClass}`;
            alert.innerHTML = `${icon} ${message}`;
            
            container.appendChild(alert);
            
            setTimeout(() => {
                alert.remove();
            }, 8000); // เพิ่มเวลาเป็น 8 วินาที
        }

        // Auto-test connection on load
        window.addEventListener('load', () => {
            setTimeout(testConnection, 500);
        });
    </script>
</body>
</html>
