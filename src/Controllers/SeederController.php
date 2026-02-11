<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;

class SeederController extends Controller {
    public function seed() {
        $db = Database::getInstance()->getConnection();

        echo "<h1>🌱 Seeding Database...</h1>";
        echo "<style>body { font-family: Arial; padding: 20px; } .success { color: green; } .error { color: red; }</style>";

        try {
            // 1. Seed Users
            echo "<p>👤 Seeding users...</p>";
            $hashedPassword = password_hash('123456', PASSWORD_DEFAULT);
            $db->exec("INSERT IGNORE INTO users (id, username, password, full_name, role, is_active) VALUES 
                (1, 'admin', '$hashedPassword', 'ผู้ดูแลระบบ', 'admin', 1),
                (2, 'pharmacist', '$hashedPassword', 'เภสัชกร', 'pharmacist', 1),
                (3, 'staff', '$hashedPassword', 'เจ้าหน้าที่', 'staff', 1)");
            echo "<p class='success'>✅ Users created (username: admin/pharmacist/staff, password: 123456)</p>";

            // 2. Seed Fiscal Years
            echo "<p>📅 Seeding fiscal years...</p>";
            $db->exec("INSERT IGNORE INTO fiscal_years (id, year, start_date, end_date) VALUES 
                (1, 2024, '2023-10-01', '2024-09-30'),
                (2, 2025, '2024-10-01', '2025-09-30'),
                (3, 2026, '2025-10-01', '2026-09-30')");
            echo "<p class='success'>✅ Fiscal years created</p>";

            // 3. Seed Suppliers
            echo "<p>🏢 Seeding suppliers...</p>";
            $db->exec("INSERT IGNORE INTO suppliers (name, contact_person, phone, email, address) VALUES 
                ('GPO (องค์การเภสัชกรรม)', 'คุณสมชาย ใจดี', '02-123-4567', 'somchai@gpo.or.th', '75/1 ถ.พระราม 6 กรุงเทพฯ'),
                ('Zuellig Pharma', 'คุณวิภา สุขใจ', '02-987-6543', 'wipa@zuellig.com', '123 อาคารเอไอเอ กรุงเทพฯ'),
                ('DKSH', 'คุณนพดล รักษ์ดี', '02-555-5555', 'nopdon@dksh.com', '456 ถ.สุขุมวิท กรุงเทพฯ'),
                ('บริษัท ไทยนครา จำกัด', 'คุณสุดา ยิ้มแย้ม', '02-111-2222', 'suda@thainakorn.co.th', '789 ถ.พระราม 4 กรุงเทพฯ')");
            echo "<p class='success'>✅ Suppliers created</p>";

            // 4. Seed Drugs
            echo "<p>💊 Seeding drugs...</p>";
            $db->exec("INSERT IGNORE INTO drugs (code, name, generic_name, unit, pack_size, price, min_stock, max_stock, category, is_active) VALUES 
                ('100001', 'Paracetamol 500mg', 'Paracetamol', 'Tablet', 100, 0.50, 1000, 5000, 'ED', 1),
                ('100002', 'Amoxicillin 500mg', 'Amoxicillin', 'Capsule', 100, 1.50, 500, 2000, 'ED', 1),
                ('100003', 'Ibuprofen 400mg', 'Ibuprofen', 'Tablet', 100, 1.00, 500, 1500, 'NED', 1),
                ('100004', 'Omeprazole 20mg', 'Omeprazole', 'Capsule', 100, 2.00, 300, 1000, 'NED', 1),
                ('100005', 'Metformin 500mg', 'Metformin', 'Tablet', 100, 0.75, 1000, 4000, 'ED', 1),
                ('100006', 'Atenolol 50mg', 'Atenolol', 'Tablet', 100, 0.80, 800, 3000, 'ED', 1),
                ('100007', 'Amlodipine 5mg', 'Amlodipine', 'Tablet', 100, 0.90, 900, 3500, 'ED', 1),
                ('100008', 'Simvastatin 20mg', 'Simvastatin', 'Tablet', 100, 1.20, 600, 2500, 'NED', 1),
                ('100009', 'Aspirin 81mg', 'Aspirin', 'Tablet', 100, 0.30, 1500, 6000, 'ED', 1),
                ('100010', 'Cetirizine 10mg', 'Cetirizine', 'Tablet', 100, 0.60, 400, 1500, 'NED', 1)");
            echo "<p class='success'>✅ Drugs created (10 items)</p>";

            // 5. Seed Sample Transactions (for 3-year history)
            echo "<p>📊 Seeding sample transactions...</p>";
            $currentYear = date('Y');
            for ($year = 0; $year < 3; $year++) {
                $transactionYear = $currentYear - $year - 1;
                for ($drug = 1; $drug <= 10; $drug++) {
                    $quantity = rand(1000, 5000);
                    $date = "$transactionYear-" . rand(1, 12) . "-" . rand(1, 28);
                    $db->exec("INSERT INTO transactions (drug_id, transaction_type, quantity, balance_after, transaction_date, user_id) VALUES 
                        ($drug, 'dispense', $quantity, 0, '$date', 1)");
                }
            }
            echo "<p class='success'>✅ Sample transactions created (3 years history)</p>";

            // 6. Seed Sample Inventory
            echo "<p>📦 Seeding sample inventory...</p>";
            $db->exec("INSERT IGNORE INTO inventory (drug_id, lot_no, expire_date, quantity, cost_price, location, received_date) VALUES 
                (1, 'LOT001', '2026-12-31', 2500, 0.50, 'main', CURDATE()),
                (2, 'LOT002', '2025-06-30', 1000, 1.50, 'main', CURDATE()),
                (3, 'LOT003', '2026-03-31', 800, 1.00, 'main', CURDATE()),
                (4, 'LOT004', '2025-12-31', 500, 2.00, 'main', CURDATE()),
                (5, 'LOT005', '2027-01-31', 3000, 0.75, 'main', CURDATE())");
            echo "<p class='success'>✅ Sample inventory created</p>";

            // 7. Seed Sample Contracts
            echo "<p>📄 Seeding sample contracts...</p>";
            $db->exec("INSERT IGNORE INTO contracts (contract_no, supplier_id, start_date, end_date, total_amount, status) VALUES 
                ('CON2025-001', 1, '2025-01-01', '2025-12-31', 500000.00, 'active'),
                ('CON2025-002', 2, '2025-01-01', '2025-12-31', 300000.00, 'active'),
                ('CON2025-003', 3, '2025-01-01', '2025-12-31', 200000.00, 'active')");
            echo "<p class='success'>✅ Sample contracts created</p>";

            // 8. Seed Safety Reference Data (DDI & Rules)
            echo "<p>🛡️ Seeding safety reference data...</p>";
            
            // Create tables if not exist (Should be in migration, but adding here for safety)
            $db->exec("CREATE TABLE IF NOT EXISTS ref_drug_interactions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                drug_a_name VARCHAR(100),
                drug_b_name VARCHAR(100),
                severity ENUM('minor', 'moderate', 'major', 'contraindicated'),
                description TEXT,
                action_suggested TEXT
            )");
            
            $db->exec("CREATE TABLE IF NOT EXISTS ref_drug_safety_rules (
                id INT AUTO_INCREMENT PRIMARY KEY,
                drug_name VARCHAR(100),
                condition_type VARCHAR(50),
                min_value FLOAT,
                max_value FLOAT,
                alert_message TEXT
            )");

            // Clear old data
            $db->exec("TRUNCATE TABLE ref_drug_interactions");
            $db->exec("TRUNCATE TABLE ref_drug_safety_rules");

            // Seed DDI
            $db->exec("INSERT INTO ref_drug_interactions (drug_a_name, drug_b_name, severity, description, action_suggested) VALUES 
                ('Simvastatin', 'Amlodipine', 'major', 'Increased risk of myopathy/rhabdomyolysis.', 'Limit Simvastatin dose to 20mg/day.'),
                ('Aspirin', 'Ibuprofen', 'moderate', 'Ibuprofen may interfere with antiplatelet effect of low-dose aspirin.', 'Take Ibuprofen 30 min after or 8 hours before Aspirin.'),
                ('Warfarin', 'Aspirin', 'major', 'Increased risk of bleeding.', 'Monitor INR closely.'),
                ('Metformin', 'Contrast Media', 'major', 'Risk of lactic acidosis.', 'Discontinue Metformin prior to procedure.')
            ");
            
            // Seed Safety Rules
            $db->exec("INSERT INTO ref_drug_safety_rules (drug_name, condition_type, min_value, max_value, alert_message) VALUES 
                ('Metformin', 'egfr', 0, 30, 'Contraindicated in eGFR < 30 ml/min'),
                ('Metformin', 'egfr', 30, 45, 'Use with caution, max dose 1000mg/day'),
                ('Ibuprofen', 'egfr', 0, 30, 'Avoid use in severe renal impairment')
            ");
            
            echo "<p class='success'>✅ Safety reference data created</p>";

            echo "<hr>";
            echo "<h2 class='success'>✅ Seeding completed successfully!</h2>";
            echo "<p><strong>Login credentials:</strong></p>";
            echo "<ul>";
            echo "<li>Username: <code>admin</code> / Password: <code>123456</code> (ผู้ดูแลระบบ)</li>";
            echo "<li>Username: <code>pharmacist</code> / Password: <code>123456</code> (เภสัชกร)</li>";
            echo "<li>Username: <code>staff</code> / Password: <code>123456</code> (เจ้าหน้าที่)</li>";
            echo "</ul>";
            echo "<p><a href='/login' style='padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px;'>Go to Login</a></p>";
            echo "<p><a href='/dashboard' style='padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 5px;'>Go to Dashboard</a></p>";

        } catch (\Exception $e) {
            echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
        }
    }
}
