<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ใบรับยาเข้าคลัง - <?= htmlspecialchars($receive['order_no']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Sarabun', sans-serif;
            padding: 20px;
            background: white;
        }
        .print-container {
            max-width: 210mm;
            margin: 0 auto;
            background: white;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #333;
        }
        .header h1 {
            font-size: 28px;
            color: #333;
            margin-bottom: 10px;
        }
        .header .subtitle {
            font-size: 18px;
            color: #666;
        }
        
        .info-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        .info-box {
            border: 2px solid #e0e0e0;
            padding: 15px;
            border-radius: 8px;
        }
        .info-box h3 {
            font-size: 16px;
            color: #667eea;
            margin-bottom: 10px;
            padding-bottom: 8px;
            border-bottom: 1px solid #e0e0e0;
        }
        .info-row {
            display: flex;
            margin-bottom: 8px;
        }
        .info-row .label {
            width: 140px;
            font-weight: 600;
            color: #333;
        }
        .info-row .value {
            flex: 1;
            color: #666;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        thead {
            background: #f8f9fa;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border: 1px solid #dee2e6;
        }
        th {
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }
        td {
            font-size: 14px;
            color: #666;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        
        .summary {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 16px;
        }
        .summary-row.total {
            font-size: 20px;
            font-weight: 700;
            color: #667eea;
            padding-top: 10px;
            border-top: 2px solid #dee2e6;
        }
        
        .signatures {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 30px;
            margin-top: 60px;
        }
        .signature-box {
            text-align: center;
        }
        .signature-line {
            border-top: 1px solid #333;
            margin-top: 60px;
            padding-top: 10px;
        }
        .signature-label {
            font-size: 14px;
            color: #666;
            margin-top: 5px;
        }
        
        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
            color: #999;
            font-size: 12px;
        }
        
        .no-print {
            text-align: center;
            margin-bottom: 20px;
        }
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            margin: 0 5px;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        @media print {
            .no-print {
                display: none;
            }
            body {
                padding: 0;
            }
            .print-container {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" class="btn btn-primary">🖨️ พิมพ์เอกสาร</button>
        <a href="/orders/show/<?= $receive['order_id'] ?? '#' ?>" class="btn btn-secondary">← กลับ</a>
    </div>

    <div class="print-container">
        <div class="header">
            <h1>ใบรับยาเข้าคลัง</h1>
            <div class="subtitle">DRUG RECEIVING DOCUMENT</div>
        </div>

        <div class="info-section">
            <div class="info-box">
                <h3>ข้อมูลใบสั่งซื้อ</h3>
                <div class="info-row">
                    <span class="label">เลขที่ใบสั่งซื้อ:</span>
                    <span class="value"><strong><?= htmlspecialchars($receive['order_no']) ?></strong></span>
                </div>
                <div class="info-row">
                    <span class="label">ผู้จำหน่าย:</span>
                    <span class="value"><?= htmlspecialchars($receive['supplier_name'] ?? 'N/A') ?></span>
                </div>
                <div class="info-row">
                    <span class="label">วันที่รับยา:</span>
                    <span class="value"><?= date('d/m/Y', strtotime($receive['receive_date'])) ?></span>
                </div>
            </div>

            <div class="info-box">
                <h3>ข้อมูลผู้รับ</h3>
                <div class="info-row">
                    <span class="label">ผู้รับยา:</span>
                    <span class="value"><?= htmlspecialchars($receive['received_by_name']) ?></span>
                </div>
                <div class="info-row">
                    <span class="label">วันที่บันทึก:</span>
                    <span class="value"><?= date('d/m/Y H:i', strtotime($receive['created_at'])) ?> น.</span>
                </div>
                <div class="info-row">
                    <span class="label">หมายเหตุ:</span>
                    <span class="value"><?= htmlspecialchars($receive['notes'] ?? '-') ?></span>
                </div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th class="text-center" style="width: 50px;">ลำดับ</th>
                    <th style="width: 100px;">รหัสยา</th>
                    <th>ชื่อยา</th>
                    <th class="text-center" style="width: 100px;">จำนวนที่สั่ง</th>
                    <th class="text-center" style="width: 100px;">จำนวนที่รับ</th>
                    <th style="width: 120px;">Lot Number</th>
                    <th class="text-center" style="width: 100px;">วันหมดอายุ</th>
                    <th class="text-right" style="width: 100px;">ราคา/หน่วย</th>
                    <th class="text-right" style="width: 120px;">รวม (บาท)</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $totalAmount = 0;
                $totalQty = 0;
                foreach ($items as $index => $item): 
                    $itemTotal = $item['quantity_received'] * $item['unit_price'];
                    $totalAmount += $itemTotal;
                    $totalQty += $item['quantity_received'];
                ?>
                <tr>
                    <td class="text-center"><?= $index + 1 ?></td>
                    <td><?= htmlspecialchars($item['drug_code']) ?></td>
                    <td><strong><?= htmlspecialchars($item['drug_name']) ?></strong></td>
                    <td class="text-center"><?= number_format($item['ordered_quantity']) ?></td>
                    <td class="text-center"><strong><?= number_format($item['quantity_received']) ?></strong></td>
                    <td><?= htmlspecialchars($item['lot_no']) ?></td>
                    <td class="text-center"><?= date('d/m/Y', strtotime($item['expire_date'])) ?></td>
                    <td class="text-right"><?= number_format($item['unit_price'], 2) ?></td>
                    <td class="text-right"><strong><?= number_format($itemTotal, 2) ?></strong></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background: #f8f9fa; font-weight: 600;">
                    <td colspan="4" class="text-right">รวมทั้งหมด:</td>
                    <td class="text-center"><strong><?= number_format($totalQty) ?></strong></td>
                    <td colspan="3" class="text-right">ยอดรวมสุทธิ:</td>
                    <td class="text-right"><strong><?= number_format($totalAmount, 2) ?></strong></td>
                </tr>
            </tfoot>
        </table>

        <div class="summary">
            <div class="summary-row">
                <span>จำนวนรายการทั้งหมด:</span>
                <span><strong><?= count($items) ?> รายการ</strong></span>
            </div>
            <div class="summary-row">
                <span>จำนวนยาที่รับทั้งหมด:</span>
                <span><strong><?= number_format($totalQty) ?> หน่วย</strong></span>
            </div>
            <div class="summary-row total">
                <span>มูลค่ารวมทั้งสิ้น:</span>
                <span><?= number_format($totalAmount, 2) ?> บาท</span>
            </div>
        </div>

        <div class="signatures">
            <div class="signature-box">
                <div class="signature-line">
                    <?= htmlspecialchars($receive['received_by_name']) ?>
                </div>
                <div class="signature-label">ผู้รับยา</div>
                <div class="signature-label">วันที่: <?= date('d/m/Y', strtotime($receive['receive_date'])) ?></div>
            </div>

            <div class="signature-box">
                <div class="signature-line">
                    ........................................................
                </div>
                <div class="signature-label">ผู้ตรวจสอบ</div>
                <div class="signature-label">วันที่: ....../....../......</div>
            </div>

            <div class="signature-box">
                <div class="signature-line">
                    ........................................................
                </div>
                <div class="signature-label">หัวหน้าเภสัชกร</div>
                <div class="signature-label">วันที่: ....../....../......</div>
            </div>
        </div>

        <div class="footer">
            <p>เอกสารนี้พิมพ์จากระบบ Drugmuk - ระบบบริหารคลังเวชภัณฑ์ยาออนไลน์</p>
            <p>พิมพ์เมื่อ: <?= date('d/m/Y H:i:s') ?> น.</p>
        </div>
    </div>

    <script>
        // Auto print on load (optional)
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>
