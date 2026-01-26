/**
 * Drug Allergy Checker
 * 
 * Real-time drug allergy checking for patient safety
 */

class DrugAllergyChecker {
    constructor() {
        this.apiBaseUrl = '/api/allergy';
        this.currentHN = null;
        this.allergyCache = new Map();
    }

    /**
     * Initialize allergy checker
     */
    init() {
        // Auto-check when HN is entered
        const hnInput = document.getElementById('patient-hn');
        if (hnInput) {
            hnInput.addEventListener('change', (e) => {
                this.setPatientHN(e.target.value);
            });
        }

        // Auto-check when drug is selected
        const drugSelects = document.querySelectorAll('.drug-select');
        drugSelects.forEach(select => {
            select.addEventListener('change', (e) => {
                const drugName = e.target.options[e.target.selectedIndex].text;
                this.checkDrug(drugName, e.target);
            });
        });

        // Check button
        const checkBtn = document.getElementById('check-allergy-btn');
        if (checkBtn) {
            checkBtn.addEventListener('click', () => {
                this.checkAllDrugs();
            });
        }
    }

    /**
     * Set current patient HN
     */
    setPatientHN(hn) {
        this.currentHN = hn;

        if (hn) {
            // Load patient allergies
            this.loadPatientAllergies(hn);
        }
    }

    /**
     * Load patient allergies from server
     */
    async loadPatientAllergies(hn) {
        try {
            const response = await fetch(`${this.apiBaseUrl}/patient/${hn}`);
            const data = await response.json();

            if (data.success) {
                // Cache allergies
                this.allergyCache.set(hn, data.allergies);

                // Display allergies
                this.displayPatientAllergies(data.allergies);

                return data.allergies;
            }
        } catch (error) {
            console.error('Error loading allergies:', error);
        }

        return [];
    }

    /**
     * Display patient allergies
     */
    displayPatientAllergies(allergies) {
        const container = document.getElementById('patient-allergies');

        if (!container) return;

        if (allergies.length === 0) {
            container.innerHTML = '<div class="alert alert-info">ไม่พบประวัติแพ้ยา</div>';
            return;
        }

        let html = '<div class="alert alert-warning"><strong>⚠️ ประวัติแพ้ยา:</strong><ul class="mb-0 mt-2">';

        allergies.forEach(allergy => {
            const severityClass = allergy.severity.color;
            html += `
                <li>
                    <strong>${allergy.allergy_name}</strong>
                    <span class="badge bg-${severityClass} ms-2">${allergy.severity.label}</span>
                    ${allergy.symptom ? `<br><small class="text-muted">อาการ: ${allergy.symptom}</small>` : ''}
                </li>
            `;
        });

        html += '</ul></div>';
        container.innerHTML = html;
    }

    /**
     * Check single drug for allergy
     */
    async checkDrug(drugName, element = null) {
        if (!this.currentHN || !drugName) {
            return;
        }

        try {
            const response = await fetch(`${this.apiBaseUrl}/check`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    hn: this.currentHN,
                    drug_name: drugName
                })
            });

            const data = await response.json();

            if (data.success && data.has_allergy) {
                // Show alert
                this.showAllergyAlert(drugName, data.allergy, element);
                return true;
            } else {
                // Clear any previous alerts for this drug
                this.clearAllergyAlert(element);
                return false;
            }

        } catch (error) {
            console.error('Error checking drug:', error);
            return false;
        }
    }

    /**
     * Check all drugs in the form
     */
    async checkAllDrugs() {
        if (!this.currentHN) {
            alert('กรุณาระบุ HN ผู้ป่วย');
            return;
        }

        // Get all drug inputs
        const drugSelects = document.querySelectorAll('.drug-select');
        const drugs = [];

        drugSelects.forEach(select => {
            if (select.value) {
                const drugName = select.options[select.selectedIndex].text;
                drugs.push(drugName);
            }
        });

        if (drugs.length === 0) {
            alert('กรุณาเลือกยา');
            return;
        }

        try {
            const response = await fetch(`${this.apiBaseUrl}/check-multiple`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    hn: this.currentHN,
                    drugs: drugs
                })
            });

            const data = await response.json();

            if (data.success) {
                if (data.has_allergy) {
                    this.showMultipleAllergiesAlert(data.allergies);
                } else {
                    this.showSuccessMessage('✅ ไม่พบประวัติแพ้ยาทั้งหมด');
                }
            }

        } catch (error) {
            console.error('Error checking drugs:', error);
        }
    }

    /**
     * Show allergy alert
     */
    showAllergyAlert(drugName, allergy, element = null) {
        const severity = allergy.severity;
        const severityClass = severity.color === 'danger' ? 'danger' : 'warning';

        // Create alert HTML
        const alertHtml = `
            <div class="alert alert-${severityClass} allergy-alert mt-2" role="alert">
                <h5 class="alert-heading">⚠️ ผู้ป่วยแพ้ยานี้!</h5>
                <p class="mb-1"><strong>ยา:</strong> ${drugName}</p>
                <p class="mb-1"><strong>ประวัติแพ้:</strong> ${allergy.name}</p>
                ${allergy.symptom ? `<p class="mb-1"><strong>อาการ:</strong> ${allergy.symptom}</p>` : ''}
                <p class="mb-0"><strong>ระดับความรุนแรง:</strong> 
                    <span class="badge bg-${severity.color}">${severity.label}</span>
                </p>
            </div>
        `;

        // Show alert near the element or in a global container
        if (element) {
            const container = element.closest('.form-group') || element.parentElement;

            // Remove existing alert
            const existingAlert = container.querySelector('.allergy-alert');
            if (existingAlert) {
                existingAlert.remove();
            }

            // Add new alert
            container.insertAdjacentHTML('beforeend', alertHtml);

            // Highlight the input
            element.classList.add('is-invalid', 'border-danger');
        } else {
            // Show in global alert container
            const globalContainer = document.getElementById('allergy-alerts');
            if (globalContainer) {
                globalContainer.innerHTML = alertHtml;
            }
        }

        // Play alert sound (optional)
        this.playAlertSound();
    }

    /**
     * Show multiple allergies alert
     */
    showMultipleAllergiesAlert(allergies) {
        let html = '<div class="modal fade" id="allergyModal" tabindex="-1">';
        html += '<div class="modal-dialog modal-dialog-centered">';
        html += '<div class="modal-content">';
        html += '<div class="modal-header bg-danger text-white">';
        html += '<h5 class="modal-title">⚠️ พบประวัติแพ้ยา!</h5>';
        html += '<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>';
        html += '</div>';
        html += '<div class="modal-body">';
        html += '<div class="alert alert-danger">';
        html += '<strong>พบยาที่ผู้ป่วยแพ้ ' + allergies.length + ' รายการ:</strong>';
        html += '<ul class="mt-2">';

        allergies.forEach(item => {
            html += `<li><strong>${item.drug}</strong> → แพ้ ${item.allergy_name}`;
            if (item.symptom) {
                html += `<br><small>อาการ: ${item.symptom}</small>`;
            }
            html += `<br><span class="badge bg-${item.severity.color}">${item.severity.label}</span>`;
            html += '</li>';
        });

        html += '</ul></div>';
        html += '<p class="text-danger"><strong>⚠️ กรุณาตรวจสอบก่อนจ่ายยา!</strong></p>';
        html += '</div>';
        html += '<div class="modal-footer">';
        html += '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>';
        html += '</div></div></div></div>';

        // Remove existing modal
        const existingModal = document.getElementById('allergyModal');
        if (existingModal) {
            existingModal.remove();
        }

        // Add new modal
        document.body.insertAdjacentHTML('beforeend', html);

        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('allergyModal'));
        modal.show();

        // Play alert sound
        this.playAlertSound();
    }

    /**
     * Clear allergy alert
     */
    clearAllergyAlert(element) {
        if (element) {
            const container = element.closest('.form-group') || element.parentElement;
            const alert = container.querySelector('.allergy-alert');

            if (alert) {
                alert.remove();
            }

            element.classList.remove('is-invalid', 'border-danger');
        }
    }

    /**
     * Show success message
     */
    showSuccessMessage(message) {
        const container = document.getElementById('allergy-alerts');

        if (container) {
            container.innerHTML = `<div class="alert alert-success">${message}</div>`;

            setTimeout(() => {
                container.innerHTML = '';
            }, 3000);
        }
    }

    /**
     * Play alert sound
     */
    playAlertSound() {
        // Create audio element
        const audio = new Audio('/sounds/alert.mp3');
        audio.volume = 0.5;

        // Play sound (may be blocked by browser)
        audio.play().catch(e => {
            console.log('Audio play blocked:', e);
        });
    }

    /**
     * Sync allergies from JHCIS
     */
    async syncAllergies(hn) {
        try {
            const response = await fetch(`${this.apiBaseUrl}/sync`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ hn: hn })
            });

            const data = await response.json();

            if (data.success) {
                // Reload allergies
                await this.loadPatientAllergies(hn);
                return true;
            }

            return false;

        } catch (error) {
            console.error('Error syncing allergies:', error);
            return false;
        }
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function () {
    window.allergyChecker = new DrugAllergyChecker();
    window.allergyChecker.init();
});

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = DrugAllergyChecker;
}
