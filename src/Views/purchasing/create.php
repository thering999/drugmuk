<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="card" style="max-width: 600px; margin: 0 auto;">
    <h2>สร้างแผนการจัดซื้อ</h2>
    
    <form action="/purchasing/store" method="POST">
        <div class="form-group">
            <label for="fiscal_year_id">ปีงบประมาณ</label>
            <select id="fiscal_year_id" name="fiscal_year_id" required style="width: 100%; padding: 0.5rem; border-radius: var(--border-radius); border: 1px solid #d1d5db;">
                <option value="">-- เลือกปีงบประมาณ --</option>
                <?php foreach ($fiscal_years as $fy): ?>
                    <option value="<?php echo $fy['id']; ?>"><?php echo $fy['year']; ?></option>
                <?php endforeach; ?>
            </select>
        </div>

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
            <label for="quantity_plan">จำนวนตามแผน</label>
            <input type="number" id="quantity_plan" name="quantity_plan" required min="1">
        </div>

        <div class="form-group">
            <label for="budget_plan">งบประมาณ (บาท)</label>
            <input type="number" id="budget_plan" name="budget_plan" required min="0" step="0.01">
        </div>

        <div style="display: flex; gap: 1rem; margin-top: 2rem;">
            <button type="submit" class="btn">บันทึก</button>
            <a href="/purchasing" class="btn" style="background-color: #9ca3af;">ยกเลิก</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
