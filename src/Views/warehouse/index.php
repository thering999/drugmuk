<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>คลังใหญ่ - Drugmuk</title>
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
        .btn {
            padding: 10px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            display: inline-block;
            margin: 5px;
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
        }
        .metric-card h3 { color: #666; font-size: 14px; margin-bottom: 10px; }
        .metric-card .value { font-size: 36px; font-weight: bold; color: #667eea; }
        .section {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .section h2 { color: #333; margin-bottom: 15px; }
        .item { padding: 12px; border-bottom: 1px solid #eee; }
        .item:last-child { border-bottom: none; }
        .no-data { text-align: center; padding: 40px; color: #999; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏭 คลังใหญ่</h1>
            <a href="/dashboard" class="btn">← กลับหน้าหลัก</a>
            <a href="/warehouse/receive" class="btn">📦 รับยา</a>
            <a href="/warehouse/approve-disbursement" class="btn">✅ อนุมัติการจ่าย</a>
            <a href="/warehouse/adjust" class="btn">⚙️ ปรับปรุงสต็อก</a>
            <a href="/warehouse/transfer" class="btn">🔄 โอนระหว่างคลัง</a>
        </div>

        <div class="metrics">
            <div class="metric-card">
                <h3>📊 รายการยาทั้งหมด</h3>
                <div class="value"><?= $stock_summary['total_items'] ?? 0 ?></div>
            </div>
            <div class="metric-card">
                <h3>📦 จำนวนทั้งหมด</h3>
                <div class="value"><?= number_format($stock_summary['total_quantity'] ?? 0) ?></div>
            </div>
            <div class="metric-card">
                <h3>💰 มูลค่ารวม</h3>
                <div class="value"><?= number_format($stock_summary['total_value'] ?? 0, 0) ?></div>
                <small>บาท</small>
            </div>
        </div>

        <div class="section">
            <h2>⚠️ ยาใกล้หมดสต็อก</h2>
            <?php if (!empty($low_stock)): ?>
                <?php foreach (array_slice($low_stock, 0, 5) as $item): ?>
                <div class="item">
                    <strong><?= htmlspecialchars($item['drug_name']) ?></strong>
                    - สต็อก: <?= $item['current_stock'] ?> / ขั้นต่ำ: <?= $item['min_stock'] ?>
                    <a href="/warehouse/stock-card/<?= $item['id'] ?>" style="margin-left: 10px; color: #667eea;">ดู Stock Card →</a>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-data">✅ ไม่มียาใกล้หมดสต็อก</div>
            <?php endif; ?>
        </div>

        <div class="section">
            <h2>⏰ ยาใกล้หมดอายุ (90 วัน)</h2>
            <?php if (!empty($expiring)): ?>
                <?php foreach (array_slice($expiring, 0, 5) as $item): ?>
                <div class="item">
                    <strong><?= htmlspecialchars($item['drug_name']) ?></strong>
                    - Lot: <?= htmlspecialchars($item['lot_no']) ?>
                    - หมดอายุ: <?= date('d/m/Y', strtotime($item['expire_date'])) ?>
                    - จำนวน: <?= $item['quantity'] ?>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-data">✅ ไม่มียาใกล้หมดอายุ</div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
