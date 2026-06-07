<div class="card">
    <h2>📚 學期開課管理</h2>
    <?php
    if($_SESSION['role'] != 'Admin') {
        echo "<p style='color:red;'>無權限。</p>";
    } else {
        if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_course'])) {
            $code = trim($_POST['course_code']);
            $name = trim($_POST['course_name']);
            $sem = trim($_POST['semester']);
            $tid = intval($_POST['teacher_id']);
            $cap = intval($_POST['capacity']);
            $sch = trim($_POST['schedule']);
            $room = trim($_POST['room']); // 新增教室
            
            $tid_val = ($tid > 0) ? $tid : null;
            $ins = $conn->prepare("INSERT INTO Courses (course_code, course_name, semester, teacher_id, capacity, schedule, room) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $ins->bind_param("sssiiss", $code, $name, $sem, $tid_val, $cap, $sch, $room);
            $ins->execute();
            echo "<div class='card' style='background:#d4edda; border-left:4px solid #28a745;'><strong>✓ 成功：</strong>課程已新增</div>";
        }
        
        if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_course'])) {
            $cid = intval($_POST['course_id']);
            $conn->query("DELETE FROM Courses WHERE course_id = $cid");
            echo "<div class='card' style='background:#d4edda; border-left:4px solid #28a745;'>已刪除</div>";
        }
        
        echo "<button class='btn' style='margin-bottom:15px; background:#28a745;' onclick='toggleForm()'>＋ 新增課程</button>";
        echo "<div id='addCourseForm' style='background:#f4f6f9; padding:15px; border-radius:5px; margin-bottom:20px; display:none;'>";
        echo "<form method='POST'><div style='display:grid; grid-template-columns: 1fr 1fr; gap:15px; margin-bottom:15px;'>";
        echo "<div><label>課程代碼：</label><input type='text' name='course_code' required></div>";
        echo "<div><label>課程名稱：</label><input type='text' name='course_name' required></div>";
        echo "<div><label>學期：</label><input type='text' name='semester' value='113-1' required></div>";
        echo "<div><label>授課教師：</label><select name='teacher_id'><option value='0'>-- 未指派 --</option>";
        $teachers = $conn->query("SELECT teacher_id, name FROM Teachers ORDER BY name");
        while($t = $teachers->fetch_assoc()) { echo "<option value='{$t['teacher_id']}'>{$t['name']}</option>"; }
        echo "</select></div>";
        echo "<div><label>上課時間：</label><input type='text' name='schedule' placeholder='如：一 2,3,4'></div>";
        echo "<div><label>上課教室：</label><input type='text' name='room' placeholder='如：資工館 R102'></div>";
        echo "<div><label>人數上限：</label><input type='number' name='capacity' value='50'></div>";
        echo "</div><button type='submit' name='add_course' class='btn'>新增課程</button></form></div>";
        
        echo "<table style='width:100%; border-collapse: collapse;'>";
        echo "<tr style='background:#f4f6f9;'><th style='padding:10px;'>代碼</th><th style='padding:10px;'>名稱</th><th style='padding:10px;'>教師</th><th style='padding:10px;'>時間</th><th style='padding:10px;'>教室</th><th style='padding:10px;'>上限</th><th style='padding:10px;'>操作</th></tr>";
        $courses = $conn->query("SELECT c.*, t.name as tname FROM Courses c LEFT JOIN Teachers t ON c.teacher_id = t.teacher_id ORDER BY semester DESC, course_code");
        while($c = $courses->fetch_assoc()) {
            echo "<tr style='border-bottom:1px solid #eee;'>";
            echo "<td style='padding:10px;'>{$c['course_code']}</td><td style='padding:10px;'>{$c['course_name']}</td><td style='padding:10px;'>".($c['tname']??'-')."</td><td style='padding:10px;'>{$c['schedule']}</td><td style='padding:10px; color:#17a2b8; font-weight:bold;'>".($c['room']??'未排定')."</td><td style='padding:10px;'>{$c['capacity']}</td>";
            echo "<td style='padding:10px;'><form method='POST'><input type='hidden' name='course_id' value='{$c['course_id']}'><button type='submit' name='delete_course' class='btn' style='background:#dc3545;'>刪除</button></form></td></tr>";
        }
        echo "</table>";
    }
    ?>
    <script>function toggleForm() { var f = document.getElementById('addCourseForm'); f.style.display = f.style.display === 'none' ? 'block' : 'none'; }</script>
</div>