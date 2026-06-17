﻿﻿<div class="card">
    <h2>📘 課程大綱與課表管理</h2>
    <p>檢視您本學期的授課時間表，並為您的每一門課程編寫詳細的教學大綱與進度規劃。</p>

    <?php
    if(!isset($_SESSION['teacher_id'])) {
        echo "<p style='color:red;'>您不是教師，無法使用此功能。</p>";
    } else {
        $teacher_id = $_SESSION['teacher_id'];

        // --- 處理更新大綱 ---
        if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_syllabus'])) {
            $course_id = intval($_POST['course_id']);
            $syllabus_content = trim($_POST['syllabus']);
            
            // 確保只能更新自己的課程
            $upd = $conn->prepare("UPDATE Courses SET syllabus=? WHERE course_id=? AND teacher_id=?");
            $upd->bind_param("sii", $syllabus_content, $course_id, $teacher_id);
            if($upd->execute()) {
                echo "<div class='card' style='background:#d4edda; border-left:4px solid #28a745;'><strong>✓ 成功：</strong>教學大綱已儲存更新！</div>";
            } else {
                echo "<div class='card' style='background:#f8d7da; border-left:4px solid #dc3545;'><strong>✗ 失敗：</strong>儲存發生錯誤。</div>";
            }
        }

        // --- 撈取本學期老師負責的課程 ---
        $courses = $conn->query("SELECT course_id, course_code, course_name, schedule, room, syllabus, capacity FROM Courses WHERE teacher_id = $teacher_id AND semester = '113-1' ORDER BY course_code");
        
        $courses_data = [];
        while($c = $courses->fetch_assoc()) {
            $courses_data[] = $c;
        }

        // ==========================================
        // 📊 繪製圖形化課表區塊 (教師版：5天 x 14節)
        // ==========================================
        $timetable = [];
        for ($d = 1; $d <= 5; $d++) {
            for ($p = 1; $p <= 14; $p++) {
                $timetable[$d][$p] = null;
            }
        }
        $day_map = ['一'=>1, '二'=>2, '三'=>3, '四'=>4, '五'=>5];

        // 將課程填入課表陣列
        foreach ($courses_data as $course) {
            $sch = trim($course['schedule']);
            if (empty($sch)) continue;
            
            $parts = explode(' ', $sch); // 解析如 "一 2,3,4"
            if (count($parts) >= 2) {
                $d_num = $day_map[$parts[0]] ?? 0;
                $periods = explode(',', $parts[1]);
                foreach ($periods as $p) {
                    $p = trim($p);
                    if ($d_num && $d_num <= 5 && is_numeric($p) && $p >= 1 && $p <= 14) {
                        $timetable[$d_num][$p] = [
                            'name' => $course['course_name'],
                            'room' => $course['room']
                        ];
                    }
                }
            }
        }

        echo "<div style='background:#f8f9fa; padding:20px; border-radius:8px; margin-bottom:30px; border:1px solid #dee2e6;'>";
        echo "<h3 style='margin:0 0 15px 0; color:#2c3e50;'>📅 我的本學期授課課表</h3>";

        echo "<table style='width:100%; border-collapse:collapse; text-align:center; table-layout:fixed; font-size:0.95em; background:#fff;'>";
        echo "<tr style='background:#e9ecef; border-bottom:2px solid #ccc;'>";
        echo "<th style='border:1px solid #ddd; padding:10px; width:50px;'>節次</th>";
        $days = ['一', '二', '三', '四', '五'];
        foreach ($days as $day) echo "<th style='border:1px solid #ddd; padding:10px;'>星期{$day}</th>";
        echo "</tr>";

        for ($p = 1; $p <= 14; $p++) {
            echo "<tr>";
            echo "<td style='border:1px solid #ddd; font-weight:bold; background:#f4f6f9; color:#555;'>{$p}</td>";
            for ($d = 1; $d <= 5; $d++) {
                $cell = $timetable[$d][$p];
                if ($cell) {
                    // 教師授課使用醒目的紫色系標示
                    echo "<td style='border:1px solid #ddd; background:#f4ebff; border-left:4px solid #6f42c1; color:#4a238b; padding:5px; vertical-align:middle;'>";
                    echo "<strong style='display:block; margin-bottom:3px;'>" . htmlspecialchars($cell['name']) . "</strong>";
                    echo "<span style='font-size:0.85em; color:#666;'>📍 " . htmlspecialchars($cell['room'] ?? '未定') . "</span>";
                    echo "</td>";
                } else {
                    echo "<td style='border:1px solid #ddd;'></td>";
                }
            }
            echo "</tr>";
        }
        echo "</table></div>";

        // ==========================================
        // 📝 教學大綱編輯區塊
        // ==========================================
        echo "<h3 style='color:#007bff; border-bottom:2px solid #007bff; padding-bottom:5px;'>📝 編輯課程大綱</h3>";

        if(count($courses_data) > 0) {
            foreach($courses_data as $course) {
                echo "<div style='background:#fff; border:1px solid #17a2b8; border-top:4px solid #17a2b8; border-radius:6px; padding:20px; margin-bottom:20px; box-shadow:0 2px 5px rgba(0,0,0,0.05);'>";
                
                // 課程標頭資訊
                echo "<div style='display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:15px;'>";
                echo "<div>";
                echo "<h4 style='margin:0 0 5px 0; color:#2c3e50; font-size:1.3em;'>" . htmlspecialchars($course['course_code']) . " - " . htmlspecialchars($course['course_name']) . "</h4>";
                echo "<span style='color:#d35400; font-weight:bold; margin-right:15px;'>🕒 " . htmlspecialchars($course['schedule']) . "</span>";
                echo "<span style='color:#28a745; font-weight:bold;'>📍 " . htmlspecialchars($course['room'] ?? '教室未定') . "</span>";
                echo "</div>";
                echo "<div style='color:#666; font-size:0.9em; background:#f4f6f9; padding:5px 10px; border-radius:4px;'>修課人數上限：{$course['capacity']} 人</div>";
                echo "</div>";
                
                // 大綱編輯表單
                echo "<form method='POST' style='margin:0;'>";
                echo "<input type='hidden' name='course_id' value='{$course['course_id']}'>";
                echo "<textarea name='syllabus' rows='6' placeholder='請在此輸入課程的教學目標、每週進度規劃、評分標準等詳細資訊...' style='width:100%; padding:12px; border:1px solid #ced4da; border-radius:4px; resize:vertical; font-family:inherit; font-size:1em; margin-bottom:15px; box-sizing:border-box;'>" . htmlspecialchars($course['syllabus'] ?? '') . "</textarea>";
                
                echo "<div style='text-align:right;'>";
                echo "<button type='submit' name='update_syllabus' class='btn' style='background:#17a2b8; padding:8px 25px; font-size:1em;'>💾 儲存大綱</button>";
                echo "</div>";
                echo "</form>";
                echo "</div>";
            }
        } else {
            echo "<div style='background:#f8f9fa; padding:30px; text-align:center; color:#999; border-radius:8px; border:1px dashed #ccc;'>您本學期目前沒有被安排授課。</div>";
        }
    }
    ?>
</div>