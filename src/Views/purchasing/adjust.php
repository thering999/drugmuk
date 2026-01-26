<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ปรับแผนรายไตรมาส - Drugmuk</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Sarabun', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 1400px; margin: 0 auto; }
        
        .header {
            background: white;
            padding: 25px 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .header h1 {
            color: #667eea;
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 15px;
        }
        .header .subtitle {
            color: #666;
            font-size: 16px;
        }
        
        .card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 25px;
        }
        .card h2 {
            color: #667eea;
            font-size: 22px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .filter-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 0;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }
        .form-group select, .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            font-family: 'Sarabun', sans-serif;
            transition: all 0.3s;
        }
        .form-group select:focus, .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .table-container {
            overflow-x: auto;
            margin-top: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }
        thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        th {
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        tbody tr:hover {
            background: #f8f9fa;
        }
        
        .input-adjust {
            width: 100px;
            padding: 8px 10px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
            text-align: center;
        }
        .input-adjust:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .textarea-reason {
            width: 100%;
            min-width: 200px;
            padding: 8px 10px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
            font-family: 'Sarabun', sans-serif;
            resize: vertical;
        }
        
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }
        
        .actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #f0f0f0;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border-left: 4px solid #17a2b8;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border-left: 4px solid #ffc107;
        }
        
        .quarter-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .quarter-tab {
            padding: 10px 20px;
            background: #f8f9fa;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .quarter-tab.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: #667eea;
        }
        .quarter-tab:hover {
            border-color: #667eea;
        }
        
        .summary-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .summary-box h3 {
            font-size: 18px;
            margin-bottom: 15px;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
        }
        .summary-item {
            text-align: center;
        }
        .summary-item .label {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 5px;
        }
        .summary-item .value {
            font-size: 24px;
            font-weight: 700;
        }
        
        .no-data {
            text-align: center;
            padding: 60px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 ปรับแผนรายไตรมาส (Quarterly Adjustment)</h1>
            <div class="subtitle">ปรับแผนการจัดซื้อตามความต้องการจริงในแต่ละไตรมาส</div>
        </div>

        <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success">
            ✅ <?= htmlspecialchars($_SESSION['success']) ?>
            <?php unset($_SESSION['success']); ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-warning">
            ⚠️ <?= htmlspecialchars($_SESSION['error']) ?>
            <?php unset($_SESSION['error']); ?>
        </div>
        <?php endif; ?>

        <!-- Filter Section -->
        <div class="card">
            <h2>🔍 เลือกปีงบประมาณและไตรมาส</h2>
            <form method="GET" action="/purchasing/adjust">
                <div class="filter-section">
                    <div class="form-group">
                        <label for="fiscal_year_id">ปีงบประมาณ *</label>
                        <select id="fiscal_year_id" name="fiscal_year_id" required onchange="this.form.submit()">
                            <option value="">-- เลือกปีงบประมาณ --</option>
                            <?php foreach ($fiscalYears as $fy): ?>
                            <option value="<?= $fy['id'] ?>" <?= (isset($_GET['fiscal_year_id']) && $_GET['fiscal_year_id'] == $fy['id']) ? 'selected' : '' ?>>
                                <?= $fy['year'] ?> (<?= date('d/m/Y', strtotime($fy['start_date'])) ?> - <?= date('d/m/Y', strtotime($fy['end_date'])) ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="quarter">ไตรมาส *</label>
                        <select id="quarter" name="quarter" required onchange="this.form.submit()">
                            <option value="">-- เลือกไตรมาส --</option>
                            <option value="1" <?= (isset($_GET['quarter']) && $_GET['quarter'] == 1) ? 'selected' : '' ?>>ไตรมาส 1 (ต.ค. - ธ.ค.)</option>
                            <option value="2" <?= (isset($_GET['quarter']) && $_GET['quarter'] == 2) ? 'selected' : '' ?>>ไตรมาส 2 (ม.ค. - มี.ค.)</option>
                            <option value="3" <?= (isset($_GET['quarter']) && $_GET['quarter'] == 3) ? 'selected' : '' ?>>ไตรมาส 3 (เม.ย. - มิ.ย.)</option>
                            <option value="4" <?= (isset($_GET['quarter']) && $_GET['quarter'] == 4) ? 'selected' : '' ?>>ไตรมาส 4 (ก.ค. - ก.ย.)</option>
                        </select>
                    </div>
                </div>
            </form>
        </div>

        <?php if (!empty($plans)): ?>
        <!-- Summary Box -->
        <div class="summary-box">
            <h3>📈 สรุปแผนรายไตรมาส</h3>
            <div class="summary-grid">
                <div class="summary-item">
                    <div class="label">จำนวนรายการ</div>
                    <div class="value"><?= count($plans) ?></div>
                </div>
                <div class="summary-item">
                    <div class="label">งบประมาณรวม</div>
                    <div class="value"><?= number_format(array_sum(array_column($plans, 'budget_plan')), 0) ?></div>
                </div>
                <div class="summary-item">
                    <div class="label">ไตรมาสที่</div>
                    <div class="value"><?= $_GET['quarter'] ?? '-' ?></div>
                </div>
            </div>
        </div>

        <!-- Adjustment Form -->
        <form method="POST" action="/purchasing/save-adjustment" id="adjustmentForm">
            <input type="hidden" name="fiscal_year_id" value="<?= $_GET['fiscal_year_id'] ?? '' ?>">
            <input type="hidden" name="quarter" value="<?= $_GET['quarter'] ?? '' ?>">
            
            <div class="card">
                <h2>📝 ปรับแผนการจัดซื้อ</h2>
                
                <div class="alert alert-info">
                    💡 <strong>คำแนะนำ:</strong> กรอกจำนวนที่ต้องการปรับและเหตุผลในการปรับแผน (เฉพาะรายการที่ต้องการปรับเท่านั้น)
                </div>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 80px;">รหัสยา</th>
                                <th>ชื่อยา</th>
                                <th style="width: 100px;">แผนเดิม</th>
                                <th style="width: 100px;">งบประมาณเดิม</th>
                                <th style="width: 100px;">จำนวนที่ปรับ *</th>
                                <th style="width: 100px;">งบที่ปรับ *</th>
                                <th style="width: 250px;">เหตุผลในการปรับ</th>
                                <th style="width: 60px;">ABC</th>
                                <th style="width: 60px;">VEN</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($plans as $index => $plan): ?>
                            <tr>
                                <td><?= htmlspecialchars($plan['drug_code']) ?></td>
                                <td><strong><?= htmlspecialchars($plan['drug_name']) ?></strong></td>
                                <td class="text-right"><?= number_format($plan['quantity_plan']) ?></td>
                                <td class="text-right"><?= number_format($plan['budget_plan'], 2) ?></td>
                                <td>
                                    <input type="hidden" name="adjustments[<?= $index ?>][plan_id]" value="<?= $plan['id'] ?>">
                                    <input type="number" 
                                           name="adjustments[<?= $index ?>][adjusted_quantity]" 
                                           class="input-adjust qty-adjust" 
                                           min="0"
                                           placeholder="0"
                                           data-index="<?= $index ?>"
                                           data-price="<?= $plan['budget_plan'] / max($plan['quantity_plan'], 1) ?>">
                                </td>
                                <td>
                                    <input type="number" 
                                           name="adjustments[<?= $index ?>][adjusted_budget]" 
                                           class="input-adjust budget-adjust-<?= $index ?>" 
                                           min="0"
                                           step="0.01"
                                           placeholder="0.00"
                                           readonly>
                                </td>
                                <td>
                                    <textarea name="adjustments[<?= $index ?>][adjustment_reason]" 
                                              class="textarea-reason" 
                                              rows="2"
                                              placeholder="ระบุเหตุผล..."></textarea>
                                </td>
                                <td class="text-center">
                                    <span style="padding: 3px 8px; border-radius: 3px; background: <?= $plan['abc_class'] === 'A' ? '#f44336' : ($plan['abc_class'] === 'B' ? '#ff9800' : '#4caf50') ?>; color: white;">
                                        <?= $plan['abc_class'] ?? 'C' ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span style="padding: 3px 8px; border-radius: 3px; background: <?= $plan['ven_class'] === 'V' ? '#e91e63' : ($plan['ven_class'] === 'E' ? '#2196f3' : '#9e9e9e') ?>; color: white;">
                                        <?= $plan['ven_class'] ?? 'N' ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="actions">
                <a href="/purchasing" class="btn btn-secondary">← กลับ</a>
                <button type="submit" class="btn btn-success">✅ บันทึกการปรับแผน</button>
            </div>
        </form>
        <?php else: ?>
        <div class="card">
            <div class="no-data">
                <p style="font-size: 18px;">📭 กรุณาเลือกปีงบประมาณและไตรมาส</p>
                <p style="margin-top: 10px; color: #666;">หรือยังไม่มีแผนซื้อสำหรับปีงบประมาณที่เลือก</p>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
        // Calculate budget when quantity changes
        document.querySelectorAll('.qty-adjust').forEach(input => {
            input.addEventListener('input', function() {
                const index = this.dataset.index;
                const price = parseFloat(this.dataset.price);
                const qty = parseFloat(this.value) || 0;
                const budget = qty * price;
                
                const budgetInput = document.querySelector(`.budget-adjust-${index}`);
                if (budgetInput) {
                    budgetInput.value = budget.toFixed(2);
                }
            });
        });

        // Form validation
        document.getElementById('adjustmentForm')?.addEventListener('submit', function(e) {
            let hasAdjustment = false;
            
            document.querySelectorAll('.qty-adjust').forEach(input => {
                if (parseFloat(input.value) > 0) {
                    hasAdjustment = true;
                }
            });

            if (!hasAdjustment) {
                e.preventDefault();
                alert('⚠️ กรุณากรอกจำนวนที่ต้องการปรับอย่างน้อย 1 รายการ');
                return false;
            }

            if (!confirm('✅ ยืนยันการบันทึกการปรับแผนรายไตรมาส?')) {
                e.preventDefault();
                return false;
            }
        });
    </script>
</body>
</html>
