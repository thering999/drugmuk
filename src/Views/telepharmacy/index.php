<?php include dirname(__DIR__) . '/layouts/header.php'; ?>

<style>
    :root {
        --tele-primary: #6366f1;
        --tele-success: #10b981;
        --tele-warning: #f59e0b;
        --tele-danger: #ef4444;
    }

    .tele-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 40px;
        border-radius: 20px;
        margin-bottom: 30px;
        box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
    }

    .tele-header h1 {
        font-size: 36px;
        font-weight: 700;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .tele-header p {
        opacity: 0.9;
        font-size: 16px;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: white;
        padding: 25px;
        border-radius: 16px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
        transition: all 0.3s;
        border-left: 4px solid;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    }

    .stat-card.primary { border-color: var(--tele-primary); }
    .stat-card.success { border-color: var(--tele-success); }
    .stat-card.warning { border-color: var(--tele-warning); }
    .stat-card.danger { border-color: var(--tele-danger); }

    .stat-card .icon {
        font-size: 40px;
        margin-bottom: 15px;
        opacity: 0.8;
    }

    .stat-card.primary .icon { color: var(--tele-primary); }
    .stat-card.success .icon { color: var(--tele-success); }
    .stat-card.warning .icon { color: var(--tele-warning); }
    .stat-card.danger .icon { color: var(--tele-danger); }

    .stat-card h3 {
        font-size: 32px;
        font-weight: 700;
        margin-bottom: 5px;
    }

    .stat-card p {
        color: #64748b;
        font-size: 14px;
        margin: 0;
    }

    .patients-section {
        background: white;
        padding: 30px;
        border-radius: 16px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
    }

    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        padding-bottom: 15px;
        border-bottom: 2px solid #f1f5f9;
    }

    .section-header h2 {
        font-size: 24px;
        font-weight: 700;
        color: #1e293b;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .patient-card {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 20px;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        margin-bottom: 15px;
        transition: all 0.3s;
        background: linear-gradient(to right, #ffffff 0%, #f8fafc 100%);
    }

    .patient-card:hover {
        border-color: var(--tele-primary);
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.15);
        transform: translateX(5px);
    }

    .patient-info {
        display: flex;
        align-items: center;
        gap: 20px;
        flex: 1;
    }

    .patient-avatar {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 24px;
        font-weight: 700;
        flex-shrink: 0;
    }

    .patient-details h4 {
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 5px;
        color: #1e293b;
    }

    .patient-meta {
        display: flex;
        gap: 15px;
        font-size: 13px;
        color: #64748b;
    }

    .patient-meta span {
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .patient-actions {
        display: flex;
        gap: 10px;
    }

    .btn-consult {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 12px 24px;
        border-radius: 10px;
        border: none;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
    }

    .btn-consult:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        color: white;
    }

    .btn-profile {
        background: white;
        color: #6366f1;
        padding: 12px 20px;
        border-radius: 10px;
        border: 2px solid #6366f1;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .btn-profile:hover {
        background: #6366f1;
        color: white;
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #94a3b8;
    }

    .empty-state i {
        font-size: 80px;
        margin-bottom: 20px;
        opacity: 0.3;
    }

    .empty-state h3 {
        font-size: 20px;
        margin-bottom: 10px;
    }

    .badge-chronic {
        background: #fef3c7;
        color: #92400e;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 700;
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .patient-card {
        animation: fadeInUp 0.5s ease-out forwards;
    }

    .patient-card:nth-child(1) { animation-delay: 0.1s; }
    .patient-card:nth-child(2) { animation-delay: 0.2s; }
    .patient-card:nth-child(3) { animation-delay: 0.3s; }
    .patient-card:nth-child(4) { animation-delay: 0.4s; }
    .patient-card:nth-child(5) { animation-delay: 0.5s; }
</style>

<div class="tele-header">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 20px;">
        <div>
            <h1>
                <i class="fas fa-video"></i>
                Tele-pharmacy Dashboard
            </h1>
            <p>ระบบให้คำปรึกษาทางเภสัชกรรมผ่านวิดีโอคอล - เชื่อมต่อกับผู้ป่วยได้ทุกที่ทุกเวลา</p>
        </div>
        <a href="/dashboard" class="btn-consult" style="background: rgba(255, 255, 255, 0.2); backdrop-filter: blur(10px); color: white; border: 1px solid rgba(255, 255, 255, 0.3);">
            <i class="fas fa-home"></i>
            กลับหน้าหลัก
        </a>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card primary">
        <div class="icon">
            <i class="fas fa-users"></i>
        </div>
        <h3><?= count($patients) ?></h3>
        <p>ผู้ป่วยที่พร้อมให้คำปรึกษา</p>
    </div>

    <div class="stat-card success">
        <div class="icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <h3>0</h3>
        <p>การปรึกษาวันนี้</p>
    </div>

    <div class="stat-card warning">
        <div class="icon">
            <i class="fas fa-clock"></i>
        </div>
        <h3>0</h3>
        <p>กำลังรอคิว</p>
    </div>

    <div class="stat-card danger">
        <div class="icon">
            <i class="fas fa-calendar-check"></i>
        </div>
        <h3>0</h3>
        <p>นัดหมายวันนี้</p>
    </div>
</div>

<div class="patients-section">
    <div class="section-header">
        <h2>
            <i class="fas fa-hospital-user"></i>
            ผู้ป่วยล่าสุด
        </h2>
        <a href="/chronic/dashboard" class="btn btn-outline-primary">
            <i class="fas fa-list"></i>
            ดูทั้งหมด
        </a>
    </div>

    <?php if (empty($patients)): ?>
        <div class="empty-state">
            <i class="fas fa-user-slash"></i>
            <h3>ยังไม่มีผู้ป่วยในระบบ</h3>
            <p>เมื่อมีผู้ป่วยเข้ารับการรักษา จะแสดงรายชื่อที่นี่</p>
        </div>
    <?php else: ?>
        <?php foreach ($patients as $patient): ?>
            <div class="patient-card">
                <div class="patient-info">
                    <div class="patient-avatar">
                        <?= strtoupper(substr($patient['first_name'] ?? 'P', 0, 1)) ?>
                    </div>
                    <div class="patient-details">
                        <h4><?= htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']) ?></h4>
                        <div class="patient-meta">
                            <span>
                                <i class="fas fa-id-card"></i>
                                HN: <?= htmlspecialchars($patient['hn']) ?>
                            </span>
                            <span>
                                <i class="fas fa-birthday-cake"></i>
                                อายุ <?= isset($patient['birth_date']) ? date_diff(date_create($patient['birth_date']), date_create('today'))->y : 'N/A' ?> ปี
                            </span>
                            <?php if (!empty($patient['chronic_diseases'])): ?>
                                <span class="badge-chronic">
                                    <i class="fas fa-heartbeat"></i>
                                    โรคเรื้อรัง
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="patient-actions">
                    <a href="/patient/<?= htmlspecialchars($patient['hn']) ?>" class="btn-profile">
                        <i class="fas fa-user"></i>
                        ประวัติ
                    </a>
                    <a href="/tele-pharmacy/room/<?= htmlspecialchars($patient['hn']) ?>" class="btn-consult">
                        <i class="fas fa-video"></i>
                        เริ่มให้คำปรึกษา
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
    // Auto-refresh patient list every 30 seconds
    setInterval(() => {
        // In production, you'd fetch updated patient list via AJAX
        console.log('Checking for new patients...');
    }, 30000);
</script>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
