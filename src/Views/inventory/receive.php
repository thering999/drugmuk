<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="card" style="max-width: 600px; margin: 0 auto;">
    <h2>รับเวชภัณฑ์เข้าคลัง</h2>
    
    <form action="/inventory/store-receive" method="POST">
        <div class="form-group">
            <label for="drug_id">รายการยา</label>
            <select id="drug_id" name="drug_id" required style="width: 100%; padding: 0.5rem; border-radius: var(--border-radius); border: 1px solid #d1d5db;">
                <option value="">-- เลือกรายการยา --</option>
                <?php foreach ($drugs as $drug): ?>
                    <option value="<?php echo $drug['id']; ?>"><?php echo $drug['code'] . ' - ' . $drug['name']; ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="lot_no">Lot No.</label>
            <input type="text" id="lot_no" name="lot_no" required>
        </div>

        <div class="form-group">
            <label for="expire_date">วันหมดอายุ</label>
            <input type="date" id="expire_date" name="expire_date" required>
        </div>

        <div class="form-group">
            <label for="quantity">จำนวนรับ</label>
            <input type="number" id="quantity" name="quantity" required min="1">
        </div>

        <div class="form-group">
            <label for="cost_price">ราคาต้นทุนต่อหน่วย (บาท)</label>
            <input type="number" id="cost_price" name="cost_price" required min="0" step="0.01">
        </div>

        <div style="display: flex; gap: 1rem; margin-top: 2rem;">
            <button type="submit" class="btn">บันทึกรับเข้า</button>
            <a href="/inventory" class="btn" style="background-color: #9ca3af;">ยกเลิก</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
