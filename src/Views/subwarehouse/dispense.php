<!DOCTYPE html>
<html lang="th">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จ่ายยา - <?= htmlspecialchars($subwarehouse['name']) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Sarabun', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh; padding: 20px;
        }
        .container { max-width: 1400px; margin: 0 auto; }
        .back-button {
            display: inline-flex; align-items: center; gap: 0.5rem;
            padding: 0.75rem 1.5rem; background: rgba(255, 255, 255, 0.2);
            color: white; text-decoration: none; border-radius: 10px;
            margin-bottom: 1rem; transition: all 0.3s ease; font-weight: 600;
        }
        .back-button:hover { background: rgba(255, 255, 255, 0.3); transform: translateX(-5px); }
        .header {
            background: rgba(255, 255, 255, 0.95); padding: 20px 30px;
            border-radius: 15px; margin-bottom: 20px; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        .header h1 { color: #667eea; font-size: 28px; margin-bottom: 5px; }
        .section {
            background: rgba(255, 255, 255, 0.95); padding: 25px;
            border-radius: 12px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .section-title { font-size: 20px; font-weight: 600; color: #333; margin-bottom: 15px; }
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; }
        .form-control {
            width: 100%; padding: 12px; border: 2px solid #e5e7eb;
            border-radius: 8px; font-size: 16px; font-family: 'Sarabun', sans-serif;
        }
        .form-control:focus { outline: none; border-color: #667eea; }
        .btn {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 12px 24px; background: #667eea; color: white;
            border: none; border-radius: 8px; font-size: 16px;
            font-weight: 600; cursor: pointer; transition: all 0.3s ease;
        }
        .btn:hover { background: #5568d3; transform: scale(1.05); }
        .btn:disabled { background: #9ca3af; cursor: not-allowed; transform: none; }
        .alert {
            padding: 15px 20px; border-radius: 8px; margin-bottom: 20px;
            display: none;
        }
        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #10b981; }
        .alert-danger { background: #fee2e2; color: #991b1b; border: 1px solid #ef4444; }
        .drug-info {
            background: #f8f9fa; padding: 15px; border-radius: 8px;
            margin-top: 10px; display: none;
        }
        .drug-info-item { margin-bottom: 8px; }
        .drug-info-item strong { color: #667eea; }
    </style>
</head>
<body>
    <div class="container">
        <!-- Back Button -->
        <a href="/subwarehouse/dashboard/<?= $subwarehouse['code'] ?>" class="back-button">
            <span style="font-size: 1.2rem;">←</span>
            <span>กลับคลังย่อย</span>
        </a>

        <!-- Header -->
        <div class="header">
            <h1>💊 จ่ายยาจากคลังย่อย</h1>
            <p>คลัง: <?= htmlspecialchars($subwarehouse['name']) ?></p>
        </div>

        <!-- Alert -->
        <div id="alert" class="alert"></div>

        <!-- Dispense Form -->
        <div class="section">
            <div class="section-title">📝 ฟอร์มจ่ายยา</div>
            
            <form id="dispenseForm" onsubmit="dispense(event)">
                <div class="form-group">
                    <label class="form-label">เลือกยา *</label>
                    <select class="form-control" id="drugSelect" required onchange="showDrugInfo()">
                        <option value="">-- เลือกยา --</option>
                        <?php foreach ($inventory as $item): ?>
                        <option value="<?= $item['drug_id'] ?>" 
                                data-stock="<?= $item['current_stock'] ?>"
                                data-unit="<?= $item['unit'] ?>"
                                data-name="<?= htmlspecialchars($item['drug_name']) ?>">
                            <?= htmlspecialchars($item['drug_name']) ?> (คงเหลือ: <?= number_format($item['current_stock'], 2) ?> <?= $item['unit'] ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <div id="drugInfo" class="drug-info">
                        <div class="drug-info-item"><strong>ชื่อยา:</strong> <span id="infoName">-</span></div>
                        <div class="drug-info-item"><strong>คงเหลือ:</strong> <span id="infoStock">-</span></div>
                        <div class="drug-info-item"><strong>หน่วย:</strong> <span id="infoUnit">-</span></div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">จำนวนที่จ่าย *</label>
                    <input type="number" class="form-control" id="quantity" 
                           step="0.01" min="0.01" required placeholder="0.00">
                </div>

                <div class="form-group">
                    <label class="form-label">ชื่อผู้ป่วย</label>
                    <input type="text" class="form-control" id="patientName" placeholder="ระบุชื่อผู้ป่วย">
                </div>

                <div class="form-group">
                    <label class="form-label">HN / เลขประจำตัว</label>
                    <input type="text" class="form-control" id="patientId" placeholder="HN หรือเลขประจำตัวผู้ป่วย">
                </div>

                <div class="form-group">
                    <label class="form-label">หมายเหตุ</label>
                    <textarea class="form-control" id="notes" rows="3" placeholder="หมายเหตุเพิ่มเติม"></textarea>
                </div>

                <button type="submit" class="btn" id="submitBtn">
                    <span>💊</span>
                    <span>จ่ายยา</span>
                </button>
            </form>
        </div>
    </div>

    <script>
        function showDrugInfo() {
            const select = document.getElementById('drugSelect');
            const option = select.options[select.selectedIndex];
            const info = document.getElementById('drugInfo');
            
            if (option.value) {
                document.getElementById('infoName').textContent = option.dataset.name;
                document.getElementById('infoStock').textContent = parseFloat(option.dataset.stock).toFixed(2);
                document.getElementById('infoUnit').textContent = option.dataset.unit;
                info.style.display = 'block';
            } else {
                info.style.display = 'none';
            }
        }

        async function dispense(e) {
            e.preventDefault();
            
            const drugSelect = document.getElementById('drugSelect');
            const option = drugSelect.options[drugSelect.selectedIndex];
            const quantity = parseFloat(document.getElementById('quantity').value);
            const stock = parseFloat(option.dataset.stock);
            
            // ตรวจสอบจำนวน
            if (quantity > stock) {
                showAlert('danger', '❌ จำนวนที่จ่ายมากกว่ายาคงเหลือ!');
                return;
            }
            
            if (!confirm(`ต้องการจ่ายยา ${option.dataset.name} จำนวน ${quantity} ${option.dataset.unit} ใช่หรือไม่?`)) {
                return;
            }
            
            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.innerHTML = '<span>⏳</span><span>กำลังบันทึก...</span>';
            
            try {
                const res = await fetch('/api/subwarehouse/<?= $subwarehouse['code'] ?>/dispense', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        drug_id: drugSelect.value,
                        quantity: quantity,
                        patient_name: document.getElementById('patientName').value,
                        patient_id: document.getElementById('patientId').value,
                        notes: document.getElementById('notes').value
                    })
                });
                
                const data = await res.json();
                
                if (data.success) {
                    showAlert('success', '✅ จ่ายยาสำเร็จ!');
                    document.getElementById('dispenseForm').reset();
                    document.getElementById('drugInfo').style.display = 'none';
                    setTimeout(() => location.reload(), 2000);
                } else {
                    showAlert('danger', '❌ ' + data.message);
                }
            } catch (e) {
                showAlert('danger', '❌ เกิดข้อผิดพลาด: ' + e.message);
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<span>💊</span><span>จ่ายยา</span>';
            }
        }
        
        function showAlert(type, message) {
            const alert = document.getElementById('alert');
            alert.className = 'alert alert-' + type;
            alert.textContent = message;
            alert.style.display = 'block';
            setTimeout(() => alert.style.display = 'none', 5000);
        }
    </script>
</body>
</html>
