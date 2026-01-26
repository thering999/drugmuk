<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>นำเข้าแผนซื้อ - Drugmuk</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Sarabun', 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 900px; margin: 0 auto; }
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
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .info-box h3 { color: #856404; margin-bottom: 10px; }
        .info-box ul { margin-left: 20px; color: #856404; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; color: #333; }
        .form-group select, .form-group input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .file-upload {
            border: 2px dashed #667eea;
            padding: 30px;
            text-align: center;
            border-radius: 10px;
            background: #f8f9fa;
            cursor: pointer;
            transition: all 0.3s;
        }
        .file-upload:hover { background: #e3f2fd; }
        .file-upload input[type="file"] { display: none; }
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
        .sample-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            font-size: 12px;
        }
        .sample-table th, .sample-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .sample-table th { background: #f8f9fa; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📥 นำเข้าแผนซื้อจาก Excel</h1>
            <p>อัพโหลดไฟล์ CSV ที่ส่งออกจากระบบหรือแก้ไขด้วย Excel</p>
        </div>

        <div class="form-container">
            <div class="info-box">
                <h3>⚠️ ข้อกำหนดไฟล์ CSV:</h3>
                <ul>
                    <li>ไฟล์ต้องเป็นรูปแบบ CSV (Comma Separated Values)</li>
                    <li>แถวแรกต้องเป็น Header (จะถูกข้าม)</li>
                    <li>รหัสยาต้องตรงกับในระบบ</li>
                    <li>ระบบจะอัพเดทข้อมูลที่มีอยู่แล้วหรือเพิ่มใหม่</li>
                </ul>
            </div>

            <form method="POST" action="/purchasing/process-import" enctype="multipart/form-data">
                <div class="form-group">
                    <label>ปีงบประมาณ *</label>
                    <select name="fiscal_year_id" required>
                        <option value="">-- เลือกปีงบประมาณ --</option>
                        <?php if (!empty($fiscal_years)): ?>
                            <?php foreach ($fiscal_years as $fy): ?>
                                <option value="<?= $fy['id'] ?>">
                                    <?= $fy['year'] ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>ไฟล์ CSV *</label>
                    <div class="file-upload" onclick="document.getElementById('csv_file').click()">
                        <p style="font-size: 48px; margin-bottom: 10px;">📄</p>
                        <p><strong>คลิกเพื่อเลือกไฟล์</strong></p>
                        <p style="color: #999; font-size: 14px; margin-top: 5px;">รองรับเฉพาะไฟล์ .csv</p>
                        <input type="file" id="csv_file" name="csv_file" accept=".csv" required 
                               onchange="document.getElementById('file-name').textContent = this.files[0].name">
                    </div>
                    <p id="file-name" style="margin-top: 10px; color: #667eea; font-weight: 500;"></p>
                </div>

                <div style="margin-top: 30px;">
                    <button type="submit" class="btn">📤 นำเข้าข้อมูล</button>
                    <a href="/purchasing" class="btn btn-secondary">← กลับ</a>
                </div>
            </form>

            <div style="margin-top: 40px; padding-top: 30px; border-top: 2px solid #eee;">
                <h3 style="margin-bottom: 15px;">📋 ตัวอย่างรูปแบบไฟล์ CSV:</h3>
                <table class="sample-table">
                    <thead>
                        <tr>
                            <th>รหัสยา</th>
                            <th>ชื่อยา</th>
                            <th>ปี 1</th>
                            <th>ปี 2</th>
                            <th>ปี 3</th>
                            <th>เฉลี่ย/ปี</th>
                            <th>แผนซื้อ</th>
                            <th>สต็อกขั้นต่ำ</th>
                            <th>ราคา/หน่วย</th>
                            <th>งบประมาณ</th>
                            <th>ABC</th>
                            <th>VEN</th>
                            <th>หมวดหมู่</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>100001</td>
                            <td>Paracetamol 500mg</td>
                            <td>10000</td>
                            <td>12000</td>
                            <td>11000</td>
                            <td>11000</td>
                            <td>12000</td>
                            <td>1000</td>
                            <td>0.50</td>
                            <td>6000.00</td>
                            <td>A</td>
                            <td>E</td>
                            <td>ED</td>
                        </tr>
                    </tbody>
                </table>
                <p style="margin-top: 15px; color: #999; font-size: 14px;">
                    <strong>หมายเหตุ:</strong> คอลัมน์ที่สำคัญคือ รหัสยา, แผนซื้อ, สต็อกขั้นต่ำ, ABC, VEN
                </p>
            </div>
        </div>
    </div>
</body>
</html>
