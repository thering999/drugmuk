<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup Manager - Drugmuk</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #667eea;
            --secondary: #764ba2;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
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

        .container { max-width: 1200px; margin: 0 auto; }

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
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header h1 {
            font-size: 24px;
            font-weight: 600;
        }

        .card-body { padding: 25px; }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: var(--light);
            border-radius: 12px;
            padding: 25px;
            text-align: center;
        }

        .stat-card .icon { font-size: 40px; margin-bottom: 10px; }
        .stat-card .value { font-size: 24px; font-weight: 700; color: var(--primary); }
        .stat-card .label { font-size: 14px; color: #666; margin-top: 5px; }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
        }

        .btn-success { background: var(--success); color: white; }
        .btn-warning { background: var(--warning); color: white; }
        .btn-danger { background: var(--danger); color: white; }
        .btn-info { background: var(--info); color: white; }

        .btn:hover { transform: translateY(-2px); }

        .btn-sm { padding: 8px 16px; font-size: 13px; }

        .backup-table {
            width: 100%;
            border-collapse: collapse;
        }

        .backup-table th,
        .backup-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .backup-table th {
            background: var(--light);
            font-weight: 600;
        }

        .backup-table tr:hover { background: #f9fafb; }

        .type-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .type-auto { background: #dbeafe; color: #1e40af; }
        .type-manual { background: #d1fae5; color: #065f46; }
        .type-pre-restore { background: #fef3c7; color: #92400e; }
        .type-full { background: #e0e7ff; color: #3730a3; }

        .action-buttons { display: flex; gap: 8px; }

        .session-message {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .session-message.success { background: #d1fae5; color: #065f46; }
        .session-message.error { background: #fee2e2; color: #991b1b; }

        .create-backup-form {
            display: flex;
            gap: 15px;
            align-items: center;
            padding: 20px;
            background: var(--light);
            border-radius: 12px;
            margin-bottom: 25px;
        }

        .create-backup-form select {
            padding: 12px 20px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 15px;
        }

        .warning-box {
            background: #fef3c7;
            border: 2px solid #fbbf24;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
        }

        .warning-box h3 {
            color: #92400e;
            margin-bottom: 10px;
        }

        .empty-state {
            text-align: center;
            padding: 60px;
            color: #999;
        }

        .empty-state .icon { font-size: 60px; margin-bottom: 15px; }
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

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon">💾</div>
                <div class="value"><?= count($backups ?? []) ?></div>
                <div class="label">Backup Files</div>
            </div>
            <div class="stat-card">
                <div class="icon">📦</div>
                <div class="value"><?= $disk_usage['used_human'] ?? '0 B' ?></div>
                <div class="label">พื้นที่ใช้งาน</div>
            </div>
            <div class="stat-card">
                <div class="icon">💿</div>
                <div class="value"><?= $disk_usage['free_human'] ?? '0 B' ?></div>
                <div class="label">พื้นที่ว่าง</div>
            </div>
            <div class="stat-card">
                <div class="icon">🕐</div>
                <div class="value"><?= $last_backup ? date('d/m/Y', $last_backup['created']) : '-' ?></div>
                <div class="label">Backup ล่าสุด</div>
            </div>
        </div>

        <!-- Create Backup -->
        <div class="card">
            <div class="card-header">
                <h1>➕ สร้าง Backup ใหม่</h1>
            </div>
            <div class="card-body">
                <form method="POST" action="/backup/create" class="create-backup-form">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                    <label><strong>ประเภท Backup:</strong></label>
                    <select name="type">
                        <option value="database">Database Only (SQL)</option>
                        <option value="full">Full Backup (DB + Files)</option>
                    </select>
                    <button type="submit" class="btn btn-success">
                        💾 สร้าง Backup
                    </button>
                </form>
            </div>
        </div>

        <!-- Warning -->
        <div class="warning-box">
            <h3>⚠️ คำเตือน</h3>
            <p>การ Restore จะ<strong>เขียนทับข้อมูลปัจจุบัน</strong>ทั้งหมด ระบบจะสร้าง backup อัตโนมัติก่อน restore เสมอ</p>
        </div>

        <!-- Backup List -->
        <div class="card">
            <div class="card-header">
                <h1>📁 รายการ Backup</h1>
            </div>
            <div class="card-body">
                <?php if (empty($backups)): ?>
                <div class="empty-state">
                    <div class="icon">📭</div>
                    <h3>ยังไม่มี Backup</h3>
                    <p>คลิก "สร้าง Backup" เพื่อสำรองข้อมูล</p>
                </div>
                <?php else: ?>
                <table class="backup-table">
                    <thead>
                        <tr>
                            <th>ชื่อไฟล์</th>
                            <th>ประเภท</th>
                            <th>ขนาด</th>
                            <th>วันที่สร้าง</th>
                            <th>การจัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($backups as $backup): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($backup['filename']) ?></strong>
                            </td>
                            <td>
                                <span class="type-badge type-<?= $backup['type'] ?>">
                                    <?= ucfirst($backup['type']) ?>
                                </span>
                            </td>
                            <td><?= $backup['size_human'] ?></td>
                            <td><?= $backup['created_human'] ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="/backup/download/<?= urlencode($backup['filename']) ?>" 
                                       class="btn btn-info btn-sm">
                                        ⬇️ Download
                                    </a>
                                    <form method="POST" action="/backup/restore" style="display: inline;"
                                          onsubmit="return confirm('⚠️ ยืนยันการ Restore?\n\nข้อมูลปัจจุบันจะถูกเขียนทับ!');">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                        <input type="hidden" name="filename" value="<?= htmlspecialchars($backup['filename']) ?>">
                                        <button type="submit" class="btn btn-warning btn-sm">
                                            🔄 Restore
                                        </button>
                                    </form>
                                    <form method="POST" action="/backup/delete" style="display: inline;"
                                          onsubmit="return confirm('ยืนยันการลบ Backup นี้?');">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                        <input type="hidden" name="filename" value="<?= htmlspecialchars($backup['filename']) ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            🗑️ ลบ
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- Auto Backup Info -->
        <div class="card">
            <div class="card-header" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                <h1>⏰ Auto Backup</h1>
            </div>
            <div class="card-body">
                <p style="margin-bottom: 15px;">
                    ระบบสามารถตั้งค่า Auto Backup ได้โดยเพิ่ม Cron Job:
                </p>
                <pre style="background: #1f2937; color: #10b981; padding: 15px; border-radius: 10px; overflow-x: auto;">
# Backup ทุก 4 ชั่วโมง
0 */4 * * * curl -s http://localhost:8080/backup/cron > /dev/null

# Backup ทุกวัน เวลา 02:00
0 2 * * * curl -s http://localhost:8080/backup/cron > /dev/null
                </pre>
                <p style="margin-top: 15px; color: #666;">
                    💡 Auto backups จะถูกเก็บไว้ 14 วัน และลบอัตโนมัติ
                </p>
            </div>
        </div>
    </div>
</body>
</html>
