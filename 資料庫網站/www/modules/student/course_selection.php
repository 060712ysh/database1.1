<div class="card">
    <h2>🖱️ 線上自主選課系統</h2>
    
    <?php
    if(!isset($_SESSION['student_id'])) {
        echo "<p style='color:red;'>您不是學生，無法使用此功能。</p>";
    } else {
        $student_id = $_SESSION['student_id'];
        
        // --- 處理送出申請單 ---
        if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
            $action = $_POST['action']; // 'Add' 或是 'Drop'
            $course_id = intval($_POST['course_id']);
            
            // 檢查是否已經有審核中的相同申請，避免重複送出
            $check_req = $conn->query("SELECT request_id FROM CourseRequests WHERE student_id='$student_id' AND course_id=$course_id AND status='Pending'");
            if ($check_req->num_rows == 0) {
                $ins_req = $conn->prepare("INSERT INTO CourseRequests (student_id, course_id, action) VALUES (?, ?, ?)");
                $ins_req->bind_param("sis", $student_id, $course_id, $action);
                $ins_req->execute();
                echo "<div class='card' style='background:#d4edda; border-left:4px solid #28a745;'><strong>✓ 成功：</strong>申請已送出，請等待系辦審核。</div>";
            } else {
                echo "<div class='card' style='background:#fff3cd; border-left:4px solid #ffc107;'>您已經送出過申請，目前正在審核中。</div>";
            }
        }
        
        // 取得課程與選課人數
        $courses = $conn->query("
            SELECT c.course_id, c.course_code, c.course_name, c.schedule, c.room, c.syllabus, 
                   t.name as teacher_name, c.capacity, COUNT(e.enrollment_id) as enrolled
            FROM Courses c
            LEFT JOIN Teachers t ON c.teacher_id = t.teacher_id
            LEFT JOIN Enrollments e ON c.course_id = e.course_id
            WHERE c.semester = '113-1' GROUP BY c.course_id ORDER BY c.course_code
        ");
        
        // 取得學生目前已確定的課表
        $my_courses = $conn->query("SELECT course_id FROM Enrollments WHERE student_id = '$student_id'");
        $my_course_ids = [];
        while($row = $my_courses->fetch_assoc()) $my_course_ids[] = $row['course_id'];

        // 取得學生目前審核中的申請
        $pending_reqs = $conn->query("SELECT course_id, action FROM CourseRequests WHERE student_id = '$student_id' AND status = 'Pending'");
        $pending_data = [];
        while($row = $pending_reqs->fetch_assoc()) {
            $pending_data[$row['course_id']] = $row['action']; // 'Add' 或 'Drop'
        }
        
        echo "<p style='color:#007bff; font-weight:bold;'>當前學期：113-1 ｜ 🟢 加退選開放申請中</p>";
        echo "<table style='width:100%; text-align:left; border-collapse: collapse; margin-top:10px;'>";
        echo "<tr style='border-bottom:2px solid #343a40; background:#f4f6f9;'><th style='padding:10px;'>代碼</th><th style='padding:10px;'>課程名稱</th><th style='padding:10px;'>教師</th><th style='padding:10px;'>上課時間與地點</th><th style='padding:10px;'>大綱</th><th style='padding:10px;'>人數狀況</th><th style='padding:10px;'>操作</th></tr>";
        
        while($course = $courses->fetch_assoc()) {
            $is_enrolled = in_array($course['course_id'], $my_course_ids);
            $pending_action = $pending_data[$course['course_id']] ?? null;
            $is_full = $course['enrolled'] >= $course['capacity'];
            
            $bg_color = $is_enrolled ? '#e8f4fd' : ($is_full ? '#fff3f3' : '');
            
            echo "<tr style='border-bottom:1px solid #e0e0e0; background:$bg_color;'>";
            echo "<td style='padding:10px; font-weight:bold;'>" . htmlspecialchars($course['course_code']) . "</td>";
            echo "<td style='padding:10px;'>" . htmlspecialchars($course['course_name']) . "</td>";
            echo "<td style='padding:10px;'>" . htmlspecialchars($course['teacher_name'] ?? '未指派') . "</td>";
            echo "<td style='padding:10px;'><span style='color:#d35400; font-weight:bold;'>🕒 " . htmlspecialchars($course['schedule']) . "</span><br><span style='color:#666; font-size:0.9em;'>📍 " . htmlspecialchars($course['room'] ?? '未定') . "</span></td>";
            
            echo "<td style='padding:10px;'><button type='button' class='btn' style='background:#17a2b8; padding:5px 10px; font-size:0.9em;' onclick='openSyllabusModal({$course['course_id']})'>📄 查看</button>";
            echo "<div id='syllabus_content_{$course['course_id']}' style='display:none;'>" . nl2br(htmlspecialchars($course['syllabus'] ?? '無大綱')) . "</div>";
            echo "<input type='hidden' id='syllabus_title_{$course['course_id']}' value='" . htmlspecialchars($course['course_name']) . "'></td>";

            echo "<td style='padding:10px;" . ($is_full ? "color:red;" : "color:green;") . "'>" . $course['enrolled'] . " / " . $course['capacity'] . ($is_full ? " (滿)" : "") . "</td>";
            
            // 動態按鈕邏輯
            echo "<td style='padding:10px;'>";
            if ($pending_action == 'Add') {
                echo "<button class='btn' style='background:#6c757d; cursor:not-allowed;' disabled>⏳ 加選審核中</button>";
            } else if ($pending_action == 'Drop') {
                echo "<button class='btn' style='background:#6c757d; cursor:not-allowed;' disabled>⏳ 退選審核中</button>";
            } else if ($is_enrolled) {
                echo "<form method='POST' style='margin:0;'><input type='hidden' name='action' value='Drop'><input type='hidden' name='course_id' value='{$course['course_id']}'><button type='submit' class='btn' style='background:#dc3545;'>❌ 申請退選</button></form>";
            } else {
                if ($is_full) {
                    echo "<button class='btn' style='background:#ccc; cursor:not-allowed;' disabled>額滿</button>";
                } else {
                    echo "<form method='POST' style='margin:0;'><input type='hidden' name='action' value='Add'><input type='hidden' name='course_id' value='{$course['course_id']}'><button type='submit' class='btn' style='background:#28a745;'>➕ 申請加選</button></form>";
                }
            }
            echo "</td></tr>";
        }
        echo "</table>";
    }
    ?>
</div>

<script>
function openSyllabusModal(courseId) {
    var title = document.getElementById('syllabus_title_' + courseId).value;
    var content = document.getElementById('syllabus_content_' + courseId).innerHTML;
    alert('【' + title + ' 大綱】\n' + content.replace(/<br\s*[\/]?>/gi, "\n")); // 簡單示範，您可保留原本的華麗 Modal 程式碼
}
</script>