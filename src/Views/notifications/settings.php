<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?= \App\Core\CSRF::metaTag() ?>
    <title>ตั้งค่าการแจ้งเตือน - Drugmuk</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh; padding: 20px;
        }
        .container { max-width: 800px; margin: 0 auto; }
        .header {
            background: white; padding: 25px 30px; border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1); margin-bottom: 25px;
        }
        .header h1 { color: #333; font-size: 26px; }
        .back-link {
            color: white; text-decoration: none; display: inline-block;
            background: rgba(255,255,255,0.2); padding: 8px 16px; border-radius: 8px; margin-bottom: 15px;
        }
        
        .card {
            background: white; padding: 25px; border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1); margin-bottom: 20px;
        }
        .card h2 { color: #333; margin-bottom: 20px; font-size: 20px; border-bottom: 1px solid #eee; padding-bottom: 15px; }
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: 600; color: #333; margin-bottom: 8px; }
        .form-group input[type="text"],
        .form-group input[type="email"] {
            width: 100%; padding: 12px 15px; border: 1px solid #ddd; border-radius: 8px;
            font-size: 15px; transition: border-color 0.3s;
        }
        .form-group input:focus { border-color: #667eea; outline: none; }
        
        .toggle-group {
            display: flex; justify-content: space-between; align-items: center;
            padding: 15px 0; border-bottom: 1px solid #f0f0f0;
        }
        .toggle-group:last-child { border-bottom: none; }
        .toggle-label { font-weight: 500; color: #333; }
        .toggle-desc { font-size: 13px; color: #666; margin-top: 3px; }
        
        .toggle-switch {
            position: relative; width: 50px; height: 26px;
        }
        .toggle-switch input { opacity: 0; width: 0; height: 0; }
        .toggle-slider {
            position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0;
            background: #ccc; transition: 0.4s; border-radius: 26px;
        }
        .toggle-slider:before {
            position: absolute; content: ""; height: 20px; width: 20px;
            left: 3px; bottom: 3px; background: white; transition: 0.4s; border-radius: 50%;
        }
        .toggle-switch input:checked + .toggle-slider { background: #10b981; }
        .toggle-switch input:checked + .toggle-slider:before { transform: translateX(24px); }
        
        .btn {
            padding: 12px 24px; border: none; border-radius: 8px;
            font-weight: 600; cursor: pointer; transition: all 0.3s; font-size: 15px;
        }
        .btn-primary { background: #667eea; color: white; }
        .btn-primary:hover { background: #5a6fd6; }
        .btn-secondary { background: #e5e7eb; color: #374151; }
        
        .btn-test { background: #f59e0b; color: white; padding: 8px 16px; font-size: 13px; }
        .btn-test:hover { background: #d97706; }
        
        .alert { padding: 15px 20px; border-radius: 10px; margin-bottom: 20px; }
        .alert-success { background: #d1fae5; color: #065f46; }
        .alert-error { background: #fee2e2; color: #991b1b; }
        
        .section-title { font-size: 14px; color: #666; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <a href="/notifications" class="back-link">← กลับไปหน้าแจ้งเตือน</a>
        
        <div class="header">
            <h1>⚙️ ตั้งค่าการแจ้งเตือน</h1>
        </div>

        <div id="alert-container"></div>

        <form id="settingsForm">
            <div class="card">
                <h2>🔔 ประเภทการแจ้งเตือน</h2>
                
                <div class="toggle-group">
                    <div>
                        <div class="toggle-label">📦 ยาใกล้หมดสต็อก</div>
                        <div class="toggle-desc">แจ้งเตือนเมื่อสต็อกยาต่ำกว่าขั้นต่ำ</div>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="notify_low_stock" <?php echo ($settings['notify_low_stock'] ?? 1) ? 'checked' : ''; ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div class="toggle-group">
                    <div>
                        <div class="toggle-label">⏰ ยาใกล้หมดอายุ</div>
                        <div class="toggle-desc">แจ้งเตือนยาที่จะหมดอายุใน 90 วัน</div>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="notify_expiring" <?php echo ($settings['notify_expiring'] ?? 1) ? 'checked' : ''; ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div class="toggle-group">
                    <div>
                        <div class="toggle-label">🧹 ปัญหาคุณภาพข้อมูล</div>
                        <div class="toggle-desc">แจ้งเตือนเมื่อพบข้อมูลซ้ำหรือขาดหาย</div>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="notify_data_quality" <?php echo ($settings['notify_data_quality'] ?? 1) ? 'checked' : ''; ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div class="toggle-group">
                    <div>
                        <div class="toggle-label">🛒 ใบสั่งซื้อ</div>
                        <div class="toggle-desc">แจ้งเตือนสถานะใบสั่งซื้อและการรับยา</div>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="notify_orders" <?php echo ($settings['notify_orders'] ?? 1) ? 'checked' : ''; ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </div>

            <div class="card">
                <h2>📧 การแจ้งเตือนทาง Email</h2>
                
                <div class="toggle-group">
                    <div>
                        <div class="toggle-label">เปิดใช้งาน Email</div>
                        <div class="toggle-desc">ส่งการแจ้งเตือนไปยังอีเมล</div>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="email_enabled" id="email_enabled" <?php echo ($settings['email_enabled'] ?? 0) ? 'checked' : ''; ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div class="form-group" id="email_field" style="<?php echo ($settings['email_enabled'] ?? 0) ? '' : 'display:none'; ?>">
                    <label>Email Address</label>
                    <input type="email" name="email_address" value="<?php echo htmlspecialchars($settings['email_address'] ?? ''); ?>" placeholder="your@email.com">
                </div>
            </div>

            <div class="card">
                <h2>💬 การแจ้งเตือนทาง LINE Notify</h2>
                
                <div class="toggle-group">
                    <div>
                        <div class="toggle-label">เปิดใช้งาน LINE Notify</div>
                        <div class="toggle-desc">ส่งการแจ้งเตือนไปยัง LINE</div>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="line_enabled" id="line_enabled" <?php echo ($settings['line_enabled'] ?? 0) ? 'checked' : ''; ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div id="line_fields" style="<?php echo ($settings['line_enabled'] ?? 0) ? '' : 'display:none'; ?>">
                    <div class="form-group">
                        <label>LINE Notify Token</label>
                        <div style="display: flex; gap: 10px;">
                            <input type="text" name="line_token" id="line_token" value="<?php echo htmlspecialchars($settings['line_token'] ?? ''); ?>" placeholder="ใส่ Token จาก notify-bot.line.me" style="flex:1">
                            <button type="button" class="btn btn-test" onclick="testLine()">ทดสอบ</button>
                        </div>
                    </div>
                    <p style="font-size: 13px; color: #666;">
                        สร้าง Token ได้ที่ <a href="https://notify-bot.line.me/" target="_blank" style="color: #667eea;">notify-bot.line.me</a>
                    </p>
                </div>
            </div>

            <div style="text-align: center; margin-top: 30px;">
                <button type="submit" class="btn btn-primary">💾 บันทึกการตั้งค่า</button>
            </div>
        </form>
    </div>

    <script>
        function getCSRFToken() {
            return document.querySelector('meta[name="csrf-token"]')?.content || '';
        }

        document.getElementById('email_enabled').addEventListener('change', function() {
            document.getElementById('email_field').style.display = this.checked ? 'block' : 'none';
        });

        document.getElementById('line_enabled').addEventListener('change', function() {
            document.getElementById('line_fields').style.display = this.checked ? 'block' : 'none';
        });

        document.getElementById('settingsForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('_csrf', getCSRFToken());

            try {
                const response = await fetch('/api/notifications/save-settings', {
                    method: 'POST',
                    headers: { 'X-CSRF-Token': getCSRFToken() },
                    body: formData
                });
                const result = await response.json();
                
                showAlert(result.success ? 'success' : 'error', 
                    result.success ? 'บันทึกการตั้งค่าสำเร็จ' : 'เกิดข้อผิดพลาด');
            } catch (error) {
                showAlert('error', 'เกิดข้อผิดพลาดในการบันทึก');
            }
        });

        async function testLine() {
            const token = document.getElementById('line_token').value;
            if (!token) {
                showAlert('error', 'กรุณาใส่ LINE Token');
                return;
            }

            try {
                const response = await fetch('/api/notifications/test-line', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-CSRF-Token': getCSRFToken()
                    },
                    body: 'token=' + encodeURIComponent(token)
                });
                const result = await response.json();
                showAlert(result.success ? 'success' : 'error', result.message);
            } catch (error) {
                showAlert('error', 'เกิดข้อผิดพลาด');
            }
        }

        function showAlert(type, message) {
            const container = document.getElementById('alert-container');
            container.innerHTML = `<div class="alert alert-${type}">${message}</div>`;
            setTimeout(() => container.innerHTML = '', 5000);
        }
    </script>
</body>
</html>
