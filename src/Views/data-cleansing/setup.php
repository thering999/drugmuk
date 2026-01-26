<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Data Cleansing - Drugmuk</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Sarabun', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            max-width: 600px;
            width: 100%;
        }
        h1 {
            font-size: 32px;
            color: #333;
            margin-bottom: 10px;
        }
        p {
            color: #666;
            margin-bottom: 30px;
        }
        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
            margin-bottom: 15px;
            transition: all 0.3s;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .result {
            margin-top: 20px;
            padding: 15px;
            border-radius: 8px;
            display: none;
        }
        .result.success {
            background: #d1fae5;
            color: #065f46;
            border: 2px solid #10b981;
        }
        .result.error {
            background: #fee2e2;
            color: #991b1b;
            border: 2px solid #ef4444;
        }
        .loading {
            display: none;
            text-align: center;
            margin-top: 20px;
        }
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧹 Setup Data Cleansing</h1>
        <p>สร้างตารางและ stored procedures สำหรับระบบทำความสะอาดข้อมูล</p>
        
        <button class="btn btn-primary" onclick="setupDataCleansing()">
            ✨ สร้างตารางและ Procedures
        </button>
        
        <a href="/data-cleansing" class="btn btn-secondary" style="display: inline-block; text-align: center; text-decoration: none;">
            ← กลับหน้า Data Cleansing
        </a>
        
        <div class="loading" id="loading">
            <div class="spinner"></div>
            <p style="margin-top: 10px;">กำลังสร้างตาราง...</p>
        </div>
        
        <div class="result" id="result"></div>
    </div>

    <script>
        async function setupDataCleansing() {
            const loading = document.getElementById('loading');
            const result = document.getElementById('result');
            
            loading.style.display = 'block';
            result.style.display = 'none';
            
            try {
                const response = await fetch('/api/setup-data-cleansing', {
                    method: 'POST'
                });
                
                const data = await response.json();
                
                loading.style.display = 'none';
                result.style.display = 'block';
                
                if (data.success) {
                    result.className = 'result success';
                    result.innerHTML = `
                        <strong>✅ สร้างตารางสำเร็จ!</strong><br><br>
                        📋 ตาราง: ${data.tables_created} ตาราง<br>
                        👁️ Views: ${data.views_created} views<br>
                        ⚙️ Procedures: ${data.procedures_created} procedures<br><br>
                        <a href="/data-cleansing" style="color: #065f46; font-weight: 600;">ไปที่หน้า Data Cleansing →</a>
                    `;
                } else {
                    result.className = 'result error';
                    result.innerHTML = `<strong>❌ เกิดข้อผิดพลาด:</strong><br>${data.message}`;
                }
            } catch (error) {
                loading.style.display = 'none';
                result.style.display = 'block';
                result.className = 'result error';
                result.innerHTML = `<strong>❌ เกิดข้อผิดพลาด:</strong><br>${error.message}`;
            }
        }
    </script>
</body>
</html>
