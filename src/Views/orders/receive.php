<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รับยาเข้าคลัง - Drugmuk</title>
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
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h1 { color: #667eea; font-size: 28px; font-weight: 600; }
        .header .order-info {
            text-align: right;
            color: #666;
        }
        .header .order-info strong { color: #333; }
        
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
        
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            font-family: 'Sarabun', sans-serif;
            transition: all 0.3s;
        }
        .form-group input:focus, .form-group textarea:focus {
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
        
        .input-small {
            width: 100px;
            padding: 8px 10px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
            text-align: center;
        }
        .input-medium {
            width: 150px;
            padding: 8px 10px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
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
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #f0f0f0;
        }
        
        .history-table {
            margin-top: 20px;
        }
        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        .badge-success {
            background: #d4edda;
            color: #155724;
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
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border-left: 4px solid #ffc107;
        }
        
        .summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .summary-item {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
        .summary-item .label {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 5px;
        }
        .summary-item .value {
            font-size: 28px;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📦 รับยาเข้าคลัง</h1>
            <div class="order-info">
                <div><strong>เลขที่ใบสั่งซื้อ:</strong> <?= htmlspecialchars($order['order_no']) ?></div>
                <div><strong>ผู้จำหน่าย:</strong> <?= htmlspecialchars($order['supplier_name'] ?? 'N/A') ?></div>
                <div><strong>วันที่สั่งซื้อ:</strong> <?= date('d/m/Y', strtotime($order['order_date'])) ?></div>
            </div>
        </div>

        <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-info">
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

        <!-- Receive History -->
        <?php if (!empty($receiveHistory)): ?>
        <div class="card">
            <h2>📋 ประวัติการรับยา</h2>
            <div class="table-container history-table">
                <table>
                    <thead>
                        <tr>
                            <th>วันที่รับ</th>
                            <th>ผู้รับ</th>
                            <th>จำนวนรายการ</th>
                            <th>จำนวนที่รับ</th>
                            <th>หมายเหตุ</th>
                            <th>การดำเนินการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($receiveHistory as $history): ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($history['receive_date'])) ?></td>
                            <td><?= htmlspecialchars($history['received_by_name']) ?></td>
                            <td><?= $history['items_count'] ?> รายการ</td>
                            <td><strong><?= number_format($history['total_received']) ?></strong></td>
                            <td><?= htmlspecialchars($history['notes'] ?? '-') ?></td>
                            <td>
                                <a href="/orders/print-receive/<?= $history['id'] ?>" class="btn btn-secondary" style="padding: 5px 10px; font-size: 14px;" target="_blank">
                                    🖨️ พิมพ์
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Receive Form -->
        <form method="POST" action="/orders/store-receive" id="receiveForm">
            <?php echo \App\Core\CSRF::field(); ?>
            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
            
            <div class="card">
                <h2>📝 บันทึกการรับยา</h2>
                
                <div class="form-group">
                    <label for="receive_date">วันที่รับยา *</label>
                    <input type="date" id="receive_date" name="receive_date" value="<?= date('Y-m-d') ?>" required>
                </div>

                <div class="form-group">
                    <label for="notes">หมายเหตุ</label>
                    <textarea id="notes" name="notes" rows="3" placeholder="ระบุหมายเหตุ (ถ้ามี)"></textarea>
                </div>
            </div>

            <div class="card">
                <h2>📋 รายการยาที่สั่งซื้อ</h2>
                
                <div class="alert alert-info">
                    💡 <strong>คำแนะนำ:</strong> กรอกจำนวนที่รับจริง, Lot Number และวันหมดอายุสำหรับแต่ละรายการ
                </div>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>รหัสยา</th>
                                <th>ชื่อยา</th>
                                <th>จำนวนที่สั่ง</th>
                                <th>จำนวนที่รับ *</th>
                                <th>Lot Number *</th>
                                <th>วันหมดอายุ *</th>
                                <th>ราคา/หน่วย</th>
                                <th>รวม</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $index => $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['drug_code']) ?></td>
                                <td><strong><?= htmlspecialchars($item['drug_name']) ?></strong></td>
                                <td><?= number_format($item['quantity']) ?></td>
                                <td>
                                    <input type="hidden" name="items[<?= $index ?>][order_item_id]" value="<?= $item['id'] ?>">
                                    <input type="hidden" name="items[<?= $index ?>][drug_id]" value="<?= $item['drug_id'] ?>">
                                    <input type="number" 
                                           name="items[<?= $index ?>][quantity_received]" 
                                           class="input-small qty-received" 
                                           min="0" 
                                           max="<?= $item['quantity'] ?>"
                                           value="<?= $item['quantity'] ?>"
                                           data-price="<?= $item['unit_price'] ?>"
                                           data-index="<?= $index ?>">
                                </td>
                                <td>
                                    <input type="text" 
                                           name="items[<?= $index ?>][lot_no]" 
                                           class="input-medium" 
                                           placeholder="LOT-XXXX"
                                           required>
                                </td>
                                <td>
                                    <input type="date" 
                                           name="items[<?= $index ?>][expire_date]" 
                                           class="input-medium"
                                           min="<?= date('Y-m-d') ?>"
                                           required>
                                </td>
                                <td><?= number_format($item['unit_price'], 2) ?></td>
                                <td class="item-total-<?= $index ?>">
                                    <?= number_format($item['quantity'] * $item['unit_price'], 2) ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr style="background: #f8f9fa; font-weight: 600;">
                                <td colspan="7" style="text-align: right;">รวมทั้งหมด:</td>
                                <td id="grandTotal"><?= number_format($order['total_amount'], 2) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="actions">
                <a href="/orders/show/<?= $order['id'] ?>" class="btn btn-secondary">← ยกเลิก</a>
                <button type="submit" class="btn btn-primary">✅ บันทึกการรับยา</button>
            </div>
        </form>
    </div>

    <script>
        // Calculate totals when quantity changes
        document.querySelectorAll('.qty-received').forEach(input => {
            input.addEventListener('input', function() {
                const index = this.dataset.index;
                const price = parseFloat(this.dataset.price);
                const qty = parseFloat(this.value) || 0;
                const total = qty * price;
                
                document.querySelector(`.item-total-${index}`).textContent = 
                    total.toLocaleString('th-TH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                
                updateGrandTotal();
            });
        });

        function updateGrandTotal() {
            let grandTotal = 0;
            document.querySelectorAll('.qty-received').forEach(input => {
                const price = parseFloat(input.dataset.price);
                const qty = parseFloat(input.value) || 0;
                grandTotal += qty * price;
            });
            
            document.getElementById('grandTotal').textContent = 
                grandTotal.toLocaleString('th-TH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }

        // Form validation
        document.getElementById('receiveForm').addEventListener('submit', function(e) {
            let hasReceived = false;
            document.querySelectorAll('.qty-received').forEach(input => {
                if (parseFloat(input.value) > 0) {
                    hasReceived = true;
                }
            });

            if (!hasReceived) {
                e.preventDefault();
                alert('⚠️ กรุณากรอกจำนวนที่รับอย่างน้อย 1 รายการ');
                return false;
            }

            if (!confirm('✅ ยืนยันการบันทึกการรับยาเข้าคลัง?')) {
                e.preventDefault();
                return false;
            }
        });
    </script>
</body>
</html>
