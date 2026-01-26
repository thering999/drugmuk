<!DOCTYPE html>
<html lang="th">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?= htmlspecialchars($subwarehouse['name']) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Sarabun', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh; padding: 20px;
        }
        .container { max-width: 1400px; margin: 0 auto; }
        .back-button {
            display: inline-flex; align-items: center; gap: 0.5rem;
            padding: 0.75rem 1.5rem; background: rgba(255, 255, 255, 0.2);
            color: white; text-decoration: none; border-radius: 10px;
            margin-bottom: 1rem; transition: all 0.3s ease; font-weight: 600;
        }
        .back-button:hover { background: rgba(255, 255, 255, 0.3); transform: translateX(-5px); }
        .header {
            background: rgba(255, 255, 255, 0.95); padding: 20px 30px;
            border-radius: 15px; margin-bottom: 20px; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        .header h1 { color: #667eea; font-size: 28px; margin-bottom: 5px; }
        .header p { color: #666; font-size: 14px; }
        .stats-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px; margin-bottom: 20px;
        }
        .stat-card {
            background: rgba(255, 255, 255, 0.95); padding: 20px;
            border-radius: 12px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .stat-value { font-size: 32px; font-weight: 700; color: #667eea; }
        .stat-label { font-size: 14px; color: #666; margin-top: 5px; }
        .actions {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px; margin-bottom: 20px;
        }
        .action-card {
            background: rgba(255, 255, 255, 0.95); padding: 25px;
            border-radius: 12px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            text-align: center; transition: all 0.3s ease; cursor: pointer;
        }
        .action-card:hover { transform: translateY(-5px); box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15); }
        .action-icon { font-size: 48px; margin-bottom: 15px; }
        .action-title { font-size: 18px; font-weight: 600; color: #333; margin-bottom: 10px; }
        .action-desc { font-size: 14px; color: #666; }
        .section {
            background: rgba(255, 255, 255, 0.95); padding: 25px;
            border-radius: 12px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .section-title { font-size: 20px; font-weight: 600; color: #333; margin-bottom: 15px; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { padding: 12px; text-align: left; border-bottom: 1px solid #f0f0f0; }
        .table th { background: #f8f9fa; font-weight: 600; color: #333; }
        .badge {
            display: inline-block; padding: 4px 12px; border-radius: 12px;
            font-size: 12px; font-weight: 600;
        }
        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .badge-info { background: #dbeafe; color: #1e40af; }
    </style>
</head>
<body>
    <div class="container">
        <!-- Back Button -->
        <a href="/subwarehouse" class="back-button">
            <span style="font-size: 1.2rem;">←</span>
            <span>กลับไปรายการคลังย่อย</span>
        </a>

        <!-- Header -->
        <div class="header">
            <h1>📦 <?= htmlspecialchars($subwarehouse['name']) ?></h1>
            <p>รหัส: <?= htmlspecialchars($subwarehouse['code']) ?> | สถานที่: <?= htmlspecialchars($subwarehouse['location'] ?? '-') ?></p>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?= $stats['total_drugs'] ?? 0 ?></div>
                <div class="stat-label">รายการยาทั้งหมด</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="color: #ef4444;"><?= $stats['critical_count'] ?? 0 ?></div>
                <div class="stat-label">ยาหมด/ใกล้หมด</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="color: #f59e0b;"><?= $stats['low_count'] ?? 0 ?></div>
                <div class="stat-label">ยาน้อยกว่า Min</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="color: #10b981;"><?= $stats['normal_count'] ?? 0 ?></div>
                <div class="stat-label">ยาปกติ</div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="actions">
            <div class="action-card" onclick="location.href='/subwarehouse/requisition/<?= $subwarehouse['code'] ?>'">
                <div class="action-icon">📋</div>
                <div class="action-title">ขอเบิกยา</div>
                <div class="action-desc">สร้างใบขอเบิกยาจากคลังหลัก</div>
            </div>
            
            <div class="action-card" onclick="location.href='/subwarehouse/dispense/<?= $subwarehouse['code'] ?>'">
                <div class="action-icon">💊</div>
                <div class="action-title">จ่ายยา</div>
                <div class="action-desc">จ่ายยาให้ผู้ป่วย</div>
            </div>
            
            <div class="action-card" onclick="location.href='/subwarehouse/configure-formula/<?= $subwarehouse['code'] ?>'">
                <div class="action-icon">⚙️</div>
                <div class="action-title">ตั้งค่าสูตร</div>
                <div class="action-desc">กำหนดสูตรคำนวณการเบิก</div>
            </div>
        </div>

        <!-- Low Stock Drugs -->
        <?php if (!empty($lowStockDrugs)): ?>
        <div class="section">
            <div class="section-title">⚠️ ยาที่ใกล้หมด</div>
            <table class="table">
                <thead>
                    <tr>
                        <th>รหัสยา</th>
                        <th>ชื่อยา</th>
                        <th>คงเหลือ</th>
                        <th>Min</th>
                        <th>Max</th>
                        <th>สถานะ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lowStockDrugs as $drug): ?>
                    <tr>
                        <td><?= htmlspecialchars($drug['drug_code']) ?></td>
                        <td><?= htmlspecialchars($drug['drug_name']) ?></td>
                        <td><?= number_format($drug['current_stock'], 2) ?> <?= htmlspecialchars($drug['unit']) ?></td>
                        <td><?= number_format($drug['min_quantity'], 2) ?></td>
                        <td><?= number_format($drug['max_quantity'], 2) ?></td>
                        <td>
                            <?php if ($drug['stock_status'] === 'critical'): ?>
                                <span class="badge badge-danger">หมด/ใกล้หมด</span>
                            <?php else: ?>
                                <span class="badge badge-warning">น้อยกว่า Min</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- Recent Requisitions -->
        <?php if (!empty($recentRequisitions)): ?>
        <div class="section">
            <div class="section-title">📝 ใบขอเบิกล่าสุด</div>
            <table class="table">
                <thead>
                    <tr>
                        <th>เลขที่</th>
                        <th>วันที่</th>
                        <th>จำนวนรายการ</th>
                        <th>ผู้ขอ</th>
                        <th>สถานะ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($recentRequisitions, 0, 5) as $req): ?>
                    <tr>
                        <td><?= htmlspecialchars($req['requisition_no']) ?></td>
                        <td><?= date('d/m/Y', strtotime($req['request_date'])) ?></td>
                        <td><?= $req['item_count'] ?? 0 ?> รายการ</td>
                        <td><?= htmlspecialchars($req['requested_by_name']) ?></td>
                        <td>
                            <?php
                            $statusBadges = [
                                'pending' => '<span class="badge badge-warning">รออนุมัติ</span>',
                                'approved' => '<span class="badge badge-info">อนุมัติแล้ว</span>',
                                'dispensed' => '<span class="badge badge-success">จ่ายแล้ว</span>',
                                'rejected' => '<span class="badge badge-danger">ปฏิเสธ</span>',
                                'cancelled' => '<span class="badge badge-danger">ยกเลิก</span>'
                            ];
                            echo $statusBadges[$req['status']] ?? $req['status'];
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
