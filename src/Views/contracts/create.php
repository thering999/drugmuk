<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เพิ่มสัญญาใหม่ - Drugmuk</title>
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
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border-left: 4px solid #17a2b8;
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
            <h1>📝 เพิ่มสัญญาใหม่</h1>
        </div>

        <div class="card">
            <div class="alert alert-info">
                💡 <strong>คำแนะนำ:</strong> กรอกข้อมูลสัญญาให้ครบถ้วน ระบบจะแจ้งเตือนอัตโนมัติเมื่อสัญญาใกล้หมดอายุ
            </div>

            <form action="/contracts/store" method="POST">
                <div class="form-group">
                    <label for="contract_no">
                        เลขที่สัญญา
                        <span class="required">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="contract_no" 
                        name="contract_no" 
                        required 
                        placeholder="เช่น CT-2025-001"
                        autocomplete="off"
                    >
                    <small>รูปแบบ: CT-ปี-เลขที่</small>
                </div>

                <div class="form-group">
                    <label for="supplier_id">
                        ผู้ขาย
                        <span class="required">*</span>
                    </label>
                    <select id="supplier_id" name="supplier_id" required>
                        <option value="">-- เลือกผู้ขาย --</option>
                        <?php if (!empty($suppliers)): ?>
                            <?php foreach ($suppliers as $supplier): ?>
                                <option value="<?= $supplier['id'] ?>">
                                    <?= htmlspecialchars($supplier['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="" disabled>ไม่พบข้อมูลผู้ขาย</option>
                        <?php endif; ?>
                    </select>
                    <small>เลือกบริษัทผู้ขายที่ทำสัญญาด้วย</small>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="start_date">
                            วันที่เริ่มสัญญา
                            <span class="required">*</span>
                        </label>
                        <input 
                            type="date" 
                            id="start_date" 
                            name="start_date" 
                            required
                            value="<?= date('Y-m-d') ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="end_date">
                            วันที่สิ้นสุดสัญญา
                            <span class="required">*</span>
                        </label>
                        <input 
                            type="date" 
                            id="end_date" 
                            name="end_date" 
                            required
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label for="total_amount">
                        มูลค่าสัญญา (บาท)
                        <span class="required">*</span>
                    </label>
                    <input 
                        type="number" 
                        id="total_amount" 
                        name="total_amount" 
                        required 
                        min="0" 
                        step="0.01"
                        placeholder="0.00"
                    >
                    <small>ระบุมูลค่ารวมของสัญญาทั้งหมด</small>
                </div>

                <div class="form-group">
                    <label for="description">
                        รายละเอียดเพิ่มเติม
                    </label>
                    <textarea 
                        id="description" 
                        name="description" 
                        placeholder="ระบุรายละเอียดเพิ่มเติมของสัญญา (ถ้ามี)"
                    ></textarea>
                </div>

                <div class="btn-group">
                    <a href="/contracts" class="btn btn-secondary">← ยกเลิก</a>
                    <button type="submit" class="btn btn-primary">✅ บันทึกสัญญา</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Auto-calculate contract duration
        const startDate = document.getElementById('start_date');
        const endDate = document.getElementById('end_date');
        
        startDate.addEventListener('change', function() {
            if (!endDate.value) {
                // Set default end date to 1 year from start
                const start = new Date(this.value);
                start.setFullYear(start.getFullYear() + 1);
                endDate.value = start.toISOString().split('T')[0];
            }
        });
        
        // Validate end date is after start date
        endDate.addEventListener('change', function() {
            if (startDate.value && this.value) {
                if (new Date(this.value) <= new Date(startDate.value)) {
                    alert('วันที่สิ้นสุดต้องมากกว่าวันที่เริ่มสัญญา');
                    this.value = '';
                }
            }
        });
    </script>
</body>
</html>
