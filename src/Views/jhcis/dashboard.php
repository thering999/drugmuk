<?php
/**
 * JHCIS Integration Dashboard View
 * 
 * หน้าจัดการการเชื่อมต่อกับ JHCIS
 */

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= \App\Core\CSRF::token() ?>">
    <title>JHCIS Integration - Drugmuk</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* ... Existing Styles ... */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Sarabun', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh; padding: 2rem;
        }
        .container { max-width: 1400px; margin: 0 auto; }
        .header {
            background: rgba(255, 255, 255, 0.15); backdrop-filter: blur(20px);
            border-radius: 20px; padding: 2rem; margin-bottom: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        .header h1 { color: white; font-size: 2rem; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 1rem; }
        .header p { color: rgba(255, 255, 255, 0.9); font-size: 1.1rem; }
        .back-button {
            display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.5rem;
            background: rgba(255, 255, 255, 0.2); color: white; text-decoration: none;
            border-radius: 10px; margin-bottom: 1rem; transition: all 0.3s ease;
        }
        .back-button:hover { background: rgba(255, 255, 255, 0.3); transform: translateX(-5px); }
        .grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem; margin-bottom: 2rem;
        }
        .card {
            background: rgba(255, 255, 255, 0.15); backdrop-filter: blur(20px);
            border-radius: 20px; padding: 2rem; border: 1px solid rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
        }
        .card:hover { transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2); }
        .card-title {
            color: white; font-size: 1.5rem; font-weight: 600; margin-bottom: 1rem;
            display: flex; align-items: center; gap: 0.75rem;
        }
        .stat-number { font-size: 3rem; font-weight: 700; color: white; margin: 1rem 0; }
        .stat-label { font-size: 1rem; color: rgba(255, 255, 255, 0.8); }
        .btn {
            display: inline-flex; align-items: center; gap: 0.5rem; padding: 1rem 2rem;
            background: white; color: #667eea; border: none; border-radius: 50px;
            font-size: 1rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease;
            text-decoration: none; margin-top: 1rem;
        }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2); }
        .btn-small { padding: 0.5rem 1rem; font-size: 0.9rem; margin-top: 0; }
        .btn-secondary { background: transparent; color: white; border: 2px solid white; }
        .btn-secondary:hover { background: white; color: #667eea; }
        .btn-danger { background: #ff4757; color: white; }
        .section {
            background: rgba(255, 255, 255, 0.15); backdrop-filter: blur(20px);
            border-radius: 20px; padding: 2rem; margin-bottom: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        .section-title {
            color: white; font-size: 1.75rem; font-weight: 600; margin-bottom: 1.5rem;
            display: flex; align-items: center; gap: 0.75rem;
        }
        .table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        .table th, .table td { padding: 1rem; text-align: left; border-bottom: 1px solid rgba(255, 255, 255, 0.2); }
        .table th { color: white; font-weight: 600; background: rgba(255, 255, 255, 0.1); }
        .table td { color: rgba(255, 255, 255, 0.9); }
        .table tr:hover { background: rgba(255, 255, 255, 0.05); }
        .badge { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 50px; font-size: 0.85rem; font-weight: 600; }
        .badge-success { background: #2ecc71; color: white; }
        .badge-warning { background: #f39c12; color: white; }
        .badge-danger { background: #e74c3c; color: white; }
        .badge-info { background: #3498db; color: white; }
        .form-group { margin-bottom: 1.5rem; }
        .form-label { display: block; color: white; font-weight: 600; margin-bottom: 0.5rem; }
        .form-input {
            width: 100%; padding: 0.75rem 1rem; border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 10px; background: rgba(255, 255, 255, 0.1); color: white; font-size: 1rem;
        }
        .alert { padding: 1rem 1.5rem; border-radius: 10px; margin-bottom: 1rem; }
        .alert-success { background: rgba(46, 204, 113, 0.2); border: 1px solid #2ecc71; color: white; }
        .alert-danger { background: rgba(231, 76, 60, 0.2); border: 1px solid #e74c3c; color: white; }
        .loading { display: inline-block; width: 20px; height: 20px; border: 3px solid rgba(255, 255, 255, 0.3); border-radius: 50%; border-top-color: white; animation: spin 1s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* Modal Styles */
        .modal {
            display: none; position: fixed; z-index: 1000; left: 0; top: 0;
            width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);
            backdrop-filter: blur(5px);
        }
        .modal-content {
            background-color: #fefefe; margin: 10% auto; padding: 0;
            border-radius: 15px; width: 80%; max-width: 800px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            animation: slideIn 0.3s ease;
        }
        @keyframes slideIn { from { transform: translateY(-50px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .modal-header {
            padding: 1.5rem; border-bottom: 1px solid #e5e7eb;
            display: flex; justify-content: space-between; align-items: center;
        }
        .modal-title { font-size: 1.25rem; font-weight: 600; color: #1f2937; }
        .close { color: #6b7280; font-size: 28px; font-weight: bold; cursor: pointer; }
        .modal-body { padding: 1.5rem; max-height: 60vh; overflow-y: auto; }
        .error-item {
            background: #fef2f2; border-left: 4px solid #ef4444; padding: 1rem;
            margin-bottom: 1rem; border-radius: 0.5rem;
        }
        .error-type { font-weight: 600; color: #991b1b; display: block; margin-bottom: 0.25rem; }
        .error-msg { color: #7f1d1d; }
        .error-data { 
            margin-top: 0.5rem; font-family: monospace; font-size: 0.875rem; 
            background: rgba(255,255,255,0.5); padding: 0.5rem; border-radius: 0.25rem;
            white-space: pre-wrap;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="/dashboard" class="back-button"><span>←</span><span>กลับหน้าหลัก</span></a>

        <div class="header">
            <h1><span>🔗</span><span>JHCIS Integration Dashboard</span></h1>
            <p>ระบบติดตามสถานะการเชื่อมต่อแบบ Real-time</p>
            <div style="margin-top: 1rem; display: flex; gap: 1rem; flex-wrap: wrap;">
                <a href="/settings/database" class="btn btn-secondary">
                    <span>⚙️</span><span>ตั้งค่าการเชื่อมต่อ</span>
                </a>
                <a href="/settings/database" class="btn" style="background: #3b82f6; color: white;">
                    <span>🏥</span><span>จัดการหลาย รพ.สต.</span>
                </a>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid">
            <div class="card">
                <div class="card-title"><span class="card-icon">📊</span><span>Drug Mapping</span></div>
                <div class="card-content">
                    <div class="stat-number" id="mappedCount">-</div>
                    <div class="stat-label">ยาที่ Map แล้ว</div>
                    <a href="/admin/jhcis/mapping" class="btn"><span>🔍</span><span>ดูรายละเอียด</span></a>
                </div>
            </div>

            <div class="card">
                <div class="card-title"><span class="card-icon">⚠️</span><span>Unmapped Drugs</span></div>
                <div class="card-content">
                    <div class="stat-number" id="unmappedCount">-</div>
                    <div class="stat-label">ยาที่ยังไม่ได้ Map</div>
                    <a href="/admin/jhcis/unmapped-drugs" class="btn btn-secondary"><span>📝</span><span>จัดการ</span></a>
                </div>
            </div>

            <div class="card">
                <div class="card-title"><span class="card-icon">🔄</span><span>Last Sync Status</span></div>
                <div class="card-content">
                    <div class="stat-number" style="font-size: 1.5rem;" id="lastSyncStatus">-</div>
                    <div class="stat-label" id="lastSyncTime">-</div>
                    <button class="btn" onclick="syncNow()"><span>⚡</span><span>Sync ด่วน</span></button>
                </div>
            </div>
        </div>

        <!-- Sync Control -->
        <div class="section">
            <div class="section-title"><span>⚙️</span><span>Manual Sync Control</span></div>
            <div id="syncAlert"></div>
            <div style="display: flex; gap: 1rem; align-items: flex-end;">
                <div class="form-group" style="flex: 1; margin-bottom: 0;">
                    <label class="form-label">วันที่เริ่มต้น</label>
                    <input type="date" class="form-input" id="fromDate" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="form-group" style="flex: 1; margin-bottom: 0;">
                    <label class="form-label">วันที่สิ้นสุด</label>
                    <input type="date" class="form-input" id="toDate" value="<?= date('Y-m-d') ?>">
                </div>
                <button class="btn" onclick="syncDispensing()" id="btnStartSync" style="margin-top: 0;">
                    <span>🚀</span><span>เริ่ม Sync</span>
                </button>
            </div>
        </div>

        <!-- Real-time Sync History -->
        <div class="section">
            <div class="section-title">
                <span>📋</span><span>Live Sync Monitor</span>
                <span class="badge badge-info" id="liveIndicator" style="display:none; margin-left:10px; font-size: 0.8rem; animation: pulse 2s infinite;">● LIVE</span>
            </div>

            <table class="table" id="syncHistoryTable">
                <thead>
                    <tr>
                        <th>เริ่ม</th>
                        <th>ประเภท</th>
                        <th>เวลาที่ใช้</th>
                        <th>ประมวลผล</th>
                        <th>สำเร็จ</th>
                        <th>ล้มเหลว</th>
                        <th>สถานะ</th>
                        <th>การกระทำ</th>
                    </tr>
                </thead>
                <tbody id="syncHistoryBody">
                    <tr><td colspan="8" style="text-align: center;"><div class="loading"></div></td></tr>
                </tbody>
            </table>
        </div>
        
        <!-- Reconciliation Short Report -->
        <div class="section">
            <div class="section-title">⚖️ Reconciliation</div>
             <p style="color:rgba(255,255,255,0.8); margin-bottom:1rem;">ตรวจสอบยอดคงเหลือ (Drugmuk vs JHCIS)</p>
             <button class="btn btn-secondary" onclick="checkReconciliation()">ตรวจสอบความถูกต้อง</button>
             <div id="reconciliationResult" style="margin-top:1rem;"></div>
        </div>
    </div>

    <!-- Error Modal -->
    <div id="errorModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">รายละเอียดข้อผิดพลาด</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <div class="modal-body" id="errorModalBody">
                <div class="loading" style="border-color: #666; border-top-color: #333;"></div>
            </div>
        </div>
    </div>

    <script>
        let pollInterval;

        function getCSRFToken() {
            return document.querySelector('meta[name="csrf-token"]').content;
        }

        document.addEventListener('DOMContentLoaded', function() {
            loadStats();
            loadSyncHistory();
            startPolling();
        });

        function startPolling() {
            // Poll every 5 seconds normally, or 2 seconds if active sync
            pollInterval = setInterval(loadSyncHistory, 5000);
        }

        async function loadStats() {
            try {
                // Drug Mapping
                fetch('/api/jhcis/mapping/drugs').then(r => r.json()).then(data => {
                    document.getElementById('mappedCount').textContent = data.length;
                });
                // Unmapped
                fetch('/api/jhcis/unmapped-drugs').then(r => r.json()).then(data => {
                    document.getElementById('unmappedCount').textContent = data.length;
                });
            } catch (e) { console.error(e); }
        }

        async function loadSyncHistory() {
            try {
                const response = await fetch('/api/jhcis/sync/status');
                const data = await response.json();
                
                const tbody = document.getElementById('syncHistoryBody');
                tbody.innerHTML = '';

                if (data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="8" style="text-align: center;">ไม่มีประวัติ</td></tr>';
                    return;
                }

                // Update Status Card
                if (data[0]) {
                    document.getElementById('lastSyncStatus').innerHTML = getStatusBadge(data[0].status);
                    const d = new Date(data[0].sync_start);
                    document.getElementById('lastSyncTime').textContent = d.toLocaleString('th-TH');
                }

                // Check if any is running to adjust polling speed (optional logic)
                const isRunning = data.some(item => item.status === 'running');
                const indicator = document.getElementById('liveIndicator');
                if (isRunning) {
                     indicator.style.display = 'inline-block';
                     if (pollInterval) clearInterval(pollInterval);
                     pollInterval = setInterval(loadSyncHistory, 2000); // Poll faster
                } else {
                     indicator.style.display = 'none';
                     if (pollInterval) clearInterval(pollInterval);
                     pollInterval = setInterval(loadSyncHistory, 5000); // Poll normal
                }

                data.forEach(item => {
                    const row = document.createElement('tr');
                    const start = new Date(item.sync_start).toLocaleString('th-TH');
                    const duration = item.duration_seconds ? formatDuration(item.duration_seconds) : '-';
                    
                    let actionBtn = '-';
                    if (item.records_failed > 0) {
                        actionBtn = `<button class="btn btn-small btn-danger" onclick="viewErrors(${item.id})">ดู Error (${item.records_failed})</button>`;
                    }

                    row.innerHTML = `
                        <td>${start}</td>
                        <td><span class="badge badge-info">${item.sync_type}</span></td>
                        <td>${duration}</td>
                        <td>${item.records_processed || 0}</td>
                        <td style="color:#2ecc71; font-weight:bold;">${item.records_success || 0}</td>
                        <td style="color:#e74c3c; font-weight:bold;">${item.records_failed || 0}</td>
                        <td>${getStatusBadge(item.status)}</td>
                        <td>${actionBtn}</td>
                    `;
                    tbody.appendChild(row);
                });
            } catch (error) {
                console.error(error);
            }
        }

        function getStatusBadge(status) {
            if (status === 'completed') return '<span class="badge badge-success">สำเร็จ</span>';
            if (status === 'failed') return '<span class="badge badge-danger">ล้มเหลว</span>';
            return '<span class="badge badge-warning">กำลังทำงาน...</span>';
        }

        function formatDuration(seconds) {
            if (seconds < 60) return seconds + ' วินาที';
            const min = Math.floor(seconds / 60);
            const sec = seconds % 60;
            return `${min} นาที ${sec} วิ`;
        }

        async function syncDispensing() {
            const from = document.getElementById('fromDate').value;
            const to = document.getElementById('toDate').value;
            if(!from || !to) return alert('กรุณาระบุวันที่');
            
            // Disable button
            const btn = document.getElementById('btnStartSync');
            btn.disabled = true; btn.innerHTML = '<span class="loading"></span> กำลังเริ่ม...';

            try {
                const res = await fetch('/api/jhcis/sync/dispensing', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-CSRF-Token': getCSRFToken()
                    },
                    body: `from_date=${from}&to_date=${to}`
                });
                const result = await res.json();
                if(result.status === 'success') {
                    // Trigger immediate refresh
                    loadSyncHistory(); 
                } else {
                    alert('Error: ' + result.message);
                }
            } catch(e) { alert(e.message); }
            finally {
                btn.disabled = false;
                btn.innerHTML = '<span>🚀</span><span>เริ่ม Sync</span>';
            }
        }
        
        async function syncNow() {
             const today = new Date().toISOString().split('T')[0];
             document.getElementById('fromDate').value = today;
             document.getElementById('toDate').value = today;
             syncDispensing();
        }

        // Modal Functions
        async function viewErrors(logId) {
            const modal = document.getElementById('errorModal');
            const body = document.getElementById('errorModalBody');
            modal.style.display = "block";
            body.innerHTML = '<div style="text-align:center; padding:20px;"><div class="loading" style="border-color:#666; border-top-color:#000;"></div> Loading...</div>';

            try {
                const res = await fetch(`/api/jhcis/sync/errors?id=${logId}`);
                const errors = await res.json();
                
                if (errors.length === 0) {
                    body.innerHTML = '<div style="text-align:center; padding:20px;">ไม่พบข้อผิดพลาด</div>';
                    return;
                }

                let html = '';
                errors.forEach(err => {
                    html += `
                        <div class="error-item">
                            <span class="error-type">${err.error_type}</span>
                            <div class="error-msg">${err.error_message}</div>
                            ${err.record_data ? `<div class="error-data">${err.record_data}</div>` : ''}
                        </div>
                    `;
                });
                body.innerHTML = html;
            } catch (e) {
                body.innerHTML = '<div style="color:red; text-align:center;">Failed to load errors</div>';
            }
        }

        function closeModal() {
            document.getElementById('errorModal').style.display = "none";
        }
        window.onclick = function(event) {
            if (event.target == document.getElementById('errorModal')) {
                closeModal();
            }
        }

        async function checkReconciliation() {
            const div = document.getElementById('reconciliationResult');
            div.innerHTML = 'Checking...';
            try {
                const res = await fetch('/api/jhcis/reconciliation');
                const response = await res.json();
                
                if (!response.success) {
                    div.innerHTML = `<div class="alert alert-danger">❌ ${response.message}</div>`;
                    return;
                }
                
                const total = response.total || 0;
                if (total === 0) {
                    div.innerHTML = '<div class="alert alert-success">✅ ข้อมูลตรงกัน</div>';
                } else {
                    div.innerHTML = `<div class="alert alert-danger">⚠️ พบข้อแตกต่าง ${total} รายการ</div>`;
                }
            } catch(e) { 
                console.error(e);
                div.innerHTML = '<div class="alert alert-danger">Error: ' + e.message + '</div>'; 
            }
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
