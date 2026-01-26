<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายการสั่งซื้อ - Drugmuk</title>
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
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h1 { color: #667eea; font-size: 28px; }
        .btn {
            padding: 10px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            display: inline-block;
            margin: 5px;
        }
        .btn:hover { transform: translateY(-2px); }
        .table-container {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow-x: auto;
        }
        table { width: 100%; border-collapse: collapse; }
        thead { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        th, td { padding: 12px 15px; text-align: left; }
        td { border-bottom: 1px solid #eee; }
        tbody tr:hover { background: #f8f9fa; }
        .badge {
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
            font-weight: 500;
        }
        .badge.pending { background: #fff3cd; color: #856404; }
        .badge.approved { background: #d4edda; color: #155724; }
        .badge.completed { background: #d1ecf1; color: #0c5460; }
        .no-data { text-align: center; padding: 40px; color: #999; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 รายการสั่งซื้อ</h1>
            <div>
                <a href="/dashboard" class="btn">← กลับหน้าหลัก</a>
                <a href="/orders/what-to-buy" class="btn">🤔 ซื้ออะไร?</a>
                <a href="/orders/create" class="btn">+ สร้างใบสั่งซื้อ</a>
            </div>
        </div>

        <div class="table-container">
            <?php if (!empty($orders)): ?>
            <table>
                <thead>
                    <tr>
                        <th>เลขที่ใบสั่งซื้อ</th>
                        <th>ผู้จำหน่าย</th>
                        <th>วันที่สั่งซื้อ</th>
                        <th>วันที่ส่งมอบ</th>
                        <th>จำนวนเงิน</th>
                        <th>สถานะ</th>
                        <th>การดำเนินการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($order['order_no']) ?></strong></td>
                        <td><?= htmlspecialchars($order['supplier_name'] ?? 'N/A') ?></td>
                        <td><?= date('d/m/Y', strtotime($order['order_date'])) ?></td>
                        <td><?= $order['delivery_date'] ? date('d/m/Y', strtotime($order['delivery_date'])) : '-' ?></td>
                        <td><?= number_format($order['total_amount'], 2) ?> บาท</td>
                        <td>
                            <span class="badge <?= $order['status'] ?>">
                                <?= $order['status'] === 'pending' ? 'รอดำเนินการ' : ($order['status'] === 'approved' ? 'อนุมัติแล้ว' : 'เสร็จสิ้น') ?>
                            </span>
                        </td>
                        <td>
                            <a href="/orders/show/<?= $order['id'] ?>" class="btn" style="padding: 5px 10px; font-size: 14px;">ดูรายละเอียด</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="no-data">
                <p>📭 ยังไม่มีรายการสั่งซื้อ</p>
                <p style="margin-top: 10px;"><a href="/orders/create" class="btn">สร้างใบสั่งซื้อแรก</a></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
