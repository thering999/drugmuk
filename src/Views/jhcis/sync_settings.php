<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sync Settings - JHCIS</title>
    <?= \App\Core\CSRF::metaTag() ?>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .settings-container {
            max-width: 900px;
            margin: 20px auto;
            padding: 20px;
        }
        .page-header {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        .settings-card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .setting-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .setting-row:last-child {
            border-bottom: none;
        }
        .setting-info {
            flex: 1;
        }
        .setting-title {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        .setting-description {
            color: #666;
            font-size: 14px;
        }
        .setting-control {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .toggle-switch {
            position: relative;
            width: 60px;
            height: 30px;
        }
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 30px;
        }
        .slider:before {
            position: absolute;
            content: "";
            height: 22px;
            width: 22px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        input:checked + .slider {
            background-color: #10b981;
        }
        input:checked + .slider:before {
            transform: translateX(30px);
        }
        .form-control {
            padding: 10px;
            border: 2px solid #e5e7eb;
            border-radius: 5px;
            font-size: 14px;
            width: 100px;
        }
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.2s;
        }
        .btn-primary {
            background: #3b82f6;
            color: white;
        }
        .btn-success {
            background: #10b981;
            color: white;
        }
        .btn-danger {
            background: #ef4444;
            color: white;
        }
        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
        }
        .status-enabled {
            background: #d1fae5;
            color: #065f46;
        }
        .status-disabled {
            background: #fee2e2;
            color: #991b1b;
        }
        .info-box {
            background: #eff6ff;
            border-left: 4px solid #3b82f6;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .connection-test {
            background: #f9fafb;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
        }
        .test-result {
            padding: 15px;
            border-radius: 5px;
            margin-top: 10px;
        }
        .test-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #10b981;
        }
        .test-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #ef4444;
        }
    </style>
</head>
<body>
    <div class="settings-container">
        <div class="page-header">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1>⚙️ Sync Settings</h1>
                    <p>กำหนดค่าการซิงค์ข้อมูลอัตโนมัติ</p>
                </div>
                <a href="/admin/jhcis/dashboard" class="btn btn-primary" style="text-decoration: none; display: inline-flex; align-items: center; gap: 8px; background: white; color: #3b82f6;">
                    ← กลับหน้าหลัก
                </a>
            </div>
        </div>

        <div class="settings-card">
            <h2>🔄 Real-time Sync Configuration</h2>
            
            <div class="info-box">
                <strong>ℹ️ Information:</strong> Real-time sync will automatically synchronize data from JHCIS at regular intervals.
            </div>

            <div class="setting-row">
                <div class="setting-info">
                    <div class="setting-title">Enable Auto-Sync</div>
                    <div class="setting-description">
                        เปิด/ปิดการซิงค์อัตโนมัติ
                    </div>
                </div>
                <div class="setting-control">
                    <span class="status-badge <?= ($status['enabled'] ?? 0) ? 'status-enabled' : 'status-disabled' ?>" id="statusBadge">
                        <?= ($status['enabled'] ?? 0) ? 'Enabled' : 'Disabled' ?>
                    </span>
                    <label class="toggle-switch">
                        <input type="checkbox" id="enableSync" 
                               <?= ($status['enabled'] ?? 0) ? 'checked' : '' ?>
                               onchange="toggleSync()">
                        <span class="slider"></span>
                    </label>
                </div>
            </div>

            <div class="setting-row">
                <div class="setting-info">
                    <div class="setting-title">Sync Interval</div>
                    <div class="setting-description">
                        ระยะเวลาระหว่างการซิงค์ (นาที)
                    </div>
                </div>
                <div class="setting-control">
                    <input type="number" id="syncInterval" class="form-control" 
                           value="<?= $status['interval_minutes'] ?? 15 ?>" 
                           min="5" max="1440">
                    <span>minutes</span>
                </div>
            </div>

            <div class="setting-row">
                <div class="setting-info">
                    <div class="setting-title">Last Sync</div>
                    <div class="setting-description">
                        การซิงค์ครั้งล่าสุด
                    </div>
                </div>
                <div class="setting-control">
                    <span style="color: #666;">
                        <?= $status['last_sync_at'] ?? 'Never' ?>
                    </span>
                </div>
            </div>

            <div style="margin-top: 30px; display: flex; gap: 15px; justify-content: flex-end;">
                <button class="btn btn-primary" onclick="saveSettings()">
                    💾 Save Settings
                </button>
                <button class="btn btn-success" onclick="syncNow()">
                    🔄 Sync Now
                </button>
            </div>
        </div>

        <div class="settings-card">
            <h2>🔌 Connection Test</h2>
            
            <div class="connection-test">
                <p style="color: #666; margin-bottom: 15px;">
                    Test the connection to JHCIS database
                </p>
                <button class="btn btn-primary" onclick="testConnection()">
                    🧪 Test Connection
                </button>
                
                <div id="testResult" style="display: none;"></div>
            </div>
        </div>

        <div class="settings-card">
            <h2>📊 Sync Statistics</h2>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                <div style="text-align: center; padding: 20px; background: #f9fafb; border-radius: 10px;">
                    <div style="font-size: 32px; font-weight: bold; color: #3b82f6;">
                        <?= $status['total_syncs'] ?? 0 ?>
                    </div>
                    <div style="color: #666; margin-top: 5px;">Total Syncs</div>
                </div>
                <div style="text-align: center; padding: 20px; background: #f9fafb; border-radius: 10px;">
                    <div style="font-size: 32px; font-weight: bold; color: #10b981;">
                        <?= $status['success_rate'] ?? '0' ?>%
                    </div>
                    <div style="color: #666; margin-top: 5px;">Success Rate</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const hospitalId = <?= $_GET['hospital_id'] ?? 0 ?>;

        async function toggleSync() {
            const enabled = document.getElementById('enableSync').checked;
            const interval = document.getElementById('syncInterval').value;

            try {
                const formData = new FormData();
                formData.append('hospital_id', hospitalId);
                formData.append('enabled', enabled ? '1' : '0');
                formData.append('interval', interval);

                const response = await fetch('/api/jhcis/sync/settings', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    updateStatusBadge(enabled);
                    alert(enabled ? 'Auto-sync enabled' : 'Auto-sync disabled');
                } else {
                    alert('Error: ' + data.message);
                    document.getElementById('enableSync').checked = !enabled;
                }
            } catch (error) {
                alert('Error: ' + error.message);
                document.getElementById('enableSync').checked = !enabled;
            }
        }

        async function saveSettings() {
            const enabled = document.getElementById('enableSync').checked;
            const interval = document.getElementById('syncInterval').value;

            if (interval < 5 || interval > 1440) {
                alert('Interval must be between 5 and 1440 minutes');
                return;
            }

            try {
                const formData = new FormData();
                formData.append('hospital_id', hospitalId);
                formData.append('enabled', enabled ? '1' : '0');
                formData.append('interval', interval);

                const response = await fetch('/api/jhcis/sync/settings', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    alert('Settings saved successfully');
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (error) {
                alert('Error: ' + error.message);
            }
        }

        async function syncNow() {
            if (!confirm('Start manual sync now?')) {
                return;
            }

            alert('Sync started in background. Check dashboard for progress.');
            
            // Trigger sync via API
            // This would call the existing sync endpoint
        }

        async function testConnection() {
            const resultDiv = document.getElementById('testResult');
            resultDiv.style.display = 'block';
            resultDiv.className = 'test-result';
            resultDiv.innerHTML = 'Testing connection...';

            try {
                const formData = new FormData();
                formData.append('hospital_id', hospitalId);

                const response = await fetch('/api/jhcis/connection/test', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success && data.data.success) {
                    resultDiv.className = 'test-result test-success';
                    resultDiv.innerHTML = `
                        <strong>✅ Connection Successful</strong><br>
                        Database: ${data.data.database}<br>
                        Version: ${data.data.version}<br>
                        Response Time: ${data.data.response_time_ms}ms
                    `;
                } else {
                    resultDiv.className = 'test-result test-error';
                    resultDiv.innerHTML = `
                        <strong>❌ Connection Failed</strong><br>
                        ${data.data.error || data.message}
                    `;
                }
            } catch (error) {
                resultDiv.className = 'test-result test-error';
                resultDiv.innerHTML = `
                    <strong>❌ Error</strong><br>
                    ${error.message}
                `;
            }
        }

        function updateStatusBadge(enabled) {
            const badge = document.getElementById('statusBadge');
            badge.textContent = enabled ? 'Enabled' : 'Disabled';
            badge.className = 'status-badge ' + (enabled ? 'status-enabled' : 'status-disabled');
        }
    </script>
    <script>
        // Auto-include CSRF token in all AJAX requests
        document.addEventListener('DOMContentLoaded', function() {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            
            if (csrfToken) {
                // Fetch API
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
    </script>
</body>
</html>
