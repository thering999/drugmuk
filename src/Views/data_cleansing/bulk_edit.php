<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?= \App\Core\CSRF::metaTag() ?>
    <title>แก้ไขข้อมูลแบบกลุ่ม - Drugmuk</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            min-height: 100vh; padding: 20px;
        }
        .container { max-width: 1200px; margin: 0 auto; }
        .header {
            background: white; padding: 25px 30px; border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1); margin-bottom: 25px;
        }
        .header h1 { color: #333; font-size: 26px; }
        .back-link {
            color: white; text-decoration: none; display: inline-block;
            background: rgba(255,255,255,0.2); padding: 8px 16px; border-radius: 8px; margin-bottom: 15px;
        }
        
        .card {
            background: white; padding: 25px; border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1); margin-bottom: 20px;
        }
        .card h2 { color: #333; margin-bottom: 20px; font-size: 20px; }
        
        .tabs {
            display: flex; gap: 10px; margin-bottom: 25px;
        }
        .tab {
            padding: 12px 24px; background: #f3f4f6; border: none; border-radius: 8px;
            cursor: pointer; font-size: 15px; font-weight: 500; transition: all 0.3s;
        }
        .tab.active { background: #f59e0b; color: white; }
        .tab:hover:not(.active) { background: #e5e7eb; }
        
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: 600; color: #333; margin-bottom: 8px; }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%; padding: 12px 15px; border: 1px solid #ddd; border-radius: 8px;
            font-size: 15px; transition: border-color 0.3s;
        }
        .form-group input:focus, .form-group select:focus { border-color: #f59e0b; outline: none; }
        
        .btn {
            padding: 12px 24px; border: none; border-radius: 8px;
            font-weight: 600; cursor: pointer; transition: all 0.3s; font-size: 15px;
            text-decoration: none; display: inline-block;
        }
        .btn-primary { background: #f59e0b; color: white; }
        .btn-primary:hover { background: #d97706; }
        .btn-success { background: #10b981; color: white; }
        .btn-danger { background: #ef4444; color: white; }
        
        .preview-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .preview-table th, .preview-table td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        .preview-table th { background: #f9fafb; font-weight: 600; }
        
        .alert { padding: 15px 20px; border-radius: 10px; margin-bottom: 20px; }
        .alert-info { background: #dbeafe; color: #1e40af; }
        .alert-warning { background: #fef3c7; color: #92400e; }
        .alert-success { background: #d1fae5; color: #065f46; }
        
        .stats-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 25px; }
        .stat-card { background: #f9fafb; padding: 20px; border-radius: 10px; text-align: center; }
        .stat-card .number { font-size: 32px; font-weight: bold; color: #f59e0b; }
        .stat-card .label { color: #666; margin-top: 5px; }
        
        .checkbox-item { display: flex; align-items: center; gap: 10px; padding: 8px 0; }
        .checkbox-item input[type="checkbox"] { width: 18px; height: 18px; }
    </style>
</head>
<body>
    <div class="container">
        <a href="/admin/data-cleansing" class="back-link">← กลับไปหน้า Data Cleansing</a>
        
        <div class="header">
            <h1>⚡ แก้ไขข้อมูลแบบกลุ่ม (Bulk Edit)</h1>
            <p style="color: #666; margin-top: 5px;">แก้ไขข้อมูลหลายรายการพร้อมกัน</p>
        </div>

        <div class="stats-row">
            <div class="stat-card">
                <div class="number"><?php echo $missingPriceCount ?? 0; ?></div>
                <div class="label">ยาไม่มีราคา</div>
            </div>
            <div class="stat-card">
                <div class="number"><?php echo $missingUnitCount ?? 0; ?></div>
                <div class="label">ยาไม่มีหน่วย</div>
            </div>
            <div class="stat-card">
                <div class="number"><?php echo $missingMinStockCount ?? 0; ?></div>
                <div class="label">ยาไม่มีขั้นต่ำ</div>
            </div>
        </div>

        <div class="tabs">
            <button class="tab active" onclick="showTab('price')">💰 กำหนดราคา</button>
            <button class="tab" onclick="showTab('unit')">📦 กำหนดหน่วย</button>
            <button class="tab" onclick="showTab('minstock')">📊 กำหนดขั้นต่ำ</button>
            <button class="tab" onclick="showTab('delete')">🗑️ ลบข้อมูล</button>
        </div>

        <!-- Tab: Set Price -->
        <div id="tab-price" class="tab-content active">
            <div class="card">
                <h2>💰 กำหนดราคายาที่ไม่มีราคา</h2>
                <div class="alert alert-info">
                    ระบบจะดึงราคาล่าสุดจากใบสั่งซื้อมาแนะนำให้อัตโนมัติ
                </div>
                
                <form id="priceForm">
                    <div class="form-group">
                        <label>เลือกยาที่ต้องการกำหนดราคา</label>
                        <div id="price-items" style="max-height: 300px; overflow-y: auto; border: 1px solid #eee; border-radius: 8px; padding: 15px;">
                            <?php foreach ($drugsWithoutPrice ?? [] as $drug): ?>
                            <div class="checkbox-item">
                                <input type="checkbox" name="drug_ids[]" value="<?php echo $drug['id']; ?>" data-suggested="<?php echo $drug['suggested_price'] ?? 0; ?>">
                                <span><?php echo htmlspecialchars($drug['name']); ?></span>
                                <?php if (!empty($drug['suggested_price'])): ?>
                                <span style="color: #10b981; font-size: 13px;">(แนะนำ: ฿<?php echo number_format($drug['suggested_price'], 2); ?>)</span>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                            <?php if (empty($drugsWithoutPrice)): ?>
                            <p style="color: #999;">✓ ทุกรายการมีราคาแล้ว</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>ราคาที่ต้องการกำหนด</label>
                        <input type="number" name="price" step="0.01" min="0" placeholder="ใส่ราคา หรือปล่อยว่างเพื่อใช้ราคาแนะนำ">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">💾 บันทึกราคา</button>
                </form>
            </div>
        </div>

        <!-- Tab: Set Unit -->
        <div id="tab-unit" class="tab-content">
            <div class="card">
                <h2>📦 กำหนดหน่วยนับ</h2>
                
                <form id="unitForm">
                    <div class="form-group">
                        <label>เลือกยาที่ต้องการกำหนดหน่วย</label>
                        <div id="unit-items" style="max-height: 300px; overflow-y: auto; border: 1px solid #eee; border-radius: 8px; padding: 15px;">
                            <?php foreach ($drugsWithoutUnit ?? [] as $drug): ?>
                            <div class="checkbox-item">
                                <input type="checkbox" name="drug_ids[]" value="<?php echo $drug['id']; ?>">
                                <span><?php echo htmlspecialchars($drug['name']); ?></span>
                            </div>
                            <?php endforeach; ?>
                            <?php if (empty($drugsWithoutUnit)): ?>
                            <p style="color: #999;">✓ ทุกรายการมีหน่วยนับแล้ว</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>หน่วยนับ</label>
                        <select name="unit">
                            <option value="">-- เลือกหน่วย --</option>
                            <?php foreach ($commonUnits ?? ['เม็ด', 'แคปซูล', 'ขวด', 'ซอง', 'หลอด', 'กล่อง', 'แผง'] as $unit): ?>
                            <option value="<?php echo $unit; ?>"><?php echo $unit; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">💾 บันทึกหน่วย</button>
                </form>
            </div>
        </div>

        <!-- Tab: Set Min Stock -->
        <div id="tab-minstock" class="tab-content">
            <div class="card">
                <h2>📊 กำหนดสต็อกขั้นต่ำ</h2>
                <div class="alert alert-warning">
                    สต็อกขั้นต่ำใช้สำหรับแจ้งเตือนเมื่อยาใกล้หมด
                </div>
                
                <form id="minstockForm">
                    <div class="form-group">
                        <label>เลือกยาที่ต้องการกำหนด</label>
                        <div style="margin-bottom: 10px;">
                            <button type="button" class="btn btn-success" onclick="selectAll('minstock-items')" style="padding: 8px 16px; font-size: 13px;">เลือกทั้งหมด</button>
                            <button type="button" class="btn" onclick="deselectAll('minstock-items')" style="padding: 8px 16px; font-size: 13px; background: #e5e7eb;">ยกเลิกทั้งหมด</button>
                        </div>
                        <div id="minstock-items" style="max-height: 300px; overflow-y: auto; border: 1px solid #eee; border-radius: 8px; padding: 15px;">
                            <?php foreach ($drugsWithoutMinStock ?? [] as $drug): ?>
                            <div class="checkbox-item">
                                <input type="checkbox" name="drug_ids[]" value="<?php echo $drug['id']; ?>">
                                <span><?php echo htmlspecialchars($drug['name']); ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>จำนวนขั้นต่ำ</label>
                        <input type="number" name="min_stock" min="0" value="10" placeholder="ระบุจำนวน">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">💾 บันทึก</button>
                </form>
            </div>
        </div>

        <!-- Tab: Delete -->
        <div id="tab-delete" class="tab-content">
            <div class="card">
                <h2>🗑️ ลบข้อมูลที่ไม่ใช้งาน</h2>
                <div class="alert alert-warning">
                    ⚠️ การลบข้อมูลไม่สามารถกู้คืนได้ กรุณาตรวจสอบให้แน่ใจก่อนดำเนินการ
                </div>
                
                <form id="deleteForm">
                    <div class="form-group">
                        <label>ประเภทข้อมูลที่ต้องการลบ</label>
                        <select name="delete_type" id="deleteType">
                            <option value="">-- เลือกประเภท --</option>
                            <option value="orphaned">Orphaned Records ที่ถูก Ignore</option>
                            <option value="old_notifications">การแจ้งเตือนเก่ากว่า 30 วัน</option>
                            <option value="old_audit">Audit Trail เก่ากว่า 90 วัน</option>
                        </select>
                    </div>
                    
                    <div id="deletePreview" style="display: none;">
                        <p style="color: #666; margin-bottom: 15px;">จะลบ <span id="deleteCount" style="font-weight: bold; color: #ef4444;">0</span> รายการ</p>
                    </div>
                    
                    <button type="submit" class="btn btn-danger">🗑️ ลบข้อมูล</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function getCSRFToken() {
            return document.querySelector('meta[name="csrf-token"]')?.content || '';
        }

        function showTab(name) {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            document.querySelector(`[onclick="showTab('${name}')"]`).classList.add('active');
            document.getElementById('tab-' + name).classList.add('active');
        }

        function selectAll(containerId) {
            document.querySelectorAll(`#${containerId} input[type="checkbox"]`).forEach(cb => cb.checked = true);
        }

        function deselectAll(containerId) {
            document.querySelectorAll(`#${containerId} input[type="checkbox"]`).forEach(cb => cb.checked = false);
        }

        // Form submissions
        ['price', 'unit', 'minstock'].forEach(type => {
            document.getElementById(type + 'Form')?.addEventListener('submit', async function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                formData.append('type', type);
                
                try {
                    const response = await fetch('/api/data-cleansing/bulk-update', {
                        method: 'POST',
                        headers: { 'X-CSRF-Token': getCSRFToken() },
                        body: formData
                    });
                    const result = await response.json();
                    alert(result.message || (result.success ? 'สำเร็จ' : 'เกิดข้อผิดพลาด'));
                    if (result.success) location.reload();
                } catch (error) {
                    alert('เกิดข้อผิดพลาด');
                }
            });
        });

        document.getElementById('deleteForm')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            if (!confirm('คุณแน่ใจหรือไม่ที่จะลบข้อมูล? การดำเนินการนี้ไม่สามารถกู้คืนได้')) return;
            
            const formData = new FormData(this);
            try {
                const response = await fetch('/api/data-cleansing/bulk-delete', {
                    method: 'POST',
                    headers: { 'X-CSRF-Token': getCSRFToken() },
                    body: formData
                });
                const result = await response.json();
                alert(result.message || (result.success ? 'ลบสำเร็จ' : 'เกิดข้อผิดพลาด'));
                if (result.success) location.reload();
            } catch (error) {
                alert('เกิดข้อผิดพลาด');
            }
        });
    </script>
</body>
</html>
