<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Export รายงาน - Drugmuk</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #667eea;
            --secondary: #764ba2;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
            --dark: #1f2937;
            --light: #f3f4f6;
            --white: #ffffff;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Sarabun', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: white;
            text-decoration: none;
            margin-bottom: 20px;
            padding: 10px 20px;
            background: rgba(255,255,255,0.2);
            border-radius: 8px;
            transition: all 0.3s;
        }

        .back-link:hover {
            background: rgba(255,255,255,0.3);
        }

        .card {
            background: var(--white);
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            margin-bottom: 25px;
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 25px 30px;
        }

        .card-header h1 {
            font-size: 26px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .card-body {
            padding: 30px;
        }

        .export-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }

        .export-card {
            background: var(--light);
            border-radius: 12px;
            padding: 25px;
            transition: all 0.3s;
            border: 2px solid transparent;
        }

        .export-card:hover {
            border-color: var(--primary);
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .export-card h3 {
            font-size: 18px;
            color: var(--dark);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .export-card p {
            color: #666;
            font-size: 14px;
            margin-bottom: 20px;
        }

        .btn-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s;
        }

        .btn-excel {
            background: #217346;
            color: white;
        }

        .btn-excel:hover {
            background: #1a5c38;
        }

        .btn-csv {
            background: var(--info);
            color: white;
        }

        .btn-csv:hover {
            background: #2563eb;
        }

        .btn-pdf {
            background: var(--danger);
            color: white;
        }

        .btn-pdf:hover {
            background: #dc2626;
        }

        .section-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--light);
        }

        /* Custom Export Form */
        .custom-form {
            background: var(--light);
            border-radius: 12px;
            padding: 25px;
            margin-top: 20px;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 0;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 8px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: var(--primary);
            outline: none;
        }

        .icon-box {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 15px;
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-box {
            background: var(--white);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }

        .stat-box .value {
            font-size: 32px;
            font-weight: 700;
            color: var(--primary);
        }

        .stat-box .label {
            font-size: 14px;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="/dashboard" class="back-link">← กลับหน้าหลัก</a>

        <div class="card">
            <div class="card-header">
                <h1>📥 Export รายงาน</h1>
            </div>
            <div class="card-body">
                <h2 class="section-title">📊 รายงานมาตรฐาน</h2>
                
                <div class="export-grid">
                    <!-- Stock Report -->
                    <div class="export-card">
                        <div class="icon-box">📦</div>
                        <h3>รายงานสต็อกยา</h3>
                        <p>รายการยาทั้งหมดในคลัง พร้อมจำนวน, Lot, วันหมดอายุ และมูลค่า</p>
                        <div class="btn-group">
                            <a href="/export/stock" class="btn btn-excel">📄 Excel</a>
                            <a href="/export/pdf?type=stock" class="btn btn-pdf">📑 PDF</a>
                        </div>
                    </div>

                    <!-- Expiring Report -->
                    <div class="export-card">
                        <div class="icon-box" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">⏰</div>
                        <h3>รายงานยาใกล้หมดอายุ</h3>
                        <p>รายการยาที่ใกล้หมดอายุภายใน 90 วัน พร้อมจำนวนและมูลค่า</p>
                        <div class="btn-group">
                            <a href="/export/expiring" class="btn btn-excel">📄 Excel</a>
                            <a href="/export/expiring?days=30" class="btn btn-csv">30 วัน</a>
                            <a href="/export/expiring?days=60" class="btn btn-csv">60 วัน</a>
                        </div>
                    </div>

                    <!-- Low Stock Report -->
                    <div class="export-card">
                        <div class="icon-box" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">📉</div>
                        <h3>รายงานยาใกล้หมดสต็อก</h3>
                        <p>รายการยาที่สต็อกต่ำกว่าจุดสั่งซื้อ พร้อมจำนวนที่ต้องสั่ง</p>
                        <div class="btn-group">
                            <a href="/export/low-stock" class="btn btn-excel">📄 Excel</a>
                            <a href="/export/pdf?type=low_stock" class="btn btn-pdf">📑 PDF</a>
                        </div>
                    </div>

                    <!-- Dispensing Report -->
                    <div class="export-card">
                        <div class="icon-box" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">💊</div>
                        <h3>รายงานการจ่ายยา</h3>
                        <p>ประวัติการจ่ายยาทั้งหมด แยกตามวันที่ ผู้ป่วย และยา</p>
                        <div class="btn-group">
                            <a href="/export/dispensing" class="btn btn-excel">📄 เดือนนี้</a>
                            <a href="/export/dispensing?date_from=<?= date('Y-01-01') ?>&date_to=<?= date('Y-m-d') ?>" class="btn btn-csv">ปีนี้</a>
                        </div>
                    </div>

                    <!-- Order Report -->
                    <div class="export-card">
                        <div class="icon-box" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);">🛒</div>
                        <h3>รายงานใบสั่งซื้อ</h3>
                        <p>รายการใบสั่งซื้อทั้งหมด พร้อมสถานะและมูลค่า</p>
                        <div class="btn-group">
                            <a href="/export/orders" class="btn btn-excel">📄 Excel</a>
                        </div>
                    </div>

                    <!-- ABC/VEN Analysis -->
                    <div class="export-card">
                        <div class="icon-box" style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);">📊</div>
                        <h3>รายงาน ABC/VEN Analysis</h3>
                        <p>การวิเคราะห์มูลค่าและความสำคัญของยา</p>
                        <div class="btn-group">
                            <a href="/export/abc-ven" class="btn btn-excel">📄 Excel</a>
                        </div>
                    </div>
                </div>

                <!-- Custom Export -->
                <h2 class="section-title" style="margin-top: 40px;">🔧 Export แบบกำหนดเอง</h2>
                
                <form action="/export/custom" method="POST" class="custom-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="report_type">ประเภทรายงาน</label>
                            <select id="report_type" name="report_type" required>
                                <option value="">-- เลือก --</option>
                                <option value="stock">สต็อกยา</option>
                                <option value="expiring">ยาใกล้หมดอายุ</option>
                                <option value="low_stock">ยาใกล้หมดสต็อก</option>
                                <option value="dispensing">การจ่ายยา</option>
                                <option value="orders">ใบสั่งซื้อ</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="date_from">ตั้งแต่วันที่</label>
                            <input type="date" id="date_from" name="date_from" value="<?= date('Y-m-01') ?>">
                        </div>
                        <div class="form-group">
                            <label for="date_to">ถึงวันที่</label>
                            <input type="date" id="date_to" name="date_to" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="form-group">
                            <label for="format">รูปแบบไฟล์</label>
                            <select id="format" name="format">
                                <option value="excel">Excel (.xls)</option>
                                <option value="csv">CSV (.csv)</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-excel" style="padding: 15px 30px; font-size: 16px;">
                        📥 Export รายงาน
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
