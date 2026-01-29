<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Updates - Drugmuk</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
    <style>
        :root {
            --primary: #8b5cf6;
            --primary-dark: #7c3aed;
            --secondary: #3b82f6;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --dark: #1f2937;
            --light: #f3f4f6;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Sarabun', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem;
        }

        .container { max-width: 1000px; margin: 0 auto; }

        /* Header */
        .header {
            text-align: center;
            color: white;
            margin-bottom: 2.5rem;
            animation: fadeInDown 0.6s ease-out;
        }

        .header h1 {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            font-family: 'Outfit', sans-serif;
            text-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        .header p {
            font-size: 1.2rem;
            opacity: 0.95;
        }

        /* Version Cards */
        .version-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 2.5rem;
        }

        .version-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            position: relative;
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
            animation: fadeInUp 0.6s ease-out;
        }

        .version-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 60px rgba(0,0,0,0.25);
        }

        .version-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
        }

        .version-label {
            font-size: 0.9rem;
            color: #6b7280;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
        }

        .version-number {
            font-size: 3rem;
            font-weight: 800;
            font-family: 'Outfit', sans-serif;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1rem;
        }

        .version-status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .status-current {
            background: #d1fae5;
            color: #065f46;
        }

        .status-available {
            background: #fef3c7;
            color: #92400e;
        }

        /* Update Info */
        .update-info {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            margin-bottom: 2.5rem;
            animation: fadeInUp 0.6s ease-out 0.2s both;
        }

        .update-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--light);
        }

        .update-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark);
            font-family: 'Outfit', sans-serif;
        }

        .release-date {
            color: #6b7280;
            font-size: 0.9rem;
        }

        .changelog {
            list-style: none;
            padding: 0;
        }

        .changelog li {
            padding: 12px 0;
            padding-left: 32px;
            position: relative;
            color: var(--dark);
            font-size: 1.05rem;
        }

        .changelog li::before {
            content: '✨';
            position: absolute;
            left: 0;
            font-size: 1.2rem;
        }

        /* Buttons */
        .btn {
            padding: 14px 32px;
            border-radius: 12px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-family: 'Sarabun', sans-serif;
            font-size: 1.05rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            box-shadow: 0 4px 16px rgba(139, 92, 246, 0.4);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(139, 92, 246, 0.5);
        }

        .btn-secondary {
            background: white;
            color: var(--primary);
            border: 2px solid var(--primary);
        }

        .btn-secondary:hover {
            background: var(--primary);
            color: white;
        }

        /* History Section */
        .history-section {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            animation: fadeInUp 0.6s ease-out 0.4s both;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 1.5rem;
            font-family: 'Outfit', sans-serif;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .history-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .history-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.25rem;
            background: #f9fafb;
            border-radius: 12px;
            border-left: 4px solid var(--primary);
            transition: all 0.2s;
        }

        .history-item:hover {
            background: #f3f4f6;
            transform: translateX(4px);
        }

        .history-version {
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--dark);
            font-family: 'Outfit', sans-serif;
        }

        .history-date {
            color: #6b7280;
            font-size: 0.9rem;
        }

        .history-status {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .status-completed {
            background: #d1fae5;
            color: #065f46;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-failed {
            background: #fee2e2;
            color: #991b1b;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #9ca3af;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.3;
        }

        /* Back Link */
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: rgba(255,255,255,0.95);
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 12px;
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(10px);
            transition: all 0.3s;
            margin-bottom: 2rem;
            font-weight: 600;
        }

        .back-link:hover {
            background: rgba(255,255,255,0.25);
            transform: translateX(-4px);
        }

        /* Animations */
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header h1 { font-size: 2rem; }
            .version-grid { grid-template-columns: 1fr; }
            .history-item { flex-direction: column; align-items: flex-start; gap: 0.5rem; }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="/dashboard" class="back-link">
            <i class="fa-solid fa-arrow-left"></i> กลับหน้าหลัก
        </a>

        <div class="header">
            <h1>🔄 ระบบอัพเดทอัตโนมัติ</h1>
            <p>Auto update System</p>
        </div>

        <!-- Version Status -->
        <div class="version-grid">
            <!-- Current Version -->
            <div class="version-card">
                <div class="version-label">เวอร์ชันปัจจุบัน</div>
                <div class="version-number"><?php echo htmlspecialchars($currentVersion); ?></div>
                <span class="version-status status-current">
                    <i class="fa-solid fa-circle-check"></i>
                    ใช้งานได้
                </span>
            </div>

            <!-- Latest Version -->
            <div class="version-card">
                <div class="version-label">เวอร์ชันล่าสุด</div>
                <div class="version-number"><?php echo htmlspecialchars($latestVersion['version']); ?></div>
                <?php if (version_compare($latestVersion['version'], $currentVersion, '>')): ?>
                    <span class="version-status status-available">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        มีอัพเดทใหม่
                    </span>
                <?php else: ?>
                    <span class="version-status status-current">
                        <i class="fa-solid fa-circle-check"></i>
                        ใหม่ล่าสุด
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Update Information -->
        <?php if (version_compare($latestVersion['version'], $currentVersion, '>')): ?>
        <div class="update-info">
            <div class="update-header">
                <div>
                    <div class="update-title">รายการเปลี่ยนแปลง:</div>
                    <div class="release-date">
                        <i class="fa-regular fa-calendar"></i>
                        Release: <?php echo htmlspecialchars($latestVersion['release_date']); ?>
                    </div>
                </div>
            </div>

            <ul class="changelog">
                <?php foreach ($latestVersion['changelog'] as $change): ?>
                    <li><?php echo htmlspecialchars($change); ?></li>
                <?php endforeach; ?>
            </ul>

            <div style="margin-top: 2rem; display: flex; gap: 1rem; flex-wrap: wrap;">
                <button class="btn btn-primary" onclick="installUpdate()">
                    <i class="fa-solid fa-download"></i>
                    ติดตั้งอัพเดท
                </button>
                <button class="btn btn-secondary" onclick="checkUpdate()">
                    <i class="fa-solid fa-rotate"></i>
                    ตรวจสอบอีกครั้ง
                </button>
            </div>
        </div>
        <?php endif; ?>

        <!-- Update History -->
        <div class="history-section">
            <h2 class="section-title">
                <i class="fa-solid fa-clock-rotate-left"></i>
                ประวัติการอัพเดท
            </h2>

            <?php if (empty($updateHistory)): ?>
                <div class="empty-state">
                    <i class="fa-solid fa-inbox"></i>
                    <p>ยังไม่มีประวัติการอัพเดท</p>
                </div>
            <?php else: ?>
                <div class="history-list">
                    <?php foreach ($updateHistory as $update): ?>
                        <div class="history-item">
                            <div>
                                <div class="history-version">v<?php echo htmlspecialchars($update['version']); ?></div>
                                <div class="history-date">
                                    <?php echo date('d/m/Y H:i', strtotime($update['created_at'])); ?>
                                </div>
                            </div>
                            <span class="history-status status-<?php echo $update['status']; ?>">
                                <?php echo $update['status']; ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Check for updates
        async function checkUpdate() {
            Swal.fire({
                title: 'กำลังตรวจสอบ...',
                html: 'กรุณารอสักครู่',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
                const response = await fetch('/api/updates/check', { 
                    method: 'POST',
                    headers: {
                        'X-CSRF-Token': csrfToken,
                        'Content-Type': 'application/json'
                    }
                });
                const data = await response.json();

                if (data.success) {
                    if (data.has_update) {
                        await Swal.fire({
                            icon: 'info',
                            title: 'มีอัพเดทใหม่!',
                            html: `เวอร์ชันล่าสุด: <strong>${data.latest_version}</strong><br>เวอร์ชันปัจจุบัน: ${data.current_version}`,
                            confirmButtonColor: '#8b5cf6'
                        });
                        location.reload();
                    } else {
                        Swal.fire({
                            icon: 'success',
                            title: 'คุณใช้เวอร์ชันล่าสุดแล้ว',
                            text: `เวอร์ชัน ${data.current_version}`,
                            confirmButtonColor: '#10b981'
                        });
                    }
                }
            } catch (e) {
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์อัพเดทได้',
                    confirmButtonColor: '#ef4444'
                });
            }
        }

        // Install update
        async function installUpdate() {
            const result = await Swal.fire({
                title: 'ยืนยันการอัพเดท?',
                html: 'ระบบจะทำการดาวน์โหลดและติดตั้งอัพเดทอัตโนมัติ<br><small>กระบวนการนี้อาจใช้เวลาสักครู่</small>',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'ติดตั้งเลย',
                cancelButtonText: 'ยกเลิก',
                confirmButtonColor: '#8b5cf6',
                cancelButtonColor: '#6b7280'
            });

            if (!result.isConfirmed) return;

            Swal.fire({
                title: 'กำลังติดตั้งอัพเดท...',
                html: `
                    <div style="margin: 20px 0;">
                        <div style="width: 100%; height: 8px; background: #e5e7eb; border-radius: 10px; overflow: hidden;">
                            <div id="progressBar" style="width: 0%; height: 100%; background: linear-gradient(90deg, #8b5cf6, #3b82f6); transition: width 0.3s;"></div>
                        </div>
                        <div id="progressText" style="margin-top: 10px; color: #6b7280;">กำลังเตรียมการ...</div>
                    </div>
                `,
                allowOutsideClick: false,
                showConfirmButton: false
            });

            // Simulate progress
            const steps = [
                { progress: 20, text: 'กำลังดาวน์โหลด...' },
                { progress: 50, text: 'กำลังตรวจสอบไฟล์...' },
                { progress: 75, text: 'กำลังติดตั้ง...' },
                { progress: 100, text: 'เสร็จสิ้น!' }
            ];

            for (const step of steps) {
                await new Promise(resolve => setTimeout(resolve, 800));
                document.getElementById('progressBar').style.width = step.progress + '%';
                document.getElementById('progressText').textContent = step.text;
            }

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
                const response = await fetch('/updates/install', { 
                    method: 'POST',
                    headers: {
                        'X-CSRF-Token': csrfToken,
                        'Content-Type': 'application/json'
                    }
                });
                const data = await response.json();

                if (data.success) {
                    await Swal.fire({
                        icon: 'success',
                        title: 'อัพเดทสำเร็จ!',
                        html: `ระบบได้รับการอัพเดทเป็นเวอร์ชัน <strong>${data.new_version}</strong> แล้ว`,
                        confirmButtonColor: '#10b981'
                    });
                    location.reload();
                } else {
                    throw new Error(data.message);
                }
            } catch (e) {
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: e.message || 'ไม่สามารถติดตั้งอัพเดทได้',
                    confirmButtonColor: '#ef4444'
                });
            }
        }

        // Auto-check on page load
        window.addEventListener('load', () => {
            console.log('✅ Update system ready');
        });
    </script>
</body>
</html>
