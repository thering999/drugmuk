<?php
/**
 * Barcode Label Preview View
 * 
 * Display and print drug labels with barcode and QR code
 */

$drug = $label['drug'];
$inventory = $label['inventory'];
$quantity = $label['quantity'];
$barcode = $label['barcode'];
$qrCode = $label['qr_code'];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Label Preview - <?= htmlspecialchars($drug['name']) ?></title>
    <link rel="stylesheet" href="/css/main.css">
    <style>
        @media print {
            .no-print { display: none; }
            body { margin: 0; padding: 0; }
        }
        
        .label-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
        }
        
        .drug-label {
            border: 2px solid #333;
            padding: 15px;
            margin: 10px 0;
            background: white;
            page-break-after: always;
            width: 10cm;
            height: 5cm;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        
        .label-header {
            border-bottom: 1px solid #ccc;
            padding-bottom: 8px;
            margin-bottom: 8px;
        }
        
        .label-header h3 {
            margin: 0;
            font-size: 16px;
            font-weight: bold;
        }
        
        .label-header p {
            margin: 2px 0;
            font-size: 12px;
            color: #666;
        }
        
        .label-body {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex: 1;
        }
        
        .barcode-section {
            flex: 1;
            text-align: center;
        }
        
        .barcode-section img {
            max-width: 100%;
            height: auto;
        }
        
        .barcode-section p {
            margin: 5px 0;
            font-size: 14px;
            font-weight: bold;
        }
        
        .qr-section {
            width: 100px;
            text-align: center;
        }
        
        .qr-section img {
            width: 80px;
            height: 80px;
        }
        
        .label-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 5px;
            font-size: 11px;
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px solid #ccc;
        }
        
        .label-info div {
            padding: 2px;
        }
        
        .label-info strong {
            font-weight: bold;
        }
        
        .print-controls {
            margin: 20px 0;
            text-align: center;
        }
        
        .btn {
            padding: 10px 20px;
            margin: 0 5px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .btn-primary {
            background: #007bff;
            color: white;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
    </style>
</head>
<body>
    <div class="label-container">
        <div class="print-controls no-print">
            <h2>Label Preview</h2>
            <button class="btn btn-primary" onclick="window.print()">🖨️ Print</button>
            <button class="btn btn-secondary" onclick="window.close()">❌ Close</button>
        </div>
        
        <?php for ($i = 0; $i < $quantity; $i++): ?>
        <div class="drug-label">
            <div class="label-header">
                <h3><?= htmlspecialchars($drug['name']) ?></h3>
                <p><?= htmlspecialchars($drug['generic_name'] ?? '') ?></p>
                <p><?= htmlspecialchars($drug['category_name'] ?? '') ?></p>
            </div>
            
            <div class="label-body">
                <div class="barcode-section">
                    <img src="<?= $barcode ?>" alt="Barcode">
                    <p><?= htmlspecialchars($drug['code']) ?></p>
                </div>
                
                <div class="qr-section">
                    <img src="<?= $qrCode ?>" alt="QR Code">
                </div>
            </div>
            
            <div class="label-info">
                <?php if ($inventory): ?>
                <div><strong>Lot:</strong> <?= htmlspecialchars($inventory['lot_no']) ?></div>
                <div><strong>Exp:</strong> <?= date('m/Y', strtotime($inventory['expire_date'])) ?></div>
                <div><strong>Qty:</strong> <?= htmlspecialchars($inventory['quantity']) ?></div>
                <?php endif; ?>
                <div><strong>Unit:</strong> <?= htmlspecialchars($drug['unit'] ?? 'tablet') ?></div>
                <div><strong>Price:</strong> ฿<?= number_format($drug['price'] ?? 0, 2) ?></div>
                <div><strong>Printed:</strong> <?= date('d/m/Y H:i') ?></div>
            </div>
        </div>
        <?php endfor; ?>
    </div>
</body>
</html>
