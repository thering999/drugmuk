/**
 * Clinical Safety Engine (Phase 4)
 * Handles DDI and Lab-based safety alerts
 */
const ClinicalSafety = {
    patientHn: null,
    currentDrugs: [],

    init: function () {
        console.log('Clinical Safety Engine Initialized');
        this.bindEvents();
    },

    bindEvents: function () {
        // Watch for drug additions in the dispensing table
        const drugTable = document.getElementById('itemsBody');
        if (drugTable) {
            const observer = new MutationObserver(() => this.runSafetyCheck());
            observer.observe(drugTable, { childList: true });

            // Listen for drug select changes
            drugTable.addEventListener('change', (e) => {
                if (e.target.classList.contains('drug-select') || e.target.name === 'drug_id[]') {
                    this.runSafetyCheck();
                }
            });
        }
    },

    setPatient: function (hn) {
        this.patientHn = hn;
        this.loadLabs();
        this.runSafetyCheck();
    },

    loadLabs: function () {
        if (!this.patientHn) return;

        fetch(`/api/safety/labs/${this.patientHn}`)
            .then(res => res.json())
            .then(data => {
                const container = document.getElementById('lab-insights');
                const list = document.getElementById('lab-results-list');

                if (data.success && data.labs.length > 0) {
                    container.style.display = 'block';
                    list.innerHTML = data.labs.map(lab => `
                        <div class="lab-item ${this.getLabStatusClass(lab)}">
                            <div class="lab-name"><strong>${lab.lab_name}</strong></div>
                            <div class="lab-value">${lab.lab_value} <small>${lab.lab_unit}</small></div>
                            <div class="lab-date" style="font-size: 10px; opacity: 0.6 text-align: right;">${lab.vstdate}</div>
                        </div>
                    `).join('');
                } else {
                    container.style.display = 'none';
                }
            });
    },

    getLabStatusClass: function (lab) {
        if (lab.lab_name === 'eGFR' && lab.lab_value < 30) return 'alert-danger';
        if (lab.lab_name === 'eGFR' && lab.lab_value < 60) return 'alert-warning';
        if (lab.lab_name === 'Potassium' && (lab.lab_value > 5.0 || lab.lab_value < 3.5)) return 'alert-warning';
        return 'alert-success';
    },

    runSafetyCheck: function () {
        const drugs = Array.from(document.querySelectorAll('select[name="drug_id[]"]'))
            .map(select => {
                const opt = select.options[select.selectedIndex];
                return (opt && opt.value) ? opt.dataset.name : null;
            })
            .filter(v => v);

        const container = document.getElementById('safety-alerts');
        if (drugs.length === 0) {
            container.innerHTML = '';
            return;
        }

        // Clear before new check
        container.innerHTML = '';

        // 1. Check DDI
        fetch('/api/safety/check-ddi', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ drugs: drugs })
        })
            .then(res => res.json())
            .then(data => {
                if (data.success && data.interactions.length > 0) {
                    this.displayDDIAlerts(data.interactions);
                }
            });

        // 2. Check Patient-Specific Safety
        if (this.patientHn) {
            drugs.forEach(drug => {
                fetch(`/api/safety/check-patient?hn=${this.patientHn}&drug=${encodeURIComponent(drug)}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.success && data.alerts.length > 0) {
                            this.displayPatientSafetyAlerts(data.alerts);
                        }
                    });
            });
        }
    },

    displayDDIAlerts: function (interactions) {
        const container = document.getElementById('safety-alerts');
        if (!interactions || interactions.length === 0) {
            // Only clear DDI if there are no OTHER safety alerts
            // Better to use a specific DDI container
            return;
        }

        const html = interactions.map(int => `
            <div class="safety-alert">
                <i class="fas fa-biohazard fa-2x"></i>
                <div class="alert-info">
                    <strong>ยาตีกัน (DDI Level: ${int.severity.toUpperCase()})</strong><br>
                    <span>${int.drug_a_name} + ${int.drug_b_name} : ${int.description}</span><br>
                    <small>💡 ข้อแนะนำ: ${int.action_suggested}</small>
                </div>
            </div>
        `).join('');

        container.innerHTML = html;
        this.playAlertSound();
    },

    displayPatientSafetyAlerts: function (alerts) {
        if (!alerts || alerts.length === 0) return;

        const container = document.getElementById('safety-alerts');
        const html = alerts.map(alert => `
            <div class="safety-alert" style="background: #fffaf0; border-color: #fbd38d; color: #744210;">
                <i class="fas fa-exclamation-triangle fa-2x"></i>
                <div class="alert-info">
                    <strong>คำเตือนความปลอดภัย (Clinical Alert)</strong><br>
                    <span>${alert.message}</span><br>
                    <small>🔍 ค่าตรวจทางห้องปฏิบัติการ: ${alert.value} ${alert.unit}</small>
                </div>
            </div>
        `).join('');

        container.innerHTML += html; // Append to DDI alerts
    },

    playAlertSound: function () {
        const audio = new Audio('https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3');
        audio.play().catch(e => console.log('Audio play blocked'));
    }
};

// Auto-init
document.addEventListener('DOMContentLoaded', () => ClinicalSafety.init());
