<div class="card">
    <h2>🖱️ 線上自主選課系統</h2>
    
    <?php
    if(!isset($_SESSION['student_id'])) {
        echo "<p style='color:red;'>您不是學生，無法使用此功能。</p>";
    } else {
        $student_id = $_SESSION['student_id'];
        
        // 取得目前系統的加退選開關狀態
        $status_query = $conn->query("SELECT setting_value FROM SystemSettings WHERE setting_key = 'enrollment_status'");
        $enrollment_status = $status_query->fetch_assoc()['setting_value'] ?? 'open';
        $is_open = ($enrollment_status === 'open');

        // --- 🚀 核心優化：處理即時選課、即時退選與滿人加簽審核 ---
        if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
            $action = $_POST['action']; 
            $course_id = intval($_POST['course_id']);
            
            // 【動作 A：取消加簽申請】(不論系統開放與否，皆允許學生抽回申請)
            if ($action == 'Cancel') {
                $del_req = $conn->prepare("DELETE FROM CourseRequests WHERE student_id=? AND course_id=? AND status='Pending'");
                $del_req->bind_param("si", $student_id, $course_id);
                if ($del_req->execute() && $del_req->affected_rows > 0) {
                    echo "<div class='card' style='background:#d4edda; border-left:4px solid #28a745;'><strong>✓ 成功：</strong>已成功撤回(取消)該筆加簽申請。</div>";
                }
            } 
            // 【動作 B：一般加選與退選】(必須在選課開放期間)
            else {
                if (!$is_open) {
                    echo "<div class='card' style='background:#f8d7da; border-left:4px solid #dc3545;'><strong>✗ 失敗：</strong>目前非加退選期間，無法進行操作。</div>";
                } else {
                    
                    // 🌟 1. 處理【即時退選】(完全免審核，點擊立即生效)
                    if ($action == 'Drop') {
                        $del_enroll = $conn->prepare("DELETE FROM Enrollments WHERE student_id=? AND course_id=?");
                        $del_enroll->bind_param("si", $student_id, $course_id);
                        if ($del_enroll->execute()) {
                            // 順便清理可能殘留的加簽單紀錄
                            $conn->query("DELETE FROM CourseRequests WHERE student_id='$student_id' AND course_id=$course_id AND status='Pending'");
                            echo "<div class='card' style='background:#d4edda; border-left:4px solid #28a745;'><strong>✓ 成功：</strong>已直接退選該課程，名額已釋出！</div>";
                        }
                    } 
                    
                    // 🌟 2. 處理【加選邏輯】(動態判斷人數是否額滿)
                    else if ($action == 'Add') {
                        // 即時查詢該課程的最新人數上限與已選人數
                        $stmt = $conn->prepare("
                            SELECT c.capacity, COUNT(e.enrollment_id) as enrolled, c.course_name 
                            FROM Courses c 
                            LEFT JOIN Enrollments e ON c.course_id = e.course_id 
                            WHERE c.course_id = ?
                            GROUP BY c.course_id
                        ");
                        $stmt->bind_param("i", $course_id);
                        $stmt->execute();
                        $stmt->bind_result($capacity, $enrolled, $c_name);
                        $stmt->fetch();
                        $stmt->close();
                        
                        if ($enrolled < $capacity) {
                            // 🟢 情況甲：人數未滿 -> 【免審核直接加選成功】
                            $ins_enroll = $conn->prepare("INSERT IGNORE INTO Enrollments (student_id, course_id) VALUES (?, ?)");
                            $ins_enroll->bind_param("si", $student_id, $course_id);
                            if ($ins_enroll->execute()) {
                                echo "<div class='card' style='background:#d4edda; border-left:4px solid #28a745;'><strong>✓ 成功：</strong>課程「{$c_name}」尚有餘額，已為您直接加選成功！</div>";
                            }
                        } else {
                            // 🔴 情況乙：人數已滿 -> 【自動轉為人工加簽單，需審核】
                            $check_req = $conn->query("SELECT request_id FROM CourseRequests WHERE student_id='$student_id' AND course_id=$course_id AND status='Pending'");
                            if ($check_req->num_rows == 0) {
                                $ins_req = $conn->prepare("INSERT INTO CourseRequests (student_id, course_id, action, status) VALUES (?, ?, 'Add', 'Pending')");
                                $ins_req->bind_param("si", $student_id, $course_id);
                                $ins_req->execute();
                                echo "<div class='card' style='background:#fff3cd; border-left:4px solid #ffc107;'><strong>⏳ 提示：</strong>課程「{$c_name}」人數已滿！系統已自動為您送出【加簽審核申請單】，請等待系辦審核。</div>";
                            } else {
                                echo "<div class='card' style='background:#fff3cd; border-left:4px solid #ffc107;'>您已送出過該課程的加簽申請，目前正在系辦審核中。</div>";
                            }
                        }
                    }
                }
            }
        }
        
        // --- 準備課表與列表資料查詢 ---
        $courses = $conn->query("
            SELECT c.course_id, c.course_code, c.course_name, c.schedule, c.room, 
                   t.name as teacher_name, c.capacity, COUNT(e.enrollment_id) as enrolled
            FROM Courses c
            LEFT JOIN Teachers t ON c.teacher_id = t.teacher_id
            LEFT JOIN Enrollments e ON c.course_id = e.course_id
            WHERE c.semester = '113-1' GROUP BY c.course_id ORDER BY c.course_code
        ");
        
        $my_courses = $conn->query("SELECT e.course_id, c.course_name, c.schedule FROM Enrollments e JOIN Courses c ON e.course_id = c.course_id WHERE e.student_id = '$student_id'");
        $my_course_ids = [];
        $enrolled_data = [];
        while($row = $my_courses->fetch_assoc()) {
            $my_course_ids[] = $row['course_id'];
            $enrolled_data[] = $row;
        }

        $pending_reqs = $conn->query("SELECT cr.course_id, cr.action, c.course_name, c.schedule FROM CourseRequests cr JOIN Courses c ON cr.course_id = c.course_id WHERE cr.student_id = '$student_id' AND cr.status = 'Pending'");
        $pending_data = [];
        $pending_add_data = []; 
        while($row = $pending_reqs->fetch_assoc()) {
            $pending_data[$row['course_id']] = $row['action']; 
            if ($row['action'] == 'Add') {
                $pending_add_data[] = $row;
            }
        }
        
        // ==========================================
        // 📊 繪製圖形化課表區塊 (5 天)
        // ==========================================
        $timetable = [];
        for ($d = 1; $d <= 5; $d++) {
            for ($p = 1; $p <= 14; $p++) {
                $timetable[$d][$p] = null;
            }
        }
        $day_map = ['一'=>1, '二'=>2, '三'=>3, '四'=>4, '五'=>5];

        // 1. 已選上的課程 (綠色)
        foreach ($enrolled_data as $course) {
            $sch = trim($course['schedule']);
            if (empty($sch)) continue;
            $parts = explode(' ', $sch); 
            if (count($parts) >= 2) {
                $d_num = $day_map[$parts[0]] ?? 0;
                $periods = explode(',', $parts[1]);
                foreach ($periods as $p) {
                    if ($d_num && $d_num <= 5 && is_numeric($p) && $p >= 1 && $p <= 14) {
                        $timetable[$d_num][$p] = ['name' => $course['course_name'], 'type' => 'enrolled'];
                    }
                }
            }
        }

        // 2. 預選加簽中的課程 (藍色)
        foreach ($pending_add_data as $course) {
            $sch = trim($course['schedule']);
            if (empty($sch)) continue;
            $parts = explode(' ', $sch);
            if (count($parts) >= 2) {
                $d_num = $day_map[$parts[0]] ?? 0;
                $periods = explode(',', $parts[1]);
                foreach ($periods as $p) {
                    if ($d_num && $d_num <= 5 && is_numeric($p) && $p >= 1 && $p <= 14 && !$timetable[$d_num][$p]) {
                        $timetable[$d_num][$p] = ['name' => $course['course_name'], 'type' => 'pending'];
                    }
                }
            }
        }

        echo "<div style='background:#f8f9fa; padding:20px; border-radius:8px; margin-bottom:30px; border:1px solid #dee2e6;'>";
        echo "<div style='display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;'>";
        echo "<h3 style='margin:0; color:#2c3e50;'>📅 我的預排課表</h3>";
        echo "<div style='font-size:0.9em;'><span style='display:inline-block; width:12px; height:12px; background:#d4edda; border-left:3px solid #28a745; margin-right:5px;'></span> 已核准 / 已選上 &nbsp;&nbsp;&nbsp; <span style='display:inline-block; width:12px; height:12px; background:#dbeafe; border-left:3px solid #007bff; margin-right:5px;'></span> 額滿加簽中 (待審核)</div>";
        echo "</div>";

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
                    $bg = ($cell['type'] == 'enrolled') ? '#d4edda' : '#dbeafe';
                    $border = ($cell['type'] == 'enrolled') ? '#28a745' : '#007bff';
                    $color = ($cell['type'] == 'enrolled') ? '#155724' : '#004085';
                    echo "<td style='border:1px solid #ddd; background:{$bg}; border-left:4px solid {$border}; color:{$color}; padding:5px; font-weight:bold; vertical-align:middle;'>" . htmlspecialchars($cell['name']) . "</td>";
                } else {
                    echo "<td style='border:1px solid #ddd;'></td>";
                }
            }
            echo "</tr>";
        }
        echo "</table></div>";

        // ==========================================
        // 📋 課程清單與按鈕渲染區塊
        // ==========================================
        if ($is_open) {
            echo "<p style='color:#007bff; font-weight:bold; font-size:1.1em;'>當前學期：113-1 ｜ 🟢 加退選開放申請中</p>";
        } else {
            echo "<p style='color:#dc3545; font-weight:bold; font-size:1.1em;'>當前學期：113-1 ｜ 🔴 加退選目前已關閉，暫停受理新申請</p>";
        }

        echo "<table style='width:100%; text-align:left; border-collapse: collapse; margin-top:10px;'>";
        echo "<tr style='border-bottom:2px solid #343a40; background:#f4f6f9;'><th style='padding:10px;'>代碼</th><th style='padding:10px;'>課程名稱</th><th style='padding:10px;'>教師</th><th style='padding:10px;'>上課時間與地點</th><th style='padding:10px;'>大綱</th><th style='padding:10px;'>人數狀況</th><th style='padding:10px;'>操作</th></tr>";
        
        while($course = $courses->fetch_assoc()) {
            $is_enrolled = in_array($course['course_id'], $my_course_ids);
            $pending_action = $pending_data[$course['course_id']] ?? null;
            $is_full = $course['enrolled'] >= $course['capacity'];
            $bg_color = $is_enrolled ? '#f8fff9' : ($is_full ? '#fff3f3' : '');
            
            echo "<tr style='border-bottom:1px solid #e0e0e0; background:$bg_color;'>";
            echo "<td style='padding:10px; font-weight:bold;'>" . htmlspecialchars($course['course_code']) . "</td>";
            echo "<td style='padding:10px;'>" . htmlspecialchars($course['course_name']) . "</td>";
            echo "<td style='padding:10px;'>" . htmlspecialchars($course['teacher_name'] ?? '未指派') . "</td>";
            echo "<td style='padding:10px;'><span style='color:#d35400; font-weight:bold;'>🕒 " . htmlspecialchars($course['schedule']) . "</span><br><span style='color:#666; font-size:0.9em;'>📍 " . htmlspecialchars($course['room'] ?? '未定') . "</span></td>";
            echo "<td style='padding:10px;'><a href='index.php?page=syllabus_detail&id={$course['course_id']}' class='btn' style='background:#17a2b8; padding:5px 10px; font-size:0.9em; text-decoration:none; color:#fff;'>📄 查看</a></td>";
            echo "<td style='padding:10px;" . ($is_full ? "color:#dc3545; font-weight:bold;" : "color:green;") . "'>" . $course['enrolled'] . " / " . $course['capacity'] . ($is_full ? " (已額滿)" : "") . "</td>";
            
            echo "<td style='padding:10px;'>";
            
            // 判斷按鈕狀態
            if ($pending_action == 'Add' || $pending_action == 'Drop') {
                $status_txt = ($pending_action == 'Add') ? "⏳ 加簽審核中" : "⏳ 退選審核中";
                echo "<div style='display:flex; gap:5px; align-items:center;'>";
                echo "<span style='background:#6c757d; color:#fff; padding:5px 10px; border-radius:4px; font-size:0.9em; cursor:not-allowed;'>{$status_txt}</span>";
                echo "<form method='POST' style='margin:0;'>
                        <input type='hidden' name='action' value='Cancel'>
                        <input type='hidden' name='course_id' value='{$course['course_id']}'>
                        <button type='submit' class='btn' style='background:#dc3545; padding:5px 8px; font-size:0.9em;' onclick='return confirm(\"確定要撤回此加簽申請嗎？\");'>✖ 取消</button>
                      </form>";
                echo "</div>";
            } else if (!$is_open) {
                echo "<button class='btn' style='background:#ccc; color:#666; cursor:not-allowed; border:1px solid #aaa;' disabled>⛔ 已關閉</button>";
            } else if ($is_enrolled) {
                // 已選上時，點擊直接執行退選
                echo "<form method='POST' style='margin:0;' onsubmit='return confirm(\"確定要退選這門課程嗎？名額會立刻釋出喔！\");'><input type='hidden' name='action' value='Drop'><input type='hidden' name='course_id' value='{$course['course_id']}'><button type='submit' class='btn' style='background:#dc3545;'>❌ 立即退選</button></form>";
            } else {
                if ($is_full) {
                    // 額滿時，按鈕提示為送出申請
                    echo "<form method='POST' style='margin:0;'><input type='hidden' name='action' value='Add'><input type='hidden' name='course_id' value='{$course['course_id']}'><button type='submit' class='btn' style='background:#ffc107; color:#333; font-weight:bold;'>✍️ 額滿申請加簽</button></form>";
                } else {
                    // 未滿時，按鈕提示為直接搶課
                    echo "<form method='POST' style='margin:0;'><input type='hidden' name='action' value='Add'><input type='hidden' name='course_id' value='{$course['course_id']}'><button type='submit' class='btn' style='background:#28a745;'>➕ 直接加選</button></form>";
                }
            }
            echo "</td></tr>";
        }
        echo "</table>";
    }
    ?>
</div>