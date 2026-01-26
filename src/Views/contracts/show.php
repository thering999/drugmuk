<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายละเอียดสัญญา - Drugmuk</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Sarabun', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 1200px; margin: 0 auto; }
        .header {
            background: white;
            padding: 25px 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h1 { color: #667eea; font-size: 28px; }
        .card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 25px;
        }
        .card h2 {
            color: #667eea;
            font-size: 22px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .info-item {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .info-label {
            font-size: 14px;
            color: #666;
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
        thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        th {
            font-weight: 600;
            font-size: 14px;
        }
        tbody tr:hover {
            background: #f8f9fa;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            display: inline-block;
        }
        .badge-active {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
            box-shadow: 0 2px 5px rgba(21, 87, 36, 0.2);
        }
        .badge-expired {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
            box-shadow: 0 2px 5px rgba(114, 28, 36, 0.2);
        }
        .badge-cancelled {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
            box-shadow: 0 2px 5px rgba(114, 28, 36, 0.2);
        }
        .info-item {
            transition: all 0.3s;
        }
        .info-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        th:first-child { border-radius: 8px 0 0 0; }
        th:last-child { border-radius: 0 8px 0 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📄 รายละเอียดสัญญา</h1>
            <div>
                <a href="/contracts" class="btn btn-secondary">← กลับ</a>
                <a href="/contracts/edit/<?= $contract['id'] ?>" class="btn btn-primary">✏️ แก้ไข</a>
            </div>
        </div>

        <div class="card">
            <h2>ข้อมูลสัญญา</h2>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">เลขที่สัญญา</div>
                    <div class="info-value"><?= htmlspecialchars($contract['contract_no']) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">ผู้จัดจำหน่าย</div>
                    <div class="info-value"><?= htmlspecialchars($contract['supplier_name']) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">วันที่เริ่มสัญญา</div>
                    <div class="info-value"><?= date('d/m/Y', strtotime($contract['start_date'])) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">วันที่สิ้นสุดสัญญา</div>
                    <div class="info-value"><?= date('d/m/Y', strtotime($contract['end_date'])) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">มูลค่าสัญญา</div>
                    <div class="info-value"><?= number_format($contract['total_amount'], 2) ?> บาท</div>
                </div>
                <div class="info-item">
                    <div class="info-label">สถานะ</div>
                    <div class="info-value">
                        <span class="badge badge-<?= $contract['status'] ?>">
                            <?php
                            $statusText = [
                                'active' => 'ใช้งานอยู่',
                                'expired' => 'หมดอายุ',
                                'cancelled' => 'ยกเลิก'
                            ];
                            echo $statusText[$contract['status']] ?? $contract['status'];
                            ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <h2>รายการยาในสัญญา</h2>
            <?php if (!empty($items)): ?>
            <table>
                <thead>
                    <tr>
                        <th>รหัสยา</th>
                        <th>ชื่อยา</th>
                        <th>หน่วย</th>
                        <th>ราคาตกลง</th>
                        <th>จำนวนตกลง</th>
                        <th>มูลค่ารวม</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['drug_code']) ?></td>
                        <td><strong><?= htmlspecialchars($item['drug_name']) ?></strong></td>
                        <td><?= htmlspecialchars($item['unit']) ?></td>
                        <td><?= number_format($item['agreed_price'], 2) ?> บาท</td>
                        <td><?= number_format($item['agreed_quantity']) ?></td>
                        <td><?= number_format($item['agreed_price'] * $item['agreed_quantity'], 2) ?> บาท</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p style="text-align: center; padding: 40px; color: #999;">ยังไม่มีรายการยาในสัญญา</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
