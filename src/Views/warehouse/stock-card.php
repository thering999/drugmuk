<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Card - Drugmuk</title>
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
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        thead { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        th, td { padding: 12px; text-align: left; }
        td { border-bottom: 1px solid #eee; }
        .form-group { margin-bottom: 20px; }
        .form-group select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            width: 300px;
        }
        .btn {
            padding: 10px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Stock Card - บัตรคุมยา</h1>
            <a href="/warehouse" class="btn">← กลับ</a>
        </div>

        <div class="section">
            <div class="form-group">
                <label><strong>เลือกยา:</strong></label>
                <select onchange="window.location.href='/warehouse/stock-card/' + this.value">
                    <option value="">-- เลือกยา --</option>
                    <?php if (!empty($drugs)): ?>
                        <?php foreach ($drugs as $drug): ?>
                            <option value="<?= $drug['id'] ?>" <?= ($selected_drug == $drug['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($drug['code']) ?> - <?= htmlspecialchars($drug['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <?php if (!empty($transactions)): ?>
            <table>
                <thead>
                    <tr>
                        <th>วันที่</th>
                        <th>ประเภท</th>
                        <th>Lot</th>
                        <th>รับ</th>
                        <th>จ่าย</th>
                        <th>คงเหลือ</th>
                        <th>ผู้ทำรายการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $trans): ?>
                    <tr>
                        <td><?= date('d/m/Y H:i', strtotime($trans['transaction_date'])) ?></td>
                        <td><?= htmlspecialchars($trans['transaction_type']) ?></td>
                        <td><?= htmlspecialchars($trans['lot_no'] ?? '-') ?></td>
                        <td><?= $trans['transaction_type'] === 'receive' ? number_format($trans['quantity']) : '-' ?></td>
                        <td><?= $trans['transaction_type'] !== 'receive' ? number_format($trans['quantity']) : '-' ?></td>
                        <td><?= number_format($trans['balance_after']) ?></td>
                        <td><?= htmlspecialchars($trans['user_name'] ?? 'N/A') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php elseif ($selected_drug): ?>
            <p style="text-align: center; color: #999; padding: 40px;">ไม่มีรายการเคลื่อนไหว</p>
            <?php else: ?>
            <p style="text-align: center; color: #999; padding: 40px;">กรุณาเลือกยาเพื่อดู Stock Card</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
