<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Drugmuk</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Sarabun', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
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

        .nav {
            display: flex;
            gap: 15px;
        }

        .nav a {
            padding: 10px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            transition: transform 0.2s;
        }

        .nav a:hover {
            transform: translateY(-2px);
        }

        .metrics {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .metric-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }

        .metric-card:hover {
            transform: translateY(-5px);
        }

        .metric-card h3 {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .metric-card .value {
            font-size: 36px;
            font-weight: bold;
            color: #667eea;
        }
        
        .metric-card.quality-excellent .value { color: #10b981; }
        .metric-card.quality-good .value { color: #3b82f6; }
        .metric-card.quality-fair .value { color: #f59e0b; }
        .metric-card.quality-poor .value { color: #ef4444; }

        .alerts {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .alerts h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 22px;
        }

        .alert-item {
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .alert-item.warning {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
        }

        .alert-item.danger {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
        }

        .alert-item.info {
            background: #d1ecf1;
            border-left: 4px solid #17a2b8;
        }

        .alert-icon {
            font-size: 24px;
        }

        .recent-activity {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
        }

        .activity-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .activity-card h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 20px;
        }

        .activity-item {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-item .date {
            color: #999;
            font-size: 12px;
        }

        .activity-item .title {
            color: #333;
            font-weight: 500;
            margin-top: 5px;
        }
        .session-message {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            animation: slideIn 0.3s ease-out;
        }
        .session-message.success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .session-message.error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        @keyframes slideIn {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .user-info span {
            color: #666;
            font-size: 14px;
        }
        .btn-logout {
            padding: 8px 16px;
            background: #dc3545;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (isset($_SESSION['success'])): ?>
        <div class="session-message success">
            ✅ <?= htmlspecialchars($_SESSION['success']) ?>
        </div>
        <?php unset($_SESSION['success']); endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
        <div class="session-message error">
            ❌ <?= htmlspecialchars($_SESSION['error']) ?>
        </div>
        <?php unset($_SESSION['error']); endif; ?>

        <div class="header">
            <h1>🏥 ระบบบริหารคลังเวชภัณฑ์ยา Drugmuk</h1>
            <div class="user-info">
                <span>👤 <?= htmlspecialchars($_SESSION['full_name'] ?? 'User') ?> (<?= htmlspecialchars($_SESSION['role'] ?? 'N/A') ?>)</span>
                <a href="/logout" class="btn-logout">🚪 ออกจากระบบ</a>
            </div>
        </div>

        <div class="header" style="margin-top: 20px;">
            <nav class="nav">
                <a href="/dashboard">🏠 หน้าหลัก</a>
                <a href="/purchasing">📊 แผนซื้อ</a>
                <a href="/orders">🛒 สั่งซื้อ</a>
                <a href="/warehouse">🏭 คลังใหญ่</a>
                <a href="/subwarehouse">🏪 คลังย่อย</a>
                <a href="/dispensing" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">💊 จ่ายยา</a>
                <a href="/contracts">📄 สัญญา</a>
                <a href="/admin/jhcis/dashboard" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">🔗 JHCIS</a>
                <a href="/admin/jhcis/mapping" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);">💊 Drug Mapping</a>
                <a href="/jhcis-import" style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);">⬇️ Import JHCIS</a>
                <a href="/jhcis-drugs" style="background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);">📋 รายการยา JHCIS</a>
                <a href="/import-history" style="background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);">📊 ประวัติ Import</a>
                <a href="/admin/intelligence" style="background: linear-gradient(135deg, #a855f7 0%, #7e22ce 100%);">🧠 Intelligence Center</a>
            </nav>
        </div>

        <!-- Phase 3 Advanced Features -->
        <div class="header" style="margin-top: 20px; background: linear-gradient(135deg, #ec4899 0%, #8b5cf6 100%);">
            <nav class="nav" style="flex-wrap: wrap;">
                <a href="/scan" style="background: rgba(255,255,255,0.2);">📷 สแกนบาร์โค้ด</a>
                <a href="/reports" style="background: rgba(255,255,255,0.2);">📊 รายงาน</a>
                <a href="/dmsic" style="background: rgba(255,255,255,0.2);">🏥 DMSIC</a>
                <a href="/data-cleansing" style="background: rgba(255,255,255,0.2);">🧹 Data Cleansing</a>
                <a href="/realtime-sync" style="background: rgba(255,255,255,0.2);">⚡ Real-time Sync</a>
                <a href="/notifications" style="background: rgba(255,255,255,0.2);">🔔 การแจ้งเตือน</a>
                <a href="/audit-trail" style="background: rgba(255,255,255,0.2);">📜 Audit Trail</a>
                <a href="/settings/database" style="background: rgba(255,255,255,0.4); border: 1px solid white;">⚙️ ตั้งค่าระบบ</a>
                <a href="/updates" style="background: rgba(255,255,255,0.2);">🔄 อัพเดท</a>
            </nav>
        </div>

        <div class="metrics">
            <div class="metric-card">
                <h3>📦 ยาใกล้หมดสต็อก</h3>
                <div class="value"><?= $metrics['low_stock_count'] ?? 0 ?></div>
            </div>
            <div class="metric-card">
                <h3>⏰ ยาใกล้หมดอายุ (90 วัน)</h3>
                <div class="value"><?= $metrics['expiring_soon_count'] ?? 0 ?></div>
            </div>
            <div class="metric-card">
                <h3>📋 ใบสั่งซื้อค้างรับ</h3>
                <div class="value"><?= $metrics['pending_orders_count'] ?? 0 ?></div>
            </div>
            <div class="metric-card">
                <h3>📄 สัญญาใกล้หมดอายุ</h3>
                <div class="value"><?= $metrics['expiring_contracts_count'] ?? 0 ?></div>
            </div>
            <div class="metric-card">
                <h3>🔔 คำขอเบิกค้างอนุมัติ</h3>
                <div class="value"><?= $metrics['pending_disbursements_count'] ?? 0 ?></div>
            </div>
            <?php 
                $quality = $metrics['data_quality_score'][0] ?? ['quality_score' => 0, 'quality_rating' => 'poor'];
                $qualityClass = 'quality-' . $quality['quality_rating'];
            ?>
            <a href="/data-cleansing" style="text-decoration: none;">
                <div class="metric-card <?= $qualityClass ?>">
                    <h3>🧹 คะแนนคุณภาพข้อมูล</h3>
                    <div class="value"><?= number_format($quality['quality_score'], 1) ?>%</div>
                </div>
            </a>
        </div>

        <?php if (!empty($alerts)): ?>
        <div class="alerts">
            <h2>🔔 การแจ้งเตือน</h2>
            <?php foreach (array_slice($alerts, 0, 10) as $alert): ?>
            <div class="alert-item <?= $alert['type'] ?>">
                <span class="alert-icon"><?= $alert['icon'] === 'box' ? '📦' : ($alert['icon'] === 'clock' ? '⏰' : '📄') ?></span>
                <div>
                    <?= htmlspecialchars($alert['message']) ?>
                    <?php if (isset($alert['link'])): ?>
                    <a href="<?= $alert['link'] ?>" style="margin-left: 10px; color: #667eea;">ดูรายละเอียด →</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="recent-activity">
            <div class="activity-card">
                <h2>📋 ใบสั่งซื้อล่าสุด</h2>
                <?php if (!empty($recent_orders)): ?>
                    <?php foreach ($recent_orders as $order): ?>
                    <div class="activity-item">
                        <div class="date"><?= date('d/m/Y', strtotime($order['order_date'])) ?></div>
                        <div class="title">
                            <?= htmlspecialchars($order['order_no']) ?> - 
                            <?= htmlspecialchars($order['supplier_name'] ?? 'N/A') ?>
                            (<?= number_format($order['total_amount'], 2) ?> บาท)
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: #999;">ไม่มีข้อมูล</p>
                <?php endif; ?>
            </div>

            <div class="activity-card">
                <h2>📦 การรับยาล่าสุด</h2>
                <?php if (!empty($recent_receives)): ?>
                    <?php foreach ($recent_receives as $receive): ?>
                    <div class="activity-item">
                        <div class="date"><?= date('d/m/Y H:i', strtotime($receive['transaction_date'])) ?></div>
                        <div class="title">
                            <?= htmlspecialchars($receive['drug_name']) ?> 
                            (<?= $receive['quantity'] ?> หน่วย)
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: #999;">ไม่มีข้อมูล</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
