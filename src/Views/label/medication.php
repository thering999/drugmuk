<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Medication Label - <?= htmlspecialchars($data['drug_name']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            background: #eee;
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
            border: 1px solid #ddd;
            padding: 5mm;
            box-sizing: border-box;
            display: grid;
            grid-template-columns: 1fr 30mm;
            position: relative;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        /* Printer Specific */
        @media print {
            body { background: none; }
            .label-container { 
                box-shadow: none; 
                border: none;
                margin: 0;
            }
            .no-print { display: none; }
        }

        .header {
            grid-column: 1 / span 2;
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 2mm;
            margin-bottom: 2mm;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .hospital-name { font-weight: 700; color: #4f46e5; font-size: 14px; }
        .patient-hn { font-size: 12px; color: #666; }

        .patient-name { font-weight: 700; font-size: 16px; margin: 2mm 0; }
        
        .medicine-info {
            grid-column: 1 / 2;
        }
        .med-name { font-weight: 700; font-size: 18px; color: #111827; }
        .med-instruction { font-size: 14px; color: #374151; line-height: 1.4; margin-top: 2mm; }
        
        .storage { 
            font-size: 11px; 
            color: #ef4444; 
            margin-top: 5mm; 
            border-top: 1px dashed #ddd; 
            padding-top: 1mm;
        }

        .qr-section {
            grid-column: 2 / 3;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
            text-align: center;
        }
        .qr-code { width: 25mm; height: 25mm; }
        .qr-help { font-size: 9px; color: #666; line-height: 1.2; }

        .btn-print {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #4f46e5;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="label-container">
        <div class="header">
            <span class="hospital-name">🏥 DRUGMUK SMART HEALTH</span>
            <span class="patient-hn">HN: <?= htmlspecialchars($data['hn']) ?></span>
        </div>

        <div class="medicine-info">
            <div class="patient-name">คุณ <?= htmlspecialchars($data['patient_name']) ?></div>
            <div class="med-name"><?= htmlspecialchars($data['drug_name']) ?></div>
            <div class="med-instruction">
                <strong>วิธีใช้:</strong> กินครั้งละ 1 เม็ด วันละ 3 ครั้ง หลังอาหาร เช้า กลางวัน เย็น
            </div>
            
            <div class="storage">
                📌 <?= htmlspecialchars($data['storage_advice'] ?? 'เก็บในที่แห้งและเย็น') ?>
            </div>
        </div>

        <div class="qr-section">
            <img src="<?= $qr_url ?>" class="qr-code" alt="QR Code">
            <div class="qr-help">
                สแกนดูวิดีโอวิธีใช้<br>และบันทึกการกินยา
            </div>
        </div>
    </div>

    <button onclick="window.print()" class="btn-print no-print">🖨️ พิมพ์ฉลากยา</button>
</body>
</html>
