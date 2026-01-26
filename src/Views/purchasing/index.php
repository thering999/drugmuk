<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แผนซื้อ - Drugmuk</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Sarabun', 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 1400px; margin: 0 auto; }
        .header {
            background: white;
            padding: 20px 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .header h1 { color: #667eea; font-size: 28px; margin-bottom: 15px; }
        .btn-group { display: flex; gap: 10px; flex-wrap: wrap; }
        .btn {
            padding: 10px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            display: inline-block;
        }
        .btn:hover { transform: translateY(-2px); }
        .btn-success { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); }
        .btn-info { background: linear-gradient(135deg, #17a2b8 0%, #138496 100%); }
        .btn-warning { background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%); }
        .table-container {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow-x: auto;
        }
        table { width: 100%; border-collapse: collapse; }
        thead { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        th, td { padding: 12px; text-align: left; }
        td { border-bottom: 1px solid #eee; }
        tbody tr:hover { background: #f8f9fa; }
        .no-data { text-align: center; padding: 60px; color: #999; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 แผนซื้อ (Purchasing Plan)</h1>
            <div class="btn-group">
                <a href="/dashboard" class="btn">← กลับหน้าหลัก</a>
                <a href="/purchasing/calculate" class="btn btn-success">🔄 คำนวณแผนซื้อ</a>
                <a href="/purchasing/analysis" class="btn btn-info">📈 ABC/VEN Analysis</a>
                <a href="/purchasing/import" class="btn btn-warning">📥 นำเข้า Excel</a>
            </div>
        </div>

        <div class="table-container">
            <h2 style="margin-bottom: 20px;">รายการแผนซื้อทั้งหมด</h2>
            
            <?php if (!empty($plans)): ?>
            <table>
                <thead>
                    <tr>
                        <th>ปีงบประมาณ</th>
                        <th>รหัสยา</th>
                        <th>ชื่อยา</th>
                        <th>แผนซื้อ</th>
                        <th>งบประมาณ</th>
                        <th>ABC</th>
                        <th>VEN</th>
                        <th>สต็อกขั้นต่ำ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($plans as $plan): ?>
                    <tr>
                        <td><?= htmlspecialchars($plan['fiscal_year']) ?></td>
                        <td><?= htmlspecialchars($plan['drug_code']) ?></td>
                        <td><strong><?= htmlspecialchars($plan['drug_name']) ?></strong></td>
                        <td><?= number_format($plan['quantity_plan']) ?></td>
                        <td><?= number_format($plan['budget_plan'], 2) ?> บาท</td>
                        <td>
                            <span style="padding: 3px 8px; border-radius: 3px; background: <?= $plan['abc_class'] === 'A' ? '#f44336' : ($plan['abc_class'] === 'B' ? '#ff9800' : '#4caf50') ?>; color: white;">
                                <?= $plan['abc_class'] ?? 'C' ?>
                            </span>
                        </td>
                        <td>
                            <span style="padding: 3px 8px; border-radius: 3px; background: <?= $plan['ven_class'] === 'V' ? '#e91e63' : ($plan['ven_class'] === 'E' ? '#2196f3' : '#9e9e9e') ?>; color: white;">
                                <?= $plan['ven_class'] ?? 'N' ?>
                            </span>
                        </td>
                        <td><?= number_format($plan['min_stock'] ?? 0) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="no-data">
                <p style="font-size: 18px;">📭 ยังไม่มีแผนซื้อ</p>
                <p style="margin-top: 15px;">
                    <a href="/purchasing/calculate" class="btn btn-success">เริ่มคำนวณแผนซื้อ</a>
                </p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
