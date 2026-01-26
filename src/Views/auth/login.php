<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - Drugmuk</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Sarabun', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            max-width: 900px;
            width: 100%;
            display: grid;
            grid-template-columns: 1fr 1fr;
        }
        .login-left {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 60px 40px;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .login-left h1 {
            font-size: 42px;
            margin-bottom: 20px;
            font-weight: 700;
        }
        .login-left p {
            font-size: 18px;
            line-height: 1.6;
            opacity: 0.9;
        }
        .feature-list {
            margin-top: 30px;
            list-style: none;
        }
        .feature-list li {
            padding: 10px 0;
            display: flex;
            align-items: center;
        }
        .feature-list li:before {
            content: "✓";
            margin-right: 10px;
            font-weight: bold;
            font-size: 20px;
        }
        .login-right {
            padding: 60px 40px;
        }
        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }
        .login-header h2 {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
        }
        .login-header p {
            color: #666;
            font-size: 14px;
        }
        .alert {
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .alert-error {
            background: #fee;
            border: 1px solid #fcc;
            color: #c33;
        }
        .alert-success {
            background: #efe;
            border: 1px solid #cfc;
            color: #3c3;
        }
        .form-group {
            margin-bottom: 25px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }
        .form-group input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s;
        }
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .btn-login {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        .login-footer {
            margin-top: 30px;
            text-align: center;
            color: #999;
            font-size: 13px;
        }
        .demo-credentials {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 13px;
        }
        .demo-credentials h4 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 14px;
        }
        .demo-credentials code {
            background: white;
            padding: 2px 6px;
            border-radius: 3px;
            color: #e83e8c;
            font-family: 'Courier New', monospace;
        }
        @media (max-width: 768px) {
            .login-container {
                grid-template-columns: 1fr;
            }
            .login-left {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-left">
            <h1>💊 Drugmuk</h1>
            <p>ระบบบริหารจัดการคลังเวชภัณฑ์ยาออนไลน์</p>
            <ul class="feature-list">
                <li>แผนซื้อ + ABC/VEN Analysis</li>
                <li>สั่งซื้อ + Decision Support</li>
                <li>คลังใหญ่ + FEFO System</li>
                <li>คลังย่อย + Auto Requisition</li>
                <li>ตัดจ่ายผู้ป่วย</li>
            </ul>
        </div>
        
        <div class="login-right">
            <div class="login-header">
                <h2>เข้าสู่ระบบ</h2>
                <p>กรุณาใช้ Username และ Password ของคุณ</p>
            </div>

            <?php if (isset($error)): ?>
            <div class="alert alert-error">
                ⚠️ <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="/login">
                <?php echo \App\Core\CSRF::field(); ?>
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required autofocus placeholder="กรอก Username">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required placeholder="กรอก Password">
                </div>

                <button type="submit" class="btn-login">
                    🔐 เข้าสู่ระบบ
                </button>
            </form>

            <div class="demo-credentials">
                <h4>🔑 ข้อมูลทดสอบ:</h4>
                <p>Username: <code>admin</code> / Password: <code>123456</code></p>
                <p style="margin-top: 5px; font-size: 12px; color: #999;">
                    หรือ <code>pharmacist</code> / <code>staff</code>
                </p>
            </div>

            <div class="login-footer">
                <p>© 2025 Drugmuk System. All rights reserved.</p>

            </div>
        </div>
    </div>
</body>
</html>
