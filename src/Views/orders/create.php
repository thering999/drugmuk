<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สร้างใบสั่งซื้อ - Drugmuk</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Sarabun', 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 1000px; margin: 0 auto; }
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
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; color: #333; }
        .form-group input, .form-group select, .form-group textarea {
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
            <h1>📝 สร้างใบสั่งซื้อ</h1>
        </div>

        <div class="form-container">
            <form method="POST" action="/orders/store">
                <?php echo \App\Core\CSRF::field(); ?>
                <div class="form-group">
                    <label>เลขที่ใบสั่งซื้อ *</label>
                    <input type="text" name="order_no" value="PO<?= date('YmdHis') ?>" required>
                </div>

                <div class="form-group">
                    <label>ผู้จำหน่าย *</label>
                    <select name="supplier_id" required>
                        <option value="">-- เลือกผู้จำหน่าย --</option>
                        <?php if (!empty($suppliers)): ?>
                            <?php foreach ($suppliers as $supplier): ?>
                                <option value="<?= $supplier['id'] ?>"><?= htmlspecialchars($supplier['name']) ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>วันที่สั่งซื้อ *</label>
                    <input type="date" name="order_date" value="<?= date('Y-m-d') ?>" required>
                </div>

                <div class="form-group">
                    <label>วันที่ส่งมอบ</label>
                    <input type="date" name="delivery_date">
                </div>

                <div class="form-group">
                    <label>หมายเหตุ</label>
                    <textarea name="notes" rows="3"></textarea>
                </div>

                <div style="margin-top: 30px;">
                    <button type="submit" class="btn">💾 บันทึก</button>
                    <a href="/orders" class="btn btn-secondary">← ยกเลิก</a>
                </div>
            </form>

            <p style="margin-top: 20px; color: #999; font-size: 14px;">
                💡 <strong>เคล็ดลับ:</strong> ใช้หน้า <a href="/orders/what-to-buy" style="color: #667eea;">"ซื้ออะไร?"</a> เพื่อดูรายการยาที่ควรสั่งซื้อ
            </p>
        </div>
    </div>
</body>
</html>
