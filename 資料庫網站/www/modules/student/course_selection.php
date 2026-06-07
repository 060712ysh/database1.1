<div class="card">
    <h2>🖱️ 線上自主選課系統</h2>
    
    <?php
    if(!isset($_SESSION['student_id'])) {
        echo "<p style='color:red;'>您不是學生，無法使用此功能。</p>";
    } else {
        $student_id = $_SESSION['student_id'];
        
        // 處理加退選
        if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
            $action = $_POST['action'];
            $course_id = intval($_POST['course_id']);
            
            if($action == 'enroll') {
                $check = $conn->prepare("SELECT * FROM Enrollments WHERE student_id = ? AND course_id = ?");
                $check->bind_param("si", $student_id, $course_id);
                $check->execute();
                if($check->get_result()->num_rows == 0) {
                    $cap = $conn->prepare("SELECT c.capacity, COUNT(e.enrollment_id) as enrolled FROM Courses c LEFT JOIN Enrollments e ON c.course_id = e.course_id WHERE c.course_id = ? GROUP BY c.course_id");
                    $cap->bind_param("i", $course_id);
                    $cap->execute();
                    $cap_result = $cap->get_result()->fetch_assoc();
                    
                    if($cap_result['enrolled'] < $cap_result['capacity']) {
                        $insert = $conn->prepare("INSERT INTO Enrollments (student_id, course_id) VALUES (?, ?)");
                        $insert->bind_param("si", $student_id, $course_id);
                        $insert->execute();
                        echo "<div class='card' style='background:#d4edda; border-left:4px solid #28a745;'><strong>✓ 成功：</strong>已加選此課程</div>";
                    } else {
                        echo "<div class='card' style='background:#f8d7da; border-left:4px solid #dc3545;'><strong>✗ 失敗：</strong>該課程人數已滿</div>";
                    }
                } else {
                    echo "<div class='card' style='background:#f8d7da; border-left:4px solid #dc3545;'><strong>✗ 失敗：</strong>您已經選過此課程</div>";
                }
            } else if($action == 'drop') {
                $delete = $conn->prepare("DELETE FROM Enrollments WHERE student_id = ? AND course_id = ?");
                $delete->bind_param("si", $student_id, $course_id);
                $delete->execute();
                echo "<div class='card' style='background:#d4edda; border-left:4px solid #28a745;'><strong>✓ 成功：</strong>已退選此課程</div>";
            }
        }
        
        // 取得課程 (加入 syllabus 欄位)
        $courses = $conn->query("
            SELECT c.course_id, c.course_code, c.course_name, c.schedule, c.room, c.syllabus, 
                   t.name as teacher_name, c.capacity, COUNT(e.enrollment_id) as enrolled
            FROM Courses c
            LEFT JOIN Teachers t ON c.teacher_id = t.teacher_id
            LEFT JOIN Enrollments e ON c.course_id = e.course_id
            WHERE c.semester = '113-1' GROUP BY c.course_id ORDER BY c.course_code
        ");
        
        $my_courses = $conn->query("SELECT course_id FROM Enrollments WHERE student_id = '$student_id'");
        $my_course_ids = [];
        while($row = $my_courses->fetch_assoc()) $my_course_ids[] = $row['course_id'];
        
        echo "<p style='color:#007bff; font-weight:bold;'>當前學期：113-1 ｜ 🟢 加退選開放中</p>";
        echo "<table style='width:100%; text-align:left; border-collapse: collapse; margin-top:10px;'>";
        // 表頭加入「課程大綱」
        echo "<tr style='border-bottom:2px solid #343a40; background:#f4f6f9;'><th style='padding:10px;'>代碼</th><th style='padding:10px;'>課程名稱</th><th style='padding:10px;'>教師</th><th style='padding:10px;'>上課時間與地點</th><th style='padding:10px;'>課程大綱</th><th style='padding:10px;'>人數狀況</th><th style='padding:10px;'>操作</th></tr>";
        
        while($course = $courses->fetch_assoc()) {
            $is_enrolled = in_array($course['course_id'], $my_course_ids);
            $is_full = $course['enrolled'] >= $course['capacity'];
            $color = $is_enrolled ? '#e8f4fd' : ($is_full ? '#fff3f3' : '');
            
            echo "<tr style='border-bottom:1px solid #e0e0e0; background:$color;'>";
            echo "<td style='padding:10px; font-weight:bold;'>" . htmlspecialchars($course['course_code']) . "</td>";
            echo "<td style='padding:10px;'>" . htmlspecialchars($course['course_name']) . "</td>";
            echo "<td style='padding:10px;'>" . htmlspecialchars($course['teacher_name'] ?? '未指派') . "</td>";
            echo "<td style='padding:10px;'><span style='color:#d35400; font-weight:bold;'>🕒 " . htmlspecialchars($course['schedule']) . "</span><br><span style='color:#666; font-size:0.9em;'>📍 " . htmlspecialchars($course['room'] ?? '未定') . "</span></td>";
            
            // 新增：課程大綱按鈕與隱藏資料
            echo "<td style='padding:10px;'>";
            echo "<button type='button' class='btn' style='background:#17a2b8; padding:5px 10px; font-size:0.9em;' onclick='openSyllabusModal({$course['course_id']})'>📄 查看</button>";
            // 將大綱內容藏在這裡，供 JS 讀取
            echo "<div id='syllabus_content_{$course['course_id']}' style='display:none;'>" . nl2br(htmlspecialchars($course['syllabus'] ?? '老師尚未上傳教學大綱。')) . "</div>";
            echo "<input type='hidden' id='syllabus_title_{$course['course_id']}' value='" . htmlspecialchars($course['course_name']) . "'>";
            echo "</td>";

            echo "<td style='padding:10px;" . ($is_full ? "color:red;" : "color:green;") . "'>" . $course['enrolled'] . " / " . $course['capacity'] . ($is_full ? " (額滿)" : "") . "</td>";
            echo "<td style='padding:10px;'>";
            $form_action = $is_enrolled ? 'drop' : 'enroll';
            $btn_style = $is_enrolled ? "background:#dc3545;" : ($is_full ? "background:#6c757d;" : "background:#28a745;");
            $btn_text = $is_enrolled ? '❌ 退選' : ($is_full ? '額滿' : '➕ 加選');
            $btn_disabled = ($is_full && !$is_enrolled) ? "disabled" : "";
            
            echo "<form method='POST' style='margin:0;'><input type='hidden' name='action' value='$form_action'><input type='hidden' name='course_id' value='{$course['course_id']}'><button type='submit' class='btn' style='$btn_style' $btn_disabled>$btn_text</button></form>";
            echo "</td></tr>";
        }
        echo "</table>";
    }
    ?>
</div>

<div id="syllabusModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:9999; justify-content:center; align-items:center;">
    <div style="background:#fff; width:90%; max-width:600px; border-radius:8px; box-shadow:0 5px 15px rgba(0,0,0,0.3); display:flex; flex-direction:column; max-height:80vh;">
        <div style="background:#007bff; color:#fff; padding:15px 20px; border-radius:8px 8px 0 0; display:flex; justify-content:space-between; align-items:center;">
            <h3 id="modalTitle" style="margin:0; font-size:1.2em;">課程大綱</h3>
            <button type="button" onclick="closeSyllabusModal()" style="background:none; border:none; color:#fff; font-size:1.8em; cursor:pointer; padding:0; line-height:1;">&times;</button>
        </div>
        <div id="modalContent" style="padding:20px; overflow-y:auto; line-height:1.6; color:#495057; font-size:0.95em;">
            </div>
        <div style="padding:15px; border-top:1px solid #dee2e6; text-align:right; background:#f8f9fa; border-radius:0 0 8px 8px;">
            <button type="button" class="btn" style="background:#6c757d;" onclick="closeSyllabusModal()">關閉視窗</button>
        </div>
    </div>
</div>

<script>
// 開啟大綱視窗
function openSyllabusModal(courseId) {
    var title = document.getElementById('syllabus_title_' + courseId).value;
    var content = document.getElementById('syllabus_content_' + courseId).innerHTML;
    
    document.getElementById('modalTitle').innerText = '📚 ' + title + ' - 課程大綱';
    document.getElementById('modalContent').innerHTML = content;
    
    var modal = document.getElementById('syllabusModal');
    modal.style.display = 'flex'; // 使用 flex 讓畫面居中
}

// 關閉大綱視窗
function closeSyllabusModal() {
    document.getElementById('syllabusModal').style.display = 'none';
}

// 點擊黑色半透明背景時，也能自動關閉視窗
window.onclick = function(event) {
    var modal = document.getElementById('syllabusModal');
    if (event.target == modal) {
        modal.style.display = "none";
    }
}
</script>