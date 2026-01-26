<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Builder - Drugmuk</title>
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
        }
        .header h1 { color: #667eea; font-size: 28px; }
        .card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 25px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            font-family: 'Sarabun', sans-serif;
        }
        .form-group textarea {
            min-height: 200px;
            font-family: 'Courier New', monospace;
        }
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
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
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border-left: 4px solid #17a2b8;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔧 สร้างรายงานใหม่</h1>
        </div>

        <div class="card">
            <div class="alert alert-info">
                💡 <strong>คำแนะนำ:</strong> สร้างรายงานด้วย SQL Query โดยตรง สามารถใช้ตารางต่างๆ เช่น drugs, inventory, dispensing, orders เป็นต้น
            </div>

            <form method="POST" action="/reports/create">
                <div class="form-group">
                    <label for="name">ชื่อรายงาน *</label>
                    <input type="text" id="name" name="name" required placeholder="เช่น รายงานสต็อกคงเหลือ">
                </div>

                <div class="form-group">
                    <label for="description">คำอธิบาย</label>
                    <input type="text" id="description" name="description" placeholder="คำอธิบายเกี่ยวกับรายงาน">
                </div>

                <div class="form-group">
                    <label for="query">SQL Query *</label>
                    <textarea id="query" name="query" required placeholder="SELECT * FROM drugs WHERE is_active = 1"></textarea>
                </div>

                <div style="display: flex; justify-content: space-between; margin-top: 30px;">
                    <a href="/reports" class="btn btn-secondary">← ยกเลิก</a>
                    <button type="submit" class="btn btn-primary">✅ สร้างรายงาน</button>
                </div>
            </form>
        </div>

        <div class="card">
            <h2 style="color: #667eea; margin-bottom: 15px;">ตัวอย่าง SQL Query</h2>
            <pre style="background: #f8f9fa; padding: 15px; border-radius: 8px; overflow-x: auto;">
-- สต็อกคงเหลือ
SELECT d.code, d.name, SUM(i.quantity) as total_qty 
FROM drugs d 
LEFT JOIN inventory i ON d.id = i.drug_id 
GROUP BY d.id;

-- ยาใกล้หมดอายุ
SELECT d.name, i.lot_no, i.expire_date, i.quantity 
FROM inventory i 
JOIN drugs d ON i.drug_id = d.id 
WHERE i.expire_date <= DATE_ADD(CURDATE(), INTERVAL 90 DAY);

-- สรุปการจ่ายยา
SELECT d.name, SUM(di.quantity) as total_qty 
FROM dispensing_items di 
JOIN drugs d ON di.drug_id = d.id 
GROUP BY d.id 
ORDER BY total_qty DESC;
            </pre>
        </div>
    </div>
</body>
</html>
