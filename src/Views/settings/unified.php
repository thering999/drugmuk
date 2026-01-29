<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?= \App\Core\CSRF::metaTag() ?>
    <title>ตั้งค่าการเชื่อมต่อ - Drugmuk</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600&family=Outfit:wght@400;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --primary-hover: #4f46e5;
            --secondary: #64748b;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --bg: #f8fafc;
            --card-bg: #ffffff;
            --text-main: #1e293b;
            --text-soft: #64748b;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Sarabun', 'Outfit', sans-serif;
            background: var(--bg);
            color: var(--text-main);
            line-height: 1.6;
        }

        .layout {
            display: grid;
            grid-template-columns: 280px 1fr;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            background: #1e293b;
            color: white;
            padding: 30px 20px;
        }

        .sidebar h2 {
            font-family: 'Outfit', sans-serif;
            font-size: 24px;
            margin-bottom: 40px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            border-radius: 8px;
            color: #94a3b8;
            text-decoration: none;
            transition: all 0.3s;
            margin-bottom: 5px;
            cursor: pointer;
        }

        .nav-item:hover, .nav-item.active {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .nav-item.active {
            border-left: 4px solid var(--primary);
        }

        /* Main Content Styles */
        .main-content {
            padding: 40px;
            max-width: 1200px;
        }

        .header {
            margin-bottom: 40px;
        }

        .header h1 {
            font-size: 32px;
            font-weight: 700;
            color: #0f172a;
        }

        .header p {
            color: var(--text-soft);
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }

        .card {
            background: var(--card-bg);
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            padding: 30px;
            border: 1px solid #e2e8f0;
            transition: transform 0.3s;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card h3 {
            font-size: 20px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #334155;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #475569;
        }

        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            gap: 8px;
            width: 100%;
        }

        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-hover); }

        .btn-outline { 
            background: transparent; 
            border: 2px solid #e2e8f0; 
            color: var(--text-main); 
        }
        .btn-outline:hover { background: #f8fafc; border-color: #cbd5e1; }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }

        .status-online { background: #d1fae5; color: #065f46; }
        .status-offline { background: #fee2e2; color: #991b1b; }

        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .main-content > * { animation: fadeIn 0.5s ease-out forwards; }

        /* JHCIS Section */
        .hospital-list {
            margin-top: 20px;
        }

        .hospital-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border: 1px solid #f1f5f9;
            border-radius: 12px;
            margin-bottom: 10px;
            background: #fdfdfd;
        }

        .hospital-info h4 { font-size: 14px; }
        .hospital-info p { font-size: 12px; color: var(--text-soft); }

        .badge-pill {
            padding: 2px 10px;
            background: #eef2ff;
            color: #4338ca;
            border-radius: 10px;
            font-size: 10px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="layout">
        <aside class="sidebar">
            <h2>💊 Drugmuk</h2>
            <nav>
                <a href="/dashboard" class="nav-item">
                    <span>📊</span> แดชบอร์ด
                </a>
                <a href="/settings/database" class="nav-item active">
                    <span>⚙️</span> ตั้งค่าระบบ
                </a>
                <a href="/tele-pharmacy/dashboard" class="nav-item">
                    <span>📹</span> Tele-pharmacy
                </a>
                <a href="/admin/jhcis/dashboard" class="nav-item">
                    <span>🏥</span> ระบบ JHCIS
                </a>
                <a href="/data-cleansing" class="nav-item">
                    <span>🧹</span> ข้อมูลคุณภาพ
                </a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="header">
                <h1>⚙️ ตั้งค่าการเชื่อมต่อ (All-in-One)</h1>
                <p>จัดการการเชื่อมต่อฐานข้อมูล Drugmuk และ JHCIS ในที่เดียว</p>
            </div>

            <div class="grid">
                <!-- Drugmuk Section -->
                <section class="card">
                    <h3><span>📦</span> ฐานข้อมูล Drugmuk (หลัก)</h3>
                    <form id="drugmukForm" onsubmit="saveDrugmuk(event)">
                        <?php echo \App\Core\CSRF::field(); ?>
                        <div class="form-group">
                            <label>Host</label>
                            <input type="text" name="host" value="<?= htmlspecialchars($drugmukConfig['host']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Port</label>
                            <input type="text" name="port" value="<?= htmlspecialchars($drugmukConfig['port']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Database Name</label>
                            <input type="text" name="database" value="<?= htmlspecialchars($drugmukConfig['database']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" name="username" value="<?= htmlspecialchars($drugmukConfig['username']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Password</label>
                            <input type="password" name="password" value="<?= htmlspecialchars($drugmukConfig['password']) ?>">
                        </div>
                        <div id="drugmukStatus" class="status-badge" style="display:none; margin-bottom: 15px; width: 100%; justify-content: center;"></div>
                        <div style="display: flex; gap: 10px;">
                            <button type="submit" class="btn btn-primary" style="flex: 2;">💾 บันทึกการตั้งค่า</button>
                            <button type="button" onclick="testDrugmukConnection()" class="btn btn-outline" style="flex: 1;">🔍 ทดสอบ</button>
                        </div>
                    </form>
                </section>

                <!-- JHCIS Database Section (Moved) -->
                <section class="card">
                    <h3><span>🏥</span> ฐานข้อมูล JHCIS (ระบบ JHCIS)</h3>
                    <form id="jhcisForm" onsubmit="saveSettings(event, 'jhcis')">
                        <?php echo \App\Core\CSRF::field(); ?>
                        <input type="hidden" name="type" value="jhcis">
                        <div class="form-group">
                            <label>Host</label>
                            <input type="text" name="host" value="<?= htmlspecialchars($jhcisConfig['host'] ?? 'localhost') ?>" placeholder="localhost หรือ IP address">
                        </div>
                        <div class="form-group">
                            <label>Port</label>
                            <input type="text" name="port" value="<?= htmlspecialchars($jhcisConfig['port'] ?? '3333') ?>" placeholder="3306 หรือ 3333">
                        </div>
                        <div class="form-group">
                            <label>Database Name</label>
                            <input type="text" name="database" value="<?= htmlspecialchars($jhcisConfig['database'] ?? 'jhcisdb') ?>" placeholder="jhcisdb">
                        </div>
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" name="username" value="<?= htmlspecialchars($jhcisConfig['username'] ?? 'root') ?>">
                        </div>
                        <div class="form-group">
                            <label>Password</label>
                            <input type="password" name="password" value="<?= htmlspecialchars($jhcisConfig['password'] ?? '') ?>">
                        </div>
                        <div id="jhcisStatus" class="status-badge" style="display:none; margin-bottom: 15px; width: 100%; justify-content: center;"></div>
                        <div style="display: flex; gap: 10px;">
                            <button type="submit" class="btn btn-primary" style="background:#10b981; flex: 2;">💾 บันทึกการตั้งค่า JHCIS</button>
                            <button type="button" onclick="testJhcisSettingsConnection()" class="btn btn-outline" style="flex: 1;">🔍 ทดสอบ</button>
                        </div>
                    </form>
                </section>

                <!-- LINE OA Section (New) -->
                <section class="card">
                    <h3><span>💬</span> LINE OA Integration (Phase 6)</h3>
                    <form onsubmit="saveSettings(event, 'line')">
                        <?php echo \App\Core\CSRF::field(); ?>
                        <input type="hidden" name="type" value="line">
                        <div class="form-group">
                            <label>Channel Access Token</label>
                            <input type="password" name="access_token" value="<?= htmlspecialchars($lineConfig['access_token']) ?>" placeholder="eyJh... (Long Lived Token)">
                        </div>
                        <div class="form-group">
                            <label>Channel Secret</label>
                            <input type="password" name="channel_secret" value="<?= htmlspecialchars($lineConfig['channel_secret']) ?>">
                        </div>
                        <div class="form-group">
                            <label>Admin User ID (For Alerts)</label>
                            <input type="text" name="admin_user_id" value="<?= htmlspecialchars($lineConfig['admin_user_id']) ?>" placeholder="U1234...">
                        </div>
                        <button type="submit" class="btn btn-primary" style="background:#06c755;">💾 บันทึกการตั้งค่า LINE</button>
                    </form>
                </section>

                <!-- AI & Machine Learning Section (New) -->
                <section class="card">
                    <h3><span>🧠</span> AI & Intelligence (Phase 6)</h3>
                    <form onsubmit="saveSettings(event, 'ai')">
                        <?php echo \App\Core\CSRF::field(); ?>
                        <input type="hidden" name="type" value="ai">
                        <div class="form-group">
                            <label>Python Binary Path</label>
                            <input type="text" name="python_path" value="<?= htmlspecialchars($aiConfig['python_path']) ?>" placeholder="python3 or C:\Python39\python.exe">
                        </div>
                        <div class="form-group">
                            <label>Forecasting Engine Model</label>
                            <select name="forecasting_model" style="width:100%; padding:12px; border-radius:10px; border:1px solid #e2e8f0;">
                                <option value="ENHANCED_EMA" <?= $aiConfig['forecasting_model'] == 'ENHANCED_EMA' ? 'selected' : '' ?>>Enhanced EMA (Smooth + Seasonal)</option>
                                <option value="PROPHET" <?= $aiConfig['forecasting_model'] == 'PROPHET' ? 'selected' : '' ?>>Facebook Prophet (Advanced Time-Series)</option>
                                <option value="LSTM" <?= $aiConfig['forecasting_model'] == 'LSTM' ? 'selected' : '' ?>>LSTM RNN (Deep Learning)</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary" style="background:#7c3aed;">💾 บันทึกการตั้งค่า AI</button>
                    </form>
                </section>
            </div>
        </main>
    </div>

    <!-- JHCIS Modal -->
    <div id="hospitalModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:100; align-items:center; justify-content:center;">
        <div class="card" style="max-width: 500px; width: 90%; margin: auto;">
            <h3 id="modalTitle">เพิ่ม/แก้ไข รพ.สต.</h3>
            <form id="hospitalForm" onsubmit="saveHospital(event)">
                <input type="hidden" id="hospitalId">
                <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>รหัส รพ.สต.</label>
                        <input type="text" id="hCode" required placeholder="เช่น MAIN">
                    </div>
                    <div class="form-group">
                        <label>ชื่อสถานพยาบาล</label>
                        <input type="text" id="hName" required placeholder="เช่น รพ.สต. บ้านโพธิ์">
                    </div>
                </div>
                <div class="form-group">
                    <label>Database Host</label>
                    <input type="text" id="hHost" value="host.docker.internal">
                </div>
                <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>Database Name</label>
                        <input type="text" id="hDb" required placeholder="เช่น jhcisdb">
                    </div>
                    <div class="form-group">
                        <label>Port</label>
                        <input type="number" id="hPort" value="3306">
                    </div>
                </div>
                <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>User</label>
                        <input type="text" id="hUser" value="root">
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" id="hPass">
                    </div>
                </div>
                <div class="form-group">
                    <label>สถานะ</label>
                    <select id="hActive" style="width:100%; padding:12px; border-radius:10px; border:1px solid #e2e8f0;">
                        <option value="1">Active (เปิดใช้งาน)</option>
                        <option value="0">Inactive (ปิดใช้งาน)</option>
                    </select>
                </div>
                <div style="display: flex; gap: 10px; margin-top: 10px;">
                    <button type="submit" class="btn btn-primary">💾 บันทึก</button>
                    <button type="button" onclick="closeModal()" class="btn btn-outline">ยกเลิก</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Get CSRF token from form or meta tag
        function getCSRFToken() {
            const tokenInput = document.querySelector('input[name="csrf_token"]');
            if (tokenInput) return tokenInput.value;
            const metaTag = document.querySelector('meta[name="csrf-token"]');
            return metaTag ? metaTag.content : '';
        }

        function showAddModal() {
            document.getElementById('modalTitle').innerText = '➕ เพิ่ม รพ.สต. ใหม่';
            document.getElementById('hospitalId').value = '';
            document.getElementById('hospitalForm').reset();
            document.getElementById('hospitalModal').style.display = 'flex';
        }

        function editHospital(h) {
            document.getElementById('modalTitle').innerText = '📝 แก้ไขข้อมูล ' + h.name;
            document.getElementById('hospitalId').value = h.id;
            document.getElementById('hCode').value = h.code;
            document.getElementById('hName').value = h.name;
            document.getElementById('hHost').value = h.db_host;
            document.getElementById('hDb').value = h.db_name;
            document.getElementById('hPort').value = h.db_port;
            document.getElementById('hUser').value = h.db_user;
            document.getElementById('hPass').value = ''; // Don't show password
            document.getElementById('hActive').value = h.is_active;
            document.getElementById('hospitalModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('hospitalModal').style.display = 'none';
        }

        async function saveSettings(event, type) {
            event.preventDefault();
            const form = event.target;
            const formData = new FormData(form);
            formData.append('type', type);
            
            try {
                const response = await fetch('/api/settings/update', { 
                    method: 'POST', 
                    headers: { 'X-CSRF-Token': getCSRFToken() },
                    body: formData
                });
                const data = await response.json();
                if (data.success) {
                    alert('✅ ' + data.message);
                    // ทริกเกอร์การทดสอบอัตโนมัติหลังบันทึก
                    if (type === 'drugmuk') testDrugmukConnection();
                    if (type === 'jhcis') testJhcisSettingsConnection();
                } else alert('❌ ' + data.message);
            } catch (error) { alert('❌ ' + error.message); }
        }

        async function testConnectionAPI(formData, statusId) {
            const statusDiv = document.getElementById(statusId);
            statusDiv.style.display = 'flex';
            statusDiv.className = 'status-badge';
            statusDiv.innerHTML = '⏳ กำลังทดสอบ...';
            statusDiv.style.backgroundColor = '#eef2ff';
            statusDiv.style.color = '#4338ca';

            try {
                const response = await fetch('/api/settings/test-connection', { 
                    method: 'POST', 
                    headers: { 'X-CSRF-Token': getCSRFToken() },
                    body: formData
                });
                const data = await response.json();
                
                if (data.success) {
                    statusDiv.className = 'status-badge status-online';
                    statusDiv.innerHTML = '✅ เชื่อมต่อสำเร็จ! (' + (data.latency || 0) + 'ms)';
                } else {
                    statusDiv.className = 'status-badge status-offline';
                    statusDiv.innerHTML = '❌ เชื่อมต่อไม่สำเร็จ: ' + data.message;
                }
            } catch (error) {
                statusDiv.className = 'status-badge status-offline';
                statusDiv.innerHTML = '❌ เกิดข้อผิดพลาดทางเทคนิค';
            }
        }

        function testDrugmukConnection() {
            const form = document.getElementById('drugmukForm');
            const formData = new FormData(form);
            testConnectionAPI(formData, 'drugmukStatus');
        }

        function testJhcisSettingsConnection() {
            const form = document.getElementById('jhcisForm');
            const formData = new FormData(form);
            testConnectionAPI(formData, 'jhcisStatus');
        }

        async function saveDrugmuk(event) {
            saveSettings(event, 'drugmuk');
        }

        async function saveHospital(event) {
            event.preventDefault();
            const id = document.getElementById('hospitalId').value;
            const url = id ? '/admin/jhcis/hospitals/update' : '/admin/jhcis/hospitals/add';
            
            const formData = new FormData();
            if (id) formData.append('id', id);
            formData.append('code', document.getElementById('hCode').value);
            formData.append('name', document.getElementById('hName').value);
            formData.append('db_host', document.getElementById('hHost').value);
            formData.append('db_port', document.getElementById('hPort').value);
            formData.append('db_name', document.getElementById('hDb').value);
            formData.append('db_user', document.getElementById('hUser').value);
            formData.append('db_pass', document.getElementById('hPass').value);
            formData.append('is_active', document.getElementById('hActive').value);

            try {
                const response = await fetch(url, { 
                    method: 'POST', 
                    headers: { 'X-CSRF-Token': getCSRFToken() },
                    body: formData
                });
                const data = await response.json();
                if (data.success) {
                    alert('✅ บันทึกสำเร็จ');
                    location.reload();
                } else alert('❌ ' + data.message);
            } catch (error) { alert('❌ ' + error.message); }
        }

        async function testConnection(id) {
            try {
                const response = await fetch('/api/jhcis/connection/test', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-CSRF-Token': getCSRFToken()
                    },
                    body: 'hospital_id=' + id
                });
                const data = await response.json();
                if (data.success) alert('✅ เชื่อมต่อสำเร็จ!\nDB Version: ' + data.version + '\nResponse: ' + data.response_time_ms + ' ms');
                else alert('❌ เชื่อมต่อไม่สำเร็จ: ' + data.error);
            } catch (error) { alert('❌ Error: ' + error.message); }
        }

        async function deleteHospital(id, name) {
            if (!confirm(`⚠️ คุณแน่ใจหรือไม่ที่จะลบ "${name}"?\n\nการลบจะไม่สามารถกู้คืนได้!`)) {
                return;
            }

            try {
                const formData = new FormData();
                formData.append('id', id);
                
                const response = await fetch('/admin/jhcis/hospitals/delete', {
                    method: 'POST',
                    headers: { 'X-CSRF-Token': getCSRFToken() },
                    body: formData
                });
                const data = await response.json();
                
                if (data.success) {
                    alert('✅ ลบ รพ.สต. สำเร็จ');
                    location.reload();
                } else {
                    alert('❌ ' + data.message);
                }
            } catch (error) {
                alert('❌ Error: ' + error.message);
            }
        }
    </script>
</body>
</html>

