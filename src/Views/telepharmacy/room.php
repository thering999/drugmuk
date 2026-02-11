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

            <!-- NEW: AI Master Insight Panel -->
            <div id="ai-master-insight" class="mt-3"></div>

            <!-- NEW: JHCIS Lab Visualizer -->
            <div id="lab-viz-panel" class="mt-3">
                <style>#lab-viz-panel h4 { font-size: 14px; color: #4a5568; margin-bottom: 10px; border-bottom: 2px solid #e2e8f0; padding-bottom: 5px; }</style>
                <h4><i class="fas fa-chart-area"></i> Lab Trends (JHCIS)</h4>
                <div id="lab-stats-container" class="small">
                    <div class="text-center p-2 text-muted">Loading labs...</div>
                </div>
            </div>

            <!-- AI Badges Grid -->
            <div class="ai-badges-grid mt-3">
                <div id="renal-safety-badge"></div>
            </div>
            
            <div id="clinical-monitoring-viz" class="mt-3"></div>
            <div id="deprescribing-viz" class="mt-3"></div>

            <!-- NEW: Clinical Interventions Tracker -->
            <div class="intervention-tracker mt-4 glass-effect p-3" style="border-radius: 12px; border: 1px solid #cbd5e0;">
                <h4 style="font-size: 14px; color: #2d3748; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-clipboard-check text-primary"></i> Clinical Interventions
                </h4>
                <div id="intervention-list" class="small">
                    <div class="text-center text-muted p-2">No interventions recorded.</div>
                </div>
            </div>
        </div>
        </div>

        <div class="consultation-notes-section">
            <h4><i class="fas fa-edit"></i> Clinical Notes</h4>
            <div id="ai-live-suggestion" class="mb-2" style="display:none;"></div>
            <textarea id="session-notes" class="form-control" placeholder="บันทึกคำแนะนำทางเภสัชกรรม..."></textarea>
            
            <div id="smart-instruction-tool" class="mt-2 glass-effect p-2" style="display:none; border: 1px dashed #4299e1;">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <small><strong>AI Smart Instruction</strong></small>
                    <button class="btn btn-close btn-sm" onclick="document.getElementById('smart-instruction-tool').style.display='none'"></button>
                </div>
                <div id="smart-instruction-text" class="small text-primary mb-2"></div>
                <button id="use-instruction" class="btn btn-sm btn-primary w-100">Apply to Notes</button>
            </div>

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
    background: rgba(255,255,255,0.7);
    border: 1px solid #e2e8f0;
    font-size: 14px;
    border-radius: 12px;
}

.ai-badges-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 10px;
}

.card {
    transition: all 0.3s ease;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 15px rgba(0,0,0,0.1);
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
        const btn = this;
        btn.disabled = true;
        
        fetch('/api/tele-pharmacy/save-notes', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ hn: '<?= $patient['hn'] ?? '' ?>', notes: notes })
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            if(data.success) alert('Notes saved successfully!');
            else alert('Error saving notes');
        });
    });

    // AI Analysis Logic
    const analyzeBtn = document.createElement('button');
    analyzeBtn.className = 'btn btn-info w-100 mt-2 text-white';
    analyzeBtn.innerHTML = '<i class="fas fa-magic"></i> Analyze with AI';
    analyzeBtn.onclick = function() {
        const notes = document.getElementById('session-notes').value;
        if(!notes) { alert('Please enter some notes first.'); return; }
        
        const btn = this;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Analyzing...';
        btn.disabled = true;

        fetch('/api/tele-pharmacy/analyze-note', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ notes: notes, hn: '<?= $patient['hn'] ?? '' ?>' })
        })
        .then(res => res.json())
        .then(data => {
            btn.innerHTML = '<i class="fas fa-magic"></i> Analyze with AI';
            btn.disabled = false;
            
            if(data.success) {
                const result = data.analysis;
                let alertHtml = '';
                
                const alertType = result.adr_detected ? (result.severity === 'Major' ? 'danger' : 'warning') : 'success';
                const icon = result.adr_detected ? 'exclamation-triangle' : 'check-circle';
                
                alertHtml = `
                    <div class="alert alert-${alertType} mt-3 mb-0" style="font-size: 13px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fas fa-${icon}"></i>
                                <strong>AI Analysis Result:</strong>
                            </div>
                            <button class="btn btn-sm btn-light" onclick="logClinicalIntervention('ADR Analysis', '${result.summary.replace(/'/g, "\\'")}', '${result.severity || 'Mild'}')" style="font-size: 10px; padding: 2px 8px;">
                                <i class="fas fa-save"></i> Log
                            </button>
                        </div>
                        <div class="p-2 bg-white bg-opacity-50 rounded mb-2">
                            ${result.summary}
                        </div>
                        
                        ${result.interactions && result.interactions.length > 0 ? `
                            <div class="interaction-details mt-3 pt-2 border-top border-dark border-opacity-10">
                                <div style="font-weight: 800; color: #721c24; font-size: 11px; margin-bottom: 5px; text-transform: uppercase;">
                                    <i class="fas fa-exclamation-circle"></i> Detected Interactions (${result.interactions.length})
                                </div>
                                ${result.interactions.map(i => `
                                    <div class="i-item mb-2 p-2 rounded" style="background: rgba(255,255,255,0.4); border: 1px solid rgba(0,0,0,0.1);">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span style="font-weight: 700; color: #1a202c;">${i.drug1} ↔ ${i.drug2}</span>
                                            <div class="d-flex gap-1 align-items-center">
                                                <span class="badge bg-${i.severity === 'major' || i.severity === 'contraindicated' ? 'danger' : 'warning'}" style="font-size: 9px;">${i.severity.toUpperCase()}</span>
                                                <button class="btn btn-sm p-0 text-primary" onclick="logClinicalIntervention('DDI', '${i.drug1} vs ${i.drug2}: ${i.effect.replace(/'/g, "\\'")}', '${i.severity}')">
                                                    <i class="fas fa-plus-circle"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div style="font-size: 12px; margin-top: 3px;">${i.effect}</div>
                                        <div style="font-size: 11px; color: #2d3748; font-style: italic; margin-top: 2px;"><strong>Action:</strong> ${i.action || i.recommendation}</div>
                                    </div>
                                `).join('')}
                            </div>
                        ` : ''}
                    </div>
                `;
                
                // Check if result container exists, if not create one
                let container = document.getElementById('ai-result-container');
                if(!container) {
                    container = document.createElement('div');
                    container.id = 'ai-result-container';
                    document.querySelector('.consultation-notes-section').appendChild(container);
                }
                container.innerHTML = alertHtml;
            }
        });
    };
    
    // Insert analyze button after save button
    document.getElementById('save-notes').parentNode.appendChild(analyzeBtn);

    // NEW: AI Auto-Summary Logic
    const summaryBtn = document.createElement('button');
    summaryBtn.className = 'btn btn-outline-primary w-100 mt-2';
    summaryBtn.innerHTML = '<i class="fas fa-file-alt"></i> Auto-Summary (SOAP)';
    summaryBtn.onclick = function() {
        const notes = document.getElementById('session-notes').value;
        if(!notes) { alert('Please enter some notes first.'); return; }
        
        const btn = this;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        btn.disabled = true;

        fetch('/api/intelligence/tele-summary', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ notes: notes })
        })
        .then(res => res.json())
        .then(data => {
            btn.innerHTML = '<i class="fas fa-file-alt"></i> Auto-Summary (SOAP)';
            btn.disabled = false;
            
            if(data.success) {
                const s = data.summary;
                const summaryText = `[S]: ${s.subjective}\n[O]: ${s.objective}\n[A]: ${s.assessment}\n[P]: ${s.plan}`;
                
                if(confirm('AI Generated SOAP Summary:\n\n' + summaryText + '\n\nDo you want to REPLACE your current notes with this summary?')) {
                    document.getElementById('session-notes').value = summaryText;
                }
            }
        });
    };
    document.getElementById('save-notes').parentNode.appendChild(summaryBtn);
    
    // NEW: Send AI Safety Report Button
    const safetyReportBtn = document.createElement('button');
    safetyReportBtn.className = 'btn btn-outline-danger w-100 mt-2';
    safetyReportBtn.innerHTML = '<i class="fas fa-shield-alt"></i> Send AI Safety Report';
    safetyReportBtn.onclick = function() {
        if(!confirm('Generate and Send AI Patient Safety Report to LINE?')) return;
        
        const btn = this;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
        btn.disabled = true;

        fetch('/api/intelligence/patient-safety-report/<?= $patient['hn'] ?? '' ?>')
            .then(res => res.json())
            .then(data => {
                btn.innerHTML = '<i class="fas fa-shield-alt"></i> Send AI Safety Report';
                btn.disabled = false;
                
                if(data.success) {
                    // We need a way to send this report text via LINE. 
                    // Let's use the send-alert endpoint or a new one.
                    fetch('/api/intelligence/send-alert', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'type=general&data=' + encodeURIComponent(JSON.stringify({
                            hn: '<?= $patient['hn'] ?? '' ?>',
                            message: data.report
                        }))
                    })
                    .then(res => res.json())
                    .then(r => {
                        if(r.success) alert('Safety Report sent to patient successfully!');
                        else alert('Failed to send: ' + r.message);
                    });
                }
            });
    };
    document.getElementById('save-notes').parentNode.appendChild(safetyReportBtn);

    // NEW: AI Renal Check Logic
    if ('<?= $patient['hn'] ?? '' ?>') {
        fetch('/api/intelligence/renal-dose-risk/<?= $patient['hn'] ?? '' ?>')
            .then(res => res.json())
            .then(data => {
                const badge = document.getElementById('renal-safety-badge');
                if (data.success && data.data) {
                    const r = data.data;
                    const color = r.is_critical ? 'danger' : 'warning';
                    const icon = r.is_critical ? 'radiation' : 'exclamation-triangle';
                    
                    badge.innerHTML = `
                        <div class="alert alert-${color} p-2 small m-0" style="cursor: pointer;" onclick="alert('${r.suggestions.join('\\n').replace(/<[^>]*>/g, '')}')">
                            <i class="fas fa-${icon}"></i> AI Safety: eGFR ${r.egfr}<br>
                            <small>${r.suggestions.length} potential adjustment(s)</small>
                        </div>
                    `;
                }
            });

        // NEW: AI Clinical Monitoring Logic
        fetch('/api/intelligence/clinical-monitoring/<?= $patient['hn'] ?? '' ?>')
            .then(res => res.json())
            .then(data => {
                const viz = document.getElementById('clinical-monitoring-viz');
                if (data.success && data.recommendations && data.recommendations.length > 0) {
                    viz.innerHTML = `
                        <div class="card p-2 small border-info" style="background: #f0f9ff; border-left: 4px solid #0ea5e9;">
                            <div style="font-weight: bold; color: #0369a1; border-bottom: 1px solid #bae6fd; margin-bottom: 5px; padding-bottom: 2px;">
                                <i class="fas fa-microscope"></i> AI Lab Advisory
                            </div>
                            <ul class="p-0 m-0 list-unstyled">
                                ${data.recommendations.map(r => `
                                    <li class="mb-1" title="${r.reason}">
                                        <span class="badge bg-info">Check ${r.lab}</span>
                                        <br><small><i class="fas fa-pills"></i> จากยา: ${r.drug}</small>
                                    </li>
                                `).join('')}
                            </ul>
                        </div>
                    `;
                }
            });

        // NEW: AI Deprescribing Assistant Logic
        fetch('/api/intelligence/deprescribing/<?= $patient['hn'] ?? '' ?>')
            .then(res => res.json())
            .then(data => {
                const viz = document.getElementById('deprescribing-viz');
                if (data.success && data.suggestions && data.suggestions.length > 0) {
                    viz.innerHTML = `
                        <div class="card p-2 small border-warning" style="background: #fffdf2; border-left: 4px solid #f59e0b;">
                            <div style="font-weight: bold; color: #b45309; border-bottom: 1px solid #fef3c7; margin-bottom: 5px; padding-bottom: 2px;">
                                <i class="fas fa-hand-holding-heart"></i> AI Deprescribing
                            </div>
                            <ul class="p-0 m-0 list-unstyled">
                                ${data.suggestions.map(s => `
                                    <li class="mb-1">
                                        <strong class="text-danger">${s.drug || s.type}</strong>: ${s.reason}<br>
                                        <small><i class="fas fa-arrow-right"></i> ${s.action}</small>
                                    </li>
                                `).join('')}
                            </ul>
                        </div>
                    `;
                }
            });
        
        // NEW: AI Master Insight Logic
        fetch('/api/intelligence/patient-insight/<?= $patient['hn'] ?? '' ?>')
            .then(res => res.json())
            .then(data => {
                const container = document.getElementById('ai-master-insight');
                if (data.success && data.insight) {
                    const i = data.insight;
                    const riskColor = i.score > 50 ? '#e53e3e' : (i.score > 30 ? '#dd6b20' : '#3182ce');
                    
                    container.innerHTML = `
                        <div class="card p-3 small" style="border-top: 4px solid ${riskColor}; background: #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.08); border-radius: 12px; margin-bottom: 15px;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                                <strong style="color: ${riskColor};"><i class="fas fa-brain"></i> AI Clinical Insight</strong>
                                <span class="badge" style="background: ${riskColor}; color: white;">Risk: ${i.score}</span>
                            </div>
                            <p class="mb-2" style="line-height: 1.4; color: #4a5568; font-weight: 500;">${i.summary}</p>
                            ${i.alerts.length > 0 ? `
                                <div style="font-size: 11px; margin-top: 10px; border-top: 1px solid #edf2f7; padding-top: 8px;">
                                    ${i.alerts.map(a => `<div class="mb-1" style="color: ${a.type === 'danger' ? '#c53030' : '#9c4221'}"><strong>• ${a.title}:</strong> ${a.message}</div>`).join('')}
                                </div>
                            ` : ''}
                        </div>
                    `;
                }
            });

        // NEW: JHCIS Lab Trends Logic
        fetch('/api/patient/<?= $patient['hn'] ?? '' ?>/labs')
            .then(res => res.json())
            .then(data => {
                const container = document.getElementById('lab-stats-container');
                if (data.success && data.labs) {
                    const labs = data.labs;
                    if (labs.length === 0) {
                        container.innerHTML = '<div class="text-muted text-center p-2">No lab data found.</div>';
                        return;
                    }

                    container.innerHTML = `
                        <div class="lab-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                            ${labs.slice(0, 4).map(l => `
                                <div class="lab-item p-2" style="background: #f8fafc; border-radius: 8px; border: 1px solid #edf2f7;">
                                    <div style="font-size: 10px; color: #718096; text-transform: uppercase; font-weight: bold;">${l.lab_name}</div>
                                    <div style="font-size: 13px; font-weight: 800; color: ${l.lab_name === 'eGFR' && l.lab_value < 60 ? '#e53e3e' : '#2d3748'}">
                                        ${l.lab_value} <small style="font-weight: normal; font-size: 9px; opacity: 0.7;">${l.lab_unit || ''}</small>
                                    </div>
                                    <div style="font-size: 9px; opacity: 0.6;">${l.vstdate}</div>
                                </div>
                            `).join('')}
                        </div>
                    `;
                }
            });

        // Smart Instruction Trigger on selection
        document.getElementById('session-notes').addEventListener('input', function(e) {
            const val = e.target.value;
            const shorthands = ['q.d.', 'b.i.d.', 't.i.d.', 'q.i.d.', 'h.s.', 'a.c.', 'p.c.'];
            const lines = val.split('\n');
            const lastLine = lines[lines.length - 1];
            
            let hasShorthand = false;
            if (lastLine) {
                shorthands.forEach(s => { if(lastLine.toLowerCase().includes(s)) hasShorthand = true; });
            }

            if (hasShorthand && !lastLine.includes('ทาน')) {
                fetch('/api/engagement/generate-instruction', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ drug_name: 'ยาของท่าน', raw_instruction: lastLine })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const tool = document.getElementById('smart-instruction-tool');
                        const text = document.getElementById('smart-instruction-text');
                        text.innerText = data.instruction;
                        tool.style.display = 'block';
                        
                        document.getElementById('use-instruction').onclick = function() {
                            const newNotes = val.substring(0, val.lastIndexOf(lastLine)) + data.instruction;
                            document.getElementById('session-notes').value = newNotes;
                            tool.style.display = 'none';
                            document.getElementById('session-notes').focus();
                        };
                    }
                });
            }
        });
    }

    // Send Invite Logic
    const inviteBtn = document.createElement('button');
    inviteBtn.className = 'btn btn-success w-100 mb-2';
    inviteBtn.innerHTML = '<i class="fab fa-line"></i> Invite via LINE';
    inviteBtn.onclick = function() {
        if(!confirm('Send invitation link to LINE?')) return;
        
        fetch('/api/tele-pharmacy/invite', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                hn: '<?= $patient['hn'] ?? '' ?>',
                roomName: options.roomName
            })
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) alert('Invitation sent successfully!');
            else alert('Failed: ' + data.message);
        });
    };
    
    // Insert button after Patient Profile link
    const profileBtn = document.querySelector('a[href*="/patient/"]');
    if(profileBtn) {
        profileBtn.parentNode.insertBefore(inviteBtn, profileBtn);
    }

    // Clinical Intervention Logging
    window.logClinicalIntervention = function(type, details, severity) {
        if (!confirm(`บันทึก Intervention: ${type}?\nรายละเอียด: ${details}`)) return;
        
        // Use persistence for demo if API fails or for immediate UI update
        const hn = '<?= $patient['hn'] ?? '' ?>';
        const current = JSON.parse(localStorage.getItem(`interventions_${hn}`) || '[]');
        current.unshift({ type, details, severity, time: new Date().toISOString() });
        localStorage.setItem(`interventions_${hn}`, JSON.stringify(current));

        fetch('/api/tele-pharmacy/log-intervention', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                hn: hn,
                type: type,
                details: details,
                severity: severity
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                renderInterventionList();
                // Show floating success toast
                const toast = document.createElement('div');
                toast.className = 'glass-effect p-2 px-3 text-success';
                toast.style = 'position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); z-index: 9999; border-radius: 20px; font-weight: 600; box-shadow: 0 4px 15px rgba(0,0,0,0.2); background: white;';
                toast.innerHTML = '<i class="fas fa-check-circle"></i> Intervention Logged Successfully';
                document.body.appendChild(toast);
                setTimeout(() => toast.remove(), 3000);
            }
        });
    };

    function renderInterventionList() {
        const list = document.getElementById('intervention-list');
        const hn = '<?= $patient['hn'] ?? '' ?>';
        const currentInterventions = JSON.parse(localStorage.getItem(`interventions_${hn}`) || '[]');
        
        if (currentInterventions.length === 0) {
            list.innerHTML = '<div class="text-center text-muted p-2">No interventions recorded.</div>';
            return;
        }

        list.innerHTML = currentInterventions.map(i => `
            <div class="mb-2 p-2 rounded bg-light" style="border-left: 3px solid ${i.severity === 'Major' || i.severity === 'major' ? '#e53e3e' : '#ecc94b'};">
                <div class="d-flex justify-content-between align-items-center">
                    <strong>${i.type}</strong>
                    <small class="text-muted" style="font-size: 9px;">${new Date(i.time).toLocaleTimeString()}</small>
                </div>
                <div class="text-muted" style="font-size: 11px;">${i.details}</div>
            </div>
        `).join('');
    }

    renderInterventionList();
});
</script>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>
