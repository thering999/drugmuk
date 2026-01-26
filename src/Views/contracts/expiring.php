<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สัญญาใกล้หมดอายุ - Drugmuk</title>
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
        }
        .header h1 { color: #667eea; font-size: 28px; margin-bottom: 15px; }
        .filter-section {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        .filter-section select {
            padding: 10px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
        }
        .card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        tbody tr:hover {
            background: #f8f9fa;
        }
        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border-left: 4px solid #ffc107;
        }
        .days-left {
            font-weight: 600;
            padding: 5px 10px;
            border-radius: 5px;
        }
        .days-critical { background: #f8d7da; color: #721c24; }
        .days-warning { background: #fff3cd; color: #856404; }
        .days-ok { background: #d1ecf1; color: #0c5460; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚠️ สัญญาใกล้หมดอายุ</h1>
            <div class="filter-section">
                <label>แสดงสัญญาที่จะหมดอายุภายใน:</label>
                <select onchange="window.location.href='/contracts/expiring?days='+this.value">
                    <option value="7" <?= $days == 7 ? 'selected' : '' ?>>7 วัน</option>
                    <option value="15" <?= $days == 15 ? 'selected' : '' ?>>15 วัน</option>
                    <option value="30" <?= $days == 30 ? 'selected' : '' ?>>30 วัน</option>
                    <option value="60" <?= $days == 60 ? 'selected' : '' ?>>60 วัน</option>
                    <option value="90" <?= $days == 90 ? 'selected' : '' ?>>90 วัน</option>
                </select>
                <a href="/contracts" class="btn btn-secondary">← กลับ</a>
            </div>
        </div>

        <?php if (empty($contracts)): ?>
        <div class="card">
            <p style="text-align: center; padding: 60px; color: #999; font-size: 18px;">
                ✅ ไม่มีสัญญาที่จะหมดอายุภายใน <?= $days ?> วัน
            </p>
        </div>
        <?php else: ?>
        <div class="alert alert-warning">
            ⚠️ <strong>พบสัญญาที่จะหมดอายุ <?= count($contracts) ?> สัญญา</strong> ภายใน <?= $days ?> วัน
        </div>

        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>เลขที่สัญญา</th>
                        <th>ผู้จัดจำหน่าย</th>
                        <th>วันที่เริ่ม</th>
                        <th>วันที่สิ้นสุด</th>
                        <th>เหลือเวลา</th>
                        <th>มูลค่า</th>
                        <th>การดำเนินการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($contracts as $contract): 
                        $daysLeft = (strtotime($contract['end_date']) - time()) / 86400;
                        $daysLeft = ceil($daysLeft);
                        $urgencyClass = $daysLeft <= 7 ? 'days-critical' : ($daysLeft <= 30 ? 'days-warning' : 'days-ok');
                    ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($contract['contract_no']) ?></strong></td>
                        <td><?= htmlspecialchars($contract['supplier_name']) ?></td>
                        <td><?= date('d/m/Y', strtotime($contract['start_date'])) ?></td>
                        <td><?= date('d/m/Y', strtotime($contract['end_date'])) ?></td>
                        <td>
                            <span class="days-left <?= $urgencyClass ?>">
                                <?= $daysLeft ?> วัน
                            </span>
                        </td>
                        <td><?= number_format($contract['total_amount'], 2) ?> บาท</td>
                        <td>
                            <a href="/contracts/show/<?= $contract['id'] ?>" class="btn btn-primary">ดูรายละเอียด</a>
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
