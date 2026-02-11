<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายละเอียดใบสั่งซื้อ - Drugmuk</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Sarabun', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
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

        .card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 20px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .info-item {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .info-label {
            font-weight: 500;
            color: #666;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .info-value {
            font-size: 18px;
            color: #333;
            font-weight: 600;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 500;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            display: inline-block;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
        }

        .badge-pending {
            background: #fff3cd;
            color: #856404;
        }

        .badge-approved {
            background: #d1ecf1;
            color: #0c5460;
        }

        .badge-received {
            background: #d4edda;
            color: #155724;
        }

        .badge-cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .total-section {
            text-align: right;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-top: 20px;
        }

        .total-amount {
            font-size: 24px;
            color: #667eea;
            font-weight: bold;
        }

        .status-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .message {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (isset($_SESSION['success'])): ?>
        <div class="message success">
            ✅ <?= htmlspecialchars($_SESSION['success']) ?>
        </div>
        <?php unset($_SESSION['success']); endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
        <div class="message error">
            ❌ <?= htmlspecialchars($_SESSION['error']) ?>
        </div>
        <?php unset($_SESSION['error']); endif; ?>

        <div class="header">
            <h1>📋 รายละเอียดใบสั่งซื้อ</h1>
            <a href="/orders" class="btn btn-secondary">← กลับ</a>
        </div>

        <div class="card">
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">เลขที่ใบสั่งซื้อ</div>
                    <div class="info-value"><?= htmlspecialchars($order['order_no']) ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label">ผู้จัดจำหน่าย</div>
                    <div class="info-value"><?= htmlspecialchars($order['supplier_name'] ?? 'N/A') ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label">วันที่สั่งซื้อ</div>
                    <div class="info-value"><?= date('d/m/Y', strtotime($order['order_date'])) ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label">วันที่กำหนดส่ง</div>
                    <div class="info-value"><?= $order['delivery_date'] ? date('d/m/Y', strtotime($order['delivery_date'])) : '-' ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label">สถานะ</div>
                    <div class="info-value">
                        <span class="badge badge-<?= $order['status'] ?>">
                            <?php
                            $statusText = [
                                'pending' => 'รออนุมัติ',
                                'approved' => 'อนุมัติแล้ว',
                                'received' => 'รับยาแล้ว',
                                'cancelled' => 'ยกเลิก'
                            ];
                            echo $statusText[$order['status']] ?? $order['status'];
                            ?>
                        </span>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-label">ผู้สร้าง</div>
                    <div class="info-value"><?= htmlspecialchars($order['created_by_name'] ?? 'N/A') ?></div>
                </div>
            </div>

            <h2 style="margin-top: 30px; margin-bottom: 15px;">รายการสินค้า</h2>
            <table>
                <thead>
                    <tr>
                        <th style="width: 10%;">ลำดับ</th>
                        <th style="width: 20%;">รหัสยา</th>
                        <th style="width: 35%;">ชื่อยา</th>
                        <th style="width: 10%;">จำนวน</th>
                        <th style="width: 15%;">ราคา/หน่วย</th>
                        <th style="width: 15%;">รวม</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; foreach ($items as $item): ?>
                    <tr>
                        <td style="text-align: center;"><?= $no++ ?></td>
                        <td><?= htmlspecialchars($item['drug_code'] ?? '') ?></td>
                        <td><?= htmlspecialchars($item['drug_name'] ?? '') ?></td>
                        <td style="text-align: right;"><?= number_format($item['quantity']) ?></td>
                        <td style="text-align: right;"><?= number_format($item['unit_price'], 2) ?> บาท</td>
                        <td style="text-align: right;"><?= number_format($item['total_price'], 2) ?> บาท</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="total-section">
                <div style="font-size: 18px; margin-bottom: 10px;">
                    รวมทั้งหมด: <span class="total-amount"><?= number_format($order['total_amount'], 2) ?> บาท</span>
                </div>
            </div>

            <?php if ($order['status'] === 'pending'): ?>
            <div class="status-actions">
                <form method="POST" action="/orders/update-status/<?= $order['id'] ?>" style="display: inline;">
                    <?= \App\Core\CSRF::field() ?>
                    <input type="hidden" name="status" value="approved">
                    <button type="submit" class="btn btn-success" onclick="return confirm('ต้องการอนุมัติใบสั่งซื้อนี้?')">
                        ✅ อนุมัติ
                    </button>
                </form>

                <form method="POST" action="/orders/update-status/<?= $order['id'] ?>" style="display: inline;">
                    <?= \App\Core\CSRF::field() ?>
                    <input type="hidden" name="status" value="cancelled">
                    <button type="submit" class="btn btn-danger" onclick="return confirm('ต้องการยกเลิกใบสั่งซื้อนี้?')">
                        ❌ ยกเลิก
                    </button>
                </form>
            </div>
            <?php endif; ?>

            <?php if ($order['status'] === 'approved'): ?>
            <div class="status-actions">
                <a href="/orders/receive/<?= $order['id'] ?>" class="btn btn-primary">
                    📦 รับยาเข้าคลัง
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
