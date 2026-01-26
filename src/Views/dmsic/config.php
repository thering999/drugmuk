<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DMSIC Configuration - Drugmuk</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary: #10b981;
            --primary-dark: #059669;
            --secondary: #3b82f6;
            --danger: #ef4444;
            --dark: #1f2937;
            --light: #f3f4f6;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Sarabun', sans-serif;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            min-height: 100vh;
            padding: 2rem;
        }

        .container { max-width: 800px; margin: 0 auto; }

        .header {
            text-align: center;
            color: white;
            margin-bottom: 2rem;
        }

        .header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            font-family: 'Outfit', sans-serif;
        }

        .card {
            background: white;
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            margin-bottom: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }

        .form-label .required {
            color: var(--danger);
            margin-left: 4px;
        }

        .form-input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.2s;
            font-family: 'Sarabun', sans-serif;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }

        .form-help {
            font-size: 0.85rem;
            color: #6b7280;
            margin-top: 0.5rem;
        }

        .form-switch {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .switch {
            position: relative;
            width: 50px;
            height: 28px;
        }

        .switch input { opacity: 0; width: 0; height: 0; }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: #cbd5e1;
            transition: .3s;
            border-radius: 34px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 20px;
            width: 20px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .3s;
            border-radius: 50%;
        }

        input:checked + .slider { background-color: var(--primary); }
        input:checked + .slider:before { transform: translateX(22px); }

        .btn {
            padding: 14px 28px;
            border-radius: 12px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-family: 'Sarabun', sans-serif;
            font-size: 1rem;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(16, 185, 129, 0.4);
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
        }

        .btn-secondary:hover { background: #4b5563; }

        .btn-group {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: rgba(255,255,255,0.9);
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 10px;
            background: rgba(255,255,255,0.1);
            transition: all 0.2s;
            margin-bottom: 2rem;
        }

        .back-link:hover {
            background: rgba(255,255,255,0.2);
            transform: translateX(-4px);
        }

        .info-box {
            background: #eff6ff;
            border-left: 4px solid var(--secondary);
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }

        .info-box i { color: var(--secondary); margin-right: 8px; }

        @media (max-width: 768px) {
            .btn-group { flex-direction: column; }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="/dmsic" class="back-link">
            <i class="fa-solid fa-arrow-left"></i> กลับไปหน้า DMSIC
        </a>

        <div class="header">
            <h1>⚙️ ตั้งค่า DMSIC</h1>
            <p>กำหนดค่าการเชื่อมต่อกับระบบ DMSIC</p>
        </div>

        <div class="card">
            <div class="info-box">
                <i class="fa-solid fa-circle-info"></i>
                <strong>คำแนะนำ:</strong> กรุณากรอกข้อมูลให้ครบถ้วนเพื่อเชื่อมต่อกับระบบ DMSIC ของกระทรวงสาธารณสุข
            </div>

            <form method="POST" action="/dmsic/config" onsubmit="return validateForm(event)">
                <!-- Hospital Code -->
                <div class="form-group">
                    <label class="form-label">
                        รหัสหน่วยบริการ (HOSPCODE)
                        <span class="required">*</span>
                    </label>
                    <input 
                        type="text" 
                        name="hospcode" 
                        class="form-input" 
                        value="<?php echo htmlspecialchars($config['hospcode'] ?? ''); ?>"
                        placeholder="เช่น 12345"
                        required
                        maxlength="10"
                    >
                    <div class="form-help">รหัส 5 หลักของหน่วยบริการ</div>
                </div>

                <!-- Hospital Name -->
                <div class="form-group">
                    <label class="form-label">
                        ชื่อหน่วยบริการ
                        <span class="required">*</span>
                    </label>
                    <input 
                        type="text" 
                        name="hospname" 
                        class="form-input" 
                        value="<?php echo htmlspecialchars($config['hospname'] ?? ''); ?>"
                        placeholder="เช่น โรงพยาบาลส่งเสริมสุขภาพตำบล..."
                        required
                    >
                </div>

                <!-- API URL -->
                <div class="form-group">
                    <label class="form-label">
                        API URL
                    </label>
                    <input 
                        type="url" 
                        name="api_url" 
                        class="form-input" 
                        value="<?php echo htmlspecialchars($config['api_url'] ?? ''); ?>"
                        placeholder="https://dmsic.moph.go.th/api/v1/upload"
                    >
                    <div class="form-help">URL สำหรับส่งข้อมูลไปยัง DMSIC (ถ้าไม่ระบุจะเก็บไฟล์ไว้ในระบบเท่านั้น)</div>
                </div>

                <!-- API Key -->
                <div class="form-group">
                    <label class="form-label">
                        API Key
                    </label>
                    <input 
                        type="password" 
                        name="api_key" 
                        class="form-input" 
                        value="<?php echo htmlspecialchars($config['api_key'] ?? ''); ?>"
                        placeholder="••••••••••••••••"
                    >
                    <div class="form-help">API Key สำหรับยืนยันตัวตน (ถ้ามี)</div>
                </div>

                <!-- Auto Send -->
                <div class="form-group">
                    <div class="form-switch">
                        <label class="switch">
                            <input 
                                type="checkbox" 
                                name="auto_send" 
                                <?php echo (isset($config['auto_send']) && $config['auto_send'] == 1) ? 'checked' : ''; ?>
                            >
                            <span class="slider"></span>
                        </label>
                        <div>
                            <div class="form-label" style="margin-bottom: 0;">ส่งอัตโนมัติ</div>
                            <div class="form-help" style="margin-top: 0;">เปิดใช้งานการส่งข้อมูลอัตโนมัติไปยัง DMSIC</div>
                        </div>
                    </div>
                </div>

                <!-- Send Schedule -->
                <div class="form-group">
                    <label class="form-label">
                        ความถี่ในการส่ง
                    </label>
                    <select name="send_schedule" class="form-input">
                        <option value="daily" <?php echo (isset($config['send_schedule']) && $config['send_schedule'] == 'daily') ? 'selected' : ''; ?>>รายวัน</option>
                        <option value="weekly" <?php echo (isset($config['send_schedule']) && $config['send_schedule'] == 'weekly') ? 'selected' : ''; ?>>รายสัปดาห์</option>
                        <option value="monthly" <?php echo (!isset($config['send_schedule']) || $config['send_schedule'] == 'monthly') ? 'selected' : ''; ?>>รายเดือน</option>
                    </select>
                    <div class="form-help">กำหนดความถี่ในการส่งข้อมูลอัตโนมัติ</div>
                </div>

                <!-- Buttons -->
                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-floppy-disk"></i>
                        บันทึกการตั้งค่า
                    </button>
                    <a href="/dmsic" class="btn btn-secondary">
                        <i class="fa-solid fa-xmark"></i>
                        ยกเลิก
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function validateForm(e) {
            const hospcode = document.querySelector('[name="hospcode"]').value;
            const hospname = document.querySelector('[name="hospname"]').value;

            if (!hospcode || !hospname) {
                Swal.fire({
                    icon: 'error',
                    title: 'ข้อมูลไม่ครบถ้วน',
                    text: 'กรุณากรอกรหัสหน่วยบริการและชื่อหน่วยบริการ',
                    confirmButtonColor: '#ef4444'
                });
                e.preventDefault();
                return false;
            }

            return true;
        }

        <?php if (isset($_SESSION['success'])): ?>
            Swal.fire({
                icon: 'success',
                title: 'สำเร็จ!',
                text: '<?php echo $_SESSION['success']; unset($_SESSION['success']); ?>',
                confirmButtonColor: '#10b981'
            });
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด',
                text: '<?php echo $_SESSION['error']; unset($_SESSION['error']); ?>',
                confirmButtonColor: '#ef4444'
            });
        <?php endif; ?>
    </script>
</body>
</html>
