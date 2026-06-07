<div class="card">
    <h2>💯 班級成績批次登錄與加權</h2>
    
    <?php
    if(!isset($_SESSION['teacher_id'])) {
        echo "<p style='color:red;'>您不是教師，無法使用此功能。</p>";
    } else {
        $teacher_id = $_SESSION['teacher_id'];
        
        // 處理成績提交
        if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_grades'])) {
            if(isset($_POST['course_id']) && isset($_POST['scores'])) {
                $course_id = intval($_POST['course_id']);
                $scores = $_POST['scores']; 
                // 取得自訂權重並轉換為小數
                $w_assign = floatval($_POST['w_assign'] ?? 30) / 100;
                $w_mid = floatval($_POST['w_mid'] ?? 30) / 100;
                $w_final = floatval($_POST['w_final'] ?? 40) / 100;
                
                // 檢查權重總和是否為 100%
                if (($w_assign + $w_mid + $w_final) != 1.0) {
                    echo "<div class='card' style='background:#f8d7da; border-left:4px solid #dc3545;'><strong>✗ 錯誤：</strong>權重總和必須為 100%！成績未儲存。</div>";
                } else {
                    foreach($scores as $enrollment_id => $score_data) {
                        $assignment = floatval($score_data['assignment'] ?? 0);
                        $midterm = floatval($score_data['midterm'] ?? 0);
                        $final = floatval($score_data['final'] ?? 0);
                        
                        // 使用加權計算總成績
                        $total = ($assignment * $w_assign) + ($midterm * $w_mid) + ($final * $w_final);
                        
                        $update = $conn->prepare(
                            "UPDATE Enrollments SET assignment_scores=?, midterm_score=?, final_score=?, total_score=? WHERE enrollment_id=?"
                        );
                        $update->bind_param("ddddi", $assignment, $midterm, $final, $total, $enrollment_id);
                        $update->execute();
                        $update->close();
                    }
                    echo "<div class='card' style='background:#d4edda; border-left:4px solid #28a745;'><strong>✓ 成功：</strong>成績已依據設定權重保存完畢！</div>";
                }
            }
        }
        
        // 取得該教師的課程
        $teacher_courses = $conn->prepare("SELECT course_id, course_code, course_name, semester FROM Courses WHERE teacher_id = ? ORDER BY semester DESC, course_code");
        $teacher_courses->bind_param("i", $teacher_id);
        $teacher_courses->execute();
        $courses_result = $teacher_courses->get_result();
        
        echo "<form method='POST' style='margin-bottom:15px; background:#f4f6f9; padding:15px; border-radius:5px;'>";
        echo "<label style='display:inline-block; margin-right:10px;'>選擇課程：</label>";
        echo "<select name='course_id' style='padding:8px; width:250px;'>";
        echo "<option value=''>-- 請選擇課程 --</option>";
        
        $selected_course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : null;
        while($course = $courses_result->fetch_assoc()) {
            if(!$selected_course_id) $selected_course_id = $course['course_id'];
            $selected = ($course['course_id'] == $selected_course_id) ? "selected" : "";
            echo "<option value='" . $course['course_id'] . "' $selected>" . htmlspecialchars($course['course_code'] . " - " . $course['course_name']) . "</option>";
        }
        echo "</select>";
        echo "<button type='submit' name='load_course' class='btn' style='margin-left:10px;'>載入名單</button>";
        echo "</form>";
        $teacher_courses->close();
        
        if($selected_course_id) {
            $verify = $conn->prepare("SELECT course_id FROM Courses WHERE course_id = ? AND teacher_id = ?");
            $verify->bind_param("ii", $selected_course_id, $teacher_id);
            $verify->execute();
            
            if($verify->get_result()->num_rows > 0) {
                $enrollments = $conn->prepare("
                    SELECT e.enrollment_id, e.student_id, s.name, e.assignment_scores, e.midterm_score, e.final_score, e.total_score
                    FROM Enrollments e JOIN Students s ON e.student_id = s.student_id
                    WHERE e.course_id = ? ORDER BY s.student_id
                ");
                $enrollments->bind_param("i", $selected_course_id);
                $enrollments->execute();
                $enrollments_result = $enrollments->get_result();
                
                echo "<form method='POST'>";
                echo "<input type='hidden' name='course_id' value='$selected_course_id'>";
                
                // 新增加權設定區塊
                echo "<div style='background:#e8f4fd; border:1px solid #b8daff; padding:15px; border-radius:5px; margin-bottom:15px; display:flex; gap:20px; align-items:center;'>";
                echo "<strong style='color:#004085;'>⚙️ 總成績加權設定：</strong>";
                echo "<div><label style='display:inline;'>平時 (%)：</label><input type='number' name='w_assign' value='30' style='width:60px; margin:0;' required></div>";
                echo "<div><label style='display:inline;'>期中 (%)：</label><input type='number' name='w_mid' value='30' style='width:60px; margin:0;' required></div>";
                echo "<div><label style='display:inline;'>期末 (%)：</label><input type='number' name='w_final' value='40' style='width:60px; margin:0;' required></div>";
                echo "</div>";
                
                echo "<table style='width:100%; text-align:left; border-collapse: collapse;'>";
                echo "<tr style='border-bottom:2px solid #343a40; background:#f4f6f9;'>";
                echo "<th style='padding:10px;'>學號</th><th style='padding:10px;'>姓名</th><th style='padding:10px;'>平時成績</th><th style='padding:10px;'>期中成績</th><th style='padding:10px;'>期末成績</th><th style='padding:10px;'>當前總成績</th>";
                echo "</tr>";
                
                $has_enrollments = false;
                while($enroll = $enrollments_result->fetch_assoc()) {
                    $has_enrollments = true;
                    echo "<tr style='border-bottom:1px solid #e0e0e0;'>";
                    echo "<td style='padding:10px;'>" . htmlspecialchars($enroll['student_id']) . "</td>";
                    echo "<td style='padding:10px;'>" . htmlspecialchars($enroll['name']) . "</td>";
                    echo "<td style='padding:10px;'><input type='number' name='scores[" . $enroll['enrollment_id'] . "][assignment]' value='" . ($enroll['assignment_scores'] ?? '') . "' step='0.5' min='0' max='100' style='width:70px; margin:0;'></td>";
                    echo "<td style='padding:10px;'><input type='number' name='scores[" . $enroll['enrollment_id'] . "][midterm]' value='" . ($enroll['midterm_score'] ?? '') . "' step='0.5' min='0' max='100' style='width:70px; margin:0;'></td>";
                    echo "<td style='padding:10px;'><input type='number' name='scores[" . $enroll['enrollment_id'] . "][final]' value='" . ($enroll['final_score'] ?? '') . "' step='0.5' min='0' max='100' style='width:70px; margin:0;'></td>";
                    echo "<td style='padding:10px; font-weight:bold; color:#007bff;'>" . ($enroll['total_score'] !== null ? round($enroll['total_score'], 1) : '-') . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
                
                if($has_enrollments) {
                    echo "<button type='submit' name='submit_grades' class='btn' style='margin-top:15px; background:#28a745;'>💾 儲存並計算加權總成績</button>";
                } else {
                    echo "<p style='color:#999; margin-top:20px;'>此課程沒有選課的學生</p>";
                }
                echo "</form>";
                $enrollments->close();
            }
            $verify->close();
        }
    }
    ?>
</div>