<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Smart Label - <?= htmlspecialchars($data['drug_name']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: #f1f5f9;
            font-family: 'Sarabun', sans-serif;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .label-container {
            width: 90mm;
            height: 60mm;
            background: white;
            padding: 4mm;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            position: relative;
            box-shadow: 0 20px 50px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }
        
        @media print {
            body { background: none; }
            .label-container { box-shadow: none; border: 0.5px solid #eee; margin: 0; border-radius: 0; }
            .no-print { display: none; }
        }

        .label-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 1.5px solid #4f46e5;
            padding-bottom: 1.5mm;
            margin-bottom: 2mm;
        }
        .hosp-brand {
            display: flex;
            align-items: center;
            gap: 2mm;
            color: #4f46e5;
            font-weight: 700;
            font-size: 13px;
        }
        .patient-tag {
            font-size: 11px;
            color: #64748b;
            text-align: right;
        }

        .main-content {
            display: flex;
            height: 100%;
            gap: 3mm;
        }

        .med-details {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .patient-name {
            font-size: 14px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 1mm;
        }
        .med-name {
            font-size: 17px;
            font-weight: 700;
            color: #4f46e5;
            margin-bottom: 2mm;
            display: flex;
            justify-content: space-between;
        }
        .med-qty { font-size: 12px; color: #64748b; font-weight: normal; }

        .instruction-box {
            background: #f8fafc;
            padding: 2mm;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            font-size: 13.5px;
            color: #334155;
            min-height: 12mm;
            position: relative;
        }

        .visual-timing {
            display: flex;
            gap: 3mm;
            margin-top: 3mm;
            justify-content: space-around;
        }
        .time-icon {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1mm;
            opacity: 0.2;
            filter: grayscale(1);
        }
        .time-icon.active {
            opacity: 1;
            filter: grayscale(0);
        }
        .time-icon i { font-size: 18px; }
        .time-icon span { font-size: 9px; font-weight: 700; }

        .active-morning { color: #f59e0b; }
        .active-noon { color: #fbbf24; }
        .active-evening { color: #f97316; }
        .active-night { color: #6366f1; }

        .footer-row {
            margin-top: auto;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            padding-top: 2mm;
            font-size: 10px;
            color: #94a3b8;
        }
        .warning-text { color: #ef4444; font-weight: 700; display: flex; align-items: center; gap: 1mm; }

        .qr-side {
            width: 28mm;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border-left: 1px dashed #cbd5e0;
            padding-left: 2mm;
        }
        .qr-img { width: 22mm; height: 22mm; margin-bottom: 2mm; }
        .qr-label { font-size: 8.5px; line-height: 1.2; text-align: center; color: #64748b; }

        .btn-print {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #4f46e5;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 50px;
            cursor: pointer;
            font-weight: 700;
            box-shadow: 0 10px 20px rgba(79, 70, 229, 0.3);
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
        }
        .btn-print:hover { transform: scale(1.05); background: #4338ca; }
    </style>
</head>
<body>
    <?php
    $instruction = $data['usage_instruction'] ?? 'กินครั้งละ 1 เม็ด วันละ 3 ครั้ง หลังอาหาร (เช้า กลางวัน เย็น)';
    $isMorning = (strpos($instruction, 'เช้า') !== false || strpos($instruction, '1x3') !== false || strpos($instruction, '1x2') !== false);
    $isNoon = (strpos($instruction, 'กลางวัน') !== false || strpos($instruction, '1x3') !== false || strpos($instruction, 'qid') !== false);
    $isEvening = (strpos($instruction, 'เย็น') !== false || strpos($instruction, '1x3') !== false || strpos($instruction, '1x2') !== false);
    $isNight = (strpos($instruction, 'ก่อนนอน') !== false || strpos($instruction, 'hs') !== false);
    ?>

    <div class="label-container">
        <div class="label-header">
            <div class="hosp-brand">
                <i class="fas fa-hand-holding-medical"></i>
                <span>DRUGMUK SMART HOSPITAL</span>
            </div>
            <div class="patient-tag">
                <div>HN: <?= htmlspecialchars($data['hn']) ?></div>
                <div style="font-weight: 700;"><?= date('d/m/Y H:i') ?></div>
            </div>
        </div>

        <div class="main-content">
            <div class="med-details">
                <div class="patient-name">คุณ <?= htmlspecialchars($data['patient_name']) ?></div>
                <div class="med-name">
                    <?= htmlspecialchars($data['drug_name']) ?>
                    <span class="med-qty"><?= (int)$data['quantity'] ?> <?= htmlspecialchars($data['unit']) ?></span>
                </div>
                
                <div class="instruction-box">
                    <strong>วิธีใช้:</strong> <?= htmlspecialchars($instruction) ?>
                </div>

                <div class="visual-timing">
                    <div class="time-icon <?= $isMorning ? 'active active-morning' : '' ?>">
                        <i class="fas fa-sun"></i>
                        <span>เช้า</span>
                    </div>
                    <div class="time-icon <?= $isNoon ? 'active active-noon' : '' ?>">
                        <i class="fas fa-cloud-sun"></i>
                        <span>กลางวัน</span>
                    </div>
                    <div class="time-icon <?= $isEvening ? 'active active-evening' : '' ?>">
                        <i class="fas fa-cloud-moon"></i>
                        <span>เย็น</span>
                    </div>
                    <div class="time-icon <?= $isNight ? 'active active-night' : '' ?>">
                        <i class="fas fa-moon"></i>
                        <span>ก่อนนอน</span>
                    </div>
                </div>

                <div class="footer-row">
                    <div class="warning-text">
                        <i class="fas fa-exclamation-triangle"></i>
                        <?= htmlspecialchars($data['storage_advice'] ?? 'เก็บให้พ้นมือเด็ก') ?>
                    </div>
                    <div>Ref: DISP-<?= htmlspecialchars($data['hn'] . '-' . rand(100,999)) ?></div>
                </div>
            </div>

            <div class="qr-side">
                <img src="<?= $qr_url ?>" class="qr-img">
                <div class="qr-label">
                    <i class="fas fa-mobile-alt"></i><br>
                    สแกนดูวิดีโอวิธีใช้<br>
                    & เตือนกินยา
                </div>
            </div>
        </div>
    </div>

    <button onclick="window.print()" class="btn-print no-print">
        <i class="fas fa-print"></i> พิมพ์ฉลากยา (Smart Print)
    </button>
</body>
</html>
