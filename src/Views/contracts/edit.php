<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขสัญญา - Drugmuk</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Sarabun', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 800px; margin: 0 auto; }
        
        .header {
            background: white;
            padding: 25px 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .header h1 { 
            color: #667eea; 
            font-size: 28px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .card {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 25px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 15px;
        }
        .form-group label .required {
            color: #dc3545;
            margin-left: 3px;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            font-family: 'Sarabun', sans-serif;
            transition: all 0.3s;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        .form-group small {
            display: block;
            margin-top: 5px;
            color: #666;
            font-size: 13px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .btn-group {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            padding-top: 30px;
            border-top: 2px solid #f0f0f0;
        }
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
            text-align: center;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            flex: 1;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        .btn-danger:hover {
            background: #c82333;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border-left: 4px solid #ffc107;
        }
        
        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            .btn-group {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✏️ แก้ไขสัญญา</h1>
        </div>

        <div class="card">
            <div class="alert alert-warning">
                ⚠️ <strong>คำเตือน:</strong> การแก้ไขสัญญาจะส่งผลต่อข้อมูลที่เกี่ยวข้อง กรุณาตรวจสอบข้อมูลให้ถูกต้องก่อนบันทึก
            </div>

            <form action="/contracts/update/<?= $contract['id'] ?>" method="POST">
                <div class="form-group">
                    <label>
                        เลขที่สัญญา
                        <span class="required">*</span>
                    </label>
                    <input 
                        type="text" 
                        name="contract_no" 
                        required 
                        value="<?= htmlspecialchars($contract['contract_no']) ?>"
                        placeholder="เช่น CT-2025-001"
                    >
                    <small>รูปแบบ: CT-ปี-เลขที่</small>
                </div>

                <div class="form-group">
                    <label>
                        ผู้ขาย
                        <span class="required">*</span>
                    </label>
                    <select name="supplier_id" required>
                        <option value="">-- เลือกผู้ขาย --</option>
                        <?php if (!empty($suppliers)): ?>
                            <?php foreach ($suppliers as $supplier): ?>
                                <option 
                                    value="<?= $supplier['id'] ?>"
                                    <?= $supplier['id'] == $contract['supplier_id'] ? 'selected' : '' ?>
                                >
                                    <?= htmlspecialchars($supplier['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>
                            วันที่เริ่มสัญญา
                            <span class="required">*</span>
                        </label>
                        <input 
                            type="date" 
                            name="start_date" 
                            required
                            value="<?= $contract['start_date'] ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label>
                            วันที่สิ้นสุดสัญญา
                            <span class="required">*</span>
                        </label>
                        <input 
                            type="date" 
                            name="end_date" 
                            required
                            value="<?= $contract['end_date'] ?>"
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label>
                        มูลค่าสัญญา (บาท)
                        <span class="required">*</span>
                    </label>
                    <input 
                        type="number" 
                        name="total_amount" 
                        required 
                        min="0" 
                        step="0.01"
                        value="<?= $contract['total_amount'] ?>"
                        placeholder="0.00"
                    >
                    <small>ระบุมูลค่ารวมของสัญญาทั้งหมด</small>
                </div>

                <div class="form-group">
                    <label>
                        สถานะ
                        <span class="required">*</span>
                    </label>
                    <select name="status" required>
                        <option value="active" <?= $contract['status'] === 'active' ? 'selected' : '' ?>>
                            ใช้งานอยู่
                        </option>
                        <option value="expired" <?= $contract['status'] === 'expired' ? 'selected' : '' ?>>
                            หมดอายุ
                        </option>
                        <option value="cancelled" <?= $contract['status'] === 'cancelled' ? 'selected' : '' ?>>
                            ยกเลิก
                        </option>
                    </select>
                    <small>
                        สถานะปัจจุบัน: 
                        <?php
                        $statusBadge = [
                            'active' => '<span class="badge badge-success">ใช้งานอยู่</span>',
                            'expired' => '<span class="badge badge-danger">หมดอายุ</span>',
                            'cancelled' => '<span class="badge badge-danger">ยกเลิก</span>'
                        ];
                        echo $statusBadge[$contract['status']] ?? $contract['status'];
                        ?>
                    </small>
                </div>

                <div class="form-group">
                    <label>รายละเอียดเพิ่มเติม</label>
                    <textarea 
                        name="description" 
                        placeholder="ระบุรายละเอียดเพิ่มเติมของสัญญา (ถ้ามี)"
                    ><?= htmlspecialchars($contract['description'] ?? '') ?></textarea>
                </div>

                <div class="btn-group">
                    <a href="/contracts/show/<?= $contract['id'] ?>" class="btn btn-secondary">← ยกเลิก</a>
                    <button type="submit" class="btn btn-primary">💾 บันทึกการแก้ไข</button>
                </div>
            </form>
        </div>

        <!-- Contract Items Section -->
        <?php if (!empty($items)): ?>
        <div class="card">
            <h2 style="color: #667eea; margin-bottom: 20px;">รายการยาในสัญญา</h2>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                            <th style="padding: 12px; text-align: left;">รหัสยา</th>
                            <th style="padding: 12px; text-align: left;">ชื่อยา</th>
                            <th style="padding: 12px; text-align: right;">ราคาตกลง</th>
                            <th style="padding: 12px; text-align: right;">จำนวนตกลง</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                        <tr style="border-bottom: 1px solid #e0e0e0;">
                            <td style="padding: 12px;"><?= htmlspecialchars($item['drug_code']) ?></td>
                            <td style="padding: 12px;"><strong><?= htmlspecialchars($item['drug_name']) ?></strong></td>
                            <td style="padding: 12px; text-align: right;"><?= number_format($item['agreed_price'], 2) ?> บาท</td>
                            <td style="padding: 12px; text-align: right;"><?= number_format($item['agreed_quantity']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p style="margin-top: 15px; color: #666; font-size: 14px;">
                💡 <strong>หมายเหตุ:</strong> การแก้ไขรายการยาต้องทำในหน้าจัดการรายการยาแยกต่างหาก
            </p>
        </div>
        <?php endif; ?>

        <!-- Delete Section -->
        <div class="card" style="border-left: 4px solid #dc3545;">
            <h3 style="color: #dc3545; margin-bottom: 15px;">⚠️ ลบสัญญา</h3>
            <p style="color: #666; margin-bottom: 20px;">
                การลบสัญญาจะลบข้อมูลทั้งหมดที่เกี่ยวข้อง รวมถึงรายการยาในสัญญา การดำเนินการนี้ไม่สามารถย้อนกลับได้
            </p>
            <form action="/contracts/delete/<?= $contract['id'] ?>" method="POST" onsubmit="return confirm('คุณแน่ใจหรือไม่ที่จะลบสัญญานี้? การดำเนินการนี้ไม่สามารถย้อนกลับได้');">
                <button type="submit" class="btn btn-danger">🗑️ ลบสัญญานี้</button>
            </form>
        </div>
    </div>

    <script>
        // Validate end date is after start date
        const startDate = document.querySelector('input[name="start_date"]');
        const endDate = document.querySelector('input[name="end_date"]');
        
        endDate.addEventListener('change', function() {
            if (startDate.value && this.value) {
                if (new Date(this.value) <= new Date(startDate.value)) {
                    alert('วันที่สิ้นสุดต้องมากกว่าวันที่เริ่มสัญญา');
                    this.value = '';
                }
            }
        });
        
        startDate.addEventListener('change', function() {
            if (endDate.value && this.value) {
                if (new Date(endDate.value) <= new Date(this.value)) {
                    alert('วันที่เริ่มต้องน้อยกว่าวันที่สิ้นสุด');
                    this.value = '';
                }
            }
        });
    </script>
</body>
</html>
