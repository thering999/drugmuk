<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตัดจ่ายผู้ป่วย - Drugmuk</title>
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
        .header p { color: #666; }
        .form-container {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; color: #333; }
        .form-group input, .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .items-section {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #eee;
        }
        .item-row {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 80px;
            gap: 10px;
            margin-bottom: 10px;
            align-items: end;
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
        .btn:hover { transform: translateY(-2px); }
        .btn-secondary { background: #6c757d; }
        .btn-small { padding: 8px 12px; font-size: 14px; }
        .btn-danger { background: #dc3545; }
        .btn-success { background: #28a745; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>💊 ตัดจ่ายยาให้ผู้ป่วย</h1>
            <p>บันทึกการจ่ายยาให้ผู้ป่วยจากคลังย่อย</p>
        </div>

        <div class="form-container">
            <form method="POST" action="/subwarehouse/store-dispense" id="dispenseForm">
                <h3 style="margin-bottom: 20px;">ข้อมูลผู้ป่วย</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>HN *</label>
                        <input type="text" name="hn" required placeholder="เลขประจำตัวผู้ป่วย">
                    </div>
                    <div class="form-group">
                        <label>VN</label>
                        <input type="text" name="vn" placeholder="เลขที่มารับบริการ">
                    </div>
                    <div class="form-group">
                        <label>ชื่อผู้ป่วย</label>
                        <input type="text" name="patient_name" placeholder="ชื่อ-นามสกุล">
                    </div>
                    <div class="form-group">
                        <label>วันที่จ่าย *</label>
                        <input type="date" name="dispense_date" value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>คลังย่อย *</label>
                    <select name="warehouse_code" required>
                        <option value="">-- เลือกคลัง --</option>
                        <option value="sub1">คลังย่อย 1</option>
                        <option value="sub2">คลังย่อย 2</option>
                        <option value="sub3">คลังย่อย 3</option>
                    </select>
                </div>

                <div class="items-section">
                    <h3 style="margin-bottom: 15px;">รายการยาที่จ่าย</h3>
                    
                    <div id="itemsContainer">
                        <div class="item-row">
                            <div class="form-group">
                                <label>รหัสยา / ชื่อยา *</label>
                                <input type="text" name="items[0][drug_code]" required placeholder="ค้นหายา...">
                                <input type="hidden" name="items[0][drug_id]">
                            </div>
                            <div class="form-group">
                                <label>จำนวน *</label>
                                <input type="number" name="items[0][quantity]" required min="1" placeholder="0">
                            </div>
                            <div class="form-group">
                                <label>หน่วย</label>
                                <input type="text" name="items[0][unit]" readonly placeholder="หน่วย">
                            </div>
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <button type="button" class="btn btn-danger btn-small" onclick="removeItem(this)">ลบ</button>
                            </div>
                        </div>
                    </div>

                    <button type="button" class="btn btn-success btn-small" onclick="addItem()">+ เพิ่มรายการ</button>
                </div>

                <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
                    <button type="submit" class="btn">💾 บันทึกการจ่ายยา</button>
                    <a href="/subwarehouse" class="btn btn-secondary">← กลับ</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        let itemCount = 1;

        function addItem() {
            const container = document.getElementById('itemsContainer');
            const newRow = document.createElement('div');
            newRow.className = 'item-row';
            newRow.innerHTML = `
                <div class="form-group">
                    <label>รหัสยา / ชื่อยา *</label>
                    <input type="text" name="items[${itemCount}][drug_code]" required placeholder="ค้นหายา...">
                    <input type="hidden" name="items[${itemCount}][drug_id]">
                </div>
                <div class="form-group">
                    <label>จำนวน *</label>
                    <input type="number" name="items[${itemCount}][quantity]" required min="1" placeholder="0">
                </div>
                <div class="form-group">
                    <label>หน่วย</label>
                    <input type="text" name="items[${itemCount}][unit]" readonly placeholder="หน่วย">
                </div>
                <div class="form-group">
                    <label>&nbsp;</label>
                    <button type="button" class="btn btn-danger btn-small" onclick="removeItem(this)">ลบ</button>
                </div>
            `;
            container.appendChild(newRow);
            itemCount++;
        }

        function removeItem(btn) {
            if (document.querySelectorAll('.item-row').length > 1) {
                btn.closest('.item-row').remove();
            } else {
                alert('ต้องมีอย่างน้อย 1 รายการ');
            }
        }
    </script>
</body>
</html>
