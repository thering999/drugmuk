<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ใบจ่ายยา - Drugmuk</title>
    <style>
        @media print {
            .no-print { display: none; }
            body { margin: 0; }
        }

        body {
            font-family: 'Sarabun', 'TH Sarabun New', sans-serif;
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background: white;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
        }

        .header p {
            margin: 5px 0;
            color: #666;
        }

        .info-section {
            margin-bottom: 20px;
        }

        .info-row {
            display: flex;
            margin-bottom: 8px;
        }

        .info-label {
            font-weight: bold;
            width: 150px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        th, td {
            border: 1px solid #333;
            padding: 10px;
            text-align: left;
        }

        th {
            background: #f0f0f0;
            font-weight: bold;
        }

        .footer {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
        }

        .signature {
            text-align: center;
            width: 200px;
        }

        .signature-line {
            border-top: 1px solid #333;
            margin-top: 60px;
            padding-top: 5px;
        }

        .btn {
            padding: 10px 20px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
        }

        .btn:hover {
            background: #5568d3;
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align: center; margin-bottom: 20px;">
        <button onclick="window.print()" class="btn">🖨️ พิมพ์</button>
        <a href="/dispensing/show/<?= $dispensing['id'] ?>" class="btn">← กลับ</a>
    </div>

    <div class="header">
        <h1>ใบจ่ายยา</h1>
        <p>ระบบบริหารคลังเวชภัณฑ์ยา Drugmuk</p>
    </div>

    <div class="info-section">
        <div class="info-row">
            <div class="info-label">เลขที่:</div>
            <div>DISP-<?= str_pad($dispensing['id'], 6, '0', STR_PAD_LEFT) ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">วันที่จ่าย:</div>
            <div><?= date('d/m/Y H:i น.', strtotime($dispensing['dispense_date'])) ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">HN:</div>
            <div><?= htmlspecialchars($dispensing['hn']) ?></div>
        </div>
        <?php if ($dispensing['vn']): ?>
        <div class="info-row">
            <div class="info-label">VN:</div>
            <div><?= htmlspecialchars($dispensing['vn']) ?></div>
        </div>
        <?php endif; ?>
        <div class="info-row">
            <div class="info-label">ชื่อผู้ป่วย:</div>
            <div><?= htmlspecialchars($dispensing['patient_name']) ?></div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 10%;">ลำดับ</th>
                <th style="width: 20%;">รหัสยา</th>
                <th style="width: 45%;">ชื่อยา</th>
                <th style="width: 15%;">จำนวน</th>
                <th style="width: 10%;">หน่วย</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1; foreach ($items as $item): ?>
            <tr>
                <td style="text-align: center;"><?= $no++ ?></td>
                <td><?= htmlspecialchars($item['drug_code']) ?></td>
                <td>
                    <?= htmlspecialchars($item['drug_name']) ?>
                    <?php if ($item['generic_name']): ?>
                    <br><small style="color: #666;">(<?= htmlspecialchars($item['generic_name']) ?>)</small>
                    <?php endif; ?>
                </td>
                <td style="text-align: right;"><?= number_format($item['quantity']) ?></td>
                <td><?= htmlspecialchars($item['unit']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div style="margin-top: 20px;">
        <strong>รวมทั้งหมด:</strong> <?= count($items) ?> รายการ
    </div>

    <div class="footer">
        <div class="signature">
            <div class="signature-line">
                ผู้จ่ายยา<br>
                (<?= htmlspecialchars($dispensing['dispensed_by_name'] ?? '') ?>)
            </div>
        </div>

        <div class="signature">
            <div class="signature-line">
                ผู้รับยา<br>
                (<?= htmlspecialchars($dispensing['patient_name']) ?>)
            </div>
        </div>
    </div>

    <div style="margin-top: 30px; text-align: center; color: #666; font-size: 12px;">
        พิมพ์เมื่อ: <?= date('d/m/Y H:i:s') ?> น.
    </div>
</body>
</html>
