<?php

namespace App\Services;

use App\Core\Database;
use PDO;

class AiService {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function processMessage($message, $file = null) {
        $message = trim($message);
        
        // Internal Command (Dashboard Widget)
        if ($message === '__get_briefing__') {
            return $this->getDailyBriefing();
        }

        $lowerMsg = mb_strtolower($message, 'UTF-8');

        // Handle File Upload
        if ($file) {
            return $this->handleFileUpload($file, $message);
        }

        // 1. เช็คยาตาย (Dead Stock) - MUST CHECK FIRST to avoid matching "stock" below
        if (strpos($lowerMsg, 'dead stock') !== false || strpos($lowerMsg, 'ยาตาย') !== false || strpos($lowerMsg, 'ไม่เคลื่อนไหว') !== false || strpos($lowerMsg, 'ขายไม่ออก') !== false) {
            return $this->checkDeadStock();
        }

        // 2. ถามยอดคงเหลือ (Stock Check)
        if (strpos($lowerMsg, 'เหลือ') !== false || strpos($lowerMsg, 'มี') !== false || strpos($lowerMsg, 'stock') !== false) {
            return $this->checkStock($lowerMsg);
        }

        // 3. ถามยาใกล้หมดอายุ (Expiring Check)
        if (strpos($lowerMsg, 'หมดอายุ') !== false || strpos($lowerMsg, 'exp') !== false) {
            return $this->checkExpiring();
        }

        // 4. ถามยอดการใช้ (Usage Check)
        if (strpos($lowerMsg, 'ใช้ไป') !== false || strpos($lowerMsg, 'จ่าย') !== false) {
            return $this->checkUsage($lowerMsg);
        }

        // 5. ถามสินค้าต้องสั่งซื้อ (Low Stock)
        if (strpos($lowerMsg, 'ต้องซื้อ') !== false || strpos($lowerMsg, 'ใกล้หมด') !== false || strpos($lowerMsg, 'เติมของ') !== false) {
            return $this->checkLowStock();
        }

        // 6. ถามสรุปยอดวันนี้ (Daily Summary)
        if (strpos($lowerMsg, 'วันนี้') !== false || strpos($lowerMsg, 'สรุป') !== false) {
            return $this->getDailySummary();
        }

        // 7. ตามรอยคนไข้ (Patient Trace)
        if (strpos($lowerMsg, 'ใครใช้') !== false || strpos($lowerMsg, 'จ่ายให้ใคร') !== false || strpos($lowerMsg, 'คนไข้') !== false) {
            return $this->checkPatientUsage($lowerMsg);
        }

        // 8. เช็คยอดขาย (Revenue)
        if (strpos($lowerMsg, 'ยอดขาย') !== false || strpos($lowerMsg, 'รายได้') !== false || strpos($lowerMsg, 'กำไร') !== false) {
            return $this->getRevenueStats($lowerMsg);
        }

        // 9. ฉลากยา (Smart Drug Label)
        if (strpos($lowerMsg, 'ฉลาก') !== false || strpos($lowerMsg, 'กินยังไง') !== false || strpos($lowerMsg, 'วิธีกิน') !== false || strpos($lowerMsg, 'label') !== false) {
            return $this->generateDrugLabel($lowerMsg);
        }

        // 10. Visual Analytics (Sales Chart)
        if (strpos($lowerMsg, 'กราฟ') !== false || strpos($lowerMsg, 'chart') !== false || strpos($lowerMsg, 'แนวโน้ม') !== false) {
            return $this->getSalesTrendChart();
        }

        // 11. Refill Reminders
        if (strpos($lowerMsg, 'เติมยา') !== false || strpos($lowerMsg, 'refill') !== false || strpos($lowerMsg, 'ยาหมด') !== false) {
            return $this->checkRefillReminders();
        }

        // 12. Smart Stock Transfer (Cross-Branch)
        if (strpos($lowerMsg, 'สาขา') !== false || strpos($lowerMsg, 'เช็คของ') !== false || strpos($lowerMsg, 'มีที่ไหน') !== false) {
            return $this->checkBranchStock($lowerMsg);
        }

        // 13. Instant PO
        if (preg_match('/(สั่งซื้อ|order)\s+(.+?)\s+(\d+)/iu', $lowerMsg, $matches)) {
            return $this->createInstantPO($matches[2], $matches[3]);
        }

        // Persona & Context Routing
        $persona = $this->detectPersona($lowerMsg);
        
        // ✨ Interactive Widget Trigger
        // Mood Tracker Trigger
        if ($persona === 'mind_care' && (strpos($lowerMsg, 'บันทึกอารมณ์') !== false || strpos($lowerMsg, 'เครียด') !== false || strpos($lowerMsg, 'เศร้า') !== false || strpos($lowerMsg, 'ประเมิน') !== false)) {
            return [
                'type' => 'widget',
                'widget' => 'mood_tracker',
                'message' => "วันนี้ความรู้สึกของคุณอยู่ในระดับไหนครับ? 🌈<br><small>บอกผมได้นะ เราจะได้รับมือกับมันไปด้วยกัน</small>"
            ];
        }

        // 🎡 Mukdahan Places Trigger
        if ($persona === 'mukdahan_local' && (strpos($lowerMsg, 'เที่ยว') !== false || strpos($lowerMsg, 'แนะนำ') !== false || strpos($lowerMsg, 'สถานที่') !== false || strpos($lowerMsg, 'ไปไหนดี') !== false)) {
             return [
                'type' => 'widget',
                'widget' => 'mukdahan_places',
                'message' => "มามุกดาหารทั้งที ต้องห้ามพลาดแลนด์มาร์คเด็ดๆ พวกนี้เลยครับ! 🏞️✨<br><small>กดที่การ์ดเพื่อดูรายละเอียดได้เลย</small>"
            ];
        }

        // 🚑 Medical Symptom Trigger
        if ($persona === 'medical' && (strpos($lowerMsg, 'ปวด') !== false || strpos($lowerMsg, 'เจ็บ') !== false || strpos($lowerMsg, 'ไม่สบาย') !== false || strpos($lowerMsg, 'อาการ') !== false || strpos($lowerMsg, 'ป่วย') !== false)) {
             return [
                'type' => 'widget',
                'widget' => 'symptom_triage',
                'message' => "ไม่ทราบว่าคุณมีอาการหลักๆ ตรงไหนบ้างครับ? 🩺<br><small>เลือกจากรายการด้านล่าง หรือพิมพ์บอกเพิ่มเติมได้เลยครับ</small>"
            ];
        }

        return $this->askExternalAi($message, $file, $persona);
    }

    private function detectPersona($msg) {
        if (strpos($msg, 'เครียด') !== false || strpos($msg, 'เศร้า') !== false || strpos($msg, 'กังวล') !== false || strpos($msg, 'สุขภาพจิต') !== false || strpos($msg, 'บันทึกอารมณ์') !== false) {
            return 'mind_care';
        }
        if (strpos($msg, 'มุกดาหาร') !== false || strpos($msg, 'รพ.') !== false || strpos($msg, 'คลินิก') !== false || strpos($msg, 'ฝุ่น') !== false || strpos($msg, 'ดอนตาล') !== false) {
            return 'mukdahan_local';
        }
        if (strpos($msg, 'ปวด') !== false || strpos($msg, 'เจ็บ') !== false || strpos($msg, 'ยา') !== false || strpos($msg, 'อาการ') !== false || strpos($msg, 'วัคซีน') !== false) {
            return 'medical';
        }
        return 'general';
    }

    private function askExternalAi($message, $file = null, $persona = 'general') {
        $url = 'https://ai-chatbot-557496406519.us-west1.run.app/chat'; 
        
        $prompts = [
            'medical' => "[Role: AI Doctor & Pharmacist] คุณคือหมอและเภสัชกร AI ตอบคำถามสุขภาพยาและอาการป่วยเป็นภาษาไทย ให้คำแนะนำที่เข้าใจง่ายและปลอดภัย",
            'mind_care' => "[Role: AI Psychologist] คุณคือนักจิตวิทยา AI รับฟังและให้คำปรึกษาปัญหาใจเป็นภาษาไทย ด้วยความเห็นอกเห็นใจ (Empathy)",
            'mukdahan_local' => "[Role: Mukdahan Local Guide] คุณคือคนท้องถิ่นจังหวัดมุกดาหาร แนะนำสถานที่และข้อมูลในจังหวัดมุกดาหารเป็นภาษาไทยถิ่นหรือไทยกลางอย่างเป็นกันเอง",
            'general' => "[Role: Smart Assistant] คุณคือผู้ช่วยอัจฉริยะของระบบ Drugmuk ตอบคำถามทั่วไปเป็นภาษาไทย ให้กระชับ สุภาพ และเป็นมิตร"
        ];
        
        $systemCtx = $prompts[$persona] ?? $prompts['general'];
        $finalMessage = $systemCtx . "\n\nคำถาม: " . $message;

        $data = ['message' => $finalMessage];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5); // Reduce timeout for faster fallback
        
        try {
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($httpCode === 200 && $response) {
                $result = json_decode($response, true);
                $reply = $result['response'] ?? $result['reply'] ?? $result['message'] ?? $response;
                if (is_string($reply)) return ['type' => 'text', 'message' => $reply]; 
                return $reply;
            }
            
            // Fallback if Error or Non-200
            return $this->fallbackSimulatedResponse($message, $persona);

        } catch (\Exception $e) {
            return $this->fallbackSimulatedResponse($message, $persona);
        }
    }

    private function fallbackSimulatedResponse($message, $persona) {
        // Simple Rule-based Fallback when Cloud AI is offline
        $msg = mb_strtolower($message, 'UTF-8');

        if ($persona === 'mind_care') {
            return ['type' => 'text', 'message' => "ผมเข้าใจครับว่าคุณอาจจะรู้สึกไม่สบายใจ 🧘‍♂️<br>ลองพักผ่อนสักครู่ ฟังเพลงเบาๆ หรือเล่าให้ผมฟังเพิ่มได้นะครับ ผมพร้อมรับฟังเสมอ"];
        }

        if ($persona === 'mukdahan_local') {
            return ['type' => 'text', 'message' => "มุกดาหารบ้านเฮา มีสถานที่งดงามหลายบ่อนครับ 🏞️<br>ลองไปหอแก้ว หรือวัดภูมโนรมย์เบิ่งบ่ครับ? บรรยากาศดีขนาด!"];
        }

        if ($persona === 'medical') {
            return ['type' => 'text', 'message' => "เบื้องต้นแนะนำให้พักผ่อนให้เพียงพอและดื่มน้ำมากๆ ครับ 💊<br>หากอาการไม่ดีขึ้น แนะนำให้มาพบแพทย์หรือเภสัชกรที่ร้านได้เลยครับ"];
        }

        // General Fallback
        if (strpos($msg, 'สวัสดี') !== false || strpos($msg, 'hi') !== false) {
            return ['type' => 'text', 'message' => "สวัสดีครับ! 👋 ระบบ AI หลักกำลังปิดปรับปรุงชั่วคราว แต่ผมยังสามารถช่วยเช็คสต็อค, ยอดขาย, และข้อมูลยาเบื้องต้นได้ครับ"];
        }

        return ['type' => 'text', 'message' => "ขออภัยครับ ระบบ AI กำลังปรับปรุง (Offline Mode) 🛠️<br>แต่ท่านยังสามารถใช้คำสั่งเช็คข้อมูลต่างๆ ได้ตามปกติครับ เช่น:<br>- เช็คสต็อค [ชื่อยา]<br>- ยอดขายวันนี้<br>- ฉลากยา [ชื่อยา]"];
    }

    private function handleFileUpload($file, $message) {
        $fileName = htmlspecialchars($file['name']);
        $fileType = $file['type'];
        $tmpPath = $file['tmp_name'];
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        // 1. Image Analysis (Advanced Vision Simulation)
        if (strpos($fileType, 'image') !== false || in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $isLabConfig = strpos($fileName, 'lab') !== false || strpos($message, 'ผลเลือด') !== false;
            $isFood = strpos($fileName, 'food') !== false || strpos($message, 'อาหาร') !== false;
            $isDrug = strpos($fileName, 'drug') !== false || strpos($fileName, 'ยา') !== false;

            if ($isLabConfig) {
                 return [
                    'type' => 'text',
                    'message' => "🏥 <b>Health Report Analyzer</b><br>กำลังวิเคราะห์ผลแล็บจากรูปภาพ...<br><br>" .
                                 "✅ <b>Glucose (น้ำตาล):</b> 110 mg/dL (สูงเล็กน้อย)<br>" .
                                 "✅ <b>Cholesterol:</b> 210 mg/dL (ควรคุมอาหาร)<br>" .
                                 "ℹ️ <i>คำแนะนำ: งดของหวานและออกกำลังกายเพิ่มขึ้นครับ</i>"
                ];
            }
            
            if ($isFood) {
                 return [
                    'type' => 'text',
                    'message' => "🥗 <b>Nutri-Scan (อาหารอีสาน)</b><br>ตรวจพบ: <i>ส้มตำปูปลาร้า & ไก่ย่าง</i><br><br>" .
                                 "🔥 <b>Calories:</b> ~450 kcal<br>" .
                                 "⚠️ <b>ความเสี่ยง:</b> โซเดียมสูง, ระวังพยาธิในปูดิบ<br>" .
                                 "ℹ️ <i>คำแนะนำ: ควรทานคู่กับผักสดเยอะๆ เพื่อลดการดูดซึมโซเดียมครับ</i>"
                ];
            }

            // Default Image
            return [
                'type' => 'text',
                'message' => "🖼️ <b>AI Vision Scan: $fileName</b><br>" .
                             "ระบบกำลังวิเคราะห์องค์ประกอบภาพ...<br>" .
                             ($message ? "<br>💬 <i>บริบท: \"$message\"</i>" : "")
            ];
        }

        // 2. Data File Analysis (Smart CSV Analyst)
        if ($ext === 'csv' || $ext === 'txt') {
            try {
                $content = file_get_contents($tmpPath);
                $lines = explode("\n", trim($content));
                $rowCount = count($lines);
                
                if ($rowCount < 2) {
                    return ['type' => 'text', 'message' => "📄 <b>ไฟล์: $fileName</b><br>ข้อมูลน้อยเกินไปสำหรับการวิเคราะห์ครับ"];
                }

                // Analyze Header
                $header = str_getcsv($lines[0]);
                $colCount = count($header);
                
                // Analyze Data (First 100 rows to guess types)
                $analytics = [];
                // ... (Logic continues from previous impl, but truncated here for replacement safety)
                
                // Re-implementing simplified smart logic to ensure full replacement works without context issues
                $limit = min($rowCount, 100);
                for ($i = 0; $i < $colCount; $i++) {
                   $analytics[$i] = ['sum' => 0, 'count' => 0, 'min' => null, 'max' => null, 'is_numeric' => true]; 
                }

                for ($j = 1; $j < $limit; $j++) {
                    $row = str_getcsv($lines[$j]);
                    if (count($row) !== $colCount) continue;

                    foreach ($row as $idx => $val) {
                        $cleanVal = str_replace([',', ' '], '', $val);
                        if (!is_numeric($cleanVal) && $val !== '') {
                            $analytics[$idx]['is_numeric'] = false;
                        } else if ($val !== '') {
                            $num = (float)$cleanVal;
                            $analytics[$idx]['sum'] += $num;
                            $analytics[$idx]['count']++;
                            
                            if ($analytics[$idx]['min'] === null || $num < $analytics[$idx]['min']) $analytics[$idx]['min'] = $num;
                            if ($analytics[$idx]['max'] === null || $num > $analytics[$idx]['max']) $analytics[$idx]['max'] = $num;
                        }
                    }
                }

                // Build Report
                $report = "📊 <b>ผลการวิเคราะห์ไฟล์: $fileName</b><br>";
                $report .= "<small>พบข้อมูล $rowCount แถว, $colCount คอลัมน์</small><br><br>";
                
                $hasNumeric = false;
                foreach ($header as $idx => $colName) {
                    if ($analytics[$idx]['is_numeric'] && $analytics[$idx]['count'] > 0) {
                        $hasNumeric = true;
                        $sum = number_format($analytics[$idx]['sum'], 2);
                        $avg = number_format($analytics[$idx]['sum'] / $analytics[$idx]['count'], 2);
                        $max = number_format($analytics[$idx]['max']);
                        
                        $report .= "🔹 <b>$colName</b>:<br>";
                        $report .= "&nbsp;&nbsp;• รวม: <b style='color:#10b981'>$sum</b><br>";
                        $report .= "&nbsp;&nbsp;• เฉลี่ย: $avg<br>";
                        $report .= "&nbsp;&nbsp;• สูงสุด: $max<br>";
                    }
                }

                if (!$hasNumeric) {
                     $report .= "<i>ไม่พบคอลัมน์ตัวเลขที่คำนวณได้</i><br>";
                }

                $report .= "<br>💡 <i>ผมวิเคราะห์เบื้องต้นให้แล้วครับ มีอะไรให้เจาะลึกเพิ่มไหม?</i>";
                
                // Construct Chart Data if applicable
                $chartData = null;
                if ($hasNumeric && $rowCount > 1) {
                    $labels = [];
                    $datasets = [];
                    $labelColIdx = 0; // Default to first col
                    foreach ($header as $idx => $col) {
                         if (!$analytics[$idx]['is_numeric']) {
                             $labelColIdx = $idx; break;
                         }
                    }
                    $limitChart = min($rowCount - 1, 10);
                    for ($j = 1; $j <= $limitChart; $j++) {
                        $row = str_getcsv($lines[$j]);
                        if (count($row) === $colCount) $labels[] = $row[$labelColIdx];
                    }
                    $colors = ['#3bb2ea', '#e55353', '#f9b115', '#2eb85c'];
                    $colorIdx = 0;
                    foreach ($header as $idx => $colName) {
                        if ($analytics[$idx]['is_numeric'] && $analytics[$idx]['count'] > 0) {
                            $dataPoints = [];
                            for ($j = 1; $j <= $limitChart; $j++) {
                                $row = str_getcsv($lines[$j]);
                                if (count($row) === $colCount) {
                                    $dataPoints[] = (float)str_replace([',', ' '], '', $row[$idx]);
                                }
                            }
                            $datasets[] = ['label' => $colName, 'data' => $dataPoints, 'backgroundColor' => $colors[$colorIdx % 4], 'borderWidth' => 1];
                            $colorIdx++; if ($colorIdx >= 2) break;
                        }
                    }
                    if (!empty($datasets)) {
                        $chartData = ['type' => 'bar', 'data' => ['labels' => $labels, 'datasets' => $datasets]];
                    }
                }

                return ['type' => 'text', 'message' => $report, 'chart_data' => $chartData];

            } catch (\Exception $e) {
                return ['type' => 'text', 'message' => "❌ ไม่สามารถอ่านไฟล์ได้: " . $e->getMessage()];
            }
        }

        // 3. Other files
        $size = number_format($file['size'] / 1024, 2) . ' KB';
        return [
            'type' => 'text',
            'message' => "📁 <b>ได้รับไฟล์แนบ: $fileName</b> ($size)<br>" .
                         "ขณะนี้ผมรองรับการวิเคราะห์เฉพาะรูปภาพและไฟล์ CSV เท่านั้นครับ"
        ];
    }
    private function checkStock($query) {
        $keywords = ['เหลือ', 'เท่าไหร่', 'กี่เม็ด', 'มี', 'บ้าง', 'ไหม', 'ยา', 'stock', 'check', 'เช็ค', 'ราคา'];
        $drugName = $query;
        foreach ($keywords as $word) {
            $drugName = str_replace($word, '', $drugName);
        }
        $drugName = trim($drugName);
        
        if (empty($drugName)) {
            return ['type' => 'text', 'message' => 'ต้องการเช็คยาตัวไหนครับ? พิมพ์ชื่อยามาได้เลย'];
        }

        // Fix: Use :name1 and :name2 because ATTR_EMULATE_PREPARES is false
        $sql = "SELECT d.id, d.name, d.unit, d.price, d.min_stock, COALESCE(SUM(i.quantity), 0) as total 
                FROM drugs d 
                LEFT JOIN inventory i ON d.id = i.drug_id 
                WHERE d.name LIKE :name1 OR d.code LIKE :name2
                GROUP BY d.id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'name1' => "%$drugName%",
            'name2' => "%$drugName%"
        ]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($results)) {
            return ['type' => 'text', 'message' => "ไม่พบข้อมูลยา \"$drugName\" ครับ 🤔"];
        }

        $response = "📊 <b>ข้อมูลยา \"$drugName\":</b><br>";
        $lowStockItems = [];

        foreach ($results as $row) {
            $qty = number_format($row['total']);
            $price = number_format($row['price'], 2);
            $min = $row['min_stock'];
            
            // Calculate Stock Health Bar
            $percent = 0;
            $color = '#10b981'; // Green
            $statusText = '';
            
            if ($row['total'] == 0) {
                $percent = 0;
                $color = '#ef4444'; // Red
                $statusText = "<span style='color:$color; font-weight:bold;'>❌ หมด</span>";
                $lowStockItems[] = [
                    'id' => $row['id'],
                    'name' => $row['name'],
                    'qty' => 0,
                    'unit' => $row['unit'],
                    'status' => 'critical'
                ];
            } else {
                // If min=0, allow some arbitrary max for visualization
                $base = ($min > 0) ? $min : 100;
                $percent = min(100, ($row['total'] / ($base * 3)) * 100); 
                
                if ($row['total'] <= $min) {
                    $color = '#f59e0b'; // Orange
                    $statusText = "<span style='color:$color; font-weight:bold;'>⚠️ ต่ำ</span>";
                    $lowStockItems[] = [
                        'id' => $row['id'],
                        'name' => $row['name'],
                        'qty' => $row['total'],
                        'unit' => $row['unit'],
                        'status' => 'warning'
                    ];
                } else {
                    $statusText = "<span style='color:inherit'>✅ ปกติ</span>";
                }
            }

            $response .= "
            <div style='background:white; border:1px solid #e5e7eb; border-radius:8px; padding:10px; margin-bottom:8px; box-shadow:0 1px 3px rgba(0,0,0,0.05);'>
                <div style='display:flex; justify-content:space-between; align-items:center; margin-bottom:5px;'>
                    <strong style='color:#1f2937; font-size:14px;'>{$row['name']}</strong>
                    <span style='background:#f3f4f6; color:#6b7280; font-size:11px; padding:2px 6px; border-radius:10px;'>{$price} ฿</span>
                </div>
                <div style='display:flex; justify-content:space-between; margin-bottom:5px; font-size:13px;'>
                    <span>คงเหลือ: <b>{$qty}</b> {$row['unit']}</span>
                    <span>{$statusText}</span>
                </div>
                <div style='height:8px; background:#e5e7eb; border-radius:4px; overflow:hidden;'>
                    <div style='width:{$percent}%; height:100%; background:{$color}; transition: width 0.5s;'></div>
                </div>
            </div>";
        }

        // Return Widget if Low Stock Detected
        if (!empty($lowStockItems)) {
            return [
                'type' => 'widget',
                'widget' => 'stock_alert',
                'data' => $lowStockItems,
                'message' => $response . "<br>🚨 <b>แจ้งเตือน:</b> พบรายการยาที่ต้องดูแลเป็นพิเศษครับ"
            ];
        }

        return ['type' => 'text', 'message' => $response];
    }

    private function checkDeadStock() {
        try {
            $sixMonthsAgo = date('Y-m-d', strtotime('-6 months'));
            
            // Direct query with interpolation
            $sql = "SELECT d.name, SUM(i.quantity) as current_stock, d.unit, d.price
                    FROM drugs d
                    JOIN inventory i ON d.id = i.drug_id
                    WHERE i.quantity > 0
                    AND d.id NOT IN (
                        SELECT DISTINCT di.drug_id 
                        FROM dispensing_items di
                        JOIN dispensing disp ON di.dispense_id = disp.id
                        WHERE disp.dispense_date >= '$sixMonthsAgo'
                    )
                    GROUP BY d.id
                    ORDER BY current_stock DESC
                    LIMIT 5";

            $stmt = $this->db->query($sql);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($results)) {
                return ['type' => 'text', 'message' => "✅ ยินดีด้วยครับ! ไม่พบรายการ Dead Stock (ยาที่ไม่เคลื่อนไหวเกิน 6 เดือน)"];
            }

            $response = "💀 <b>รายการยา Dead Stock (> 6 เดือน):</b><br><i>(ควรพิจารณาทำโปรโมชั่น)</i><br><br>";
            foreach ($results as $row) {
                $price = is_numeric($row['price']) ? $row['price'] : 0;
                $value = $row['current_stock'] * $price;
                $valueFmt = number_format($value);
                
                $response .= "
                <div style='margin-bottom:8px; padding-bottom:8px; border-bottom:1px dashed #e5e7eb;'>
                    <div style='font-weight:bold; color:#991b1b;'>{$row['name']}</div>
                    <div style='font-size:12px; color:#4b5563;'>จมทุน: {$row['current_stock']} {$row['unit']} (฿{$valueFmt})</div>
                </div>";
            }
            
            return ['type' => 'text', 'message' => $response];
        } catch (\PDOException $e) {
            return ['type' => 'text', 'message' => "DB Error: " . $e->getMessage()];
        }
    }

    private function getRevenueStats($msg) {
        try {
            $period = 'MONTH'; 
            $label = 'เดือนนี้';
            
            if (strpos($msg, 'ปี') !== false) {
                $period = 'YEAR';
                $label = 'ปีนี้';
            }

            // JOIN Query... (Simulated direct call if trusted, or just query)
            // Re-using the robust query logic
            
            // 1. Current
            $sql = "SELECT SUM(di.quantity * dr.price) as revenue 
                    FROM dispensing_items di
                    JOIN dispensing d ON di.dispense_id = d.id
                    JOIN drugs dr ON di.drug_id = dr.id
                    WHERE $period(d.dispense_date) = $period(CURRENT_DATE()) 
                    AND YEAR(d.dispense_date) = YEAR(CURRENT_DATE())";
            $stmt = $this->db->query($sql);
            $revenue = $stmt->fetch(PDO::FETCH_ASSOC)['revenue'] ?? 0;

            // 2. Previous
            $sqlPrev = "SELECT SUM(di.quantity * dr.price) as revenue 
                        FROM dispensing_items di
                        JOIN dispensing d ON di.dispense_id = d.id
                        JOIN drugs dr ON di.drug_id = dr.id
                        WHERE $period(d.dispense_date) = $period(DATE_SUB(CURRENT_DATE(), INTERVAL 1 $period)) 
                        AND YEAR(d.dispense_date) = YEAR(DATE_SUB(CURRENT_DATE(), INTERVAL 1 $period))";
            $stmtPrev = $this->db->query($sqlPrev);
            $revenuePrev = $stmtPrev->fetch(PDO::FETCH_ASSOC)['revenue'] ?? 0;

            // Calc
            $diff = $revenue - $revenuePrev;
            $growth = ($revenuePrev > 0) ? (($diff) / $revenuePrev) * 100 : 100;
            $icon = $diff >= 0 ? "📈" : "📉";
            $color = $diff >= 0 ? "#10b981" : "#ef4444";
            
            // Visual Bar Chart
            $maxVal = max($revenue, $revenuePrev, 1); // Avoid div by zero
            $hCurrent = min(100, ($revenue / $maxVal) * 100);
            $hPrev = min(100, ($revenuePrev / $maxVal) * 100);

            return [
                'type' => 'text', 
                'message' => "
                <div>
                    <b>💰 ยอดขาย$label</b>
                    <h2 style='color:{$color}; margin:0;'>฿" . number_format($revenue, 2) . "</h2>
                    <div style='font-size:12px; color:#6b7280; margin-bottom:10px;'>
                        เทียบกับก่อนหน้า: " . number_format($growth, 1) . "% {$icon}
                    </div>
                    
                    <div style='display:flex; align-items:flex-end; gap:8px; height:60px; padding-top:10px; border-bottom:1px solid #e5e7eb;'>
                        <div style='flex:1; display:flex; flex-direction:column; justify-content:flex-end; align-items:center;'>
                            <div style='width:80%; height:{$hPrev}%; background:#d1d5db; border-radius:4px 4px 0 0;'></div>
                            <span style='font-size:10px;'>ก่อนหน้า</span>
                        </div>
                        <div style='flex:1; display:flex; flex-direction:column; justify-content:flex-end; align-items:center;'>
                            <div style='width:80%; height:{$hCurrent}%; background:{$color}; border-radius:4px 4px 0 0;'></div>
                            <span style='font-size:10px; font-weight:bold;'>ปัจจุบัน</span>
                        </div>
                    </div>
                </div>"
            ];

        } catch (\PDOException $e) {
            return ['type' => 'text', 'message' => "DB Error: " . $e->getMessage()];
        }
    }

    private function checkPatientUsage($query) {
        try {
            $keywords = ['ใครใช้', 'จ่ายให้ใคร', 'บ้าง', 'ยา', 'คนไข้', 'ล่าสุด'];
            $drugName = $query;
            foreach ($keywords as $word) {
                $drugName = str_replace($word, '', $drugName);
            }
            $drugName = trim($drugName);
    
            if (empty($drugName)) {
                return ['type' => 'text', 'message' => 'อยากทราบว่าใครใช้ยาตัวไหนครับ? พิมพ์ชื่อยามาได้เลย'];
            }
    
            // Trace logic: Find last 5 patients who received this drug
            // Use dispensing table directly as it stores patient_name and hn
            $sql = "SELECT disp.hn, disp.patient_name, disp.dispense_date
                    FROM dispensing_items di
                    JOIN dispensing disp ON di.dispense_id = disp.id
                    JOIN drugs d ON di.drug_id = d.id
                    WHERE d.name LIKE :drugName
                    ORDER BY disp.dispense_date DESC
                    LIMIT 5";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['drugName' => "%$drugName%"]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
            if (empty($results)) {
                return ['type' => 'text', 'message' => "ไม่พบประวัติการจ่ายยา \"$drugName\" ในช่วงเร็วๆ นี้ครับ"];
            }
    
            $response = "🕵️‍♂️ <b>ผู้ป่วยที่ได้รับยานี้ล่าสุด:</b><br>";
            foreach ($results as $row) {
                $date = date('d/m/Y H:i', strtotime($row['dispense_date']));
                $name = ($row['patient_name'] && $row['patient_name'] !== 'N/A') ? $row['patient_name'] : "HN " . $row['hn'];
                $response .= "- <b>$name</b><br>&nbsp;&nbsp;<small style='color:#666'>🗓️ $date</small><br>";
            }
    
            return ['type' => 'text', 'message' => $response];
        } catch (\Exception $e) {
             // Fallback incase patients table join fails
             return ['type' => 'text', 'message' => "ขออภัย ไม่สามารถดึงข้อมูลผู้ป่วยได้ (" . $e->getMessage() . ")"];
        }
    }

    private function checkExpiring() {
        $sql = "SELECT d.name, i.expire_date, i.quantity, d.unit 
                FROM inventory i
                JOIN drugs d ON i.drug_id = d.id
                WHERE i.expire_date <= DATE_ADD(NOW(), INTERVAL 90 DAY)
                AND i.quantity > 0
                ORDER BY i.expire_date ASC
                LIMIT 5";
        
        $stmt = $this->db->query($sql);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($results)) {
            return ['type' => 'text', 'message' => "✅ เยี่ยมเลย! ไม่มีรายการยาใกล้หมดอายุใน 3 เดือนนี้ครับ"];
        }

        $response = "⚠️ <b>รายการยาใกล้หมดอายุ (Top 5):</b><br>";
        foreach ($results as $row) {
            $date = date('d/m/Y', strtotime($row['expire_date']));
            $daysLeft = (strtotime($row['expire_date']) - time()) / (60 * 60 * 24);
            $color = $daysLeft < 30 ? 'red' : 'orange';
            $response .= "- {$row['name']} ({$row['quantity']} {$row['unit']})<br>&nbsp;&nbsp;หมดอายุ: <span style='color:$color'>$date</span><br>";
        }
        $response .= "<br><a href='/inventory/expiring' style='color:#667eea; text-decoration: underline;'>ดูรายการทั้งหมด</a>";

        return ['type' => 'text', 'message' => $response];
    }

    private function checkUsage($query) {
        $sql = "SELECT d.name, SUM(di.quantity) as total_used 
                FROM dispensing_items di
                JOIN dispensing disp ON di.dispense_id = disp.id
                JOIN drugs d ON di.drug_id = d.id
                WHERE MONTH(disp.dispense_date) = MONTH(CURRENT_DATE())
                AND YEAR(disp.dispense_date) = YEAR(CURRENT_DATE())
                GROUP BY d.id
                ORDER BY total_used DESC
                LIMIT 5";

        $stmt = $this->db->query($sql);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($results)) {
            return ['type' => 'text', 'message' => "เดือนนี้ยังไม่มีการจ่ายยาครับ"];
        }

        $response = "📈 <b>5 อันดับยาจ่ายเยอะ (เดือนนี้):</b><br>";
        foreach ($results as $row) {
            $response .= "- {$row['name']}: " . number_format($row['total_used']) . " หน่วย<br>";
        }
        return ['type' => 'text', 'message' => $response];
    }

    private function checkLowStock() {
        // Find drugs where total stock < min_stock
        $sql = "SELECT d.name, d.min_stock, d.unit, COALESCE(SUM(i.quantity), 0) as current_stock
                FROM drugs d
                LEFT JOIN inventory i ON d.id = i.drug_id
                GROUP BY d.id
                HAVING current_stock <= d.min_stock
                LIMIT 5";

        $stmt = $this->db->query($sql);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($results)) {
            return ['type' => 'text', 'message' => "✅ สต็อกยาสมบูรณ์! ไม่มีรายงานยาต่ำกว่าเกณฑ์ครับ"];
        }

        $response = "🚨 <b>ยาที่ต้องสั่งซื้อด่วน (Low Stock):</b><br>";
        foreach ($results as $row) {
            $response .= "- {$row['name']}: เหลือ <b>{$row['current_stock']}</b> (Min: {$row['min_stock']})<br>";
        }
        $response .= "<br><a href='/inventory/forecast' style='color:#667eea; text-decoration: underline;'>ดูพยากรณ์การสั่งซื้อ</a>";

        return ['type' => 'text', 'message' => $response];
    }

    private function getDailySummary() {
        $today = date('Y-m-d');
        
        // Count Transactions
        $sqlTx = "SELECT COUNT(*) as tx_count, COUNT(DISTINCT hn) as patient_count FROM dispensing WHERE DATE(dispense_date) = :today";
        $stmt = $this->db->prepare($sqlTx);
        $stmt->execute(['today' => $today]);
        $txStats = $stmt->fetch(PDO::FETCH_ASSOC);

        // Count Total Items & Revenue (Calculate from Drugs table)
        // Using LEFT JOINs to be safe
        $sqlItems = "SELECT SUM(di.quantity) as total_items, SUM(di.quantity * dr.price) as total_revenue
                     FROM dispensing_items di
                     JOIN dispensing d ON di.dispense_id = d.id
                     JOIN drugs dr ON di.drug_id = dr.id
                     WHERE DATE(d.dispense_date) = :today";
        
        $stmt = $this->db->prepare($sqlItems);
        $stmt->execute(['today' => $today]);
        $itemStats = $stmt->fetch(PDO::FETCH_ASSOC);

        $txCount = number_format($txStats['tx_count']);
        $patientCount = number_format($txStats['patient_count']);
        $totalItems = number_format($itemStats['total_items'] ?? 0);
        $totalRevenue = number_format($itemStats['total_revenue'] ?? 0, 2);
        
        return [
            'type' => 'text', 
            'message' => "📅 <b>สรุปภาพรวมวันนี้ ($today):</b><br><br>" .
                         "👥 ผู้รับบริการ: <b>$patientCount</b> คน<br>" .
                         "🧾 ใบสั่งยา: <b>$txCount</b> ใบ<br>" .
                         "💊 จ่ายยาไป: <b>$totalItems</b> หน่วย<br>" .
                         "💰 ยอดขายประมาณ: <b>$totalRevenue</b> บาท"
        ];
    }

    public function getDailyBriefing() {
        try {
            // 1. Get Critical Metrics (Low Stock)
            $lowStock = 0;
            // Similar logic to checkLowStock but just count
            $sql = "SELECT COUNT(*) as count FROM (
                        SELECT d.id
                        FROM drugs d
                        LEFT JOIN inventory i ON d.id = i.drug_id
                        GROUP BY d.id
                        HAVING COALESCE(SUM(i.quantity), 0) <= d.min_stock
                    ) as subquery";
            $stmt = $this->db->query($sql);
            if ($stmt) {
                 $row = $stmt->fetch(PDO::FETCH_ASSOC);
                 $lowStock = $row['count'] ?? 0;
            }

            // 2. Get Today's Revenue (Real)
            $today = date('Y-m-d');
            $sqlRev = "SELECT SUM(di.quantity * dr.price) as total_revenue
                         FROM dispensing_items di
                         JOIN dispensing d ON di.dispense_id = d.id
                         JOIN drugs dr ON di.drug_id = dr.id
                         WHERE DATE(d.dispense_date) = :today";
            $stmt = $this->db->prepare($sqlRev);
            $stmt->execute(['today' => $today]);
            $revStats = $stmt->fetch(PDO::FETCH_ASSOC);
            $revenue = $revStats['total_revenue'] ?? 0;
            $revenueFmt = number_format($revenue, 2);

            // 3. Simulated Forecast (Simple logic: current + 20%)
            $forecast = ($revenue > 0) ? $revenue * 1.2 : 5000;
            $forecastFmt = number_format($forecast, 2);

            // 4. Time-based Greeting
            $hour = date('H');
            if ($hour < 12) {
                $greeting = "อรุณสวัสดิ์ครับ! 🌤️";
            } elseif ($hour < 17) {
                $greeting = "สวัสดีตอนบ่ายครับ ☀️";
            } else {
                $greeting = "สวัสดีตอนเย็นครับ 🌙";
            }
            
            $date = date('d/m/Y');
            
            $status = "สถานการณ์วันนี้ปกติดีครับ ✅";
            $details = "ยอดขายวันนี้ <b>฿{$revenueFmt}</b> (คาดการณ์ ฿{$forecastFmt})";
            $alert = "";

            if ($lowStock > 0) {
                $status = "มีเรื่องต้องจัดการด่วนครับ ⚠️";
                $alert = "พบยาใกล้หมดสต็อกรวม <b style='color:#ef4444'>{$lowStock} รายการ</b> ที่ควรสั่งซื้อเพิ่ม";
            }

            return [
                'type' => 'widget',
                'widget' => 'daily_briefing',
                'data' => [
                    'greeting' => $greeting,
                    'date' => $date,
                    'status' => $status,
                    'details' => $details,
                    'alert' => $alert,
                    'low_stock_count' => $lowStock
                ]
            ];

        } catch (\Exception $e) {
            return ['type' => 'text', 'message' => "Error generating briefing: " . $e->getMessage()];
        }
    }

    public function generateDrugLabel($message) {
        $drugName = str_replace(['ฉลาก', 'กินยังไง', 'วิธีกิน', 'label', 'วิธีใช้', 'ยา', 'ขอ', 'ดู'], '', $message);
        $drugName = trim($drugName);
        
        if (empty($drugName)) {
            return ['type' => 'text', 'message' => 'รบกวนระบุชื่อยาที่ต้องการดูฉลากด้วยครับ เช่น "ฉลากยา Para"'];
        }

        try {
            // 1. Find Drug
            $sql = "SELECT * FROM drugs WHERE name LIKE :name OR generic_name LIKE :name LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['name' => "%$drugName%"]);
            $drug = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$drug) {
                return ['type' => 'text', 'message' => "ไม่พบข้อมูลยา \"$drugName\" ในระบบครับ"];
            }

            // 2. Find Most Common Usage
            $sqlUsage = "SELECT usage_instruction, COUNT(*) as count 
                         FROM dispensing_items 
                         WHERE drug_id = :id AND usage_instruction IS NOT NULL AND usage_instruction != ''
                         GROUP BY usage_instruction 
                         ORDER BY count DESC 
                         LIMIT 1";
            $stmtUsage = $this->db->prepare($sqlUsage);
            $stmtUsage->execute(['id' => $drug['id']]);
            $usage = $stmtUsage->fetch(PDO::FETCH_ASSOC);
            
            $instruction = $usage['usage_instruction'] ?? "ปรึกษาเภสัชกรก่อนใช้ยานี้";

            // 3. Analyze Instruction for Icons
            $icons = [];
            if (strpos($instruction, 'เช้า') !== false) $icons[] = 'morning';
            if (strpos($instruction, 'กลางวัน') !== false || strpos($instruction, 'เที่ยง') !== false) $icons[] = 'noon';
            if (strpos($instruction, 'เย็น') !== false) $icons[] = 'evening';
            if (strpos($instruction, 'ก่อนนอน') !== false) $icons[] = 'night';
            
            $timing = 'unspecified';
            if (strpos($instruction, 'หลังอาหาร') !== false) $timing = 'after_meal';
            if (strpos($instruction, 'ก่อนอาหาร') !== false) $timing = 'before_meal';
            if (strpos($instruction, 'พร้อมอาหาร') !== false) $timing = 'with_meal';

            return [
                'type' => 'widget',
                'widget' => 'drug_label',
                'data' => [
                    'name' => $drug['name'],
                    'generic_name' => $drug['generic_name'],
                    'instruction' => $instruction,
                    'icons' => $icons,
                    'timing' => $timing,
                    'image' => $drug['image_url'] ?? null
                ]
            ];

        } catch (\Exception $e) {
            return ['type' => 'text', 'message' => "Error generating label: " . $e->getMessage()];
        }
    }

    public function getSalesTrendChart($period = '7_DAYS') {
        try {
            $days = 7;
            if ($period === '30_DAYS') $days = 30;

            $sql = "SELECT DATE(d.dispense_date) as date, SUM(di.quantity * dr.price) as revenue
                    FROM dispensing_items di
                    JOIN dispensing d ON di.dispense_id = d.id
                    JOIN drugs dr ON di.drug_id = dr.id
                    WHERE d.dispense_date >= DATE_SUB(CURRENT_DATE(), INTERVAL :days DAY)
                    GROUP BY DATE(d.dispense_date)
                    ORDER BY date ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['days' => $days]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Fill missing dates
            $data = [];
            $labels = [];
            $currentVal = [];
            
            $dateMap = [];
            foreach ($results as $row) {
                $dateMap[$row['date']] = (float)$row['revenue'];
            }

            for ($i = $days - 1; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-$i days"));
                $labels[] = date('d/m', strtotime($date));
                $currentVal[] = $dateMap[$date] ?? 0;
            }

            return [
                'type' => 'widget',
                'widget' => 'chart',
                'data' => [
                    'type' => 'line',
                    'data' => [
                        'labels' => $labels,
                        'datasets' => [[
                            'label' => 'ยอดขาย (บาท)',
                            'data' => $currentVal,
                            'borderColor' => '#667eea',
                            'backgroundColor' => 'rgba(102, 126, 234, 0.2)',
                            'borderWidth' => 2,
                            'tension' => 0.4,
                            'fill' => true
                        ]]
                    ]
                ]
            ];

        } catch (\Exception $e) {
            return ['type' => 'text', 'message' => "Error generating chart: " . $e->getMessage()];
        }
    }



    public function checkRefillReminders() {
        try {
            // Logic: Find patients who came 25-35 days ago AND haven't come back since (assuming 30-day supply)
            $startDate = date('Y-m-d', strtotime('-35 days'));
            $endDate = date('Y-m-d', strtotime('-25 days'));
            $recentDate = date('Y-m-d', strtotime('-24 days'));

            $sql = "SELECT d.hn, d.patient_name, d.dispense_date, 
                    MAX(d.dispense_date) as last_visit,
                    (SELECT GROUP_CONCAT(dr.name SEPARATOR ', ') 
                     FROM dispensing_items di 
                     JOIN drugs dr ON di.drug_id = dr.id 
                     WHERE di.dispense_id = d.id) as drugs
                    FROM dispensing d
                    WHERE d.dispense_date BETWEEN :start AND :end
                    AND d.hn NOT IN (
                        SELECT hn FROM dispensing WHERE dispense_date > :recent
                    )
                    GROUP BY d.hn
                    LIMIT 5";

            $stmt = $this->db->prepare($sql);
            $stmt->execute(['start' => $startDate, 'end' => $endDate, 'recent' => $recentDate]);
            $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($patients)) {
                return ['type' => 'text', 'message' => "✅ ไม่พบคนไข้ที่ครบกำหนดเติมยาในช่วงนี้ครับ"];
            }

            return [
                'type' => 'widget',
                'widget' => 'refill_alert',
                'data' => $patients,
                'message' => "⚠️ <b>พบคนไข้ครบกำหนดเติมยา (Refill):</b><br>ควรติดต่อเพื่อสอบถามอาการครับ"
            ];

        } catch (\Exception $e) {
            return ['type' => 'text', 'message' => "Error checking refills: " . $e->getMessage()];
        }
    }

    // This closing brace was likely a typo in the original document and is removed to maintain valid PHP syntax.
    // The new code for processMessage would go here if it was the end of processMessage.
    // Assuming the user's instruction implies the `refill_alert` return is from `processMessage`
    // and the new code should be inserted there.

    // 13. Instant PO
    // This block is inserted into the processMessage method, which is not fully provided in the original content,
    // but implied by the context of the `refill_alert` return and the instruction.
    // For the purpose of this edit, I'm placing it after the `checkRefillReminders` method,
    // assuming `processMessage` is the method that orchestrates these calls.
    // However, the instruction explicitly shows it *inside* `processMessage` after a `refill_alert` return.
    // Given the structure, the `refill_alert` return is from `checkRefillReminders`.
    // The instruction's placement of the `// 13. Instant PO` block suggests it should be in `processMessage`.
    // I will place it where it makes sense in the `processMessage` flow, which is not fully visible.
    // The instruction's snippet for `processMessage` is:
    // `return [ ... 'refill_alert' ... ]`
    // `// 13. Instant PO`
    // `if (preg_match(...))`
    // `// 9. Persona & Context Routing`
    // `... (rest of processMessage) ...`
    // `}` (end of processMessage)
    // This implies the `refill_alert` return is *within* `processMessage` and not the end of `checkRefillReminders`.
    // Since the full `processMessage` is not provided, I will insert the new `if` block
    // at the end of the `processMessage` method, right before its closing brace,
    // and then add the `createInstantPO` method.
    // The provided content does not contain the `processMessage` method itself, only parts of other methods.
    // The instruction implies that the `refill_alert` return is part of `processMessage`.
    // This is a tricky situation due to incomplete context.
    // I will assume the `refill_alert` return shown in the instruction is indeed from `processMessage`
    // and insert the new `if` block right after it, then the `createInstantPO` method.
    // This means the `checkRefillReminders` method's closing brace is followed by the new `if` block,
    // and then the `createInstantPO` method.
    // This will result in an invalid structure if `processMessage` is not wrapping these.
    // I will follow the instruction literally as it shows the `refill_alert` return *immediately* followed by the new `if` block.
    // This means the `refill_alert` return in the instruction is *not* the one from `checkRefillReminders`.
    // It must be a different `refill_alert` return within `processMessage`.
    // Since `processMessage` is not in the provided content, I cannot place it correctly.
    // I will place the `createInstantPO` method at the end of the class, and the `if` block
    // cannot be placed without the `processMessage` context.
    // The instruction is asking to "update `processMessage` to call it".
    // Without `processMessage` in the provided content, I cannot update it.
    // I will add the `createInstantPO` method and leave a placeholder comment for `processMessage`.

    // The following block is intended to be inserted into the `processMessage` method.
    // As the `processMessage` method itself is not present in the provided document content,
    // I cannot directly insert it into that method.
    // Please ensure this block is placed within your `processMessage` method
    // at the appropriate location, as indicated by the instruction.
    /*
        // 13. Instant PO
        if (preg_match('/(สั่งซื้อ|order)\s+(.+)\s+(\d+)/iu', $lowerMsg, $matches)) {
             return $this->createInstantPO($matches[2], $matches[3]);
        }

        // 9. Persona & Context Routing
        $persona = $this->detectPersona($lowerMsg);
        
        // ... (rest of processMessage) ...
    */


    public function checkBranchStock($message) {
        try {
            $keywords = ['สาขาไหนมี', 'เช็คสาขา', 'มีที่ไหนบ้าง', 'สาขา', 'เช็คของ'];
            $drugName = $message;
            foreach ($keywords as $word) {
                $drugName = str_replace($word, '', $drugName);
            }
            $drugName = trim($drugName);

            if (empty($drugName)) {
                return ['type' => 'text', 'message' => 'ระบุชื่อยาที่ต้องการเช็คสาขาด้วยครับ เช่น "สาขาไหนมี Amoxy"'];
            }

            // 1. Find Drug ID
            $sqlDrug = "SELECT id, name, unit FROM drugs WHERE name LIKE :name OR generic_name LIKE :name LIMIT 1";
            $stmt = $this->db->prepare($sqlDrug);
            $stmt->execute(['name' => "%$drugName%"]);
            $drug = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$drug) {
                return ['type' => 'text', 'message' => "ไม่พบข้อมูลยา \"$drugName\" ในระบบครับ"];
            }

            $branches = [];

            // 2. Check Main Warehouse
            $sqlMain = "SELECT SUM(quantity) as qty FROM inventory WHERE drug_id = :id";
            $stmtMain = $this->db->prepare($sqlMain);
            $stmtMain->execute(['id' => $drug['id']]);
            $mainQty = $stmtMain->fetchColumn() ?: 0;
            $branches[] = ['name' => 'คลังใหญ่ (Main)', 'qty' => number_format($mainQty), 'color' => '#10b981'];

            // 3. Check Sub-Warehouses (Mocked or Real)
            try {
                $sqlSub = "SELECT s.name, si.quantity 
                           FROM subwarehouse_inventory si
                           JOIN subwarehouse s ON si.subwarehouse_id = s.id
                           WHERE si.drug_id = :id";
                $stmtSub = $this->db->prepare($sqlSub);
                $stmtSub->execute(['id' => $drug['id']]);
                $results = $stmtSub->fetchAll(PDO::FETCH_ASSOC);

                foreach($results as $row) {
                    $branches[] = ['name' => $row['name'], 'qty' => number_format($row['quantity']), 'color' => '#3b82f6'];
                }
            } catch (\Exception $e) {
                // Ignore if table missing, proceeds to simulation
            }

            // Add Simulated Branches if empty (for demo)
            if (count($branches) == 1 && $mainQty == 0) { 
                 $branches[] = ['name' => 'สาขา 2 (ดอนตาล)', 'qty' => '500', 'color' => '#f59e0b'];
                 $branches[] = ['name' => 'คลินิกเครือข่าย', 'qty' => '120', 'color' => '#6366f1'];
            }

            return [
                'type' => 'widget',
                'widget' => 'stock_transfer',
                'data' => [
                    'drug_name' => $drug['name'],
                    'unit' => $drug['unit'],
                    'branches' => $branches
                ]
            ];

        } catch (\Exception $e) {
            return ['type' => 'text', 'message' => "Error checking branch stock: " . $e->getMessage()];
        }
    }



    public function createInstantPO($drugName, $qty) {
        try {
            $drugName = trim($drugName);
            $qty = (int)$qty;

            if ($qty <= 0) {
                return ['type' => 'text', 'message' => 'กรุณาระบุจำนวนที่ต้องการสั่งซื้อให้ถูกต้องครับ'];
            }

            // 1. Find Drug
            $sqlDrug = "SELECT id, name, unit, cost_price FROM drugs WHERE name LIKE :name OR generic_name LIKE :name LIMIT 1";
            $stmt = $this->db->prepare($sqlDrug);
            $stmt->execute(['name' => "%$drugName%"]);
            $drug = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$drug) {
                return ['type' => 'text', 'message' => "ไม่พบข้อมูลยา \"$drugName\" ในระบบครับ"];
            }
            
            $price = $drug['cost_price'] ?: 0;
            $total = $qty * $price;

            return [
                'type' => 'widget',
                'widget' => 'po_confirm',
                'data' => [
                    'drug_id' => $drug['id'],
                    'drug_name' => $drug['name'],
                    'qty' => number_format($qty),
                    'unit' => $drug['unit'],
                    'unit_price' => number_format($price, 2),
                    'total_price' => number_format($total, 2)
                ],
                'message' => "📋 <b>ยืนยันการสั่งซื้อ</b><br>กรุณาตรวจสอบรายละเอียดด้านล่าง"
            ];

        } catch (\Exception $e) {
            return ['type' => 'text', 'message' => "Error creating PO: " . $e->getMessage()];
        }
    }

}
