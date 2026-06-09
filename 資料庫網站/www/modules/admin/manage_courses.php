<div class="card">
    <h2>📚 學期開課管理</h2>
    <p>管理員可在此新增、修改與刪除本學期的課程資訊。</p>

    <?php
    if ($_SESSION['role'] != 'Admin') {
        echo "<p style='color:red;'>無權限。</p>";
    } else {
        // 1. 處理新增課程
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_course'])) {
            $code = trim($_POST['course_code']);
            $name = trim($_POST['course_name']);
            $tid = intval($_POST['teacher_id']);
            $cap = intval($_POST['capacity']);
            $sch = trim($_POST['schedule']);
            $room = trim($_POST['room']);
            $sem = '113-1'; // 預設學期

            if ($code && $name) {
                $ins = $conn->prepare("INSERT INTO Courses (course_code, course_name, semester, teacher_id, capacity, schedule, room) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $ins->bind_param("sssiiss", $code, $name, $sem, $tid, $cap, $sch, $room);
                if ($ins->execute()) {
                    echo "<div class='card' style='background:#d4edda; border-left:4px solid #28a745;'><strong>✓ 成功：</strong>課程新增完成。</div>";
                } else {
                    echo "<div class='card' style='background:#f8d7da; border-left:4px solid #dc3545;'><strong>✗ 失敗：</strong>" . $conn->error . "</div>";
                }
            }
        }

        // 2. 處理修改課程 (本次新增)
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_course'])) {
            $cid = intval($_POST['course_id']);
            $code = trim($_POST['course_code']);
            $name = trim($_POST['course_name']);
            $tid = intval($_POST['teacher_id']);
            $cap = intval($_POST['capacity']);
            $sch = trim($_POST['schedule']);
            $room = trim($_POST['room']);

            if ($cid && $code && $name) {
                $upd = $conn->prepare("UPDATE Courses SET course_code=?, course_name=?, teacher_id=?, capacity=?, schedule=?, room=? WHERE course_id=?");
                $upd->bind_param("ssiissi", $code, $name, $tid, $cap, $sch, $room, $cid);
                if ($upd->execute()) {
                    echo "<div class='card' style='background:#d4edda; border-left:4px solid #28a745;'><strong>✓ 成功：</strong>課程資料已更新。</div>";
                }
            }
        }

        // 3. 處理刪除課程
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_course'])) {
            $del_id = intval($_POST['delete_course_id']);
            $conn->query("DELETE FROM Courses WHERE course_id = $del_id");
            echo "<div class='card' style='background:#d4edda; border-left:4px solid #28a745;'><strong>✓ 成功：</strong>課程已刪除。</div>";
        }

        // 準備教師選單 (供新增與修改表單使用)
        $teachers = $conn->query("SELECT teacher_id, name FROM Teachers");
        $teacher_options = "";
        while ($t = $teachers->fetch_assoc()) {
            $teacher_options .= "<option value='{$t['teacher_id']}'>" . htmlspecialchars($t['name']) . "</option>";
        }

        // 新增課程按鈕與隱藏表單
        echo "<button class='btn' style='background:#28a745; margin-bottom:15px;' onclick='toggleAddForm()'>＋ 新增課程</button>";

        echo "<form id='addCourseForm' method='POST' style='display:none; background:#f4f6f9; padding:20px; border-radius:5px; margin-bottom:20px; grid-template-columns: 1fr 1fr; gap:15px;'>";
        echo "<div style='grid-column: span 2;'><h4 style='margin-top:0; color:#28a745;'>📝 新增課程</h4></div>";
        echo "<div><label>課程代碼：</label><input type='text' name='course_code' required></div>";
        echo "<div><label>課程名稱：</label><input type='text' name='course_name' required></div>";
        echo "<div><label>授課教師：</label><select name='teacher_id' required><option value=''>請選擇教師...</option>$teacher_options</select></div>";
        echo "<div><label>修課人數上限：</label><input type='number' name='capacity' value='50' required></div>";
        echo "<div><label>上課時間：</label><input type='text' name='schedule' placeholder='如：一 2,3,4'></div>";
        echo "<div><label>上課教室：</label><input type='text' name='room' placeholder='如：資工系館 R101'></div>";
        echo "<div style='grid-column: span 2;'><button type='submit' name='add_course' class='btn' style='background:#007bff; width:100%;'>確認新增</button></div>";
        echo "</form>";

        // 課程列表
        echo "<table style='width:100%; border-collapse:collapse; margin-bottom:30px;'>";
        echo "<tr style='background:#f4f6f9;'><th style='padding:10px;'>代碼</th><th style='padding:10px;'>名稱</th><th style='padding:10px;'>教師</th><th style='padding:10px;'>時間</th><th style='padding:10px;'>教室</th><th style='padding:10px;'>上限</th><th style='padding:10px;'>操作</th></tr>";

        $courses = $conn->query("
            SELECT c.*, t.name as teacher_name 
            FROM Courses c 
            LEFT JOIN Teachers t ON c.teacher_id = t.teacher_id 
            ORDER BY c.course_code
        ");

        while ($c = $courses->fetch_assoc()) {
            echo "<tr style='border-bottom:1px solid #eee;'>";
            echo "<td style='padding:10px;'>" . htmlspecialchars($c['course_code']) . "</td>";
            echo "<td style='padding:10px;'>" . htmlspecialchars($c['course_name']) . "</td>";
            echo "<td style='padding:10px;'>" . htmlspecialchars($c['teacher_name'] ?? '未指派') . "</td>";
            echo "<td style='padding:10px;'>" . htmlspecialchars($c['schedule']) . "</td>";
            echo "<td style='padding:10px;'>" . htmlspecialchars($c['room']) . "</td>";
            echo "<td style='padding:10px;'>" . htmlspecialchars($c['capacity']) . "</td>";
            
            // 操作按鈕區 (加入修改按鈕)
            echo "<td style='padding:10px; display:flex; gap:5px;'>";
            // 修改按鈕 (利用 data attributes 將原始資料傳遞給 JS)
            echo "<button type='button' class='btn' style='background:#ffc107; color:#333; padding:5px 10px; font-size:0.9em;' 
                    data-id='{$c['course_id']}' 
                    data-code='" . htmlspecialchars($c['course_code'], ENT_QUOTES) . "' 
                    data-name='" . htmlspecialchars($c['course_name'], ENT_QUOTES) . "' 
                    data-tid='{$c['teacher_id']}' 
                    data-cap='{$c['capacity']}' 
                    data-sch='" . htmlspecialchars($c['schedule'], ENT_QUOTES) . "' 
                    data-room='" . htmlspecialchars($c['room'], ENT_QUOTES) . "' 
                    onclick='openEditModal(this)'>修改</button>";

            // 刪除按鈕
            echo "<form method='POST' style='margin:0;'>
                    <input type='hidden' name='delete_course_id' value='{$c['course_id']}'>
                    <button type='submit' name='delete_course' class='btn' style='background:#dc3545; padding:5px 10px; font-size:0.9em;' onclick='return confirm(\"確定刪除此課程？相關的選課與成績紀錄也將一併刪除！\");'>刪除</button>
                  </form>";
            echo "</td></tr>";
        }
        echo "</table>";
        
        ?>
        <div id="editCourseModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:9999; justify-content:center; align-items:center;">
            <div style="background:#fff; width:90%; max-width:500px; border-radius:8px; box-shadow:0 5px 15px rgba(0,0,0,0.3); overflow:hidden;">
                <div style="background:#ffc107; color:#333; padding:15px 20px; font-weight:bold; font-size:1.2em; display:flex; justify-content:space-between; align-items:center;">
                    <span>✏️ 修改課程資訊</span>
                    <span style="cursor:pointer; font-size:1.5em; line-height:1;" onclick="closeEditModal()">&times;</span>
                </div>
                <form method="POST" style="padding:20px; display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                    <input type="hidden" name="course_id" id="edit_course_id">
                    <div><label>課程代碼：</label><input type="text" name="course_code" id="edit_course_code" required></div>
                    <div><label>課程名稱：</label><input type="text" name="course_name" id="edit_course_name" required></div>
                    <div style="grid-column: span 2;"><label>授課教師：</label>
                        <select name="teacher_id" id="edit_teacher_id" required>
                            <option value=''>請選擇教師...</option>
                            <?php echo $teacher_options; ?>
                        </select>
                    </div>
                    <div><label>上課時間：</label><input type="text" name="schedule" id="edit_schedule"></div>
                    <div><label>上課教室：</label><input type="text" name="room" id="edit_room"></div>
                    <div style="grid-column: span 2;"><label>修課人數上限：</label><input type="number" name="capacity" id="edit_capacity" required></div>
                    
                    <div style="grid-column: span 2; text-align:right; margin-top:10px;">
                        <button type="button" class="btn" style="background:#6c757d; margin-right:10px;" onclick="closeEditModal()">取消</button>
                        <button type="submit" name="update_course" class="btn" style="background:#28a745;">儲存修改</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
        // 控制「新增課程」表單的開關
        function toggleAddForm() {
            var form = document.getElementById('addCourseForm');
            if(form.style.display === 'none') {
                form.style.display = 'grid';
            } else {
                form.style.display = 'none';
            }
        }

        // 開啟「修改」視窗並自動填入原本的資料
        function openEditModal(btn) {
            document.getElementById('edit_course_id').value = btn.getAttribute('data-id');
            document.getElementById('edit_course_code').value = btn.getAttribute('data-code');
            document.getElementById('edit_course_name').value = btn.getAttribute('data-name');
            document.getElementById('edit_teacher_id').value = btn.getAttribute('data-tid');
            document.getElementById('edit_schedule').value = btn.getAttribute('data-sch');
            document.getElementById('edit_room').value = btn.getAttribute('data-room');
            document.getElementById('edit_capacity').value = btn.getAttribute('data-cap');
            
            document.getElementById('editCourseModal').style.display = 'flex';
        }

        // 關閉修改視窗
        function closeEditModal() {
            document.getElementById('editCourseModal').style.display = 'none';
        }
        
        // 點擊黑色背景也可以關閉視窗
        window.onclick = function(event) {
            var modal = document.getElementById('editCourseModal');
            if (event.target == modal) {
                closeEditModal();
            }
        }
        </script>
        <?php
    }
    ?>
</div>