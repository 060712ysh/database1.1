<div class="card">
    <h2>📋 教學大綱與負責課程</h2>
    <p>以下是您本學期負責的課程。點擊編輯可更新教學大綱資料：</p>
    
    <?php
    if(!isset($_SESSION['teacher_id'])) {
        echo "<p style='color:red;'>您不是教師，無法使用此功能。</p>";
    } else {
        $teacher_id = $_SESSION['teacher_id'];
        
        // 處理大綱編輯
        if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_syllabus'])) {
            $course_id = intval($_POST['course_id']);
            $syllabus = trim($_POST['syllabus'] ?? '');
            
            $update = $conn->prepare("UPDATE Courses SET syllabus = ? WHERE course_id = ? AND teacher_id = ?");
            $update->bind_param("sii", $syllabus, $course_id, $teacher_id);
            $update->execute();
            echo "<div class='card' style='background:#d4edda; border-left:4px solid #28a745;'><strong>✓ 成功：</strong>教學大綱已更新</div>";
            $update->close();
        }
        
        // 如果選擇編輯某門課程
        $editing_course_id = isset($_POST['edit_course']) ? intval($_POST['edit_course']) : null;
        
        if($editing_course_id) {
            // 取得課程資訊
            $course = $conn->prepare(
                "SELECT course_id, course_code, course_name, semester, syllabus
                 FROM Courses
                 WHERE course_id = ? AND teacher_id = ?"
            );
            $course->bind_param("ii", $editing_course_id, $teacher_id);
            $course->execute();
            $course_result = $course->get_result()->fetch_assoc();
            $course->close();
            
            if($course_result) {
                echo "<div style='background:#f4f6f9; padding:15px; border-radius:5px; margin-bottom:20px;'>";
                echo "<h4>編輯課程大綱</h4>";
                echo "<p><strong>課程：</strong>" . htmlspecialchars($course_result['course_code'] . " - " . $course_result['course_name']) . "</p>";
                echo "<form method='POST'>";
                echo "<input type='hidden' name='course_id' value='" . $editing_course_id . "'>";
                echo "<label>教學大綱與教學計畫：</label>";
                echo "<textarea name='syllabus' rows='8' style='width:100%; padding:10px; border:1px solid #ccc; border-radius:4px;'>" . htmlspecialchars($course_result['syllabus'] ?? '') . "</textarea>";
                echo "<br><br>";
                echo "<button type='submit' name='update_syllabus' class='btn'>保存大綱</button>";
                echo "</form>";
                echo "</div>";
            }
        }
        
        echo "<table style='width:100%; text-align:left; border-collapse: collapse; margin-top:15px;'>";
        echo "<tr style='border-bottom:2px solid #343a40; background:#f4f6f9;'>";
        echo "<th style='padding:10px;'>學期</th>";
        echo "<th style='padding:10px;'>課程代碼</th>";
        echo "<th style='padding:10px;'>課程名稱</th>";
        echo "<th style='padding:10px;'>上課時間</th>";
        echo "<th style='padding:10px;'>操作</th>";
        echo "</tr>";
        
        // 取得該教師的課程
        $courses = $conn->prepare(
            "SELECT course_id, semester, course_code, course_name, schedule
             FROM Courses
             WHERE teacher_id = ?
             ORDER BY semester DESC, course_code"
        );
        $courses->bind_param("i", $teacher_id);
        $courses->execute();
        $courses_result = $courses->get_result();
        
        while($c = $courses_result->fetch_assoc()) {
            echo "<tr style='border-bottom:1px solid #e0e0e0;'>";
            echo "<td style='padding:10px;'>" . htmlspecialchars($c['semester']) . "</td>";
            echo "<td style='padding:10px;'>" . htmlspecialchars($c['course_code']) . "</td>";
            echo "<td style='padding:10px;'>" . htmlspecialchars($c['course_name']) . "</td>";
            echo "<td style='padding:10px;'>" . htmlspecialchars($c['schedule']) . "</td>";
            echo "<td style='padding:10px;'>";
            echo "<form method='POST' style='display:inline;'>";
            echo "<input type='hidden' name='edit_course' value='" . $c['course_id'] . "'>";
            echo "<button type='submit' class='btn'>編輯大綱</button>";
            echo "</form>";
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
        $courses->close();
    }
    ?>
</div>
