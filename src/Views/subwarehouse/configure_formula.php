<!DOCTYPE html>
<html lang="th">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตั้งค่าสูตรคำนวณ - <?= htmlspecialchars($subwarehouse['name']) ?></title>
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
        .container { max-width: 900px; margin: 0 auto; }
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
        .formula-option {
            border: 2px solid #e5e7eb; border-radius: 12px; padding: 20px;
            margin-bottom: 15px; cursor: pointer; transition: all 0.3s ease;
        }
        .formula-option:hover { border-color: #667eea; background: #f8f9fa; }
        .formula-option.active { border-color: #667eea; background: #ede9fe; }
        .formula-title { font-size: 18px; font-weight: 600; color: #333; margin-bottom: 8px; }
        .formula-desc { color: #666; font-size: 14px; margin-bottom: 10px; }
        .formula-example {
            background: #f8f9fa; padding: 10px; border-radius: 6px;
            font-family: 'Courier New', monospace; font-size: 13px; color: #667eea;
        }
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
        .info-box {
            background: #dbeafe; border-left: 4px solid #3b82f6;
            padding: 15px; border-radius: 8px; margin-top: 20px;
        }
        .info-box-title { font-weight: 600; color: #1e40af; margin-bottom: 8px; }
        .info-box-content { color: #1e40af; font-size: 14px; }
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
            <h1>⚙️ ตั้งค่าสูตรคำนวณการเบิก</h1>
            <p>คลัง: <?= htmlspecialchars($subwarehouse['name']) ?></p>
        </div>

        <!-- Formula Selection -->
        <div class="section">
            <div class="section-title">เลือกสูตรคำนวณ</div>
            
            <div class="formula-option active" onclick="selectFormula('max_min')">
                <div class="formula-title">📊 Max - Min Formula</div>
                <div class="formula-desc">
                    คำนวณจากความแตกต่างระหว่างปริมาณสูงสุด (Max) และปริมาณคงเหลือปัจจุบัน
                </div>
                <div class="formula-example">
                    ปริมาณเบิก = (Max - Current) + Buffer
                </div>
            </div>

            <div class="formula-option" onclick="selectFormula('average_usage')">
                <div class="formula-title">📈 Average Usage Formula</div>
                <div class="formula-desc">
                    คำนวณจากการใช้ยาเฉลี่ยต่อวันในช่วงเวลาที่กำหนด
                </div>
                <div class="formula-example">
                    ปริมาณเบิก = (Average Daily Usage × Period Days) + Buffer
                </div>
            </div>

            <div class="formula-option" onclick="selectFormula('custom')">
                <div class="formula-title">🔧 Custom Formula</div>
                <div class="formula-desc">
                    กำหนดสูตรคำนวณเองตามต้องการ (ฟีเจอร์นี้อยู่ระหว่างการพัฒนา)
                </div>
                <div class="formula-example">
                    ปริมาณเบิก = Custom Calculation
                </div>
            </div>
        </div>

        <!-- Formula Parameters -->
        <div class="section" id="maxMinParams">
            <div class="section-title">ตั้งค่าพารามิเตอร์ - Max-Min Formula</div>
            
            <div class="form-group">
                <label class="form-label">Buffer Percentage (%)</label>
                <input type="number" class="form-control" id="bufferPercentage" 
                       value="10" min="0" max="100" step="1">
                <small style="color: #666;">เพิ่มปริมาณสำรอง (%) เพื่อป้องกันยาหมด</small>
            </div>
        </div>

        <div class="section" id="avgUsageParams" style="display: none;">
            <div class="section-title">ตั้งค่าพารามิเตอร์ - Average Usage Formula</div>
            
            <div class="form-group">
                <label class="form-label">ช่วงเวลาคำนวณ (วัน)</label>
                <input type="number" class="form-control" id="periodDays" 
                       value="30" min="7" max="90" step="1">
                <small style="color: #666;">จำนวนวันย้อนหลังที่ใช้คำนวณค่าเฉลี่ย</small>
            </div>

            <div class="form-group">
                <label class="form-label">Buffer Percentage (%)</label>
                <input type="number" class="form-control" id="avgBufferPercentage" 
                       value="20" min="0" max="100" step="1">
                <small style="color: #666;">เพิ่มปริมาณสำรอง (%) เพื่อป้องกันยาหมด</small>
            </div>
        </div>

        <div class="section" id="customParams" style="display: none;">
            <div class="section-title">ตั้งค่าพารามิเตอร์ - Custom Formula</div>
            <p style="color: #666; text-align: center; padding: 40px;">
                🚧 ฟีเจอร์นี้อยู่ระหว่างการพัฒนา<br>
                กรุณาเลือกสูตรอื่นในขณะนี้
            </p>
        </div>

        <!-- Info Box -->
        <div class="info-box">
            <div class="info-box-title">💡 คำแนะนำ:</div>
            <div class="info-box-content">
                <strong>Max-Min Formula:</strong> เหมาะสำหรับยาที่มีการใช้สม่ำเสมอ<br>
                <strong>Average Usage Formula:</strong> เหมาะสำหรับยาที่มีการใช้ผันแปร<br>
                <strong>Buffer:</strong> ควรตั้งค่า 10-20% เพื่อป้องกันยาหมดกะทันหัน
            </div>
        </div>

        <!-- Save Button -->
        <div class="section" style="text-align: center;">
            <button class="btn" onclick="saveFormula()">
                <span>💾</span>
                <span>บันทึกการตั้งค่า</span>
            </button>
        </div>
    </div>

    <script>
        let selectedFormula = 'max_min';

        function selectFormula(type) {
            selectedFormula = type;
            
            // Update active state
            document.querySelectorAll('.formula-option').forEach(opt => {
                opt.classList.remove('active');
            });
            event.currentTarget.classList.add('active');
            
            // Show/hide parameters
            document.getElementById('maxMinParams').style.display = type === 'max_min' ? 'block' : 'none';
            document.getElementById('avgUsageParams').style.display = type === 'average_usage' ? 'block' : 'none';
            document.getElementById('customParams').style.display = type === 'custom' ? 'block' : 'none';
        }

        function saveFormula() {
            let config = {};
            
            if (selectedFormula === 'max_min') {
                config = {
                    buffer_percentage: parseInt(document.getElementById('bufferPercentage').value)
                };
            } else if (selectedFormula === 'average_usage') {
                config = {
                    period_days: parseInt(document.getElementById('periodDays').value),
                    buffer_percentage: parseInt(document.getElementById('avgBufferPercentage').value)
                };
            } else {
                alert('ฟีเจอร์ Custom Formula อยู่ระหว่างการพัฒนา');
                return;
            }
            
            if (confirm('ต้องการบันทึกการตั้งค่าสูตรคำนวณหรือไม่?')) {
                // Get subwarehouse ID from URL
                const pathParts = window.location.pathname.split('/');
                const subwarehouseId = <?= $subwarehouse['id'] ?>;
                
                // Prepare request data
                const requestData = {
                    subwarehouse_id: subwarehouseId,
                    formula_type: selectedFormula,
                    config: config
                };
                
                // Make AJAX request
                fetch('/api/subwarehouse/formula/save', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(requestData)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('✅ บันทึกการตั้งค่าสำเร็จ!');
                        // Optionally redirect back to dashboard
                        // window.location.href = '/subwarehouse/dashboard/<?= $subwarehouse['code'] ?>';
                    } else {
                        alert('❌ เกิดข้อผิดพลาด: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('❌ เกิดข้อผิดพลาดในการบันทึก');
                });
            }
        }
    </script>
</body>
</html>
