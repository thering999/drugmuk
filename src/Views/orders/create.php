<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สร้างใบสั่งซื้อ - Drugmuk</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Sarabun', 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 1200px; margin: 0 auto; }
        .header {
            background: white;
            padding: 20px 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            display: flex; justify-content: space-between; align-items: center;
        }
        .header h1 { color: #667eea; font-size: 24px; }
        .form-container {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .form-row { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; color: #4a5568; font-size: 14px; }
        .form-control {
            width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px;
            transition: all 0.2s;
        }
        .form-control:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
        
        /* Table Styles */
        .items-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .items-table th { background: #f7fafc; padding: 12px; text-align: left; font-size: 13px; color: #4a5568; border-bottom: 2px solid #e2e8f0; }
        .items-table td { padding: 10px; border-bottom: 1px solid #edf2f7; vertical-align: top; }
        .items-table tr:hover { background: #f8fbff; }
        
        .btn {
            padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer;
            font-weight: 600; display: inline-flex; align-items: center; gap: 8px; text-decoration: none;
            transition: transform 0.1s;
        }
        .btn:active { transform: scale(0.98); }
        .btn-primary { background: #667eea; color: white; }
        .btn-success { background: #48bb78; color: white; }
        .btn-danger { background: #fc8181; color: white; padding: 8px; border-radius: 5px; }
        .btn-secondary { background: #cbd5e0; color: #4a5568; }
        
        .remove-row { color: #e53e3e; cursor: pointer; font-size: 18px; }
        .total-section { 
            display: flex; justify-content: flex-end; margin-top: 20px; padding-top: 20px; border-top: 2px solid #edf2f7;
        }
        .total-box { text-align: right; }
        .total-label { font-size: 14px; color: #718096; }
        .total-amount { font-size: 32px; font-weight: 800; color: #2d3748; }

        /* Auto-complete style dropdown */
        select.drug-select { font-family: 'Courier New', monospace; }
    </style>
</head>
<body>
    <div class="container">
        <form method="POST" action="/orders/store" id="orderForm">
            <?php echo \App\Core\CSRF::field(); ?>
            
            <div class="header">
                <div>
                    <h1>📝 สร้างใบสั่งซื้อใหม่ (New Purchase Order)</h1>
                    <p style="color: #718096; font-size: 14px;">สร้างรายการสั่งซื้อยาไปยังบริษัทผู้ผลิต/ผู้จำหน่าย</p>
                </div>
                <div>
                    <a href="/orders" class="btn btn-secondary">✖ ยกเลิก</a>
                    <button type="submit" class="btn btn-primary" onclick="return validateForm()">💾 บันทึกใบสั่งซื้อ</button>
                </div>
            </div>

            <div class="form-container">
                <!-- Header Info -->
                <div class="form-row">
                    <div class="form-group">
                        <label>PO Number (เลขที่ใบสั่งซื้อ)</label>
                        <input type="text" name="order_no" class="form-control" value="PO<?= date('YmdHis') ?>" readonly style="background: #f7fafc; color: #718096;">
                    </div>
                    <div class="form-group">
                        <label>Supplier (ผู้จำหน่าย) *</label>
                        <select name="supplier_id" class="form-control" required onchange="filterDrugsBySupplier(this.value)">
                            <option value="">-- เลือกผู้จำหน่าย --</option>
                            <?php foreach ($suppliers as $supplier): ?>
                                <option value="<?= $supplier['id'] ?>"><?= htmlspecialchars($supplier['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Order Date (วันที่สั่งซื้อ) *</label>
                        <input type="date" name="order_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Expected Delivery (กำหนดส่งมอบ)</label>
                        <input type="date" name="delivery_date" class="form-control">
                    </div>
                    <div class="form-group" style="grid-column: span 2;">
                        <label>Notes (หมายเหตุ)</label>
                        <input type="text" name="notes" class="form-control" placeholder="ระบุเงื่อนไขการส่งมอบ หรือรายละเอียดเพิ่มเติม...">
                    </div>
                </div>

                <!-- Items -->
                <h3 style="margin-top: 30px; border-bottom: 2px solid #eee; padding-bottom: 10px; color: #2d3748;">
                    📦 รายการสินค้า (Items)
                </h3>
                
                <table class="items-table">
                    <thead>
                        <tr>
                            <th style="width: 40%;">รายการยา (Drug Name)</th>
                            <th style="width: 15%;">ราคาต่อหน่วย</th>
                            <th style="width: 15%;">จำนวน (Qty)</th>
                            <th style="width: 20%;">รวม (Total)</th>
                            <th style="width: 5%;"></th>
                        </tr>
                    </thead>
                    <tbody id="itemsTableBody">
                        <!-- Rows will be added here -->
                    </tbody>
                </table>

                <div style="margin-top: 20px;">
                    <button type="button" class="btn btn-success p-2" onclick="addNewRow()">
                        <i class="fas fa-plus"></i> เพิ่มรายการ
                    </button>
                </div>

                <div class="total-section">
                    <div class="total-box">
                        <div class="total-label">ยอดรวมทั้งสิ้น (Grand Total)</div>
                        <div class="total-amount">฿<span id="grandTotal">0.00</span></div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Hidden Template -->
    <template id="drugOptions">
        <?php foreach ($drugs as $drug): ?>
            <option value="<?= $drug['id'] ?>" data-price="<?= $drug['cost_price'] ?>" data-unit="<?= $drug['unit'] ?>">
                <?= htmlspecialchars($drug['name']) ?> (<?= $drug['code'] ?>)
            </option>
        <?php endforeach; ?>
    </template>

    <script>
        // Store drugs data for easy access
        const drugsData = <?php echo json_encode($drugs); ?>;
        
        function addNewRow(prefilledData = null) {
            const tbody = document.getElementById('itemsTableBody');
            const rowIdx = tbody.children.length;
            const tr = document.createElement('tr');
            
            let drugOptionsHtml = '<option value="">-- เลือกยา --</option>';
            drugsData.forEach(d => {
                const selected = (prefilledData && prefilledData.drugId == d.id) ? 'selected' : '';
                drugOptionsHtml += `<option value="${d.id}" data-price="${d.price}" ${selected}>${d.name} (${d.code})</option>`;
            });

            tr.innerHTML = `
                <td>
                    <select name="items[${rowIdx}][drug_id]" class="form-control drug-select" onchange="updateRowPrice(this, ${rowIdx})" required>
                        ${drugOptionsHtml}
                    </select>
                    <div class="drug-info" id="info_${rowIdx}" style="font-size: 12px; color: #718096; margin-top: 4px;"></div>
                </td>
                <td>
                    <input type="number" name="items[${rowIdx}][unit_price]" class="form-control price-input" 
                           step="0.01" oninput="calcRowTotal(${rowIdx})" required>
                </td>
                <td>
                    <input type="number" name="items[${rowIdx}][quantity]" class="form-control qty-input" 
                           value="${prefilledData ? prefilledData.qty : 1}" min="1" oninput="calcRowTotal(${rowIdx})" required>
                </td>
                <td>
                    <input type="text" class="form-control total-input" id="total_${rowIdx}" readonly style="background: #f7fafc; font-weight: bold;">
                </td>
                <td style="text-align: center; vertical-align: middle;">
                    <i class="fas fa-trash-alt remove-row" onclick="removeRow(this)"></i>
                </td>
            `;
            tbody.appendChild(tr);

            // If prefilled, trigger update to set prices
            if (prefilledData) {
                const select = tr.querySelector('select');
                updateRowPrice(select, rowIdx);
            }
        }

        function removeRow(btn) {
            btn.closest('tr').remove();
            calcGrandTotal();
        }

        function updateRowPrice(select, idx) {
            const option = select.options[select.selectedIndex];
            const price = option.getAttribute('data-price') || 0;
            const row = select.closest('tr');
            
            row.querySelector('.price-input').value = price;
            
            // Fetch extra info via API (optional, but good for real-time stock check)
            // For now, we use local data
            
            calcRowTotal(idx);
        }

        function calcRowTotal(idx) {
            const row = document.querySelectorAll('#itemsTableBody tr')[idx]; // This might fail if rows removed. Better to use relative passing.
        }
        
        // Re-write calcRowLogic to be robust against deletion
        document.getElementById('itemsTableBody').addEventListener('input', function(e) {
            if(e.target.classList.contains('qty-input') || e.target.classList.contains('price-input')) {
                const row = e.target.closest('tr');
                const qty = parseFloat(row.querySelector('.qty-input').value) || 0;
                const price = parseFloat(row.querySelector('.price-input').value) || 0;
                const total = qty * price;
                row.querySelector('.total-input').value = total.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                calcGrandTotal();
            }
        });

        function calcGrandTotal() {
            let total = 0;
            document.querySelectorAll('#itemsTableBody tr').forEach(row => {
               const val = row.querySelector('.total-input').value.replace(/,/g, '');
               total += parseFloat(val) || 0;
            });
            document.getElementById('grandTotal').textContent = total.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }

        function filterDrugsBySupplier(supplierId) {
            // Optional: Filter drugs list based on supplier contract
            // Currently not implemented as 1 drug can have multiple suppliers
        }

        function validateForm() {
            const rows = document.querySelectorAll('#itemsTableBody tr');
            if(rows.length === 0) {
                alert('กรุณาเพิ่มรายการยาอย่างน้อย 1 รายการ');
                return false;
            }
            return true;
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            const drugId = urlParams.get('drug_id');
            const qty = urlParams.get('qty');
            
            if (drugId && qty) {
                // Auto-add row from URL
                addNewRow({ drugId: drugId, qty: qty });
            } else {
                addNewRow(); // Add empty row
            }
        });

    </script>
</body>
</html>
