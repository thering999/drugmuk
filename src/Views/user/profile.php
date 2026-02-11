<?php require_once __DIR__ . '/../layouts/header_responsive.php'; ?>

<style>
    .profile-container {
        max-width: 800px;
        margin: 40px auto;
        padding: 0 20px;
    }

    .profile-card {
        background: white;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        overflow: hidden;
    }

    .profile-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 40px 20px;
        text-align: center;
        color: white;
    }

    .profile-avatar {
        width: 100px;
        height: 100px;
        background: rgba(255,255,255,0.2);
        border-radius: 50%;
        margin: 0 auto 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 40px;
        font-weight: 600;
        border: 4px solid rgba(255,255,255,0.3);
    }

    .profile-name {
        font-size: 24px;
        margin-bottom: 5px;
        font-weight: 600;
    }

    .profile-role {
        background: rgba(255,255,255,0.2);
        padding: 5px 15px;
        border-radius: 20px;
        font-size: 14px;
        display: inline-block;
    }

    .profile-body {
        padding: 40px;
    }

    .form-group {
        margin-bottom: 25px;
    }

    .form-label {
        display: block;
        margin-bottom: 8px;
        color: #4b5563;
        font-weight: 500;
        font-size: 14px;
    }

    .form-control {
        width: 100%;
        padding: 12px 15px;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        font-size: 16px;
        transition: all 0.2s;
        font-family: 'Sarabun', sans-serif;
    }

    .form-control:focus {
        border-color: #667eea;
        outline: none;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .form-control[readonly] {
        background-color: #f8fafc;
        color: #64748b;
    }

    .btn-save {
        width: 100%;
        padding: 14px;
        background: #667eea;
        color: white;
        border: none;
        border-radius: 10px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.2s;
    }

    .btn-save:hover {
        background: #5a67d8;
    }

    .section-title {
        font-size: 18px;
        color: #1f2937;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .session-message {
        padding: 15px 20px;
        border-radius: 10px;
        margin-bottom: 20px;
        text-align: center;
    }
    .session-message.success {
        background: #d4edda;
        color: #155724;
    }
    .session-message.error {
        background: #f8d7da;
        color: #721c24;
    }

    @media (max-width: 640px) {
        .profile-body {
            padding: 20px;
        }
    }
</style>

<div class="profile-container">
    
    <?php if (isset($_SESSION['success'])): ?>
    <div class="session-message success">
        ✅ <?= htmlspecialchars($_SESSION['success']) ?>
    </div>
    <?php unset($_SESSION['success']); endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
    <div class="session-message error">
        ❌ <?= htmlspecialchars($_SESSION['error']) ?>
    </div>
    <?php unset($_SESSION['error']); endif; ?>

    <div class="profile-card">
        <div class="profile-header">
            <div class="profile-avatar">
                <?= strtoupper(substr($user['username'], 0, 1)) ?>
            </div>
            <h1 class="profile-name"><?= htmlspecialchars($user['full_name']) ?></h1>
            <div class="profile-role">
                <i class="fas fa-shield-alt"></i> <?= htmlspecialchars($user['role']) ?>
            </div>
        </div>

        <div class="profile-body">
            <form action="/profile" method="POST">
                <?= \App\Core\CSRF::field() ?>
                
                <h2 class="section-title">
                    <i class="fas fa-user-circle"></i> ข้อมูลส่วนตัว
                </h2>

                <div class="form-group">
                    <label class="form-label">Username</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" readonly>
                </div>

                <div class="form-group">
                    <label class="form-label">ชื่อ-นามสกุล</label>
                    <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($user['full_name']) ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">อีเมล / เบอร์โทรศัพท์ (ถ้ามี)</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '-') ?>" readonly>
                    <small style="color: #999;">* ติดต่อผู้ดูแลระบบเพื่อแก้ไข</small>
                </div>

                <h2 class="section-title" style="margin-top: 40px;">
                    <i class="fas fa-key"></i> เปลี่ยนรหัสผ่าน
                </h2>

                <div class="form-group">
                    <label class="form-label">รหัสผ่านใหม่ (ว่างไว้ถ้าไม่เปลี่ยน)</label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••">
                </div>

                <div class="form-group">
                    <label class="form-label">ยืนยันรหัสผ่านใหม่</label>
                    <input type="password" name="confirm_password" class="form-control" placeholder="••••••••">
                </div>

                <button type="submit" class="btn-save">
                    <i class="fas fa-save"></i> บันทึกการเปลี่ยนแปลง
                </button>

            </form>
        </div>
    </div>
</div>

</main>
</body>
</html>
