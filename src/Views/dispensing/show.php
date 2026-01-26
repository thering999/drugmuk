<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายละเอียดการจ่ายยา - Drugmuk</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
            max-width: 1000px;
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
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
            margin-right: 10px;
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

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .actions {
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
    </style>
</head>
<body>
    <div class="container">
        <?php if (isset($_SESSION['success'])): ?>
        <div class="message success">
            ✅ <?= htmlspecialchars($_SESSION['success']) ?>
        </div>
        <?php unset($_SESSION['success']); endif; ?>

        <div class="header">
            <h1>💊 รายละเอียดการจ่ายยา</h1>
            <a href="/dispensing" class="btn btn-secondary">← กลับ</a>
        </div>

        <div class="card">
            <h2 style="margin-bottom: 20px;">ข้อมูลการจ่ายยา</h2>
            
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">เลขที่</div>
                    <div class="info-value">DISP-<?= str_pad($dispensing['id'], 6, '0', STR_PAD_LEFT) ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label">วันที่จ่าย</div>
                    <div class="info-value"><?= date('d/m/Y H:i', strtotime($dispensing['dispense_date'])) ?> น.</div>
                </div>

                <div class="info-item">
                    <div class="info-label">HN</div>
                    <div class="info-value"><?= htmlspecialchars($dispensing['hn']) ?></div>
                </div>

                <?php if ($dispensing['vn']): ?>
                <div class="info-item">
                    <div class="info-label">VN</div>
                    <div class="info-value"><?= htmlspecialchars($dispensing['vn']) ?></div>
                </div>
                <?php endif; ?>

                <div class="info-item">
                    <div class="info-label">ชื่อผู้ป่วย</div>
                    <div class="info-value"><?= htmlspecialchars($dispensing['patient_name']) ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label">ผู้จ่ายยา</div>
                    <div class="info-value"><?= htmlspecialchars($dispensing['dispensed_by_name'] ?? 'N/A') ?></div>
                </div>
            </div>

            <?php if ($dispensing['clinical_notes']): ?>
            <div style="background: #fffaf0; border-left: 5px solid #f6ad55; padding: 20px; border-radius: 8px; margin-bottom: 25px;">
                <h3 style="color: #c05621; margin-bottom: 10px; font-size: 16px;">
                    <i class="fas fa-comment-medical"></i> Clinical Notes / Pharmacist Advice:
                </h3>
                <div style="font-size: 16px; line-height: 1.6; color: #744210; white-space: pre-wrap;"><?= htmlspecialchars($dispensing['clinical_notes']) ?></div>
            </div>
            <?php endif; ?>

            <h2 style="margin-top: 30px; margin-bottom: 15px;">รายการยาที่จ่าย</h2>
            <table>
                <thead>
                    <tr>
                        <th style="width: 5%; text-align: center;">ลำดับ</th>
                        <th style="width: 15%;">รหัสยา</th>
                        <th style="width: 35%;">ชื่อยา</th>
                        <th style="width: 15%;">จำนวน</th>
                        <th style="width: 10%;">หน่วย</th>
                        <th style="width: 20%; text-align: center;">Smart Label</th>
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
                        <td style="text-align: right;"><strong><?= number_format($item['quantity']) ?></strong></td>
                        <td><?= htmlspecialchars($item['unit']) ?></td>
                        <td style="text-align: center;">
                            <a href="/label/print/<?= $dispensing['id'] ?>/<?= $item['id'] ?>" target="_blank" class="btn btn-secondary btn-sm" style="background: #10b981; margin: 0;">
                                <i class="fas fa-qrcode"></i> พิมพ์ฉลาก QR
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div style="text-align: right; padding: 15px; background: #f8f9fa; border-radius: 8px; margin-top: 20px;">
                <strong>รวมทั้งหมด:</strong> <?= count($items) ?> รายการ
            </div>

            <div class="actions">
                <a href="/dispensing/print/<?= $dispensing['id'] ?>" target="_blank" class="btn btn-primary">
                    🖨️ พิมพ์ใบจ่ายยา
                </a>
                
                <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
                <form method="POST" action="/dispensing/delete/<?= $dispensing['id'] ?>" style="display: inline;" onsubmit="return confirm('ต้องการลบข้อมูลการจ่ายยานี้? การกระทำนี้ไม่สามารถย้อนกลับได้');">
                    <button type="submit" class="btn btn-danger">
                        🗑️ ลบข้อมูล
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
