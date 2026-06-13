<div class="card">
    <h2>📅 我的學期課表與成績查詢</h2>
    <p>檢視本學期已選修的課程、上課時間地點，以及各階段成績與學期總平均。</p>
    
    <?php
    if(!isset($_SESSION['student_id'])) {
        echo "<p style='color:red;'>請先登入學生帳號</p>";
    } else {
        $student_id = $_SESSION['student_id'];
        
        // 撈取學生的選課與成績
        $query = $conn->query("
            SELECT e.*, c.course_code, c.course_name, c.schedule, c.room, t.name as teacher_name
            FROM Enrollments e
            JOIN Courses c ON e.course_id = c.course_id
            LEFT JOIN Teachers t ON c.teacher_id = t.teacher_id
            WHERE e.student_id = '$student_id'
            ORDER BY c.course_code
        ");
        
        if($query && $query->num_rows > 0) {
            echo "<table style='width:100%; border-collapse:collapse; margin-bottom:20px; text-align:left;'>";
            echo "<tr style='background:#f4f6f9; border-bottom:2px solid #ddd;'>
                    <th style='padding:10px;'>課程代碼</th>
                    <th style='padding:10px;'>課程名稱</th>
                    <th style='padding:10px;'>授課教師</th>
                    <th style='padding:10px;'>上課時間地點</th>
                    <th style='padding:10px;'>平時成績</th>
                    <th style='padding:10px;'>期中成績</th>
                    <th style='padding:10px;'>期末成績</th>
                    <th style='padding:10px;'>課程總成績</th>
                  </tr>";
            
            $total_score_sum = 0;
            $graded_courses_count = 0;
            
            while($r = $query->fetch_assoc()) {
                $c_total = $r['total_score'];
                $display_total = ($c_total !== null) ? round($c_total, 2) : '尚未結算';
                $total_color = ($c_total !== null && $c_total >= 60) ? '#007bff' : (($c_total !== null) ? '#dc3545' : '#666');
                
                // 累加已結算總分
                if($c_total !== null) {
                    $total_score_sum += $c_total;
                    $graded_courses_count++;
                }
                
                echo "<tr style='border-bottom:1px solid #eee;'>";
                echo "<td style='padding:10px; font-weight:bold;'>{$r['course_code']}</td>";
                echo "<td style='padding:10px;'>" . htmlspecialchars($r['course_name']) . "</td>";
                echo "<td style='padding:10px;'>" . htmlspecialchars($r['teacher_name'] ?? '未指派') . "</td>";
                echo "<td style='padding:10px;'><span style='color:#d35400;'>🕒 {$r['schedule']}</span><br><span style='color:#666; font-size:0.9em;'>📍 {$r['room']}</span></td>";
                echo "<td style='padding:10px;'>" . ($r['assignment_scores'] !== null ? $r['assignment_scores'] : '-') . "</td>";
                echo "<td style='padding:10px;'>" . ($r['midterm_score'] !== null ? $r['midterm_score'] : '-') . "</td>";
                echo "<td style='padding:10px;'>" . ($r['final_score'] !== null ? $r['final_score'] : '-') . "</td>";
                echo "<td style='padding:10px; font-weight:bold; color:{$total_color};'>{$display_total}</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // ⚠️【優化修正算法】不計個別加權，直取 (每門課程總成績之和 / 總選修科目數)
            echo "<div style='background:#e8f4fd; padding:15px; border-radius:6px; border:1px solid #b8daff; display:inline-block;'>";
            if($graded_courses_count > 0) {
                $semester_avg = round($total_score_sum / $graded_courses_count, 2);
                $avg_color = $semester_avg >= 60 ? '#28a745' : '#dc3545';
                echo "📊 <strong>學期平均成績：</strong> 已結算課程共 <span style='color:#007bff; font-weight:bold;'>$graded_courses_count</span> 門，學期總平均為 <span style='color:$avg_color; font-weight:bold; font-size:1.25em;'>$semester_avg</span> 分。";
            } else {
                echo "📊 <strong>學期平均成績：</strong> 目前尚無任何修讀課程完成分數結算。";
            }
            echo "</div>";
        } else {
            echo "<p style='color:#999; text-align:center; padding:20px; background:#f9f9f9; border-radius:6px;'>您本學期尚未修讀任何課程。</p>";
        }
    }
    ?>
</div>