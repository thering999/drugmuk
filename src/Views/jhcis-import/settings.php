<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตั้งค่าการเชื่อมต่อ JHCIS - Drugmuk</title>
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
            max-width: 800px;
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

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }

        input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        input:focus {
            outline: none;
            border-color: #667eea;
        }

        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: all 0.3s;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
        }

        .alert-warning {
            background: #fff3cd;
            color: #856404;
        }

        .help-text {
            font-size: 14px;
            color: #666;
            margin-top: 5px;
        }

        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>🔧 ตั้งค่าการเชื่อมต่อ JHCIS</h1>
            <p class="subtitle">กรุณากรอกข้อมูลการเชื่อมต่อฐานข้อมูล JHCIS</p>

            <div class="alert alert-info">
                ℹ️ <strong>หมายเหตุ:</strong> ข้อมูลเหล่านี้จะถูกบันทึกในไฟล์ .env หรือ environment variables
            </div>

            <div class="alert alert-warning">
                ⚠️ <strong>Connection Refused?</strong> ลองแก้ไขดังนี้:<br>
                1. ตรวจสอบว่า MySQL Server ทำงานอยู่<br>
                2. ถ้ารันใน Docker ให้ใช้ <code>host.docker.internal</code> แทน 127.0.0.1<br>
                3. หรือใช้ IP ของ host machine (เช่น 192.168.x.x)<br>
                4. ตรวจสอบว่า MySQL bind-address อนุญาตให้เชื่อมต่อจากภายนอก
            </div>

            <form id="settingsForm">
                <div class="form-group">
                    <label for="host">Host / IP Address:</label>
                    <input type="text" id="host" name="host" value="host.docker.internal" required>
                    <div class="help-text">
                        <strong>แนะนำ:</strong><br>
                        • Docker: ใช้ <code>host.docker.internal</code><br>
                        • Local: ใช้ <code>127.0.0.1</code><br>
                        • Remote: ใช้ IP address (เช่น 192.168.1.100)
                    </div>
                </div>

                <div class="form-group">
                    <label for="port">Port:</label>
                    <input type="text" id="port" name="port" value="3306" required>
                    <div class="help-text">Port ของ MySQL (ปกติคือ 3306)</div>
                </div>

                <div class="form-group">
                    <label for="database">Database Name:</label>
                    <input type="text" id="database" name="database" value="jhcisdb" required>
                    <div class="help-text">ชื่อฐานข้อมูล JHCIS (ปกติคือ jhcisdb)</div>
                </div>

                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" value="root" required>
                    <div class="help-text">Username สำหรับเข้าถึงฐานข้อมูล</div>
                </div>

                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" value="123456">
                    <div class="help-text">Password สำหรับเข้าถึงฐานข้อมูล (เว้นว่างถ้าไม่มี password)</div>
                </div>

                <div class="button-group">
                    <button type="button" onclick="testConnection()" class="btn btn-primary">
                        🔍 ทดสอบการเชื่อมต่อ
                    </button>
                    <a href="/jhcis-import" class="btn btn-secondary">
                        ← กลับ
                    </a>
                </div>
            </form>

            <div id="result" style="margin-top: 20px;"></div>
        </div>

        <div class="card">
            <h2 style="margin-bottom: 15px;">🔧 วิธีแก้ปัญหา "Connection Refused"</h2>
            
            <h3 style="margin-top: 20px; margin-bottom: 10px;">1. ตรวจสอบ MySQL Server</h3>
            <pre style="background: #f5f5f5; padding: 15px; border-radius: 8px; overflow-x: auto;">
# ตรวจสอบว่า MySQL ทำงานหรือไม่
docker ps | grep mysql
# หรือ
systemctl status mysql
            </pre>

            <h3 style="margin-top: 20px; margin-bottom: 10px;">2. สำหรับ Docker (แนะนำ)</h3>
            <p>ใช้ <code>host.docker.internal</code> เพื่อเชื่อมต่อจาก container ไปยัง host:</p>
            <pre style="background: #f5f5f5; padding: 15px; border-radius: 8px; overflow-x: auto;">
Host: host.docker.internal
Port: 3306
Database: jhcisdb
Username: root
Password: 123456
            </pre>

            <h3 style="margin-top: 20px; margin-bottom: 10px;">3. ตั้งค่า MySQL ให้รับ connection จากภายนอก</h3>
            <p>แก้ไขไฟล์ <code>/etc/mysql/mysql.conf.d/mysqld.cnf</code>:</p>
            <pre style="background: #f5f5f5; padding: 15px; border-radius: 8px; overflow-x: auto;">
# เปลี่ยนจาก
bind-address = 127.0.0.1

# เป็น
bind-address = 0.0.0.0

# จากนั้น restart MySQL
systemctl restart mysql
            </pre>

            <h3 style="margin-top: 20px; margin-bottom: 10px;">4. สร้าง User ที่อนุญาตให้เชื่อมต่อจากภายนอก</h3>
            <pre style="background: #f5f5f5; padding: 15px; border-radius: 8px; overflow-x: auto;">
# เข้า MySQL
mysql -u root -p

# สร้าง user หรือให้สิทธิ์
CREATE USER 'root'@'%' IDENTIFIED BY '123456';
GRANT ALL PRIVILEGES ON jhcisdb.* TO 'root'@'%';
FLUSH PRIVILEGES;
            </pre>

            <h3 style="margin-top: 20px; margin-bottom: 10px;">5. ถ้า JHCIS อยู่ใน Docker container เดียวกัน</h3>
            <p>ใช้ชื่อ service ใน docker-compose.yml:</p>
            <pre style="background: #f5f5f5; padding: 15px; border-radius: 8px; overflow-x: auto;">
# ถ้า docker-compose.yml มี:
services:
  mysql:
    image: mysql:5.7
    ...

# ใช้:
Host: mysql
Port: 3306
            </pre>
        </div>

        <div class="card">
            <h2 style="margin-bottom: 15px;">📝 วิธีตั้งค่า Environment Variables</h2>
            
            <h3 style="margin-top: 20px; margin-bottom: 10px;">สำหรับ Docker:</h3>
            <p>เพิ่มใน <code>docker-compose.yml</code>:</p>
            <pre style="background: #f5f5f5; padding: 15px; border-radius: 8px; overflow-x: auto;">
environment:
  - JHCIS_DB_HOST=host.docker.internal
  - JHCIS_DB_PORT=3306
  - JHCIS_DB_NAME=jhcisdb
  - JHCIS_DB_USER=root
  - JHCIS_DB_PASS=123456
            </pre>

            <h3 style="margin-top: 20px; margin-bottom: 10px;">สำหรับ .env file:</h3>
            <p>สร้างไฟล์ <code>.env</code> ใน root directory:</p>
            <pre style="background: #f5f5f5; padding: 15px; border-radius: 8px; overflow-x: auto;">
JHCIS_DB_HOST=host.docker.internal
JHCIS_DB_PORT=3306
JHCIS_DB_NAME=jhcisdb
JHCIS_DB_USER=root
JHCIS_DB_PASS=123456
            </pre>

            <h3 style="margin-top: 20px; margin-bottom: 10px;">⚠️ หมายเหตุสำคัญ:</h3>
            <ul style="margin-left: 20px;">
                <li>ถ้าไม่มี JHCIS Database จริง ระบบจะไม่สามารถ Import ข้อมูลได้</li>
                <li>สามารถข้ามขั้นตอนนี้และใช้ระบบ Drugmuk ได้ปกติ</li>
                <li>การ Import จาก JHCIS เป็นฟีเจอร์เสริมเท่านั้น</li>
            </ul>
        </div>
    </div>

    <script>
        async function testConnection() {
            const host = document.getElementById('host').value;
            const port = document.getElementById('port').value;
            const database = document.getElementById('database').value;
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;

            const resultDiv = document.getElementById('result');
            resultDiv.innerHTML = '<div class="alert alert-info">⏳ กำลังทดสอบการเชื่อมต่อ...</div>';

            try {
                // Note: This is a simplified test - in production, you'd save these settings first
                const response = await fetch('/jhcis-import/test-connection');
                const data = await response.json();

                if (data.success) {
                    resultDiv.innerHTML = `
                        <div class="alert" style="background: #d4edda; color: #155724;">
                            ✅ <strong>เชื่อมต่อสำเร็จ!</strong><br>
                            พบยาทั้งหมด ${data.total_drugs.toLocaleString()} รายการ<br>
                            <a href="/jhcis-import" class="btn btn-primary" style="margin-top: 10px;">
                                ไปหน้า Import ข้อมูล →
                            </a>
                        </div>
                    `;
                } else {
                    resultDiv.innerHTML = `
                        <div class="alert" style="background: #f8d7da; color: #721c24;">
                            ❌ <strong>เชื่อมต่อไม่สำเร็จ</strong><br>
                            ${data.message}<br><br>
                            <strong>แนะนำ:</strong><br>
                            1. ตรวจสอบว่า MySQL Server ทำงานอยู่<br>
                            2. ตรวจสอบ username และ password<br>
                            3. ตรวจสอบว่าฐานข้อมูล jhcisdb มีอยู่จริง<br>
                            4. ลองใช้ 127.0.0.1 แทน localhost
                        </div>
                    `;
                }
            } catch (error) {
                resultDiv.innerHTML = `
                    <div class="alert" style="background: #f8d7da; color: #721c24;">
                        ❌ <strong>เกิดข้อผิดพลาด:</strong> ${error.message}
                    </div>
                `;
            }
        }

        // Auto-test on load
        window.addEventListener('load', () => {
            setTimeout(testConnection, 500);
        });
    </script>
</body>
</html>
