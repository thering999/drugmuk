<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>อนุมัติการจ่าย - Drugmuk</title>
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
        .header h1 { color: #667eea; font-size: 28px; }
        .section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        table { width: 100%; border-collapse: collapse; }
        thead { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        th, td { padding: 12px; text-align: left; }
        td { border-bottom: 1px solid #eee; }
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            margin: 2px;
        }
        .btn-approve { background: #28a745; color: white; }
        .btn-reject { background: #dc3545; color: white; }
        .badge { padding: 5px 10px; border-radius: 5px; font-size: 12px; }
        .badge.urgent { background: #f8d7da; color: #721c24; }
        .badge.normal { background: #d1ecf1; color: #0c5460; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h1>✅ อนุมัติการจ่ายยา</h1>
                <a href="/warehouse" class="btn" style="background: #6c757d; color: white; text-decoration: none;">
                    ← กลับหน้าหลัก
                </a>
            </div>
        </div>

        <div class="section">
            <h2 style="margin-bottom: 20px;">คำขอเบิกยาที่รออนุมัติ</h2>
            <?php if (!empty($requests)): ?>
            <table>
                <thead>
                    <tr>
                        <th>คลัง</th>
                        <th>รหัสยา</th>
                        <th>ชื่อยา</th>
                        <th>จำนวนขอ</th>
                        <th>สต็อกคงเหลือ</th>
                        <th>ประเภท</th>
                        <th>วันที่ขอ</th>
                        <th>การดำเนินการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $req): ?>
                    <tr>
                        <td><?= htmlspecialchars($req['warehouse_code']) ?></td>
                        <td><?= htmlspecialchars($req['code']) ?></td>
                        <td><strong><?= htmlspecialchars($req['drug_name']) ?></strong></td>
                        <td><?= number_format($req['quantity_requested']) ?></td>
                        <td>-</td>
                        <td>
                            <span class="badge <?= $req['urgent'] ? 'urgent' : 'normal' ?>">
                                <?= $req['urgent'] ? 'ด่วน' : 'ปกติ' ?>
                            </span>
                        </td>
                        <td><?= date('d/m/Y H:i', strtotime($req['request_date'])) ?></td>
                        <td>
                            <form method="POST" action="/warehouse/process-disbursement" style="display: inline;">
                                <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                <input type="hidden" name="approved_quantity" value="<?= $req['quantity_requested'] ?>">
                                <input type="hidden" name="action" value="approve">
                                <button type="submit" class="btn btn-approve">✓ อนุมัติ</button>
                            </form>
                            <form method="POST" action="/warehouse/process-disbursement" style="display: inline;">
                                <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                <input type="hidden" name="action" value="reject">
                                <button type="submit" class="btn btn-reject">✗ ปฏิเสธ</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p style="text-align: center; color: #999; padding: 40px;">✅ ไม่มีคำขอที่รออนุมัติ</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
