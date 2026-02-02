<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตัดสินใจซื้ออะไร - Drugmuk</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
        }

        .header h1 {
            color: #667eea;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .header p {
            color: #666;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 15px;
            color: #667eea;
            text-decoration: none;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .table-container {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        th {
            padding: 15px;
            text-align: left;
            font-weight: 500;
        }

        td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
        }

        tbody tr:hover {
            background: #f8f9fa;
        }

        .badge {
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
            font-weight: 500;
        }

        .badge.danger {
            background: #f8d7da;
            color: #721c24;
        }

        .badge.warning {
            background: #fff3cd;
            color: #856404;
        }

        .badge.success {
            background: #d4edda;
            color: #155724;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: transform 0.2s;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1>🤔 ตัดสินใจซื้ออะไร?</h1>
                    <p>รายการยาที่ควรสั่งซื้อ (คำนวณจาก: สต็อกปัจจุบัน + ค้างรับ - ค้างจ่าย)</p>
                </div>
                <a href="/orders" class="btn btn-secondary">← กลับหน้าสั่งซื้อ</a>
            </div>
        </div>

        <div class="table-container">
            <?php if (!empty($drugs)): ?>
            <table>
                <thead>
                    <tr>
                        <th>รหัสยา</th>
                        <th>ชื่อยา</th>
                        <th>สต็อกปัจจุบัน</th>
                        <th>ค้างรับ</th>
                        <th>ค้างจ่าย</th>
                        <th>สต็อกสุทธิ</th>
                        <th>สต็อกขั้นต่ำ</th>
                        <th style="background: #a855f7;">AI Forecast (30D)</th>
                        <th>แนะนำสั่งซื้อ</th>
                        <th>สถานะ</th>
                        <th>การดำเนินการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($drugs as $drug): ?>
                    <tr>
                        <td><?= htmlspecialchars($drug['code']) ?></td>
                        <td><strong><?= htmlspecialchars($drug['name']) ?></strong></td>
                        <td><?= number_format($drug['current_stock']) ?></td>
                        <td><?= number_format($drug['pending_receive']) ?></td>
                        <td><?= number_format($drug['pending_issue']) ?></td>
                        <td><strong><?= number_format($drug['net_stock']) ?></strong></td>
                        <td><?= number_format($drug['min_stock']) ?></td>
                        <td>
                            <span style="color: #7e22ce; font-weight: bold;">
                                <i class="fas fa-brain"></i> <?= number_format($drug['next_month_forecast']) ?>
                            </span>
                        </td>
                        <td><strong style="color: #667eea;"><?= number_format($drug['suggested_order_qty']) ?></strong></td>
                        <td>
                            <?php 
                            $percentage = ($drug['net_stock'] / max($drug['min_stock'], 1)) * 100;
                            if ($percentage < 25): ?>
                                <span class="badge danger">วิกฤต</span>
                            <?php elseif ($percentage < 50): ?>
                                <span class="badge warning">ต่ำ</span>
                            <?php else: ?>
                                <span class="badge success">ปกติ</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="/orders/create?drug_id=<?= $drug['id'] ?>" class="btn btn-primary">สั่งซื้อ</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="no-data">
                <p>✅ ไม่มียาที่ต้องสั่งซื้อในขณะนี้</p>
                <p style="margin-top: 10px; font-size: 14px;">สต็อกทุกรายการอยู่ในระดับปกติ</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
