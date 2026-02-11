<?php require_once __DIR__ . '/../layouts/header_responsive.php'; ?>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h2><i class="fas fa-warehouse"></i> คลังสินค้า (Inventory)</h2>
        <a href="/inventory/receive" class="btn" style="background-color: var(--primary-color, #667eea); color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none;">
            <i class="fas fa-plus"></i> รับเวชภัณฑ์เข้าคลัง
        </a>
    </div>

    <div style="overflow-x: auto;"> <!-- Responsive Table Container -->
        <table style="width: 100%; border-collapse: collapse; min-width: 800px;">
            <thead>
                <tr style="background-color: #f3f4f6; text-align: left;">
                    <th style="padding: 12px; border-bottom: 2px solid #e5e7eb;">รหัสยา</th>
                    <th style="padding: 12px; border-bottom: 2px solid #e5e7eb;">ชื่อยา</th>
                    <th style="padding: 12px; border-bottom: 2px solid #e5e7eb;">Lot No.</th>
                    <th style="padding: 12px; border-bottom: 2px solid #e5e7eb;">วันหมดอายุ</th>
                    <th style="padding: 12px; border-bottom: 2px solid #e5e7eb;">คงเหลือ</th>
                    <th style="padding: 12px; border-bottom: 2px solid #e5e7eb;">หน่วย</th>
                    <th style="padding: 12px; border-bottom: 2px solid #e5e7eb;">สถานะ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($inventory)): ?>
                    <tr>
                        <td colspan="7" style="padding: 2rem; text-align: center; color: #6b7280;">ไม่พบข้อมูลสินค้าในคลัง</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($inventory as $item): ?>
                        <tr style="border-bottom: 1px solid #e5e7eb;">
                            <td style="padding: 12px;"><?php echo htmlspecialchars($item['drug_code']); ?></td>
                            <td style="padding: 12px; font-weight: 500;"><?php echo htmlspecialchars($item['drug_name']); ?></td>
                            <td style="padding: 12px; font-family: monospace; color: #4b5563;"><?php echo htmlspecialchars($item['lot_no']); ?></td>
                            <td style="padding: 12px;"><?php echo date('d/m/Y', strtotime($item['expire_date'])); ?></td>
                            <td style="padding: 12px; font-weight: bold; color: #2563eb;"><?php echo number_format($item['quantity']); ?></td>
                            <td style="padding: 12px;"><?php echo htmlspecialchars($item['unit']); ?></td>
                            <td style="padding: 12px;">
                                <?php 
                                    // Simple expiry check
                                    $expire = strtotime($item['expire_date']);
                                    $now = time();
                                    $diff = $expire - $now;
                                    $days = floor($diff / (60 * 60 * 24));

                                    if ($days < 0) {
                                        echo "<span class='badge' style='background-color: #fee2e2; color: #991b1b; padding: 4px 8px; border-radius: 4px; font-size: 12px;'>หมดอายุ</span>";
                                    } elseif ($days < 90) {
                                        echo "<span class='badge' style='background-color: #fef3c7; color: #92400e; padding: 4px 8px; border-radius: 4px; font-size: 12px;'>ใกล้หมดอายุ ($days วัน)</span>";
                                    } else {
                                        echo "<span class='badge' style='background-color: #d1fae5; color: #065f46; padding: 4px 8px; border-radius: 4px; font-size: 12px;'>ปกติ</span>";
                                    }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</main>
</body>
</html>
