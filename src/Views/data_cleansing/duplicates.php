<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?= \App\Core\CSRF::metaTag() ?>
    <title>จัดการข้อมูลซ้ำ - Drugmuk</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 1400px; margin: 0 auto; }
        .header {
            background: white; padding: 30px; border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2); margin-bottom: 30px;
            display: flex; justify-content: space-between; align-items: center;
        }
        .header h1 { color: #333; font-size: 28px; }
        .card {
            background: white; padding: 25px; border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1); margin-bottom: 20px;
        }
        .btn {
            padding: 10px 20px; border: none; border-radius: 8px;
            font-weight: 600; cursor: pointer; transition: all 0.3s ease;
            text-decoration: none; display: inline-block;
        }
        .btn-primary { background: #667eea; color: white; }
        .btn-danger { background: #ef4444; color: white; }
        .btn-secondary { background: #e5e7eb; color: #374151; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        
        .duplicate-group {
            border: 1px solid #e5e7eb; border-radius: 10px; margin-bottom: 20px;
            overflow: hidden;
        }
        .duplicate-header {
            background: #f9fafb; padding: 15px; border-bottom: 1px solid #e5e7eb;
            display: flex; justify-content: space-between; align-items: center;
        }
        .duplicate-content { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; padding: 20px; }
        .record-card {
            border: 2px solid transparent; padding: 15px; border-radius: 8px;
            background: #fefefe; transition: all 0.2s;
        }
        .record-card.selected { border-color: #10b981; background: #ecfdf5; }
        .record-card.remove { border-color: #ef4444; background: #fef2f2; opacity: 0.7; }
        
        .field-row {
            display: flex; margin-bottom: 8px; font-size: 14px;
        }
        .field-label { font-weight: 600; width: 120px; color: #6b7280; }
        .field-value { color: #111827; flex: 1; }
        
        .controls {
            margin-top: 15px; text-align: center;
            border-top: 1px solid #eee; padding-top: 15px;
        }
        
        .badge { padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: 600; }
        .badge-score { background: #fee2e2; color: #991b1b; }
        
        .back-link {
            color: white; text-decoration: none; margin-bottom: 15px; display: inline-block;
            background: rgba(255,255,255,0.2); padding: 8px 16px; border-radius: 8px;
        }
        .back-link:hover { background: rgba(255,255,255,0.3); }

        .loading-overlay {
            position: fixed; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.5); display: none;
            justify-content: center; align-items: center; z-index: 1000;
        }
        .spinner {
            width: 50px; height: 50px; border: 5px solid #f3f3f3;
            border-top: 5px solid #667eea; border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div class="loading-overlay" id="loading">
        <div class="spinner"></div>
    </div>

    <div class="container">
        <a href="/admin/data-cleansing" class="back-link">← กลับไปหน้าทำความสะอาดข้อมูล</a>
        
        <div class="header">
            <div>
                <h1>จัดการข้อมูลซ้ำ</h1>
                <p style="color: #666; margin-top: 5px;">
                    พบข้อมูลที่มีโอกาสซ้ำกัน <?php echo count($duplicates); ?> รายการ
                </p>
            </div>
            <div class="filter">
                <select onchange="window.location.search='?table='+this.value" style="padding: 8px; border-radius: 6px;">
                    <option value="">ทุกตาราง</option>
                    <option value="drugs" <?php echo ($tableName ?? '') === 'drugs' ? 'selected' : ''; ?>>ยา (Drugs)</option>
                    <option value="suppliers" <?php echo ($tableName ?? '') === 'suppliers' ? 'selected' : ''; ?>>ผู้จำหน่าย (Suppliers)</option>
                </select>
            </div>
        </div>

        <?php if (empty($duplicates)): ?>
            <div class="card" style="text-align: center; py-5;">
                <h3 style="color: #6b7280;">ไม่พบรายการข้อมูลซ้ำ</h3>
                <p style="color: #9ca3af; margin-top: 10px;">ระบบสะอาดดีเยี่ยม หรือยังไม่ได้รันการตรวจสอบ</p>
                <a href="/admin/data-cleansing" class="btn btn-primary" style="margin-top: 20px;">กลับไปหน้าหลัก</a>
            </div>
        <?php else: ?>
            <?php foreach ($duplicates as $dup): ?>
                <div class="card" id="duplicate-<?php echo $dup['id']; ?>">
                    <div class="duplicate-header">
                        <div>
                            <span class="badge badge-score">ความคล้าย: <?php echo number_format($dup['similarity_score'], 1); ?>%</span>
                            <span style="font-weight: 600; margin-left: 10px; color: #4b5563;">
                                ตาราง: <?php echo htmlspecialchars($dup['table_name']); ?>
                            </span>
                        </div>
                        <button class="btn btn-secondary" onclick="markFalsePositive(<?php echo $dup['id']; ?>)">
                            ไม่ใช่ข้อมูลซ้ำ (False Positive)
                        </button>
                    </div>
                    
                    <div class="duplicate-content">
                        <!-- Record 1 -->
                        <div class="record-card" id="card-<?php echo $dup['id']; ?>-1">
                            <h3>รายการที่ 1 (ID: <?php echo $dup['record1_id']; ?>)</h3>
                            <hr style="margin: 10px 0; border: 0; border-top: 1px solid #eee;">
                            <div class="field-row">
                                <div class="field-label">รหัสยา:</div>
                                <div class="field-value"><?php echo htmlspecialchars($dup['drug1_code'] ?? 'N/A'); ?></div>
                            </div>
                            <div class="field-row">
                                <div class="field-label">ชื่อยา:</div>
                                <div class="field-value"><?php echo htmlspecialchars($dup['drug1_name'] ?? 'N/A'); ?></div>
                            </div>
                            <div class="field-row">
                                <div class="field-label">หน่วย:</div>
                                <div class="field-value"><?php echo htmlspecialchars($dup['drug1_unit'] ?? 'N/A'); ?></div>
                            </div>
                            <div class="field-row">
                                <div class="field-label">ราคา:</div>
                                <div class="field-value"><?php echo number_format($dup['drug1_price'] ?? 0, 2); ?> บาท</div>
                            </div>
                            <div class="controls">
                                <button class="btn btn-primary" onclick="selectKeep(<?php echo $dup['id']; ?>, <?php echo $dup['record1_id']; ?>, <?php echo $dup['record2_id']; ?>, 1)">
                                    เก็บรายการนี้
                                </button>
                            </div>
                        </div>

                        <!-- Record 2 -->
                        <div class="record-card" id="card-<?php echo $dup['id']; ?>-2">
                            <h3>รายการที่ 2 (ID: <?php echo $dup['record2_id']; ?>)</h3>
                            <hr style="margin: 10px 0; border: 0; border-top: 1px solid #eee;">
                            <div class="field-row">
                                <div class="field-label">รหัสยา:</div>
                                <div class="field-value"><?php echo htmlspecialchars($dup['drug2_code'] ?? 'N/A'); ?></div>
                            </div>
                            <div class="field-row">
                                <div class="field-label">ชื่อยา:</div>
                                <div class="field-value"><?php echo htmlspecialchars($dup['drug2_name'] ?? 'N/A'); ?></div>
                            </div>
                            <div class="field-row">
                                <div class="field-label">หน่วย:</div>
                                <div class="field-value"><?php echo htmlspecialchars($dup['drug2_unit'] ?? 'N/A'); ?></div>
                            </div>
                            <div class="field-row">
                                <div class="field-label">ราคา:</div>
                                <div class="field-value"><?php echo number_format($dup['drug2_price'] ?? 0, 2); ?> บาท</div>
                            </div>
                            <div class="controls">
                                <button class="btn btn-primary" onclick="selectKeep(<?php echo $dup['id']; ?>, <?php echo $dup['record2_id']; ?>, <?php echo $dup['record1_id']; ?>, 2)">
                                    เก็บรายการนี้
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
        function getCSRFToken() {
            return document.querySelector('meta[name="csrf-token"]').content;
        }

        function selectKeep(duplicateId, keepId, removeId, side) {
            // Update UI
            document.querySelectorAll(`#duplicate-${duplicateId} .record-card`).forEach(el => {
                el.classList.remove('selected');
                el.classList.add('remove');
            });
            const selectedCard = document.getElementById(`card-${duplicateId}-${side}`);
            selectedCard.classList.remove('remove');
            selectedCard.classList.add('selected');

            if (confirm('คุณแน่ใจหรือไม่ที่จะรวมข้อมูล? รายการที่ไม่ได้เลือกจะถูกลบถาวร')) {
                mergeDuplicates(duplicateId, keepId, removeId);
            } else {
                // Reset UI
                document.querySelectorAll(`#duplicate-${duplicateId} .record-card`).forEach(el => {
                    el.classList.remove('selected', 'remove');
                });
            }
        }

        async function mergeDuplicates(duplicateId, keepId, removeId) {
            document.getElementById('loading').style.display = 'flex';
            try {
                const formData = new FormData();
                formData.append('duplicate_id', duplicateId);
                formData.append('keep_id', keepId);
                formData.append('remove_id', removeId);

                const response = await fetch('/api/data-cleansing/merge-duplicates', {
                    method: 'POST',
                    headers: { 'X-CSRF-Token': getCSRFToken() },
                    body: formData
                });
                const data = await response.json();

                if (data.success) {
                    document.getElementById(`duplicate-${duplicateId}`).remove();
                    // Check if empty
                    if (document.querySelectorAll('.card').length === 0) {
                        location.reload();
                    }
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (error) {
                alert('เกิดข้อผิดพลาดในการเชื่อมต่อ');
            } finally {
                document.getElementById('loading').style.display = 'none';
            }
        }

        async function markFalsePositive(duplicateId) {
            if (!confirm('ยืนยันว่าข้อมูลนี้ไม่ใช่ข้อมูลซ้ำ?')) return;
            
            document.getElementById('loading').style.display = 'flex';
            try {
                const formData = new FormData();
                formData.append('duplicate_id', duplicateId);

                const response = await fetch('/api/data-cleansing/mark-false-positive', {
                    method: 'POST',
                    headers: { 'X-CSRF-Token': getCSRFToken() },
                    body: formData
                });
                const data = await response.json();

                if (data.success) {
                    document.getElementById(`duplicate-${duplicateId}`).remove();
                    if (document.querySelectorAll('.card').length === 0) location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (error) {
                alert('เกิดข้อผิดพลาดในการเชื่อมต่อ');
            } finally {
                document.getElementById('loading').style.display = 'none';
            }
        }
    </script>
</body>
</html>
