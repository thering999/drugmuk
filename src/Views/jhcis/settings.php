<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตั้งค่า JHCIS - Drugmuk</title>
    <?= \App\Core\CSRF::metaTag() ?>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Sarabun', sans-serif;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            min-height: 100vh; padding: 2rem;
        }
        .container { max-width: 900px; margin: 0 auto; }
        .header {
            background: rgba(255, 255, 255, 0.15); backdrop-filter: blur(20px);
            border-radius: 20px; padding: 2rem; margin-bottom: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.3); color: white;
        }
        .card {
            background: white; border-radius: 15px; padding: 2rem;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1); margin-bottom: 2rem;
        }
        .form-group { margin-bottom: 1.5rem; }
        .label {
            display: block; margin-bottom: 0.5rem; font-weight: 600;
            color: #374151; font-size: 0.95rem;
        }
        .input {
            width: 100%; padding: 0.75rem 1rem; border: 2px solid #e5e7eb;
            border-radius: 8px; font-size: 1rem; transition: border 0.3s;
        }
        .input:focus { outline: none; border-color: #10b981; }
        .btn {
            padding: 0.75rem 1.5rem; border: none; border-radius: 8px;
            font-size: 1rem; font-weight: 600; cursor: pointer;
            transition: all 0.2s; display: inline-flex; align-items: center;
            gap: 0.5rem;
        }
        .btn-primary { background: #10b981; color: white; }
        .btn-primary:hover { background: #059669; }
        .btn-secondary { background: #6b7280; color: white; }
        .btn-secondary:hover { background: #4b5563; }
        .btn:disabled { background: #9ca3af; cursor: not-allowed; }
        .alert {
            padding: 1rem; border-radius: 8px; margin-bottom: 1rem;
            display: none;
        }
        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #10b981; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #ef4444; }
        .alert-info { background: #dbeafe; color: #1e40af; border: 1px solid #3b82f6; }
        .spinner {
            width: 20px; height: 20px; border: 3px solid #ffffff;
            border-top: 3px solid rgba(255,255,255,0.3); border-radius: 50%;
            animation: spin 1s linear infinite; display: none;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        .back-link {
            display: inline-block; margin-bottom: 20px; color: white;
            text-decoration: none; background: rgba(255,255,255,0.2);
            padding: 8px 16px; border-radius: 8px;
        }
        .info-box {
            background: #f3f4f6; padding: 1rem; border-radius: 8px;
            border-left: 4px solid #10b981; margin-bottom: 1.5rem;
        }
        .table-list {
            max-height: 200px; overflow-y: auto; background: #f9fafb;
            padding: 1rem; border-radius: 8px; margin-top: 0.5rem;
        }
        .table-item {
            padding: 0.5rem; border-bottom: 1px solid #e5e7eb;
            display: flex; justify-content: space-between;
        }
        .badge {
            display: inline-block; padding: 0.25rem 0.75rem;
            border-radius: 12px; font-size: 0.85rem; font-weight: 600;
        }
        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-info { background: #dbeafe; color: #1e40af; }
    </style>
</head>
<body>
    <div class="container">
        <a href="/admin/jhcis/dashboard" class="back-link">← กลับไป JHCIS Dashboard</a>

        <div class="header">
            <h1>⚙️ ตั้งค่าการเชื่อมต่อ JHCIS</h1>
            <p>กำหนดค่าการเชื่อมต่อกับฐานข้อมูล JHCIS</p>
        </div>

        <div id="alertBox" class="alert"></div>

        <div class="card">
            <h2 style="margin-bottom: 1.5rem; color: #374151;">การตั้งค่าฐานข้อมูล</h2>

            <div class="info-box">
                <strong>💡 คำแนะนำ:</strong> กรอกข้อมูลการเชื่อมต่อฐานข้อมูล JHCIS ของคุณ 
                จากนั้นกดปุ่ม "ทดสอบการเชื่อมต่อ" เพื่อตรวจสอบก่อนบันทึก<br><br>
                <strong>📌 สำหรับ Docker:</strong> ถ้า JHCIS อยู่บนเครื่องเดียวกัน ให้ใช้ <code>localhost</code> 
                (ระบบจะแปลงเป็น host.docker.internal อัตโนมัติ)
            </div>

            <form id="settingsForm">
                <div class="form-group">
                    <label class="label">Host / IP Address</label>
                    <input type="text" class="input" id="host" name="host" 
                           value="<?php echo htmlspecialchars($config['host'] ?? 'localhost'); ?>" 
                           placeholder="localhost หรือ 192.168.1.100">
                </div>

                <div class="form-group">
                    <label class="label">Port</label>
                    <input type="text" class="input" id="port" name="port" 
                           value="<?php echo htmlspecialchars($config['port'] ?? '3306'); ?>" 
                           placeholder="3306">
                </div>

                <div class="form-group">
                    <label class="label">ชื่อฐานข้อมูล (Database Name)</label>
                    <input type="text" class="input" id="dbname" name="dbname" 
                           value="<?php echo htmlspecialchars($config['dbname'] ?? 'jhcisdb'); ?>" 
                           placeholder="jhcisdb">
                </div>

                <div class="form-group">
                    <label class="label">Username</label>
                    <input type="text" class="input" id="user" name="user" 
                           value="<?php echo htmlspecialchars($config['user'] ?? 'root'); ?>" 
                           placeholder="root">
                </div>

                <div class="form-group">
                    <label class="label">Password</label>
                    <input type="password" class="input" id="pass" name="pass" 
                           value="<?php echo htmlspecialchars($config['pass'] ?? ''); ?>" 
                           placeholder="••••••••">
                </div>

                <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                    <button type="button" class="btn btn-secondary" onclick="testConnection()">
                        <div class="spinner" id="testSpinner"></div>
                        <span id="testText">🔍 ทดสอบการเชื่อมต่อ</span>
                    </button>
                    <button type="button" class="btn btn-primary" onclick="saveSettings()">
                        <div class="spinner" id="saveSpinner"></div>
                        <span id="saveText">💾 บันทึกการตั้งค่า</span>
                    </button>
                </div>
            </form>
        </div>

        <div class="card" id="connectionResult" style="display: none;">
            <h3 style="margin-bottom: 1rem; color: #374151;">ผลการทดสอบการเชื่อมต่อ</h3>
            <div id="resultContent"></div>
        </div>

        <div class="card">
            <h3 style="margin-bottom: 1rem; color: #374151;">ตรวจสอบโครงสร้างตาราง</h3>
            <p style="color: #6b7280; margin-bottom: 1rem;">
                ดูโครงสร้างตารางในฐานข้อมูล JHCIS เพื่อใช้ในการ Mapping<br>
                <strong style="color: #dc2626;">⚠️ กรุณาบันทึกการตั้งค่าก่อนตรวจสอบตาราง</strong>
            </p>
            <button type="button" class="btn btn-secondary" onclick="inspectTables()">
                <span>🔍 ตรวจสอบตาราง</span>
            </button>
            <div id="tablesResult" style="margin-top: 1rem;"></div>
        </div>

        <div class="card">
            <h3 style="margin-bottom: 1rem; color: #374151;">💊 วิเคราะห์ตารางยาใน JHCIS</h3>
            <p style="color: #6b7280; margin-bottom: 1rem;">
                วิเคราะห์ตารางที่เกี่ยวข้องกับระบบยาทั้งหมด พร้อมแสดงโครงสร้าง จำนวนข้อมูล และคำแนะนำ<br>
                <strong style="color: #10b981;">✨ ระบบจะวิเคราะห์ตารางมากกว่า 30 ตาราง</strong>
            </p>
            <button type="button" class="btn btn-primary" onclick="analyzeDrugTables()">
                <div class="spinner" id="analyzeSpinner"></div>
                <span id="analyzeText">🔬 วิเคราะห์ตารางยา</span>
            </button>
            <div id="drugAnalysisResult" style="margin-top: 1rem;"></div>
        </div>
    </div>

    <script>
        async function testConnection() {
            const btn = document.querySelector('button[onclick="testConnection()"]');
            const spinner = document.getElementById('testSpinner');
            const text = document.getElementById('testText');
            const alertBox = document.getElementById('alertBox');
            const resultBox = document.getElementById('connectionResult');
            
            btn.disabled = true;
            spinner.style.display = 'inline-block';
            text.textContent = 'กำลังทดสอบ...';
            alertBox.style.display = 'none';

            const data = {
                host: document.getElementById('host').value,
                port: document.getElementById('port').value,
                dbname: document.getElementById('dbname').value,
                user: document.getElementById('user').value,
                pass: document.getElementById('pass').value
            };

            try {
                const response = await fetch('/admin/jhcis/settings/test', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await response.json();

                if (result.success) {
                    showAlert('success', '✅ ' + result.message);
                    
                    // แสดงผลลัพธ์
                    let html = '<div class="info-box">';
                    html += `<strong>จำนวนตารางทั้งหมด:</strong> ${result.details.total_tables} ตาราง<br>`;
                    html += `<strong>ตารางสำคัญที่พบ:</strong> ${result.details.found_tables.join(', ') || 'ไม่พบ'}<br><br>`;
                    html += '<strong>ตัวอย่างตาราง 10 ตารางแรก:</strong><br>';
                    html += '<div class="table-list">';
                    result.details.sample_tables.forEach(table => {
                        html += `<div class="table-item"><span>${table}</span></div>`;
                    });
                    html += '</div></div>';
                    
                    document.getElementById('resultContent').innerHTML = html;
                    resultBox.style.display = 'block';
                } else {
                    showAlert('error', '❌ ' + result.message);
                    resultBox.style.display = 'none';
                }
            } catch (e) {
                showAlert('error', '❌ เกิดข้อผิดพลาด: ' + e.message);
            } finally {
                btn.disabled = false;
                spinner.style.display = 'none';
                text.textContent = '🔍 ทดสอบการเชื่อมต่อ';
            }
        }

        async function saveSettings() {
            const btn = document.querySelector('button[onclick="saveSettings()"]');
            const spinner = document.getElementById('saveSpinner');
            const text = document.getElementById('saveText');
            
            btn.disabled = true;
            spinner.style.display = 'inline-block';
            text.textContent = 'กำลังบันทึก...';

            const data = {
                host: document.getElementById('host').value,
                port: document.getElementById('port').value,
                dbname: document.getElementById('dbname').value,
                user: document.getElementById('user').value,
                pass: document.getElementById('pass').value
            };

            try {
                const response = await fetch('/admin/jhcis/settings/save', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await response.json();

                if (result.success) {
                    showAlert('success', '✅ ' + result.message);
                } else {
                    showAlert('error', '❌ ' + result.message);
                }
            } catch (e) {
                showAlert('error', '❌ เกิดข้อผิดพลาด: ' + e.message);
            } finally {
                btn.disabled = false;
                spinner.style.display = 'none';
                text.textContent = '💾 บันทึกการตั้งค่า';
            }
        }

        async function inspectTables() {
            const resultDiv = document.getElementById('tablesResult');
            resultDiv.innerHTML = '<p style="color:#666;">กำลังโหลด...</p>';

            try {
                const response = await fetch('/admin/jhcis/settings/inspect');
                const result = await response.json();

                if (result.success) {
                    let html = '<div class="info-box">';
                    html += `<strong>พบตารางทั้งหมด:</strong> ${result.tables.length} ตาราง<br><br>`;
                    
                    if (Object.keys(result.structures).length > 0) {
                        html += '<strong>โครงสร้างตารางสำคัญ:</strong><br>';
                        for (const [table, columns] of Object.entries(result.structures)) {
                            html += `<br><strong>${table}</strong> (${columns.length} คอลัมน์):<br>`;
                            html += '<div class="table-list">';
                            columns.slice(0, 10).forEach(col => {
                                html += `<div class="table-item">`;
                                html += `<span>${col.field}</span>`;
                                html += `<span class="badge badge-info">${col.type}</span>`;
                                html += `</div>`;
                            });
                            if (columns.length > 10) {
                                html += `<div style="text-align:center; padding:0.5rem; color:#666;">... และอีก ${columns.length - 10} คอลัมน์</div>`;
                            }
                            html += '</div>';
                        }
                    }
                    html += '</div>';
                    resultDiv.innerHTML = html;
                } else {
                    // แสดง error แบบละเอียด
                    let errorHtml = '<div style="padding: 1rem; background: #fee2e2; border-left: 4px solid #ef4444; border-radius: 8px;">';
                    errorHtml += '<strong style="color: #991b1b;">❌ เกิดข้อผิดพลาด:</strong><br>';
                    errorHtml += `<p style="color: #991b1b; margin-top: 0.5rem;">${result.message}</p>`;
                    
                    // ตรวจสอบ error ที่เป็น password
                    if (result.message.includes('using password: NO') || result.message.includes('Access denied')) {
                        errorHtml += '<br><strong style="color: #dc2626;">💡 แนะนำ:</strong>';
                        errorHtml += '<ol style="margin-top: 0.5rem; margin-left: 1.5rem; color: #991b1b;">';
                        errorHtml += '<li>ตรวจสอบว่าได้กรอก Password แล้ว</li>';
                        errorHtml += '<li>กดปุ่ม "💾 บันทึกการตั้งค่า" ก่อน</li>';
                        errorHtml += '<li>ลองกดปุ่ม "🔍 ทดสอบการเชื่อมต่อ" ก่อน</li>';
                        errorHtml += '</ol>';
                    }
                    
                    errorHtml += '</div>';
                    resultDiv.innerHTML = errorHtml;
                }
            } catch (e) {
                resultDiv.innerHTML = `<p style="color:red;">Error: ${e.message}</p>`;
            }
        }

        async function analyzeDrugTables() {
            const btn = document.querySelector('button[onclick="analyzeDrugTables()"]');
            const spinner = document.getElementById('analyzeSpinner');
            const text = document.getElementById('analyzeText');
            const resultDiv = document.getElementById('drugAnalysisResult');
            
            btn.disabled = true;
            spinner.style.display = 'inline-block';
            text.textContent = 'กำลังวิเคราะห์...';
            resultDiv.innerHTML = '<p style="color:#666;">กำลังวิเคราะห์ตารางยา กรุณารอสักครู่...</p>';

            try {
                const response = await fetch('/admin/jhcis/settings/analyze-drugs');
                const result = await response.json();

                if (result.success) {
                    let html = '';
                    
                    // Summary
                    html += '<div style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); padding: 1.5rem; border-radius: 12px; color: white; margin-bottom: 1.5rem;">';
                    html += '<h4 style="margin-bottom: 1rem;">📊 สรุปผลการวิเคราะห์</h4>';
                    html += '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">';
                    html += `<div><strong>ตรวจสอบทั้งหมด:</strong> ${result.summary.total_tables_checked} ตาราง</div>`;
                    html += `<div><strong>พบในระบบ:</strong> ${result.summary.found_tables} ตาราง</div>`;
                    html += `<div><strong>ไม่พบ:</strong> ${result.summary.missing_tables} ตาราง</div>`;
                    html += `<div><strong>ตารางเพิ่มเติม:</strong> ${result.summary.additional_drug_tables} ตาราง</div>`;
                    html += '</div></div>';

                    // Recommendations
                    if (result.recommendations && result.recommendations.length > 0) {
                        html += '<div style="margin-bottom: 1.5rem;">';
                        html += '<h4 style="color: #374151; margin-bottom: 1rem;">💡 คำแนะนำ</h4>';
                        result.recommendations.forEach(rec => {
                            let bgColor = '#dbeafe';
                            let borderColor = '#3b82f6';
                            let textColor = '#1e40af';
                            let icon = 'ℹ️';
                            
                            if (rec.type === 'success') {
                                bgColor = '#d1fae5';
                                borderColor = '#10b981';
                                textColor = '#065f46';
                                icon = '✅';
                            } else if (rec.type === 'error') {
                                bgColor = '#fee2e2';
                                borderColor = '#ef4444';
                                textColor = '#991b1b';
                                icon = '❌';
                            }
                            
                            html += `<div style="background: ${bgColor}; border-left: 4px solid ${borderColor}; padding: 0.75rem; margin-bottom: 0.5rem; border-radius: 4px; color: ${textColor};">`;
                            html += `${icon} ${rec.message}`;
                            html += '</div>';
                        });
                        html += '</div>';
                    }

                    // Main Tables
                    html += '<div style="margin-bottom: 1.5rem;">';
                    html += '<h4 style="color: #374151; margin-bottom: 1rem;">🎯 ตารางหลัก (Core Tables)</h4>';
                    const mainTables = ['cdrug', 'cdrugremaiin', 'visitdrug'];
                    mainTables.forEach(tableName => {
                        if (result.analysis[tableName] && result.analysis[tableName].exists) {
                            const table = result.analysis[tableName];
                            html += '<div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 1rem; margin-bottom: 1rem;">';
                            html += `<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">`;
                            html += `<strong style="color: #10b981; font-size: 1.1rem;">${tableName}</strong>`;
                            html += `<span class="badge badge-success">${table.row_count.toLocaleString()} รายการ</span>`;
                            html += '</div>';
                            html += `<p style="color: #6b7280; font-size: 0.9rem; margin-bottom: 0.75rem;">${table.description}</p>`;
                            
                            // Columns
                            if (table.columns && table.columns.length > 0) {
                                html += '<details style="margin-top: 0.5rem;">';
                                html += `<summary style="cursor: pointer; color: #10b981; font-weight: 600;">ดูโครงสร้างตาราง (${table.columns.length} คอลัมน์)</summary>`;
                                html += '<div style="margin-top: 0.5rem; max-height: 200px; overflow-y: auto; background: white; padding: 0.5rem; border-radius: 4px;">';
                                table.columns.slice(0, 15).forEach(col => {
                                    html += '<div style="display: flex; justify-content: space-between; padding: 0.25rem; border-bottom: 1px solid #f3f4f6;">';
                                    html += `<span style="font-family: monospace; color: #374151;">${col.field}</span>`;
                                    html += `<span style="color: #6b7280; font-size: 0.85rem;">${col.type}</span>`;
                                    html += '</div>';
                                });
                                if (table.columns.length > 15) {
                                    html += `<div style="text-align: center; padding: 0.5rem; color: #6b7280; font-size: 0.85rem;">... และอีก ${table.columns.length - 15} คอลัมน์</div>`;
                                }
                                html += '</div></details>';
                            }
                            html += '</div>';
                        }
                    });
                    html += '</div>';

                    // Reference Tables
                    html += '<div style="margin-bottom: 1.5rem;">';
                    html += '<h4 style="color: #374151; margin-bottom: 1rem;">📚 ตารางอ้างอิง (Reference Tables)</h4>';
                    const refTables = ['cdrugmaptype', 'cdrugunitsell', 'cdruggallergysymtom', 'cdrugmappowerful'];
                    html += '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 1rem;">';
                    refTables.forEach(tableName => {
                        if (result.analysis[tableName] && result.analysis[tableName].exists) {
                            const table = result.analysis[tableName];
                            html += '<div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 1rem;">';
                            html += `<strong style="color: #059669;">${tableName}</strong><br>`;
                            html += `<span style="font-size: 0.85rem; color: #6b7280;">${table.description}</span><br>`;
                            html += `<span class="badge badge-info" style="margin-top: 0.5rem;">${table.row_count.toLocaleString()} รายการ</span>`;
                            html += '</div>';
                        }
                    });
                    html += '</div></div>';

                    // Found Tables List
                    if (result.found_tables_list && result.found_tables_list.length > 0) {
                        html += '<details style="margin-top: 1rem;">';
                        html += `<summary style="cursor: pointer; color: #10b981; font-weight: 600;">📋 รายการตารางที่พบทั้งหมด (${result.found_tables_list.length} ตาราง)</summary>`;
                        html += '<div style="margin-top: 0.5rem; max-height: 300px; overflow-y: auto; background: #f9fafb; padding: 1rem; border-radius: 8px;">';
                        html += '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 0.5rem;">';
                        result.found_tables_list.forEach(table => {
                            html += `<div style="padding: 0.5rem; background: white; border-radius: 4px; font-family: monospace; font-size: 0.9rem;">${table}</div>`;
                        });
                        html += '</div></div></details>';
                    }

                    // Additional Drug Tables
                    if (result.additional_drug_tables && result.additional_drug_tables.length > 0) {
                        html += '<details style="margin-top: 1rem;">';
                        html += `<summary style="cursor: pointer; color: #f59e0b; font-weight: 600;">🔍 ตารางยาเพิ่มเติมที่พบ (${result.additional_drug_tables.length} ตาราง)</summary>`;
                        html += '<div style="margin-top: 0.5rem; background: #fef3c7; padding: 1rem; border-radius: 8px; border-left: 4px solid #f59e0b;">';
                        html += '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 0.5rem;">';
                        result.additional_drug_tables.forEach(table => {
                            html += `<div style="padding: 0.5rem; background: white; border-radius: 4px; font-family: monospace; font-size: 0.9rem;">${table}</div>`;
                        });
                        html += '</div></div></details>';
                    }

                    resultDiv.innerHTML = html;
                } else {
                    resultDiv.innerHTML = `<div style="padding: 1rem; background: #fee2e2; border-left: 4px solid #ef4444; border-radius: 8px; color: #991b1b;">❌ ${result.message}</div>`;
                }
            } catch (e) {
                resultDiv.innerHTML = `<div style="padding: 1rem; background: #fee2e2; border-left: 4px solid #ef4444; border-radius: 8px; color: #991b1b;">❌ เกิดข้อผิดพลาด: ${e.message}</div>`;
            } finally {
                btn.disabled = false;
                spinner.style.display = 'none';
                text.textContent = '🔬 วิเคราะห์ตารางยา';
            }
        }

        function showAlert(type, message) {
            const alertBox = document.getElementById('alertBox');
            alertBox.className = `alert alert-${type}`;
            alertBox.textContent = message;
            alertBox.style.display = 'block';
            
            setTimeout(() => {
                alertBox.style.display = 'none';
            }, 5000);
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
