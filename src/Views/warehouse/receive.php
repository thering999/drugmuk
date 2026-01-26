<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รับยา - Drugmuk</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Sarabun', 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 1200px; margin: 0 auto; }
        .header {
            background: white;
            padding: 20px 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .header h1 { color: #667eea; font-size: 28px; }
        .section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; }
        .form-group input, .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .btn {
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        thead { background: #f8f9fa; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📦 รับยาเข้าคลัง</h1>
        </div>

        <div class="section">
            <h2 style="margin-bottom: 20px;">ใบสั่งซื้อที่รอรับ</h2>
            <?php if (!empty($pending_orders)): ?>
            <table>
                <thead>
                    <tr>
                        <th>เลขที่ใบสั่งซื้อ</th>
                        <th>ผู้จำหน่าย</th>
                        <th>วันที่สั่งซื้อ</th>
                        <th>จำนวนเงิน</th>
                        <th>การดำเนินการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pending_orders as $order): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($order['order_no']) ?></strong></td>
                        <td><?= htmlspecialchars($order['supplier_name'] ?? 'N/A') ?></td>
                        <td><?= date('d/m/Y', strtotime($order['order_date'])) ?></td>
                        <td><?= number_format($order['total_amount'], 2) ?> บาท</td>
                        <td>
                            <a href="#" class="btn" style="padding: 8px 16px; font-size: 14px;">รับยา</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p style="text-align: center; color: #999; padding: 40px;">✅ ไม่มีใบสั่งซื้อที่รอรับ</p>
            <?php endif; ?>
        </div>

        <div class="section">
            <h2 style="margin-bottom: 20px;">รับยาโดยไม่มีใบสั่งซื้อ</h2>
            <form method="POST" action="/warehouse/store-receive">
                <div class="form-group">
                    <label>เลขที่ใบส่งของ</label>
                    <input type="text" name="invoice_no" placeholder="INV-XXXXX">
                </div>
                <div class="form-group">
                    <label>วันที่รับ</label>
                    <input type="date" name="receive_date" value="<?= date('Y-m-d') ?>">
                </div>
                <button type="submit" class="btn">💾 บันทึก</button>
                <a href="/warehouse" class="btn" style="background: #6c757d;">← กลับ</a>
            </form>
        </div>
    </div>
</body>
</html>
