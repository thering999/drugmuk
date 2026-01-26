<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h2>คลังสินค้า (Inventory)</h2>
        <a href="/inventory/receive" class="btn">รับเวชภัณฑ์เข้าคลัง</a>
    </div>

    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background-color: #f3f4f6; text-align: left;">
                <th style="padding: 0.75rem;">รหัสยา</th>
                <th style="padding: 0.75rem;">ชื่อยา</th>
                <th style="padding: 0.75rem;">Lot No.</th>
                <th style="padding: 0.75rem;">วันหมดอายุ</th>
                <th style="padding: 0.75rem;">จำนวนคงเหลือ</th>
                <th style="padding: 0.75rem;">หน่วย</th>
                <th style="padding: 0.75rem;">สถานะ</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($inventory)): ?>
                <tr>
                    <td colspan="7" style="padding: 1rem; text-align: center;">ไม่พบข้อมูลสินค้าในคลัง</td>
                </tr>
            <?php else: ?>
                <?php foreach ($inventory as $item): ?>
                    <tr style="border-bottom: 1px solid #e5e7eb;">
                        <td style="padding: 0.75rem;"><?php echo $item['drug_code']; ?></td>
                        <td style="padding: 0.75rem;"><?php echo $item['drug_name']; ?></td>
                        <td style="padding: 0.75rem;"><?php echo $item['lot_no']; ?></td>
                        <td style="padding: 0.75rem;"><?php echo $item['expire_date']; ?></td>
                        <td style="padding: 0.75rem; font-weight: bold;"><?php echo number_format($item['quantity']); ?></td>
                        <td style="padding: 0.75rem;"><?php echo $item['unit']; ?></td>
                        <td style="padding: 0.75rem;">
                            <?php 
                                // Simple expiry check
                                $expire = strtotime($item['expire_date']);
                                $now = time();
                                $diff = $expire - $now;
                                $days = floor($diff / (60 * 60 * 24));

                                if ($days < 0) {
                                    echo "<span style='color: var(--danger);'>หมดอายุ</span>";
                                } elseif ($days < 90) {
                                    echo "<span style='color: #f59e0b;'>ใกล้หมดอายุ ($days วัน)</span>";
                                } else {
                                    echo "<span style='color: var(--success);'>ปกติ</span>";
                                }
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
