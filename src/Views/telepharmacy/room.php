<?php include dirname(__DIR__) . '/layouts/header.php'; ?>

<div class="telepharmacy-container">
    <div class="tele-sidebar glass-effect">
        <div class="patient-brief">
            <div class="avatar-large">
                <i class="fas fa-user-circle"></i>
            </div>
            <h3><?= $patient ? htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']) : 'General Consultation' ?></h3>
            <span class="badge bg-primary">HN: <?= $patient['hn'] ?? 'N/A' ?></span>
            
            <div class="clinical-summary mt-4">
                <div class="summary-item">
                    <label>Allergies:</label>
                    <span class="text-danger">None Reported</span>
                </div>
                <div class="summary-item">
                    <label>Status:</label>
                    <span class="text-success"><i class="fas fa-circle"></i> In Session</span>
                </div>
            </div>
        </div>

        <div class="consultation-notes-section">
            <h4><i class="fas fa-edit"></i> Clinical Notes</h4>
            <textarea id="session-notes" class="form-control" placeholder="บันทึกคำแนะนำทางเภสัชกรรม..."></textarea>
            <button id="save-notes" class="btn btn-success w-100 mt-3">
                <i class="fas fa-save"></i> บันทึกข้อมูล
            </button>
        </div>

        <div class="quick-tools mt-4">
            <h4><i class="fas fa-bolt"></i> Quick Links</h4>
            <a href="/patient/<?= $patient['hn'] ?? '' ?>" class="btn btn-outline-info w-100 mb-2" target="_blank">
                <i class="fas fa-id-card"></i> View Patient Profile
            </a>
            <a href="/dispensing?hn=<?= $patient['hn'] ?? '' ?>" class="btn btn-outline-primary w-100" target="_blank">
                <i class="fas fa-pills"></i> Quick Dispensing
            </a>
        </div>
    </div>

    <div class="video-main">
        <div id="jitsi-container" class="glass-effect">
            <div class="loading-overlay">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2">Initializing Secure Connection...</p>
            </div>
        </div>
    </div>
</div>

<style>
.telepharmacy-container {
    display: flex;
    height: calc(100vh - 100px);
    gap: 20px;
    padding: 10px 0;
}

.tele-sidebar {
    width: 320px;
    padding: 25px;
    display: flex;
    flex-direction: column;
    overflow-y: auto;
}

.avatar-large {
    font-size: 80px;
    color: #cbd5e0;
    margin-bottom: 15px;
}

.patient-brief {
    text-align: center;
    border-bottom: 1px solid #edf2f7;
    padding-bottom: 25px;
    margin-bottom: 25px;
}

.clinical-summary {
    text-align: left;
}

.summary-item {
    display: flex;
    justify-content: space-between;
    font-size: 14px;
    margin-bottom: 8px;
}

.summary-item label { font-weight: 600; color: #718096; }

.video-main {
    flex: 1;
    position: relative;
}

#jitsi-container {
    height: 100%;
    width: 100%;
    border-radius: 20px;
    overflow: hidden;
    background: #1a202c;
    display: flex;
    align-items: center;
    justify-content: center;
}

.loading-overlay {
    text-align: center;
    color: white;
}

#session-notes {
    height: 150px;
    background: rgba(255,255,255,0.5);
    border: 1px solid #e2e8f0;
    font-size: 14px;
}
</style>

<!-- Jitsi Meet External API -->
<script src="https://meet.jit.si/external_api.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const domain = 'meet.jit.si';
    const options = {
        roomName: '<?= $roomName ?>',
        width: '100%',
        height: '100%',
        parentNode: document.querySelector('#jitsi-container'),
        configOverwrite: {
            startWithAudioMuted: true,
            disableThirdPartyRequests: true,
            prejoinPageEnabled: false
        },
        interfaceConfigOverwrite: {
            TOOLBAR_BUTTONS: [
                'microphone', 'camera', 'closedcaptions', 'desktop', 'fullscreen',
                'fodeviceselection', 'hangup', 'profile', 'info', 'chat', 'recording',
                'livestreaming', 'etherpad', 'sharedvideo', 'settings', 'raisehand',
                'videoquality', 'filmstrip', 'invite', 'feedback', 'stats', 'shortcuts',
                'tileview', 'videobackgroundblur', 'download', 'help', 'mute-everyone',
                'security'
            ],
        },
        userInfo: {
            displayName: '<?= $_SESSION['username'] ?> (Pharmacist)'
        }
    };
    
    const api = new JitsiMeetExternalAPI(domain, options);

    api.addEventListener('videoConferenceJoined', () => {
        document.querySelector('.loading-overlay').style.display = 'none';
    });

    // Save Notes Logic
    document.getElementById('save-notes').addEventListener('click', function() {
        const notes = document.getElementById('session-notes').value;
        const hn = '<?= $patient['hn'] ?? '' ?>';

        if (!notes) {
            alert('กรุณากรอกบันทึกก่อนบันทึก');
            return;
        }

        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

        fetch('/api/tele-pharmacy/save-notes', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ hn, notes })
        })
        .then(res => res.json())
        .then(data => {
            this.disabled = false;
            this.innerHTML = '<i class="fas fa-save"></i> บันทึกข้อมูล';
            if (data.success) {
                alert('บันทึกคำแนะนำเรียบร้อยแล้ว');
            }
        });
    });
});
</script>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
