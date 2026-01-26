<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตั้งค่าการแจ้งเตือน - Drugmuk</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #667eea;
            --secondary: #764ba2;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --dark: #1f2937;
            --light: #f3f4f6;
            --white: #ffffff;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Sarabun', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
        }

        .card {
            background: var(--white);
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            margin-bottom: 25px;
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 20px 30px;
        }

        .card-header h1 {
            font-size: 24px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-body {
            padding: 30px;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: white;
            text-decoration: none;
            margin-bottom: 20px;
            padding: 10px 20px;
            background: rgba(255,255,255,0.2);
            border-radius: 8px;
            transition: all 0.3s;
        }

        .back-link:hover {
            background: rgba(255,255,255,0.3);
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 8px;
        }

        .form-group input[type="text"],
        .form-group input[type="time"],
        .form-group input[type="number"],
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
        }

        .form-group small {
            display: block;
            color: #666;
            margin-top: 5px;
        }

        .toggle-group {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px;
            background: var(--light);
            border-radius: 10px;
            margin-bottom: 15px;
        }

        .toggle-group label {
            margin: 0;
            cursor: pointer;
        }

        .toggle-switch {
            position: relative;
            width: 50px;
            height: 26px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: 0.3s;
            border-radius: 26px;
        }

        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 20px;
            width: 20px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: 0.3s;
            border-radius: 50%;
        }

        input:checked + .toggle-slider {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
        }

        input:checked + .toggle-slider:before {
            transform: translateX(24px);
        }

        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--light);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-warning {
            background: var(--warning);
            color: white;
        }

        .btn-outline {
            background: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
        }

        .btn-outline:hover {
            background: var(--primary);
            color: white;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
        }

        .status-connected {
            background: #d1fae5;
            color: #065f46;
        }

        .status-disconnected {
            background: #fee2e2;
            color: #991b1b;
        }

        .test-result {
            padding: 15px;
            border-radius: 10px;
            margin-top: 15px;
            display: none;
        }

        .test-result.success {
            background: #d1fae5;
            color: #065f46;
        }

        .test-result.error {
            background: #fee2e2;
            color: #991b1b;
        }

        .btn-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 30px;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .quick-action-btn {
            padding: 15px;
            background: var(--light);
            border: 2px solid transparent;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
        }

        .quick-action-btn:hover {
            border-color: var(--primary);
            background: white;
        }

        .quick-action-btn span {
            display: block;
            font-size: 24px;
            margin-bottom: 8px;
        }

        .session-message {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .session-message.success {
            background: #d1fae5;
            color: #065f46;
        }

        .session-message.error {
            background: #fee2e2;
            color: #991b1b;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="/dashboard" class="back-link">← กลับหน้าหลัก</a>

        <?php if (isset($_SESSION['success'])): ?>
        <div class="session-message success">✅ <?= htmlspecialchars($_SESSION['success']) ?></div>
        <?php unset($_SESSION['success']); endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
        <div class="session-message error">❌ <?= htmlspecialchars($_SESSION['error']) ?></div>
        <?php unset($_SESSION['error']); endif; ?>

        <div class="card">
            <div class="card-header">
                <h1>🔔 ตั้งค่าการแจ้งเตือน</h1>
            </div>
            <div class="card-body">
                <form method="POST" action="/notifications/save-settings" id="settingsForm">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                    
                    <!-- LINE Notify Section -->
                    <h3 class="section-title">📱 LINE Notify</h3>
                    
                    <div class="form-group">
                        <label>สถานะการเชื่อมต่อ</label>
                        <?php if ($is_line_connected ?? false): ?>
                            <span class="status-badge status-connected">✅ เชื่อมต่อแล้ว</span>
                        <?php else: ?>
                            <span class="status-badge status-disconnected">❌ ยังไม่ได้เชื่อมต่อ</span>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="line_notify_token">LINE Notify Token</label>
                        <input type="text" id="line_notify_token" name="line_notify_token" 
                               value="<?= htmlspecialchars($settings['line_notify_token'] ?? '') ?>"
                               placeholder="กรอก LINE Notify Token">
                        <small>
                            รับ Token ได้ที่ <a href="https://notify-bot.line.me/my/" target="_blank">LINE Notify</a>
                        </small>
                    </div>

                    <button type="button" class="btn btn-outline" onclick="testLineNotify()">
                        🧪 ทดสอบส่ง LINE
                    </button>
                    <div id="testResult" class="test-result"></div>

                    <!-- Notification Types -->
                    <h3 class="section-title" style="margin-top: 30px;">📋 ประเภทการแจ้งเตือน</h3>

                    <div class="toggle-group">
                        <label for="notify_low_stock">🔴 แจ้งเตือนยาใกล้หมดสต็อก</label>
                        <label class="toggle-switch">
                            <input type="checkbox" id="notify_low_stock" name="notify_low_stock" 
                                   <?= ($settings['notify_low_stock'] ?? false) ? 'checked' : '' ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>

                    <div class="toggle-group">
                        <label for="notify_expiring">⚠️ แจ้งเตือนยาใกล้หมดอายุ</label>
                        <label class="toggle-switch">
                            <input type="checkbox" id="notify_expiring" name="notify_expiring"
                                   <?= ($settings['notify_expiring'] ?? false) ? 'checked' : '' ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>

                    <div class="toggle-group">
                        <label for="notify_contracts">📄 แจ้งเตือนสัญญาใกล้หมดอายุ</label>
                        <label class="toggle-switch">
                            <input type="checkbox" id="notify_contracts" name="notify_contracts"
                                   <?= ($settings['notify_contracts'] ?? false) ? 'checked' : '' ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>

                    <div class="toggle-group">
                        <label for="notify_receive">📦 แจ้งเตือนเมื่อรับยาเข้าคลัง</label>
                        <label class="toggle-switch">
                            <input type="checkbox" id="notify_receive" name="notify_receive"
                                   <?= ($settings['notify_receive'] ?? false) ? 'checked' : '' ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>

                    <div class="toggle-group">
                        <label for="notify_allergy">🚨 แจ้งเตือนเมื่อพบประวัติแพ้ยา</label>
                        <label class="toggle-switch">
                            <input type="checkbox" id="notify_allergy" name="notify_allergy"
                                   <?= ($settings['notify_allergy'] ?? false) ? 'checked' : '' ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>

                    <div class="toggle-group">
                        <label for="daily_summary">📊 ส่งรายงานสรุปประจำวัน</label>
                        <label class="toggle-switch">
                            <input type="checkbox" id="daily_summary" name="daily_summary"
                                   <?= ($settings['daily_summary'] ?? false) ? 'checked' : '' ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>

                    <!-- Settings -->
                    <h3 class="section-title" style="margin-top: 30px;">⚙️ ตั้งค่าเพิ่มเติม</h3>

                    <div class="form-group">
                        <label for="daily_summary_time">เวลาส่งรายงานประจำวัน</label>
                        <input type="time" id="daily_summary_time" name="daily_summary_time"
                               value="<?= htmlspecialchars($settings['daily_summary_time'] ?? '08:00') ?>">
                    </div>

                    <div class="form-group">
                        <label for="expiring_days">แจ้งเตือนยาหมดอายุล่วงหน้า (วัน)</label>
                        <input type="number" id="expiring_days" name="expiring_days" min="7" max="365"
                               value="<?= (int)($settings['expiring_days'] ?? 90) ?>">
                    </div>

                    <div class="btn-group">
                        <button type="submit" class="btn btn-primary">💾 บันทึกการตั้งค่า</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">
                <h1>⚡ ส่งการแจ้งเตือนทันที</h1>
            </div>
            <div class="card-body">
                <div class="quick-actions">
                    <button class="quick-action-btn" onclick="sendNow('low_stock')">
                        <span>📦</span>
                        ยาใกล้หมดสต็อก
                    </button>
                    <button class="quick-action-btn" onclick="sendNow('expiring')">
                        <span>⏰</span>
                        ยาใกล้หมดอายุ
                    </button>
                    <button class="quick-action-btn" onclick="sendNow('contracts')">
                        <span>📄</span>
                        สัญญาใกล้หมดอายุ
                    </button>
                    <button class="quick-action-btn" onclick="sendNow('daily_summary')">
                        <span>📊</span>
                        รายงานสรุปวันนี้
                    </button>
                </div>
                <div id="sendResult" class="test-result" style="margin-top: 20px;"></div>
            </div>
        </div>
    </div>

    <script>
        function testLineNotify() {
            const token = document.getElementById('line_notify_token').value;
            const resultDiv = document.getElementById('testResult');
            
            if (!token) {
                resultDiv.className = 'test-result error';
                resultDiv.textContent = '❌ กรุณากรอก LINE Notify Token';
                resultDiv.style.display = 'block';
                return;
            }

            resultDiv.className = 'test-result';
            resultDiv.textContent = '🔄 กำลังทดสอบ...';
            resultDiv.style.display = 'block';

            fetch('/notifications/test-line', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'token=' + encodeURIComponent(token)
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    resultDiv.className = 'test-result success';
                    resultDiv.textContent = '✅ ส่งข้อความทดสอบสำเร็จ! ตรวจสอบ LINE ของคุณ';
                } else {
                    resultDiv.className = 'test-result error';
                    resultDiv.textContent = '❌ ไม่สามารถส่งได้: ' + (data.error || 'Unknown error');
                }
            })
            .catch(err => {
                resultDiv.className = 'test-result error';
                resultDiv.textContent = '❌ เกิดข้อผิดพลาด: ' + err.message;
            });
        }

        function sendNow(type) {
            const resultDiv = document.getElementById('sendResult');
            resultDiv.className = 'test-result';
            resultDiv.textContent = '🔄 กำลังส่ง...';
            resultDiv.style.display = 'block';

            fetch('/notifications/send-now', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'type=' + encodeURIComponent(type)
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    resultDiv.className = 'test-result success';
                    resultDiv.textContent = '✅ ส่งการแจ้งเตือนสำเร็จ!';
                } else {
                    resultDiv.className = 'test-result error';
                    resultDiv.textContent = '❌ ไม่สามารถส่งได้: ' + (data.error || data.message || 'Unknown error');
                }
            })
            .catch(err => {
                resultDiv.className = 'test-result error';
                resultDiv.textContent = '❌ เกิดข้อผิดพลาด: ' + err.message;
            });
        }
    </script>
</body>
</html>
