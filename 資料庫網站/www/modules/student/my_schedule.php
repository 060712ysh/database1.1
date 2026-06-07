<div class="card">
    <h2>📅 我的課表與歷年成績</h2>
    
    <?php
    if(!isset($_SESSION['student_id'])) {
        echo "<p style='color:red;'>您不是學生，無法使用此功能。</p>";
    } else {
        $student_id = $_SESSION['student_id'];
        $selected_semester = isset($_POST['semester']) ? $_POST['semester'] : '113-1';
        
        echo "<div style='margin-bottom:20px;'>";
        echo "<form method='POST' style='display:flex; gap:10px; align-items:flex-end;'>";
        echo "<div>";
        echo "<label>查詢學期：</label>";
        echo "<select name='semester' style='padding:8px;'>";
        
        // 列出所有學期
        $semesters_result = $conn->query("SELECT DISTINCT semester FROM Courses ORDER BY semester DESC");
        while($sem = $semesters_result->fetch_assoc()) {
            $selected = ($sem['semester'] == $selected_semester) ? 'selected' : '';
            echo "<option value='" . htmlspecialchars($sem['semester']) . "' $selected>" . htmlspecialchars($sem['semester']);
            if($sem['semester'] == '113-1') echo " (本學期)";
            echo "</option>";
        }
        echo "</select>";
        echo "</div>";
        echo "<button type='submit' class='btn'>查詢</button>";
        echo "</form>";
        echo "</div>";
        
        // 取得該學期的選課紀錄
        $enrollments = $conn->prepare("
            SELECT e.enrollment_id, c.course_code, c.course_name, c.schedule,
                   e.assignment_scores, e.midterm_score, e.final_score, e.total_score
            FROM Enrollments e
            JOIN Courses c ON e.course_id = c.course_id
            WHERE e.student_id = ? AND c.semester = ?
            ORDER BY c.course_code
        ");
        $enrollments->bind_param("ss", $student_id, $selected_semester);
        $enrollments->execute();
        $enrollments_result = $enrollments->get_result();
        
        echo "<table style='width:100%; text-align:left; border-collapse: collapse;'>";
        echo "<tr style='border-bottom:2px solid #343a40; background:#f4f6f9;'>";
        echo "<th style='padding:10px;'>代碼</th>";
        echo "<th style='padding:10px;'>課程名稱</th>";
        echo "<th style='padding:10px;'>上課時間</th>";
        echo "<th style='padding:10px;'>平時</th>";
        echo "<th style='padding:10px;'>期中</th>";
        echo "<th style='padding:10px;'>期末</th>";
        echo "<th style='padding:10px;'>總成績</th>";
        echo "</tr>";
        
        $total_score_sum = 0;
        $count = 0;
        $has_courses = false;
        
        while($enroll = $enrollments_result->fetch_assoc()) {
            $has_courses = true;
            $total = ($enroll['assignment_scores'] + $enroll['midterm_score'] + $enroll['final_score']) / 3;
            if($total > 0) {
                $total_score_sum += $total;
                $count++;
            }
            
            echo "<tr style='border-bottom:1px solid #e0e0e0;'>";
            echo "<td style='padding:10px;'>" . htmlspecialchars($enroll['course_code']) . "</td>";
            echo "<td style='padding:10px;'>" . htmlspecialchars($enroll['course_name']) . "</td>";
            echo "<td style='padding:10px;'>" . htmlspecialchars($enroll['schedule']) . "</td>";
            echo "<td style='padding:10px;'>" . ($enroll['assignment_scores'] != null ? $enroll['assignment_scores'] : '-') . "</td>";
            echo "<td style='padding:10px;'>" . ($enroll['midterm_score'] != null ? $enroll['midterm_score'] : '-') . "</td>";
            echo "<td style='padding:10px;'>" . ($enroll['final_score'] != null ? $enroll['final_score'] : '-') . "</td>";
            echo "<td style='padding:10px;'>" . ($enroll['total_score'] != null ? $enroll['total_score'] : '-') . "</td>";
            echo "</tr>";
        }
        
        if($has_courses) {
            echo "<tr style='border-bottom:1px solid #e0e0e0; background:#f9f9f9;'>";
            echo "<td colspan='6' style='padding:10px; text-align:right;'>學期平均：</td>";
            echo "<td style='padding:10px; font-weight:bold; color:#007bff;'>" . ($count > 0 ? round($total_score_sum / $count, 1) : '計算中') . "</td>";
            echo "</tr>";
        } else {
            echo "<tr><td colspan='7' style='padding:20px; text-align:center; color:#999;'>此學期無選課紀錄</td></tr>";
        }
        echo "</table>";
        
        $enrollments->close();
    }
    ?>
</div>
