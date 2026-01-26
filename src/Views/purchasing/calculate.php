<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>คำนวณแผนซื้อ - Drugmuk</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Sarabun', 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 800px; margin: 0 auto; }
        .header {
            background: white;
            padding: 20px 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .header h1 { color: #667eea; font-size: 28px; margin-bottom: 10px; }
        .header p { color: #666; }
        .form-container {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .info-box h3 { color: #1976d2; margin-bottom: 10px; }
        .info-box ul { margin-left: 20px; color: #555; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; color: #333; }
        .form-group select, .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        .btn {
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
        }
        .btn:hover { transform: translateY(-2px); }
        .btn-secondary { background: #6c757d; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 คำนวณแผนซื้อจากข้อมูล 3 ปี</h1>
            <p>ระบบจะคำนวณแผนซื้อโดยอัตโนมัติจากข้อมูลการใช้ยา 3 ปีย้อนหลัง</p>
        </div>

        <div class="form-container">
            <div class="info-box">
                <h3>📋 วิธีการคำนวณ:</h3>
                <ul>
                    <li>ดึงข้อมูลการใช้ยา 3 ปีย้อนหลัง</li>
                    <li>คำนวณค่าเฉลี่ยต่อปี แล้วแปลงเป็น 12 เดือน</li>
                    <li>สต็อกขั้นต่ำ = เฉลี่ย 1 เดือน</li>
                    <li>ABC Analysis: A=80%, B=15%, C=5% ของมูลค่า</li>
                    <li>VEN Classification: V=Vital, E=Essential, N=Non-essential</li>
                </ul>
            </div>

            <form method="POST" action="/purchasing/process-calculation">
                <div class="form-group">
                    <label>ปีงบประมาณ *</label>
                    <select name="fiscal_year_id" required>
                        <option value="">-- เลือกปีงบประมาณ --</option>
                        <?php if (!empty($fiscal_years)): ?>
                            <?php foreach ($fiscal_years as $fy): ?>
                                <option value="<?= $fy['id'] ?>">
                                    <?= $fy['year'] ?> (<?= date('d/m/Y', strtotime($fy['start_date'])) ?> - <?= date('d/m/Y', strtotime($fy['end_date'])) ?>)
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>% การเพิ่มขึ้นของการใช้ (ถ้ามี)</label>
                    <input type="number" name="increase_percent" value="0" min="0" max="100" step="0.1">
                    <small style="color: #999;">เช่น ใส่ 5 หมายถึง เพิ่มขึ้น 5%</small>
                </div>

                <div style="margin-top: 30px;">
                    <button type="submit" class="btn">🔄 คำนวณแผนซื้อ</button>
                    <a href="/purchasing" class="btn btn-secondary">← กลับ</a>
                </div>
            </form>

            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
                <p style="color: #999; font-size: 14px;">
                    <strong>หมายเหตุ:</strong> การคำนวณจะใช้เวลาสักครู่ ขึ้นอยู่กับจำนวนรายการยา
                </p>
            </div>
        </div>
    </div>
</body>
</html>
