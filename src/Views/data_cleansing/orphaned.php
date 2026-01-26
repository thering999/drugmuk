<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการ Orphaned Records - Drugmuk</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); /* Orange/Warning theme */
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
        .btn-danger { background: #ef4444; color: white; }
        .btn-danger:hover { background: #dc2626; transform: translateY(-2px); }
        .btn-secondary { background: #e5e7eb; color: #374151; }
        .btn-secondary:hover { background: #d1d5db; }
        
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f9fafb; color: #4b5563; font-weight: 600; }
        tr:hover { background: #fef2f2; }
        
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
            border-top: 5px solid #d97706; border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        
        /* Checkbox style */
        .custom-checkbox {
            width: 20px; height: 20px; cursor: pointer;
        }
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
                <h1>จัดการ Orphaned Records</h1>
                <p style="color: #666; margin-top: 5px;">
                    ข้อมูลที่ไม่มีการเชื่อมโยง (ขยะตกค้าง) จำนวน <?php echo count($orphanedRecords); ?> รายการ
                </p>
            </div>
            <div class="filter">
                <button class="btn btn-danger" onclick="deleteSelected()" id="delete-btn" style="display: none;">
                    ลบรายการที่เลือก (<span id="selected-count">0</span>)
                </button>
            </div>
        </div>

        <?php if (empty($orphanedRecords)): ?>
            <div class="card" style="text-align: center; py-5;">
                <h3 style="color: #6b7280;">ไม่พบ Orphaned Records</h3>
                <p style="color: #9ca3af; margin-top: 10px;">ระบบสะอาดดีเยี่ยม หรือยังไม่ได้รันการตรวจสอบ</p>
            </div>
        <?php else: ?>
            <div class="card table-container">
                <table>
                    <thead>
                        <tr>
                            <th width="50"><input type="checkbox" class="custom-checkbox" onchange="toggleAll(this)"></th>
                            <th>ID</th>
                            <th>ตาราง</th>
                            <th>รายละเอียดข้อมูล</th>
                            <th>สาเหตุ</th>
                            <th>วันที่พบ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orphanedRecords as $rec): ?>
                            <?php 
                                $data = json_decode($rec['record_data'], true);
                                $displayInfo = '';
                                if (!empty($data)) {
                                    $cnt = 0;
                                    foreach ($data as $k => $v) {
                                        if ($cnt++ > 3) break; // Show max 3 fields
                                        if (!is_array($v)) $displayInfo .= "<b>$k:</b> $v <br>";
                                    }
                                } else {
                                    $displayInfo = 'No data preview';
                                }
                            ?>
                            <tr id="row-<?php echo $rec['id']; ?>">
                                <td>
                                    <input type="checkbox" class="custom-checkbox record-checkbox" 
                                           value="<?php echo $rec['id']; ?>" onchange="updateButton()">
                                </td>
                                <td><?php echo $rec['record_id']; ?></td>
                                <td><span class="badge"><?php echo htmlspecialchars($rec['table_name']); ?></span></td>
                                <td style="font-size: 14px; color: #4b5563;"><?php echo $displayInfo; ?></td>
                                <td style="color: #ef4444;"><?php echo htmlspecialchars($rec['orphaned_reason']); ?></td>
                                <td style="font-size: 13px; color: #6b7280;">
                                    <?php echo date('d/m/Y H:i', strtotime($rec['detected_at'])); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function toggleAll(source) {
            document.querySelectorAll('.record-checkbox').forEach(cb => cb.checked = source.checked);
            updateButton();
        }

        function updateButton() {
            const count = document.querySelectorAll('.record-checkbox:checked').length;
            document.getElementById('selected-count').textContent = count;
            document.getElementById('delete-btn').style.display = count > 0 ? 'inline-block' : 'none';
        }

        async function deleteSelected() {
            const selected = Array.from(document.querySelectorAll('.record-checkbox:checked'))
                                 .map(cb => cb.value);
            
            if (selected.length === 0) return;
            if (!confirm(`ยืนยันการลบข้อมูลถาวรจำนวน ${selected.length} รายการ?`)) return;

            document.getElementById('loading').style.display = 'flex';
            try {
                const formData = new FormData();
                // Send array properly
                selected.forEach(id => formData.append('record_ids[]', id));

                const response = await fetch('/api/data-cleansing/delete-orphaned', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.success) {
                    location.reload();
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
