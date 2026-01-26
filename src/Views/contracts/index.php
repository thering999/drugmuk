<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>บริหารจัดการสัญญา - Drugmuk</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Sarabun', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 1400px; margin: 0 auto; }
        
        .header {
            background: white;
            padding: 25px 30px;
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
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .btn-group { display: flex; gap: 10px; }
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        .btn-success {
            background: #28a745;
            color: white;
        }
        .btn-success:hover {
            background: #218838;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border-left: 4px solid;
            transition: all 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }
        .stat-card.active { border-color: #28a745; }
        .stat-card.expiring { border-color: #ffc107; }
        .stat-card.expired { border-color: #dc3545; }
        .stat-card.total { border-color: #667eea; }
        
        .stat-label {
            font-size: 14px;
            color: #666;
            margin-bottom: 8px;
        }
        .stat-value {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        .stat-card.active .stat-value { color: #28a745; }
        .stat-card.expiring .stat-value { color: #ffc107; }
        .stat-card.expired .stat-value { color: #dc3545; }
        .stat-card.total .stat-value { color: #667eea; }
        
        .card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 25px;
        }
        .card h2 {
            color: #667eea;
            font-size: 22px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .table-container {
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 12px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
        }
        th:first-child { border-radius: 8px 0 0 0; }
        th:last-child { border-radius: 0 8px 0 0; }
        
        td {
            padding: 15px 12px;
            border-bottom: 1px solid #e0e0e0;
            font-size: 14px;
        }
        tr:hover {
            background: #f8f9fa;
        }
        tr:last-child td {
            border-bottom: none;
        }
        
        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }
        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }
        
        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        .no-data-icon {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.3;
        }
        
        .action-links {
            display: flex;
            gap: 10px;
        }
        .action-link {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
        }
        .action-link:hover {
            color: #764ba2;
            text-decoration: underline;
        }
        
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 บริหารจัดการสัญญา</h1>
            <div class="btn-group">
                <a href="/dashboard" class="btn btn-secondary">← กลับหน้าหลัก</a>
                <a href="/contracts/create" class="btn btn-primary">➕ เพิ่มสัญญาใหม่</a>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card total">
                <div class="stat-label">สัญญาทั้งหมด</div>
                <div class="stat-value"><?= count($contracts) ?></div>
                <div style="font-size: 12px; color: #999;">รายการ</div>
            </div>
            <div class="stat-card active">
                <div class="stat-label">สัญญาที่ใช้งานอยู่</div>
                <div class="stat-value">
                    <?= count(array_filter($contracts, fn($c) => $c['status'] === 'active')) ?>
                </div>
                <div style="font-size: 12px; color: #999;">รายการ</div>
            </div>
            <div class="stat-card expiring">
                <div class="stat-label">ใกล้หมดอายุ (30 วัน)</div>
                <div class="stat-value">
                    <?php
                    $expiringSoon = array_filter($contracts, function($c) {
                        $endDate = strtotime($c['end_date']);
                        $now = time();
                        $daysLeft = ($endDate - $now) / 86400;
                        return $daysLeft > 0 && $daysLeft <= 30;
                    });
                    echo count($expiringSoon);
                    ?>
                </div>
                <div style="font-size: 12px; color: #999;">รายการ</div>
            </div>
            <div class="stat-card expired">
                <div class="stat-label">หมดอายุแล้ว</div>
                <div class="stat-value">
                    <?= count(array_filter($contracts, fn($c) => $c['status'] === 'expired')) ?>
                </div>
                <div style="font-size: 12px; color: #999;">รายการ</div>
            </div>
        </div>

        <!-- Contracts Table -->
        <div class="card">
            <h2>รายการสัญญาทั้งหมด</h2>
            
            <div class="table-container">
                <?php if (empty($contracts)): ?>
                    <div class="no-data">
                        <div class="no-data-icon">📋</div>
                        <h3>ยังไม่มีข้อมูลสัญญา</h3>
                        <p style="margin-top: 10px; color: #666;">เริ่มต้นโดยการเพิ่มสัญญาใหม่</p>
                        <a href="/contracts/create" class="btn btn-primary" style="margin-top: 20px;">➕ เพิ่มสัญญาใหม่</a>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>เลขที่สัญญา</th>
                                <th>ผู้ขาย</th>
                                <th>วันที่เริ่ม</th>
                                <th>วันที่สิ้นสุด</th>
                                <th>มูลค่าสัญญา</th>
                                <th>สถานะ</th>
                                <th>จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($contracts as $contract): ?>
                                <?php
                                // Calculate days left
                                $endDate = strtotime($contract['end_date']);
                                $now = time();
                                $daysLeft = ($endDate - $now) / 86400;
                                
                                // Determine badge class
                                if ($contract['status'] === 'expired' || $daysLeft < 0) {
                                    $badgeClass = 'badge-danger';
                                    $statusText = 'หมดอายุ';
                                } elseif ($daysLeft <= 30) {
                                    $badgeClass = 'badge-warning';
                                    $statusText = 'ใกล้หมดอายุ';
                                } else {
                                    $badgeClass = 'badge-success';
                                    $statusText = 'ใช้งานอยู่';
                                }
                                ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($contract['contract_no']) ?></strong></td>
                                    <td><?= htmlspecialchars($contract['supplier_name']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($contract['start_date'])) ?></td>
                                    <td>
                                        <?= date('d/m/Y', strtotime($contract['end_date'])) ?>
                                        <?php if ($daysLeft > 0 && $daysLeft <= 30): ?>
                                            <br><small style="color: #ffc107;">เหลือ <?= ceil($daysLeft) ?> วัน</small>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?= number_format($contract['total_amount'], 2) ?></strong> บาท</td>
                                    <td>
                                        <span class="badge <?= $badgeClass ?>">
                                            <?= $statusText ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-links">
                                            <a href="/contracts/show/<?= $contract['id'] ?>" class="action-link">รายละเอียด</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <div style="margin-top: 20px; padding-top: 20px; border-top: 2px solid #f0f0f0; color: #666;">
                        <strong>จำนวนรายการทั้งหมด:</strong> <?= count($contracts) ?> รายการ
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
