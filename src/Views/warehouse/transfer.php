<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>โอนระหว่างคลัง - Drugmuk</title>
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
        .header h1 { color: #667eea; font-size: 28px; }
        .form-container {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
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
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔄 โอนยาระหว่างคลัง</h1>
        </div>

        <div class="form-container">
            <form method="POST" action="/warehouse/process-transfer">
                <div class="form-group">
                    <label>จากคลัง *</label>
                    <select name="from_warehouse" required>
                        <option value="main">คลังใหญ่</option>
                        <?php if (!empty($warehouses)): ?>
                            <?php foreach ($warehouses as $wh): ?>
                                <option value="<?= $wh['code'] ?>"><?= htmlspecialchars($wh['name']) ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>ไปยังคลัง *</label>
                    <select name="to_warehouse" required>
                        <?php if (!empty($warehouses)): ?>
                            <?php foreach ($warehouses as $wh): ?>
                                <option value="<?= $wh['code'] ?>"><?= htmlspecialchars($wh['name']) ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>รหัสยา *</label>
                    <input type="text" name="drug_id" required placeholder="ค้นหายา...">
                </div>

                <div class="form-group">
                    <label>Lot Number</label>
                    <input type="text" name="lot_no" placeholder="LOT-XXXXX">
                </div>

                <div class="form-group">
                    <label>จำนวน *</label>
                    <input type="number" name="quantity" required min="1">
                </div>

                <div class="form-group">
                    <label>หมายเหตุ</label>
                    <textarea name="notes" rows="3" placeholder="ระบุหมายเหตุ (ถ้ามี)..."></textarea>
                </div>

                <div style="margin-top: 30px;">
                    <button type="submit" class="btn">🔄 โอนยา</button>
                    <a href="/warehouse" class="btn" style="background: #6c757d;">← กลับ</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
