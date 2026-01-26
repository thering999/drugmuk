<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายการยาของฉัน - Drugmuk</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #4f46e5;
            --success: #10b981;
            --bg: #f3f4f6;
        }
        body {
            font-family: 'Inter', 'Sarabun', sans-serif;
            background-color: var(--bg);
            margin: 0;
            padding: 0;
            color: #1f2937;
        }
        .mobile-container {
            max-width: 500px;
            margin: 0 auto;
            background: white;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        header {
            background-color: var(--primary);
            color: white;
            padding: 30px 20px;
            border-bottom-left-radius: 30px;
            border-bottom-right-radius: 30px;
            text-align: center;
        }
        header h1 { margin: 0; font-size: 24px; }
        header p { margin: 5px 0 0; opacity: 0.9; }

        .content {
            padding: 20px;
            flex: 1;
        }

        .welcome-card {
            background: #eef2ff;
            padding: 15px;
            border-radius: 15px;
            margin-bottom: 25px;
            border-left: 5px solid var(--primary);
        }

        .section-title {
            font-weight: 700;
            font-size: 18px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .med-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            position: relative;
            overflow: hidden;
        }
        .med-card::before {
            content: '';
            position: absolute;
            left: 0; top: 0; bottom: 0;
            width: 6px;
            background: var(--success);
        }
        .med-name {
            font-weight: 700;
            font-size: 18px;
            color: var(--primary);
            margin-bottom: 10px;
        }
        .med-instruction {
            font-size: 16px;
            line-height: 1.5;
            color: #4b5563;
        }
        
        .action-btns {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        .btn-check {
            flex: 1;
            padding: 12px;
            border-radius: 10px;
            border: none;
            background: #ecfdf5;
            color: #065f46;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }
        .btn-check:active { background: #d1fae5; }

        footer {
            padding: 20px;
            text-align: center;
            color: #9ca3af;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="mobile-container">
        <header>
            <h1>🩺 My Medications</h1>
            <p>Drugmuk Smart Patient Link</p>
        </header>

        <div class="content">
            <div class="welcome-card">
                <strong>สวัสดีครับ/ค่ะ คุณ <?= htmlspecialchars($patient['full_name'] ?? 'ผู้ป่วย') ?></strong>
                <p style="margin: 5px 0 0; font-size: 14px; color: #6366f1;">มีรายการยาที่คุณต้องทานวันนี้ ดังนี้ครับ/ค่ะ</p>
            </div>

            <div class="section-title">
                <i class="fas fa-pills" style="color: var(--primary);"></i> รายการยาของคุณ
            </div>

            <?php if (empty($instructions)): ?>
                <div style="text-align: center; color: #9ca3af; padding: 40px 0;">
                    <i class="fas fa-box-open" style="font-size: 48px; margin-bottom: 10px;"></i>
                    <p>ยังไม่มีข้อมูลยาที่บันทึกไว้ครับ/ค่ะ</p>
                </div>
            <?php else: ?>
                <?php foreach ($instructions as $med): ?>
                    <div class="med-card">
                        <div class="med-name"><?= htmlspecialchars($med['drug_name']) ?></div>
                        <div class="med-instruction">
                            <?= nl2br(htmlspecialchars($med['instruction_text'])) ?>
                        </div>
                        <div class="action-btns">
                            <button class="btn-check" onclick="markTaken(this, '<?= $med['hn'] ?>', '<?= $med['drug_id'] ?>')">
                                <i class="far fa-check-circle"></i> บันทึกว่าทานแล้ว
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <div style="margin-top: 30px; text-align: center;">
                <button style="border: none; background: #fef2f2; color: #991b1b; padding: 15px; border-radius: 15px; width: 100%; font-weight: 600;">
                    <i class="fas fa-phone-alt"></i> ติดต่อเภสัชกรทางไกล (Tele-Consult)
                </button>
            </div>
        </div>

        <footer>
            &copy; 2026 Powered by Drugmuk Intelligence System
        </footer>
    </div>

    <script>
        function markTaken(btn, hn, drugId) {
            btn.innerHTML = '<i class="fas fa-check-circle"></i> บันทึกแล้ว!';
            btn.style.background = '#10b981';
            btn.style.color = 'white';
            btn.disabled = true;
            
            // In reality, this would call the API
            console.log('Marking drug as taken:', hn, drugId);
            fetch('/api/engagement/record-adherence', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ hn, drugId, status: 'taken' })
            });
        }
    </script>
</body>
</html>
