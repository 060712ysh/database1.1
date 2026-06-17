<div class="card">
    <h2>🖱️ 線上選課系統</h2>
    
    <?php
    if(!isset($_SESSION['role']) || $_SESSION['role'] != 'Student') {
        echo "<p style='color:red;'>只有學生可以使用此功能。</p>";
    } else {
        $student_id = $_SESSION['username']; 

        // 檢查全域系統設定：加退選是否開放
        $sys = $conn->query("SELECT setting_value FROM SystemSettings WHERE setting_key = 'enrollment_status'");
        $sys_status = 'open';
        if ($sys && $sys->num_rows > 0) {
            $sys_status = $sys->fetch_assoc()['setting_value'];
        }

        if ($sys_status == 'closed') {
            echo "<div style='background:#f8d7da; padding:15px; border-left:4px solid #dc3545; color:#721c24; margin-bottom:20px;'><strong>⚠️ 目前非加退選期間：</strong>系統暫不開放選課異動。</div>";
        } else {
            echo "<div style='background:#d4edda; padding:15px; border-left:4px solid #28a745; color:#155724; margin-bottom:20px;'><strong>🟢 加退選開放申請中</strong>：請留意上課時間，衝堂課程將無法加選。</div>";
        }

        // ==========================================
        // 處理 1：加選與退選邏輯 (包含衝堂防呆檢查)
        // ==========================================
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_course']) && $sys_status == 'open') {
            $course_id = intval($_POST['course_id']);
            
            $q_target = $conn->query("SELECT course_name, schedule FROM Courses WHERE course_id = $course_id");
            $target_course = $q_target->fetch_assoc();
            $target_sch = trim($target_course['schedule']);
            
            $has_conflict = false;
            $conflict_msg = "";

            if (!empty($target_sch) && strpos($target_sch, ' ') !== false) {
                list($t_day, $t_periods_str) = explode(' ', $target_sch);
                $t_periods = array_map('trim', explode(',', $t_periods_str));

                // 撈取已選上與審核中的課程時間
                $q_current = $conn->query("
                    SELECT c.course_name, c.schedule 
                    FROM Courses c 
                    WHERE c.course_id IN (
                        SELECT course_id FROM Enrollments WHERE student_id = '$student_id'
                        UNION 
                        SELECT course_id FROM CourseRequests WHERE student_id = '$student_id' AND status = 'Pending'
                    )
                ");

                while ($curr = $q_current->fetch_assoc()) {
                    $c_sch = trim($curr['schedule']);
                    if (!empty($c_sch) && strpos($c_sch, ' ') !== false) {
                        list($c_day, $c_periods_str) = explode(' ', $c_sch);
                        if ($t_day === $c_day) {
                            $c_periods = array_map('trim', explode(',', $c_periods_str));
                            $intersection = array_intersect($t_periods, $c_periods);
                            
                            if (count($intersection) > 0) {
                                $has_conflict = true;
                                $conflict_msg = "「" . htmlspecialchars($curr['course_name']) . "」時間重疊 (星期{$t_day} 第 " . implode(',', $intersection) . " 節)";
                                break;
                            }
                        }
                    }
                }
            }

            // 若衝堂，拒絕寫入
            if ($has_conflict) {
                echo "<div class='card' style='background:#f8d7da; border-left:4px solid #dc3545;'><strong>⛔ 衝堂阻擋：</strong>無法加選，與您目前的 {$conflict_msg}！</div>";
            } else {
                $chk_enr = $conn->query("SELECT * FROM Enrollments WHERE student_id='$student_id' AND course_id=$course_id");
                $chk_req = $conn->query("SELECT * FROM CourseRequests WHERE student_id='$student_id' AND course_id=$course_id AND status='Pending'");
                
                if($chk_enr->num_rows > 0) {
                    echo "<div class='card' style='background:#fff3cd; border-left:4px solid #ffc107;'>⚠️ 您已經選上此課程了。</div>";
                } elseif($chk_req->num_rows > 0) {
                    echo "<div class='card' style='background:#fff3cd; border-left:4px solid #ffc107;'>⚠️ 您已經送出申請，目前正在等待審核中。</div>";
                } else {
                    $cap_q = $conn->query("SELECT capacity, (SELECT COUNT(*) FROM Enrollments WHERE course_id = Courses.course_id) as enrolled FROM Courses WHERE course_id = $course_id");
                    $cap_data = $cap_q->fetch_assoc();
                    
                    if($cap_data['enrolled'] >= $cap_data['capacity']) {
                        $conn->query("INSERT INTO CourseRequests (student_id, course_id, action, status) VALUES ('$student_id', $course_id, 'Add', 'Pending')");
                        echo "<div class='card' style='background:#d1ecf1; border-left:4px solid #17a2b8;'><strong>📝 課程已額滿：</strong>已為您送出加簽申請單，請等候教師審核。</div>";
                    } else {
                        $conn->query("INSERT INTO Enrollments (student_id, course_id) VALUES ('$student_id', $course_id)");
                        echo "<div class='card' style='background:#d4edda; border-left:4px solid #28a745;'><strong>✓ 加選成功：</strong>您已成功選上此課程！</div>";
                    }
                }
            }
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['drop_course']) && $sys_status == 'open') {
            $course_id = intval($_POST['course_id']);
            $conn->query("DELETE FROM Enrollments WHERE student_id='$student_id' AND course_id=$course_id");
            $conn->query("DELETE FROM CourseRequests WHERE student_id='$student_id' AND course_id=$course_id");
            echo "<div class='card' style='background:#d4edda; border-left:4px solid #28a745;'><strong>✓ 退選成功：</strong>已為您退選此課程。</div>";
        }


        // ==========================================
        // 區塊 1：視覺化預排課表
        // ==========================================
        echo "<div style='display:flex; justify-content:space-between; align-items:flex-end; margin-top: 30px; border-bottom: 2px solid #1976d2; padding-bottom: 10px; margin-bottom: 20px;'>";
        echo "  <h3 style='margin:0; color: #1976d2;'>📅 預排課表狀態</h3>";
        echo "  <div style='font-size:0.9em; color:#555;'>";
        echo "      <span style='display:inline-block; width:12px; height:12px; background:#d4edda; border-left:4px solid #28a745; margin-right:5px;'></span> 已核准 / 已選上 &nbsp;&nbsp;";
        echo "      <span style='display:inline-block; width:12px; height:12px; background:#e8f4fd; border-left:4px solid #17a2b8; margin-right:5px;'></span> 額滿加簽中";
        echo "  </div>";
        echo "</div>";

        $timetable = [];
        for ($d = 1; $d <= 5; $d++) {
            for ($p = 1; $p <= 14; $p++) {
                $timetable[$d][$p] = null;
            }
        }
        $day_map = ['一'=>1, '二'=>2, '三'=>3, '四'=>4, '五'=>5];

        $q_schedule = $conn->query("
            SELECT c.course_name, c.schedule, c.room, 'Approved' as status 
            FROM Courses c 
            JOIN Enrollments e ON c.course_id = e.course_id 
            WHERE e.student_id = '$student_id'
            UNION
            SELECT c.course_name, c.schedule, c.room, 'Pending' as status 
            FROM Courses c 
            JOIN CourseRequests cr ON c.course_id = cr.course_id 
            WHERE cr.student_id = '$student_id' AND cr.status = 'Pending'
        ");

        if ($q_schedule && $q_schedule->num_rows > 0) {
            while($c = $q_schedule->fetch_assoc()) {
                $sch = trim($c['schedule']);
                if (!empty($sch) && strpos($sch, ' ') !== false) {
                    $parts = explode(' ', $sch);
                    if (count($parts) >= 2) {
                        $d_num = $day_map[$parts[0]] ?? 0;
                        $periods = explode(',', $parts[1]);
                        foreach($periods as $p) {
                            $p = trim($p);
                            if ($d_num && $d_num <= 5 && is_numeric($p) && $p >= 1 && $p <= 14) {
                                $timetable[$d_num][$p] = [
                                    'name' => $c['course_name'],
                                    'room' => $c['room'],
                                    'status' => $c['status']
                                ];
                            }
                        }
                    }
                }
            }
        }

        $days_label = [1=>'星期一', 2=>'星期二', 3=>'星期三', 4=>'星期四', 5=>'星期五'];
        echo "<div style='overflow-x: auto; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); border: 1px solid #dee2e6; margin-bottom: 40px;'>";
        echo "<table style='width: 100%; min-width: 600px; border-collapse: collapse; font-size: 0.95em;'>";
        echo "<tr style='background: #f8f9fa;'>";
        echo "<th style='padding: 10px; border: 1px solid #dee2e6; width: 60px; color:#495057;'>節次</th>";
        foreach ($days_label as $d_label) echo "<th style='padding: 10px; border: 1px solid #dee2e6; width: 18.8%; color:#495057;'>$d_label</th>";
        echo "</tr>";
        
        for ($i = 1; $i <= 14; $i++) {
            echo "<tr>";
            echo "<td style='padding: 8px; border: 1px solid #dee2e6; font-weight: bold; color: #495057; text-align:center; background: #fff;'>{$i}</td>";
            for ($d = 1; $d <= 5; $d++) {
                echo "<td style='padding: 8px; border: 1px solid #dee2e6; vertical-align: top; background: #fff;'>";
                $cell = $timetable[$d][$i];
                if ($cell) {
                    if ($cell['status'] == 'Approved') {
                        $bg = '#d4edda'; $border = '#28a745'; $text = '#155724';
                    } else {
                        $bg = '#e8f4fd'; $border = '#17a2b8'; $text = '#0c5460';
                    }
                    echo "<div style='background: {$bg}; border-left: 4px solid {$border}; padding: 6px; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);'>";
                    echo "<strong style='color: {$text}; display: block; margin-bottom: 2px; font-size:0.95em;'>" . htmlspecialchars($cell['name']) . "</strong>";
                    echo "<span style='color: #555; font-size: 0.8em; display:block;'>📍 " . htmlspecialchars($cell['room'] ?? '未定') . "</span>";
                    echo "</div>";
                }
                echo "</td>";
            }
            echo "</tr>";
        }
        echo "</table></div>";


        // ==========================================
        // 區塊 2：可選課程清單 (按鈕區)
        // ==========================================
        echo "<h3 style='color: #343a40; border-bottom: 2px solid #343a40; padding-bottom: 10px; margin-bottom: 15px;'>📝 系統開放課程清單</h3>";
        
        $my_enrollments = [];
        $res1 = $conn->query("SELECT course_id FROM Enrollments WHERE student_id='$student_id'");
        while($r = $res1->fetch_assoc()) $my_enrollments[] = $r['course_id'];
        
        $my_pending = [];
        $res2 = $conn->query("SELECT course_id FROM CourseRequests WHERE student_id='$student_id' AND status='Pending'");
        while($r = $res2->fetch_assoc()) $my_pending[] = $r['course_id'];

        echo "<table style='width:100%; border-collapse:collapse; text-align:left; font-size:0.95em;'>";
        echo "<tr style='background:#f4f6f9; border-bottom:2px solid #343a40;'>";
        echo "<th style='padding:12px 10px;'>代碼</th><th style='padding:12px 10px;'>課程名稱</th><th style='padding:12px 10px;'>教師</th><th style='padding:12px 10px;'>上課時間與地點</th><th style='padding:12px 10px;'>大綱</th><th style='padding:12px 10px;'>人數狀況</th><th style='padding:12px 10px;'>操作</th></tr>";

        $courses = $conn->query("
            SELECT c.*, t.name as teacher_name, 
                   (SELECT COUNT(*) FROM Enrollments WHERE course_id = c.course_id) as enrolled_count 
            FROM Courses c 
            LEFT JOIN Teachers t ON c.teacher_id = t.teacher_id 
            ORDER BY c.course_code
        ");

        while($c = $courses->fetch_assoc()) {
            echo "<tr style='border-bottom:1px solid #eee;' onmouseover=\"this.style.background='#f9f9f9'\" onmouseout=\"this.style.background='#fff'\">";
            echo "<td style='padding:12px 10px; font-weight:bold;'>{$c['course_code']}</td>";
            echo "<td style='padding:12px 10px;'>".htmlspecialchars($c['course_name'])."</td>";
            echo "<td style='padding:12px 10px;'>".htmlspecialchars($c['teacher_name'] ?? '未定')."</td>";
            
            echo "<td style='padding:12px 10px; line-height:1.5;'>";
            echo "<strong style='color:#d35400;'>🕒 {$c['schedule']}</strong><br>";
            echo "<span style='color:#666; font-size:0.9em;'>📍 ".htmlspecialchars($c['room'] ?? '未定')."</span>";
            echo "</td>";
            
            echo "<td style='padding:12px 10px;'><a href='index.php?page=syllabus_detail&id={$c['course_id']}' style='background:#17a2b8; color:#fff; padding:4px 10px; border-radius:4px; text-decoration:none; font-size:0.9em;'>📄 查看</a></td>";
            
            $color = ($c['enrolled_count'] >= $c['capacity']) ? '#dc3545' : '#28a745';
            echo "<td style='padding:12px 10px; color:{$color}; font-weight:bold;'>{$c['enrolled_count']} / {$c['capacity']}</td>";
            
            echo "<td style='padding:12px 10px;'>";
            if ($sys_status == 'closed') {
                echo "<span style='color:#999;'>未開放</span>";
            } else {
                echo "<form method='POST' style='margin:0;'>";
                echo "<input type='hidden' name='course_id' value='{$c['course_id']}'>";
                if (in_array($c['course_id'], $my_enrollments)) {
                    echo "<button type='submit' name='drop_course' class='btn' style='background:#dc3545; padding:6px 12px; font-size:0.9em;' onclick='return confirm(\"確定要退選嗎？\");'>✖ 立即退選</button>";
                } elseif (in_array($c['course_id'], $my_pending)) {
                    echo "<button type='button' class='btn' style='background:#6c757d; padding:6px 12px; font-size:0.9em; cursor:not-allowed;' disabled>⏳ 審核中</button>";
                    echo "<br><button type='submit' name='drop_course' style='background:none; border:none; color:#dc3545; font-size:0.85em; cursor:pointer; margin-top:5px; text-decoration:underline;'>取消申請</button>";
                } else {
                    $btn_text = ($c['enrolled_count'] >= $c['capacity']) ? '📝 登記加簽' : '➕ 直接加選';
                    $btn_color = ($c['enrolled_count'] >= $c['capacity']) ? '#ffc107' : '#28a745';
                    $text_color = ($c['enrolled_count'] >= $c['capacity']) ? '#333' : '#fff';
                    echo "<button type='submit' name='add_course' class='btn' style='background:{$btn_color}; color:{$text_color}; padding:6px 12px; font-size:0.9em;'>{$btn_text}</button>";
                }
                echo "</form>";
            }
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    ?>
    
    <script>
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
    </script>
</div>