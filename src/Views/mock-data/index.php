<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สร้างข้อมูลตัวอย่าง - Drugmuk</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Sarabun', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
        }

        .card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            padding: 40px;
            margin-bottom: 20px;
        }

        h1 {
            color: #667eea;
            margin-bottom: 10px;
        }

        .subtitle {
            color: #666;
            margin-bottom: 30px;
        }

        .btn {
            padding: 15px 30px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: all 0.3s;
            margin: 10px 5px;
            display: inline-block;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
        }

        .step {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin: 15px 0;
            border-left: 4px solid #667eea;
        }

        .step h3 {
            color: #667eea;
            margin-bottom: 10px;
        }

        .step p {
            color: #666;
            margin-bottom: 15px;
        }

        .progress {
            width: 100%;
            height: 30px;
            background: #f0f0f0;
            border-radius: 15px;
            overflow: hidden;
            margin: 20px 0;
            display: none;
        }

        .progress-bar {
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transition: width 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>🎲 สร้างข้อมูลตัวอย่างสำหรับทดสอบ</h1>
            <p class="subtitle">ใช้สำหรับทดสอบระบบโดยไม่ต้องมี JHCIS Database</p>

            <div class="alert alert-info">
                ℹ️ <strong>คำแนะนำ:</strong> ทำตามลำดับขั้นตอนด้านล่าง เพื่อสร้างข้อมูลตัวอย่างครบถ้วน
            </div>

            <div id="alertContainer"></div>

            <div class="step">
                <h3>ขั้นตอนที่ 1: สร้างข้อมูลยา</h3>
                <p>สร้างยาตัวอย่าง 10 รายการ (Paracetamol, Amoxicillin, ฯลฯ)</p>
                <button onclick="generateDrugs()" class="btn btn-primary" id="btnDrugs">
                    💊 สร้างข้อมูลยา (10 รายการ)
                </button>
                <div class="progress" id="progressDrugs">
                    <div class="progress-bar" id="progressBarDrugs">0%</div>
                </div>
            </div>

            <div class="step">
                <h3>ขั้นตอนที่ 2: สร้างข้อมูล Inventory</h3>
                <p>สร้างสต็อกยาในคลังหลัก (2 lot ต่อยา)</p>
                <button onclick="generateInventory()" class="btn btn-primary" id="btnInventory">
                    📦 สร้างข้อมูล Inventory
                </button>
                <div class="progress" id="progressInventory">
                    <div class="progress-bar" id="progressBarInventory">0%</div>
                </div>
            </div>

            <div class="step">
                <h3>ขั้นตอนที่ 3: สร้างข้อมูลการจ่ายยา</h3>
                <p>สร้างประวัติการจ่ายยา 10 ครั้ง (สำหรับทดสอบสถิติ)</p>
                <button onclick="generateDispensing()" class="btn btn-primary" id="btnDispensing">
                    💉 สร้างข้อมูลการจ่ายยา (10 ครั้ง)
                </button>
                <div class="progress" id="progressDispensing">
                    <div class="progress-bar" id="progressBarDispensing">0%</div>
                </div>
            </div>

            <div class="step" style="border-left-color: #dc3545;">
                <h3 style="color: #dc3545;">⚠️ ล้างข้อมูลทั้งหมด</h3>
                <p>ลบข้อมูลทั้งหมดและเริ่มใหม่ (ระวัง: ไม่สามารถย้อนกลับได้!)</p>
                <button onclick="clearAll()" class="btn btn-danger" id="btnClear">
                    🗑️ ล้างข้อมูลทั้งหมด
                </button>
            </div>

            <div style="margin-top: 30px; text-align: center;">
                <a href="/dashboard" class="btn btn-secondary">← กลับหน้าหลัก</a>
                <a href="/dispensing/statistics" class="btn btn-success">📊 ดูสถิติ</a>
            </div>
        </div>
    </div>

    <script>
        async function generateDrugs() {
            const btn = document.getElementById('btnDrugs');
            const progress = document.getElementById('progressDrugs');
            const progressBar = document.getElementById('progressBarDrugs');

            btn.disabled = true;
            progress.style.display = 'block';
            progressBar.style.width = '50%';
            progressBar.textContent = 'กำลังสร้าง...';

            try {
                const response = await fetch('/mock-data/generate-drugs', { method: 'POST' });
                const data = await response.json();

                progressBar.style.width = '100%';
                progressBar.textContent = 'เสร็จสิ้น!';

                if (data.success) {
                    showAlert('success', 
                        `✅ สร้างข้อมูลยาสำเร็จ! เพิ่มใหม่ ${data.imported} รายการ, อัพเดท ${data.updated} รายการ`
                    );
                } else {
                    showAlert('error', data.message);
                }

                setTimeout(() => {
                    progress.style.display = 'none';
                    progressBar.style.width = '0%';
                }, 2000);

            } catch (error) {
                showAlert('error', 'เกิดข้อผิดพลาด: ' + error.message);
                progress.style.display = 'none';
            }

            btn.disabled = false;
        }

        async function generateInventory() {
            const btn = document.getElementById('btnInventory');
            const progress = document.getElementById('progressInventory');
            const progressBar = document.getElementById('progressBarInventory');

            btn.disabled = true;
            progress.style.display = 'block';
            progressBar.style.width = '50%';
            progressBar.textContent = 'กำลังสร้าง...';

            try {
                const response = await fetch('/mock-data/generate-inventory', { method: 'POST' });
                const data = await response.json();

                progressBar.style.width = '100%';
                progressBar.textContent = 'เสร็จสิ้น!';

                if (data.success) {
                    showAlert('success', 
                        `✅ สร้างข้อมูล Inventory สำเร็จ! ${data.imported} รายการ`
                    );
                } else {
                    showAlert('error', data.message);
                }

                setTimeout(() => {
                    progress.style.display = 'none';
                    progressBar.style.width = '0%';
                }, 2000);

            } catch (error) {
                showAlert('error', 'เกิดข้อผิดพลาด: ' + error.message);
                progress.style.display = 'none';
            }

            btn.disabled = false;
        }

        async function generateDispensing() {
            const btn = document.getElementById('btnDispensing');
            const progress = document.getElementById('progressDispensing');
            const progressBar = document.getElementById('progressBarDispensing');

            btn.disabled = true;
            progress.style.display = 'block';
            progressBar.style.width = '50%';
            progressBar.textContent = 'กำลังสร้าง...';

            try {
                const response = await fetch('/mock-data/generate-dispensing', { method: 'POST' });
                const data = await response.json();

                progressBar.style.width = '100%';
                progressBar.textContent = 'เสร็จสิ้น!';

                if (data.success) {
                    showAlert('success', 
                        `✅ สร้างข้อมูลการจ่ายยาสำเร็จ! ${data.imported} ครั้ง`
                    );
                } else {
                    showAlert('error', data.message);
                }

                setTimeout(() => {
                    progress.style.display = 'none';
                    progressBar.style.width = '0%';
                }, 2000);

            } catch (error) {
                showAlert('error', 'เกิดข้อผิดพลาด: ' + error.message);
                progress.style.display = 'none';
            }

            btn.disabled = false;
        }

        async function clearAll() {
            if (!confirm('⚠️ ต้องการล้างข้อมูลทั้งหมด? การกระทำนี้ไม่สามารถย้อนกลับได้!')) {
                return;
            }

            const btn = document.getElementById('btnClear');
            btn.disabled = true;

            try {
                const response = await fetch('/mock-data/clear-all', { method: 'POST' });
                const data = await response.json();

                if (data.success) {
                    showAlert('success', '✅ ล้างข้อมูลทั้งหมดสำเร็จ!');
                } else {
                    showAlert('error', data.message);
                }

            } catch (error) {
                showAlert('error', 'เกิดข้อผิดพลาด: ' + error.message);
            }

            btn.disabled = false;
        }

        function showAlert(type, message) {
            const container = document.getElementById('alertContainer');
            const alertClass = type === 'success' ? 'alert-success' : 'alert-error';
            const icon = type === 'success' ? '✅' : '❌';
            
            const alert = document.createElement('div');
            alert.className = `alert ${alertClass}`;
            alert.innerHTML = `${icon} ${message}`;
            
            container.appendChild(alert);
            
            setTimeout(() => {
                alert.remove();
            }, 5000);
        }
    </script>
</body>
</html>
