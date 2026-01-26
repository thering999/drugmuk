<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จ่ายยาให้ผู้ป่วย - Drugmuk</title>
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
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            background: white;
            padding: 20px 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            color: #667eea;
            font-size: 28px;
        }

        .card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-sm {
            padding: 8px 16px;
            font-size: 14px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: 500;
            border-bottom: 2px solid #dee2e6;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
        }

        .autocomplete {
            position: relative;
        }

        .autocomplete-items {
            position: absolute;
            border: 1px solid #d4d4d4;
            border-top: none;
            z-index: 99;
            top: 100%;
            left: 0;
            right: 0;
            max-height: 300px;
            overflow-y: auto;
            background: white;
            border-radius: 0 0 8px 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .autocomplete-items div {
            padding: 12px;
            cursor: pointer;
            border-bottom: 1px solid #e0e0e0;
        }

        .autocomplete-items div:hover {
            background-color: #f0f0f0;
        }

        .patient-info {
            background: #e7f3ff;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: none;
        }

        .patient-info.show {
            display: block;
        }

        .patient-history {
            margin-top: 10px;
            font-size: 14px;
            color: #666;
        }

        .message {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
        }

        /* Lab Dashboard Styling */
        .lab-dashboard {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
            font-size: 13px;
        }
        .lab-header {
            background: #edf2f7;
            padding: 10px 15px;
            color: #4a5568;
            border-bottom: 1px solid #e2e8f0;
        }
        .lab-content {
            padding: 10px 15px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        .lab-item {
            background: white;
            padding: 8px 12px;
            border-radius: 8px;
            border-left: 3px solid #cbd5e0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .lab-item.alert-danger { border-left-color: #f56565; background: #fff5f5; }
        .lab-item.alert-warning { border-left-color: #ed8936; background: #fffaf0; }
        .lab-item.alert-success { border-left-color: #48bb78; background: #f0fff4; }
        
        .safety-alert {
            background: #fff5f5;
            border: 1px solid #fed7d7;
            color: #c53030;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: pulse-red 2s infinite;
        }
        @keyframes pulse-red {
            0% { box-shadow: 0 0 0 0 rgba(245, 101, 101, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(245, 101, 101, 0); }
            100% { box-shadow: 0 0 0 0 rgba(245, 101, 101, 0); }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (isset($_SESSION['error'])): ?>
        <div class="message error">
            ❌ <?= htmlspecialchars($_SESSION['error']) ?>
        </div>
        <?php unset($_SESSION['error']); endif; ?>

        <div class="header">
            <h1>💊 จ่ายยาให้ผู้ป่วย</h1>
            <a href="/dispensing" class="btn btn-secondary">← กลับ</a>
        </div>

        <form method="POST" action="/dispensing/store" id="dispensingForm">
            <?php echo \App\Core\CSRF::field(); ?>
            <!-- Patient Information -->
            <div class="card">
                <h2 style="margin-bottom: 20px;">ข้อมูลผู้ป่วย</h2>
                
                <div class="form-row">
                    <div class="form-row">
                        <div class="form-group autocomplete">
                            <label>HN <span style="color: red;">*</span></label>
                            <input type="text" name="hn" id="patient-hn" required autocomplete="off" placeholder="ค้นหา HN หรือชื่อผู้ป่วย">
                            <div id="hnAutocomplete" class="autocomplete-items"></div>
                        </div>

                        <div class="form-group">
                            <label>VN</label>
                            <input type="text" name="vn" id="vn" placeholder="Visit Number (ถ้ามี)">
                        </div>

                        <div class="form-group">
                            <label>ชื่อผู้ป่วย <span style="color: red;">*</span></label>
                            <input type="text" name="patient_name" id="patient_name" required placeholder="ชื่อ-นามสกุล">
                        </div>

                        <div class="form-group">
                            <label>วันที่จ่าย</label>
                            <input type="datetime-local" name="dispense_date" value="<?= date('Y-m-d\TH:i') ?>">
                        </div>
                    </div>
                </div>

                <!-- Allergy Alert Containers -->
                <div id="patient-allergies" style="margin-top: 15px;"></div>
                <div id="allergy-alerts" style="margin-top: 10px;"></div>

                <!-- NEW: Clinical Safety (Phase 4) -->
                <div id="safety-alerts" style="margin-top: 10px;"></div>
                <div id="lab-insights" class="lab-dashboard" style="margin-top: 15px; display: none;">
                    <div class="lab-header">
                        <i class="fas fa-flask"></i> <strong>ผล Lab ล่าสุด & การวิเคราะห์ความปลอดภัย</strong>
                    </div>
                    <div id="lab-results-list" class="lab-content"></div>
                </div>

                <div class="patient-info" id="patientInfo">
                    <strong>ประวัติการรับยา:</strong>
                    <div class="patient-history" id="patientHistory"></div>
                </div>

                <!-- NEW: Clinical Notes (Phase 5) -->
                <div class="form-group" style="margin-top: 20px;">
                    <label>📝 Clinical Notes / Tele-health Note (บันทึกเพิ่มเติม/คำแนะนำโดยเภสัชกร)</label>
                    <textarea name="clinical_notes" rows="3" class="form-control" style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-family: inherit; font-size: 15px;" placeholder="ระบุคำแนะนำเพิ่มเติม หรือบันทึกเพื่อติดตามผลการใช้ยา..."></textarea>
                </div>
            </div>

            <!-- Drug Items -->
            <div class="card">
                <h2 style="margin-bottom: 20px;">รายการยา</h2>
                
                <table id="itemsTable">
                    <thead>
                        <tr>
                            <th style="width: 50%;">ยา</th>
                            <th style="width: 20%;">จำนวน</th>
                            <th style="width: 20%;">หน่วย</th>
                            <th style="width: 10%;"></th>
                        </tr>
                    </thead>
                    <tbody id="itemsBody">
                        <tr>
                            <td>
                                <select name="drug_id[]" class="drug-select" required>
                                    <option value="">-- เลือกยา --</option>
                                    <?php foreach ($drugs as $drug): ?>
                                    <option value="<?= $drug['id'] ?>" data-unit="<?= htmlspecialchars($drug['unit']) ?>" data-name="<?= htmlspecialchars($drug['name']) ?>">
                                        <?= htmlspecialchars($drug['code']) ?> - <?= htmlspecialchars($drug['name']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <input type="number" name="quantity[]" min="1" step="1" required placeholder="จำนวน">
                            </td>
                            <td>
                                <input type="text" class="unit-display" readonly placeholder="หน่วย">
                            </td>
                            <td>
                                <button type="button" class="btn btn-danger btn-sm remove-row" style="display: none;">🗑️</button>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <button type="button" class="btn btn-success btn-sm" id="addRow" style="margin-top: 15px;">
                    ➕ เพิ่มรายการยา
                </button>
            </div>

            <!-- Submit -->
            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <a href="/dispensing" class="btn btn-secondary">ยกเลิก</a>
                <button type="submit" class="btn btn-primary">💾 บันทึกการจ่ายยา</button>
            </div>
        </form>
    </div>

    <script src="/js/drug-allergy-checker.js"></script>
    <script src="/js/clinical-safety.js"></script>
    <script>
        // Patient autocomplete
        const hnInput = document.getElementById('patient-hn');
        const hnAutocomplete = document.getElementById('hnAutocomplete');
        const patientNameInput = document.getElementById('patient_name');
        const patientInfo = document.getElementById('patientInfo');
        const patientHistory = document.getElementById('patientHistory');

        let searchTimeout;

        hnInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const keyword = this.value;

            if (keyword.length < 2) {
                hnAutocomplete.innerHTML = '';
                return;
            }

            searchTimeout = setTimeout(() => {
                fetch(`/dispensing/search-patient?q=${encodeURIComponent(keyword)}`)
                    .then(res => res.json())
                    .then(patients => {
                        hnAutocomplete.innerHTML = '';
                        
                        if (patients.length === 0) {
                            hnAutocomplete.innerHTML = '<div style="padding: 12px; color: #999;">ไม่พบข้อมูล</div>';
                            return;
                        }

                        patients.forEach(patient => {
                            const div = document.createElement('div');
                            div.innerHTML = `<strong>${patient.hn}</strong> - ${patient.patient_name}`;
                            div.addEventListener('click', () => {
                                hnInput.value = patient.hn;
                                patientNameInput.value = patient.patient_name;
                                hnAutocomplete.innerHTML = '';
                                loadPatientHistory(patient.hn);
                                
                                // Trigger safety & allergy checker
                                if (window.allergyChecker) {
                                    window.allergyChecker.setPatientHN(patient.hn);
                                }
                                if (window.ClinicalSafety) {
                                    window.ClinicalSafety.setPatient(patient.hn);
                                }
                            });
                            hnAutocomplete.appendChild(div);
                        });
                    });
            }, 300);
        });

        // Also trigger on manual blur/change
        hnInput.addEventListener('change', function() {
            if (window.allergyChecker) {
                window.allergyChecker.setPatientHN(this.value);
            }
        });

        // Load patient history
        function loadPatientHistory(hn) {
            fetch(`/dispensing/patient-history/${hn}`)
                .then(res => res.json())
                .then(history => {
                    if (history.length > 0) {
                        patientInfo.classList.add('show');
                        patientHistory.innerHTML = history.slice(0, 3).map(h => 
                            `<div>📅 ${new Date(h.dispense_date).toLocaleDateString('th-TH')} - ${h.item_count} รายการ</div>`
                        ).join('');
                    }
                });
        }

        // Close autocomplete when clicking outside
        document.addEventListener('click', function(e) {
            if (e.target !== hnInput) {
                hnAutocomplete.innerHTML = '';
            }
        });

        // Drug selection - show unit
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('drug-select')) {
                const row = e.target.closest('tr');
                const selectedOption = e.target.options[e.target.selectedIndex];
                const unit = selectedOption.getAttribute('data-unit');
                row.querySelector('.unit-display').value = unit || '';
            }
        });

        // Add row
        document.getElementById('addRow').addEventListener('click', function() {
            const tbody = document.getElementById('itemsBody');
            const firstRow = tbody.querySelector('tr');
            const newRow = firstRow.cloneNode(true);
            
            // Reset values
            newRow.querySelectorAll('input, select').forEach(input => {
                input.value = '';
            });
            
            // Show remove button
            newRow.querySelector('.remove-row').style.display = 'inline-block';
            
            tbody.appendChild(newRow);
            updateRemoveButtons();
        });

        // Remove row
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-row') || e.target.closest('.remove-row')) {
                const row = e.target.closest('tr');
                row.remove();
                updateRemoveButtons();
            }
        });

        // Update remove buttons visibility
        function updateRemoveButtons() {
            const rows = document.querySelectorAll('#itemsBody tr');
            rows.forEach((row, index) => {
                const removeBtn = row.querySelector('.remove-row');
                if (rows.length > 1) {
                    removeBtn.style.display = 'inline-block';
                } else {
                    removeBtn.style.display = 'none';
                }
            });
        }

        // Form validation
        document.getElementById('dispensingForm').addEventListener('submit', function(e) {
            const rows = document.querySelectorAll('#itemsBody tr');
            let hasItems = false;

            rows.forEach(row => {
                const drugSelect = row.querySelector('.drug-select');
                const quantityInput = row.querySelector('input[name="quantity[]"]');
                
                if (drugSelect.value && quantityInput.value > 0) {
                    hasItems = true;
                }
            });

            if (!hasItems) {
                e.preventDefault();
                alert('กรุณาเพิ่มรายการยาอย่างน้อย 1 รายการ');
            }
        });
    </script>
</body>
</html>
