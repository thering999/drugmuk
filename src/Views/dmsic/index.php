<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?= \App\Core\CSRF::metaTag() ?>
    <title>DMSIC Integration - Drugmuk</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary: #10b981;
            --primary-dark: #059669;
            --secondary: #3b82f6;
            --danger: #ef4444;
            --warning: #f59e0b;
            --success: #10b981;
            --dark: #1f2937;
            --light: #f3f4f6;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Sarabun', sans-serif;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            min-height: 100vh;
            padding: 2rem;
        }

        .container { max-width: 1200px; margin: 0 auto; }

        /* Header */
        .header {
            text-align: center;
            color: white;
            margin-bottom: 2rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            font-family: 'Outfit', sans-serif;
        }

        .header p { font-size: 1.1rem; opacity: 0.9; }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }

        .stat-card:hover { transform: translateY(-4px); }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .stat-label { font-size: 0.9rem; color: #6b7280; margin-bottom: 0.5rem; }
        .stat-value { font-size: 2rem; font-weight: 700; color: var(--dark); font-family: 'Outfit'; }

        /* Main Card */
        .main-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            margin-bottom: 2rem;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--light);
        }

        .card-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Buttons */
        .btn {
            padding: 12px 24px;
            border-radius: 12px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-family: 'Sarabun', sans-serif;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(16, 185, 129, 0.4);
        }

        .btn-secondary {
            background: var(--secondary);
            color: white;
        }

        .btn-secondary:hover { background: #2563eb; }

        .btn-sm {
            padding: 6px 12px;
            font-size: 0.85rem;
        }

        .btn:disabled {
            background: #9ca3af;
            cursor: not-allowed;
            transform: none;
        }

        /* Filter Bar */
        .filter-bar {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        .filter-input {
            flex: 1;
            min-width: 200px;
            padding: 10px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 0.95rem;
            transition: border-color 0.2s;
        }

        .filter-input:focus {
            outline: none;
            border-color: var(--primary);
        }

        /* History Table */
        .history-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 8px;
        }

        .history-table thead th {
            background: var(--light);
            padding: 12px 16px;
            text-align: left;
            font-weight: 600;
            color: var(--dark);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .history-table thead th:first-child { border-radius: 10px 0 0 10px; }
        .history-table thead th:last-child { border-radius: 0 10px 10px 0; }

        .history-table tbody tr {
            background: #f9fafb;
            transition: all 0.2s;
        }

        .history-table tbody tr:hover {
            background: #f3f4f6;
            transform: translateX(4px);
        }

        .history-table tbody td {
            padding: 16px;
            border-top: 1px solid #e5e7eb;
            border-bottom: 1px solid #e5e7eb;
        }

        .history-table tbody td:first-child { border-left: 1px solid #e5e7eb; border-radius: 10px 0 0 10px; }
        .history-table tbody td:last-child { border-right: 1px solid #e5e7eb; border-radius: 0 10px 10px 0; }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .status-exported { background: #fef3c7; color: #92400e; }
        .status-success { background: #d1fae5; color: #065f46; }
        .status-failed { background: #fee2e2; color: #991b1b; }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #9ca3af;
        }

        .empty-state i { font-size: 4rem; margin-bottom: 1rem; opacity: 0.3; }

        /* Export Center */
        .export-center {
            text-align: center;
            padding: 3rem 2rem;
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            border-radius: 16px;
            margin-bottom: 2rem;
        }

        .export-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            display: block;
        }

        .export-center h2 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
            color: var(--dark);
        }

        .export-center p {
            color: #6b7280;
            margin-bottom: 2rem;
        }

        /* Back Link */
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: rgba(255,255,255,0.9);
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 10px;
            background: rgba(255,255,255,0.1);
            transition: all 0.2s;
        }

        .back-link:hover {
            background: rgba(255,255,255,0.2);
            transform: translateX(-4px);
        }

        /* Loading Spinner */
        .spinner {
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .stats-grid { grid-template-columns: 1fr; }
            .filter-bar { flex-direction: column; }
            .card-header { flex-direction: column; align-items: flex-start; gap: 1rem; }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="/dashboard" class="back-link">
            <i class="fa-solid fa-arrow-left"></i> กลับหน้าหลัก
        </a>

        <div class="header">
            <h1>🏥 DMSIC Integration</h1>
            <p>ศูนย์ข้อมูลยาและเวชภัณฑ์ (Ministry of Public Health)</p>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background: #dbeafe; color: #1e40af;">
                    <i class="fa-solid fa-file-export"></i>
                </div>
                <div class="stat-label">ส่งออกทั้งหมด</div>
                <div class="stat-value"><?php echo number_format($stats['total_exports'] ?? 0); ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: #d1fae5; color: #065f46;">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
                <div class="stat-label">สำเร็จ</div>
                <div class="stat-value"><?php echo number_format($stats['successful_exports'] ?? 0); ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: #fee2e2; color: #991b1b;">
                    <i class="fa-solid fa-circle-xmark"></i>
                </div>
                <div class="stat-label">ล้มเหลว</div>
                <div class="stat-value"><?php echo number_format($stats['failed_exports'] ?? 0); ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: #fef3c7; color: #92400e;">
                    <i class="fa-solid fa-database"></i>
                </div>
                <div class="stat-label">รายการทั้งหมด</div>
                <div class="stat-value"><?php echo number_format($stats['total_records'] ?? 0); ?></div>
            </div>
        </div>

        <!-- Export Center -->
        <div class="export-center">
            <span class="export-icon">📡</span>
            <h2>พร้อมส่งข้อมูล</h2>
            <p>ระบบจะรวบรวมข้อมูลคลังยาและอัตราการใช้ เพื่อส่งไปยังส่วนกลาง</p>
            
            <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                <button class="btn btn-primary" onclick="exportData()">
                    <i class="fa-solid fa-paper-plane"></i>
                    <span>ส่งข้อมูลประจำเดือน</span>
                </button>
                <a href="/dmsic/config" class="btn btn-secondary">
                    <i class="fa-solid fa-gear"></i>
                    <span>ตั้งค่า DMSIC</span>
                </a>
            </div>
        </div>

        <!-- History Section -->
        <div class="main-card">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    ประวัติการส่งข้อมูล
                </h2>
            </div>

            <!-- Filter Bar -->
            <div class="filter-bar">
                <input type="text" class="filter-input" id="searchInput" placeholder="🔍 ค้นหาชื่อไฟล์...">
                <select class="filter-input" id="statusFilter" style="flex: 0 0 200px;">
                    <option value="">ทุกสถานะ</option>
                    <option value="exported">Exported</option>
                    <option value="success">Success</option>
                    <option value="failed">Failed</option>
                </select>
            </div>

            <?php if(empty($history)): ?>
                <div class="empty-state">
                    <i class="fa-solid fa-inbox"></i>
                    <p>ยังไม่มีประวัติการส่งข้อมูล</p>
                </div>
            <?php else: ?>
                <table class="history-table" id="historyTable">
                    <thead>
                        <tr>
                            <th>ไฟล์</th>
                            <th>วันที่ส่ง</th>
                            <th>จำนวนรายการ</th>
                            <th>สถานะ</th>
                            <th>การดำเนินการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($history as $log): ?>
                        <tr data-status="<?php echo htmlspecialchars($log['status']); ?>">
                            <td>
                                <strong><?php echo htmlspecialchars($log['file_name']); ?></strong>
                            </td>
                            <td>
                                <small style="color: #6b7280;">
                                    <?php echo date('d/m/Y H:i', strtotime($log['export_date'])); ?>
                                </small>
                            </td>
                            <td>
                                <span style="color: var(--primary); font-weight: 600;">
                                    <?php echo number_format($log['record_count']); ?> รายการ
                                </span>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo $log['status']; ?>">
                                    <?php echo strtoupper($log['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; gap: 8px;">
                                    <a href="/dmsic/download/<?php echo $log['id']; ?>" class="btn btn-sm btn-secondary" title="ดาวน์โหลด">
                                        <i class="fa-solid fa-download"></i>
                                    </a>
                                    <?php if ($log['status'] !== 'success'): ?>
                                        <button class="btn btn-sm btn-primary" onclick="resendData(<?php echo $log['id']; ?>)" title="ส่งใหม่">
                                            <i class="fa-solid fa-rotate-right"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function getCSRFToken() {
            return document.querySelector('meta[name="csrf-token"]').content;
        }

        // Export Data
        async function exportData() {
            const result = await Swal.fire({
                title: 'ยืนยันการส่งข้อมูล',
                text: 'ต้องการส่งข้อมูลประจำเดือนไปยัง DMSIC?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'ส่งข้อมูล',
                cancelButtonText: 'ยกเลิก',
                confirmButtonColor: '#10b981'
            });

            if (!result.isConfirmed) return;

            Swal.fire({
                title: 'กำลังประมวลผล...',
                html: 'กรุณารอสักครู่',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            try {
                const response = await fetch('/dmsic/send', { 
                    method: 'POST',
                    headers: {
                        'X-CSRF-Token': getCSRFToken()
                    }
                });
                const data = await response.json();

                if (data.success) {
                    await Swal.fire({
                        icon: 'success',
                        title: 'สำเร็จ!',
                        html: `${data.message}<br><small>จำนวน: ${data.record_count} รายการ</small>`,
                        confirmButtonColor: '#10b981'
                    });
                    location.reload();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: data.message,
                        confirmButtonColor: '#ef4444'
                    });
                }
            } catch (e) {
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้',
                    confirmButtonColor: '#ef4444'
                });
            }
        }

        // Resend Data
        async function resendData(id) {
            const result = await Swal.fire({
                title: 'ส่งข้อมูลอีกครั้ง?',
                text: 'ต้องการส่งข้อมูลนี้ไปยัง DMSIC อีกครั้ง?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'ส่งใหม่',
                cancelButtonText: 'ยกเลิก',
                confirmButtonColor: '#10b981'
            });

            if (!result.isConfirmed) return;

            Swal.fire({
                title: 'กำลังส่งข้อมูล...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            try {
                const response = await fetch(`/dmsic/api/send/${id}`, { 
                    method: 'POST',
                    headers: {
                        'X-CSRF-Token': getCSRFToken()
                    }
                });
                const data = await response.json();

                if (data.success) {
                    await Swal.fire({
                        icon: 'success',
                        title: 'ส่งข้อมูลสำเร็จ!',
                        text: 'Transaction ID: ' + data.transaction_id,
                        confirmButtonColor: '#10b981'
                    });
                    location.reload();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: data.message,
                        confirmButtonColor: '#ef4444'
                    });
                }
            } catch (e) {
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: e.message,
                    confirmButtonColor: '#ef4444'
                });
            }
        }

        // Search & Filter
        const searchInput = document.getElementById('searchInput');
        const statusFilter = document.getElementById('statusFilter');
        const tableRows = document.querySelectorAll('#historyTable tbody tr');

        function filterTable() {
            const searchTerm = searchInput?.value.toLowerCase() || '';
            const statusValue = statusFilter?.value || '';

            tableRows.forEach(row => {
                const fileName = row.cells[0]?.textContent.toLowerCase() || '';
                const status = row.dataset.status || '';

                const matchesSearch = fileName.includes(searchTerm);
                const matchesStatus = !statusValue || status === statusValue;

                row.style.display = (matchesSearch && matchesStatus) ? '' : 'none';
            });
        }

        searchInput?.addEventListener('input', filterTable);
        statusFilter?.addEventListener('change', filterTable);
    </script>
</body>
</html>
