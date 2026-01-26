<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตั้งค่าสูตรคำนวณ - Drugmuk</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Sarabun', 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 1200px; margin: 0 auto; }
        .header {
            background: white;
            padding: 20px 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .header h1 { color: #667eea; font-size: 28px; margin-bottom: 10px; }
        .btn {
            padding: 10px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            display: inline-block;
            margin: 5px;
        }
        .info-box {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚙️ ตั้งค่าสูตรคำนวณการเบิก</h1>
            <p>คลัง: <?= htmlspecialchars($warehouse_code ?? 'N/A') ?></p>
        </div>

        <div class="info-box">
            <h3>🚧 ระบบกำลังพัฒนา</h3>
            <p>ฟีเจอร์นี้อยู่ระหว่างการพัฒนาใน Phase 2</p>
            <p style="margin-top: 15px;"><strong>ตัวอย่างสูตร:</strong></p>
            <ul style="margin-left: 20px; margin-top: 10px;">
                <li>ปริมาณเบิก = (Max - Current) หรือ</li>
                <li>ปริมาณเบิก = การใช้เฉลี่ย 1 เดือน</li>
                <li>ปริมาณเบิก = Custom formula</li>
            </ul>
            <p style="margin-top: 15px;">
                <a href="/subwarehouse" class="btn">← กลับคลังย่อย</a>
            </p>
        </div>
    </div>
</body>
</html>
