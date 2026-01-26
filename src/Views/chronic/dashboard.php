<?php include dirname(__DIR__) . '/layouts/header.php'; ?>

<div class="chronic-management-container">
    <div class="page-header">
        <h1><i class="fas fa-stethoscope"></i> ระบบติดตามผู้ป่วยโรคเรื้อรัง (Chronic Care)</h1>
        <div class="header-stats">
            <div class="stat-box glass-effect">
                <span class="stat-label">ผู้ป่วยทั้งหมด</span>
                <span class="stat-value" id="total-patients">-</span>
            </div>
            <div class="stat-box glass-effect warning">
                <span class="stat-label">ครบกำหนดรับยา</span>
                <span class="stat-value" id="due-refills">-</span>
            </div>
            <div class="stat-box glass-effect danger">
                <span class="stat-label">ค้างรับยา (Overdue)</span>
                <span class="stat-value" id="overdue-count">-</span>
            </div>
        </div>
    </div>

    <div class="tabs-container">
        <div class="tabs-header">
            <button class="tab-btn active" data-tab="all">ผู้ป่วยทั้งหมด</button>
            <button class="tab-btn" data-tab="due">ครบกำหนด (7 วัน)</button>
            <button class="tab-btn" data-tab="overdue">ค้างรับยา</button>
        </div>

        <div class="tab-content" id="tab-all">
            <div class="filter-bar glass-effect">
                <input type="text" id="patient-filter" placeholder="ค้นหา HN / ชื่อ...">
                <select id="disease-filter">
                    <option value="">ทุกโรค</option>
                </select>
            </div>
            <div id="patients-list" class="patients-table-wrapper">
                <table class="table">
                    <thead>
                        <tr>
                            <th>HN</th>
                            <th>ชื่อ-นามสกุล</th>
                            <th>โรคประจำตัว</th>
                            <th>วันที่รับยาล่าสุด</th>
                            <th>สถานะ</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody id="patients-tbody">
                        <!-- Content -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Reminders -->
<div id="reminder-modal" class="modal">
    <div class="modal-content glass-effect">
        <div class="modal-header">
            <h2>ส่งการแจ้งเตือนเคือนรับยา</h2>
            <span class="close-modal">&times;</span>
        </div>
        <div class="modal-body">
            <div class="reminder-preview">
                <p><strong>ผู้ป่วย:</strong> <span id="remind-name"></span></p>
                <p><strong>รายการยา:</strong> <span id="remind-drug"></span></p>
                <p><strong>กำหนดรับยา:</strong> <span id="remind-date"></span></p>
            </div>
            <div class="form-group">
                <label>ช่องทางแจ้งเตือน</label>
                <select id="remind-channel" class="form-control">
                    <option value="sms">SMS</option>
                    <option value="line">LINE Official</option>
                </select>
            </div>
            <button id="confirm-remind" class="btn btn-primary btn-block">ยืนยันส่งการแจ้งเตือน</button>
        </div>
    </div>
</div>

<style>
.chronic-management-container {
    padding: 20px 0;
}

.page-header {
    margin-bottom: 30px;
}

.header-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin-top: 20px;
}

.stat-box {
    padding: 20px;
    border-radius: 20px;
    text-align: center;
}

.stat-label { display: block; font-size: 14px; color: #718096; margin-bottom: 5px; }
.stat-value { font-size: 32px; font-weight: bold; color: #2d3748; }

.stat-box.warning .stat-value { color: #d69e2e; }
.stat-box.danger .stat-value { color: #e53e3e; }

/* Tabs */
.tabs-header {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    border-bottom: 2px solid #edf2f7;
    padding-bottom: 5px;
}

.tab-btn {
    padding: 10px 25px;
    border: none;
    background: none;
    font-weight: 600;
    color: #a0aec0;
    cursor: pointer;
    transition: all 0.3s;
}

.tab-btn.active {
    color: #4299e1;
    border-bottom: 3px solid #4299e1;
}

.filter-bar {
    display: flex;
    gap: 15px;
    padding: 15px;
    margin-bottom: 20px;
}

.filter-bar input, .filter-bar select {
    padding: 10px 15px;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
}

.patients-table-wrapper {
    background: white;
    border-radius: 20px;
    padding: 20px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
}

.badge-status {
    padding: 4px 10px;
    border-radius: 10px;
    font-size: 11px;
    font-weight: bold;
}

.status-on-time { background: #c6f6d5; color: #22543d; }
.status-due { background: #feebc8; color: #744210; }
.status-overdue { background: #fed7d7; color: #822727; }

/* Modal */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
}

.modal-content {
    position: relative;
    margin: 10% auto;
    width: 400px;
    padding: 30px;
}

.modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.close-modal { cursor: pointer; font-size: 24px; }
.reminder-preview { background: #f7fafc; padding: 15px; border-radius: 12px; margin-bottom: 20px; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    loadStats();
    loadPatients();

    // Tab switching
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            const tab = this.getAttribute('data-tab');
            if (tab === 'due') loadDueRefills();
            else if (tab === 'overdue') loadOverdue();
            else loadPatients();
        });
    });

    function loadStats() {
        fetch('/api/chronic/statistics')
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('total-patients').textContent = data.statistics.total_patients;
                    document.getElementById('due-refills').textContent = data.statistics.due_for_refill;
                    document.getElementById('overdue-count').textContent = data.statistics.overdue;
                }
            });
    }

    function loadPatients() {
        fetch('/api/chronic/patients')
            .then(res => res.json())
            .then(data => {
                if (data.success) renderTable(data.patients);
            });
    }

    function loadDueRefills() {
        fetch('/api/chronic/due-refills')
            .then(res => res.json())
            .then(data => {
                if (data.success) renderRefillTable(data.patients);
            });
    }

    function loadOverdue() {
        fetch('/api/chronic/overdue')
            .then(res => res.json())
            .then(data => {
                if (data.success) renderRefillTable(data.patients, true);
            });
    }

    function renderTable(patients) {
        const tbody = document.getElementById('patients-tbody');
        tbody.innerHTML = patients.map(p => `
            <tr>
                <td><strong>${p.hn}</strong></td>
                <td>${p.full_name}</td>
                <td><small>${p.diseases}</small></td>
                <td>${p.last_visit_date || '-'}</td>
                <td><span class="badge-status status-on-time">ปกติ</span></td>
                <td>
                    <a href="/patient/${p.hn}" class="btn btn-sm btn-outline">ดูข้อมูล</a>
                </td>
            </tr>
        `).join('');
    }

    function renderRefillTable(patients, isOverdue = false) {
        const tbody = document.getElementById('patients-tbody');
        tbody.innerHTML = patients.map(p => `
            <tr>
                <td><strong>${p.hn}</strong></td>
                <td>${p.full_name}</td>
                <td><small>${p.drug_name}</small></td>
                <td>${p.next_refill_date}</td>
                <td>
                    <span class="badge-status ${isOverdue ? 'status-overdue' : 'status-due'}">
                        ${isOverdue ? 'ค้างรับยา ' + p.days_overdue + ' วัน' : 'อีก ' + p.days_until_refill + ' วัน'}
                    </span>
                </td>
                <td>
                    <button class="btn btn-sm btn-warning remind-btn" 
                        data-hn="${p.hn}" 
                        data-name="${p.full_name}"
                        data-drug="${p.drug_name}"
                        data-date="${p.next_refill_date}">
                        แจ้งเตือน
                    </button>
                    <a href="/patient/${p.hn}" class="btn btn-sm btn-outline">ประวัติ</a>
                </td>
            </tr>
        `).join('');
    }

    // Modal Handling
    const modal = document.getElementById('reminder-modal');
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remind-btn')) {
            const btn = e.target;
            document.getElementById('remind-name').textContent = btn.dataset.name;
            document.getElementById('remind-drug').textContent = btn.dataset.drug;
            document.getElementById('remind-date').textContent = btn.dataset.date;
            window.activeRemindData = {
                hn: btn.dataset.hn,
                drug_name: btn.dataset.drug,
                next_refill_date: btn.dataset.date
            };
            modal.style.display = 'block';
        }
        if (e.target.classList.contains('close-modal')) modal.style.display = 'none';
    });

    document.getElementById('confirm-remind').addEventListener('click', function() {
        const channel = document.getElementById('remind-channel').value;
        const payload = { ...window.activeRemindData, channel };
        
        fetch('/api/chronic/send-reminder', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('ส่งการแจ้งเตือนสำเร็จ');
                modal.style.display = 'none';
                loadStats();
                loadDueRefills();
            } else {
                alert('เกิดข้อผิดพลาด: ' + data.message);
            }
        });
    });
});
</script>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
