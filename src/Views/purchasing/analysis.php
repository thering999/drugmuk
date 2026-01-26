<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ABC/VEN Analysis - Drugmuk</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Sarabun', 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 1600px; margin: 0 auto; }
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
        .header h1 { color: #667eea; font-size: 28px; }
        .filter-box {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .filter-box select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-right: 10px;
        }
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .summary-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .summary-card h3 { font-size: 14px; color: #666; margin-bottom: 10px; }
        .summary-card .value { font-size: 28px; font-weight: bold; color: #667eea; }
        .table-container {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow-x: auto;
        }
        table { width: 100%; border-collapse: collapse; }
        thead { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        th, td { padding: 12px; text-align: left; font-size: 14px; }
        td { border-bottom: 1px solid #eee; }
        tbody tr:hover { background: #f8f9fa; }
        .badge {
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
            font-weight: 500;
        }
        .badge.abc-a { background: #f44336; color: white; }
        .badge.abc-b { background: #ff9800; color: white; }
        .badge.abc-c { background: #4caf50; color: white; }
        .badge.ven-v { background: #e91e63; color: white; }
        .badge.ven-e { background: #2196f3; color: white; }
        .badge.ven-n { background: #9e9e9e; color: white; }
        .btn {
            padding: 10px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            display: inline-block;
            margin: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 ABC/VEN Analysis</h1>
            <div>
                <a href="/purchasing" class="btn">← กลับ</a>
                <?php if ($selected_fy): ?>
                <a href="/purchasing/export?fiscal_year_id=<?= $selected_fy ?>" class="btn">📥 Export Excel</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="filter-box">
            <form method="GET" action="/purchasing/analysis">
                <label><strong>เลือกปีงบประมาณ:</strong></label>
                <select name="fiscal_year_id" onchange="this.form.submit()">
                    <option value="">-- เลือกปีงบประมาณ --</option>
                    <?php if (!empty($fiscal_years)): ?>
                        <?php foreach ($fiscal_years as $fy): ?>
                            <option value="<?= $fy['id'] ?>" <?= ($selected_fy == $fy['id']) ? 'selected' : '' ?>>
                                <?= $fy['year'] ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </form>
        </div>

        <?php if (!empty($plans)): ?>
            <?php
            $totalBudget = array_sum(array_column($plans, 'budget_plan'));
            $countA = count(array_filter($plans, fn($p) => $p['abc_class'] === 'A'));
            $countB = count(array_filter($plans, fn($p) => $p['abc_class'] === 'B'));
            $countC = count(array_filter($plans, fn($p) => $p['abc_class'] === 'C'));
            $countV = count(array_filter($plans, fn($p) => $p['ven_class'] === 'V'));
            $countE = count(array_filter($plans, fn($p) => $p['ven_class'] === 'E'));
            $countN = count(array_filter($plans, fn($p) => $p['ven_class'] === 'N'));
            ?>

            <div class="summary-cards">
                <div class="summary-card">
                    <h3>งบประมาณรวม</h3>
                    <div class="value"><?= number_format($totalBudget, 0) ?></div>
                    <small>บาท</small>
                </div>
                <div class="summary-card">
                    <h3>ABC Class A</h3>
                    <div class="value" style="color: #f44336;"><?= $countA ?></div>
                    <small>รายการ (80% มูลค่า)</small>
                </div>
                <div class="summary-card">
                    <h3>ABC Class B</h3>
                    <div class="value" style="color: #ff9800;"><?= $countB ?></div>
                    <small>รายการ (15% มูลค่า)</small>
                </div>
                <div class="summary-card">
                    <h3>ABC Class C</h3>
                    <div class="value" style="color: #4caf50;"><?= $countC ?></div>
                    <small>รายการ (5% มูลค่า)</small>
                </div>
                <div class="summary-card">
                    <h3>VEN - Vital</h3>
                    <div class="value" style="color: #e91e63;"><?= $countV ?></div>
                    <small>รายการ</small>
                </div>
                <div class="summary-card">
                    <h3>VEN - Essential</h3>
                    <div class="value" style="color: #2196f3;"><?= $countE ?></div>
                    <small>รายการ</small>
                </div>
            </div>

            <div class="table-container">
                <h2 style="margin-bottom: 20px;">รายการแผนซื้อ (<?= count($plans) ?> รายการ)</h2>
                <table>
                    <thead>
                        <tr>
                            <th>รหัสยา</th>
                            <th>ชื่อยา</th>
                            <th>แผนซื้อ</th>
                            <th>สต็อกขั้นต่ำ</th>
                            <th>งบประมาณ</th>
                            <th>ABC</th>
                            <th>VEN</th>
                            <th>หมวดหมู่</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($plans as $plan): ?>
                        <tr>
                            <td><?= htmlspecialchars($plan['drug_code']) ?></td>
                            <td><strong><?= htmlspecialchars($plan['drug_name']) ?></strong></td>
                            <td><?= number_format($plan['quantity_plan']) ?></td>
                            <td><?= number_format($plan['min_stock']) ?></td>
                            <td><?= number_format($plan['budget_plan'], 2) ?></td>
                            <td>
                                <span class="badge abc-<?= strtolower($plan['abc_class']) ?>">
                                    <?= $plan['abc_class'] ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge ven-<?= strtolower($plan['ven_class']) ?>">
                                    <?= $plan['ven_class'] ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($plan['category'] ?? '-') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="table-container" style="text-align: center; padding: 60px;">
                <p style="color: #999; font-size: 18px;">กรุณาเลือกปีงบประมาณเพื่อดูข้อมูล</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
