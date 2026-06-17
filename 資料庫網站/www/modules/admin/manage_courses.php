﻿<div class="card">
    <h2>📚 學期開課管理</h2>
    <p>管理員可在此新增、修改與刪除本學期的課程資訊。</p>

    <?php
    if ($_SESSION['role'] != 'Admin') {
        echo "<p style='color:red;'>無權限。</p>";
    } else {
        // --- 處理時間組合函式 ---
        function buildScheduleString($day, $periods_arr) {
            if (!$day || empty($periods_arr)) return '';
            sort($periods_arr, SORT_NUMERIC); 
            return $day . ' ' . implode(',', $periods_arr);
        }

        // --- 🚀 核心衝堂檢查引擎 ---
        // 回傳 false 代表沒衝突；回傳字串代表衝突的錯誤訊息
        function checkConflict($conn, $sem, $sch_day, $sch_periods_arr, $room, $tid, $exclude_cid = 0) {
            $safe_sem = $conn->real_escape_string($sem);
            $safe_exclude_cid = intval($exclude_cid);

            // 撈取同一個學期所有的課程進行比對
            $res = $conn->query("SELECT course_code, course_name, schedule, room, teacher_id FROM Courses WHERE semester = '$safe_sem' AND course_id != $safe_exclude_cid");

            while($row = $res->fetch_assoc()) {
                $existing_sch = trim($row['schedule']);
                if(empty($existing_sch)) continue;

                $parts = explode(' ', $existing_sch);
                if(count($parts) < 2) continue;

                $e_day = $parts[0];
                $e_periods = explode(',', $parts[1]);

                // 如果星期一樣，就比對節次有沒有交集
                if($e_day === $sch_day) {
                    $intersect = array_intersect($sch_periods_arr, $e_periods);
                    if(count($intersect) > 0) {
                        $conflict_periods_str = implode(',', $intersect);

                        // 檢查 1：同一個老師衝堂
                        if($row['teacher_id'] == $tid) {
                            return "【教師衝堂】該授課教師在同一時間已被安排教授「{$row['course_code']} {$row['course_name']}」(衝突節次：星期{$sch_day} 第 {$conflict_periods_str} 節)。";
                        }

                        // 檢查 2：同一個教室衝堂
                        if(trim($row['room']) === trim($room)) {
                            return "【教室衝堂】「{$room}」在該時段已被「{$row['course_code']} {$row['course_name']}」借用 (衝突節次：星期{$sch_day} 第 {$conflict_periods_str} 節)。";
                        }
                    }
                }
            }
            return false;
        }

        // --- 處理 1：新增課程 ---
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_course'])) {
            $code = trim($_POST['course_code']);
            $name = trim($_POST['course_name']);
            $tid = intval($_POST['teacher_id']);
            $cap = intval($_POST['capacity']);
            $room = trim($_POST['room']);
            $sem = '113-1';
            
            $sch_day = $_POST['sch_day'] ?? '';
            $sch_periods = $_POST['sch_periods'] ?? [];
            $sch = buildScheduleString($sch_day, $sch_periods);
            
            $error_msg = "";

            // 基礎防呆檢查
            if(empty($code) || empty($name) || empty($room) || $tid <= 0) $error_msg = "請填寫所有必填欄位！";
            else if($cap <= 0) $error_msg = "修課人數上限必須大於 0！";
            else if(empty($sch_day) || empty($sch_periods)) $error_msg = "請確實勾選上課的星期與節次！";
            
            // 課程代碼重複檢查
            if(empty($error_msg)) {
                $chk_code = $conn->query("SELECT course_id FROM Courses WHERE course_code = '{$conn->real_escape_string($code)}'");
                if($chk_code && $chk_code->num_rows > 0) {
                    $error_msg = "【代碼重複】課程代碼「{$code}」已被現有課程使用，請更換！";
                }
            }

            // 衝堂檢查
            if(empty($error_msg)) {
                $conflict = checkConflict($conn, $sem, $sch_day, $sch_periods, $room, $tid, 0);
                if($conflict) $error_msg = $conflict;
            }

            // 執行結果判定
            if(empty($error_msg)) {
                $ins = $conn->prepare("INSERT INTO Courses (course_code, course_name, semester, teacher_id, capacity, schedule, room) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $ins->bind_param("sssiiss", $code, $name, $sem, $tid, $cap, $sch, $room);
                if ($ins->execute()) {
                    echo "<div class='card' style='background:#d4edda; border-left:4px solid #28a745;'><strong>✓ 成功：</strong>課程「{$name}」新增完成，且通過所有排課檢查。</div>";
                } else {
                    echo "<div class='card' style='background:#f8d7da; border-left:4px solid #dc3545;'><strong>✗ 失敗：</strong>" . $conn->error . "</div>";
                }
            } else {
                echo "<div class='card' style='background:#f8d7da; border-left:4px solid #dc3545;'><strong>✗ 新增失敗：</strong><br>{$error_msg}</div>";
            }
        }

        // --- 處理 2：修改課程 ---
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_course'])) {
            $cid = intval($_POST['course_id']);
            $code = trim($_POST['course_code']);
            $name = trim($_POST['course_name']);
            $tid = intval($_POST['teacher_id']);
            $cap = intval($_POST['capacity']);
            $room = trim($_POST['room']);
            $sem = '113-1';
            
            $sch_day = $_POST['sch_day'] ?? '';
            $sch_periods = $_POST['sch_periods'] ?? [];
            $sch = buildScheduleString($sch_day, $sch_periods);

            $error_msg = "";

            if(empty($code) || empty($name) || empty($room) || $tid <= 0) $error_msg = "請填寫所有必填欄位！";
            else if($cap <= 0) $error_msg = "修課人數上限必須大於 0！";
            else if(empty($sch_day) || empty($sch_periods)) $error_msg = "請確實勾選上課的星期與節次！";
            
            // 課程代碼重複檢查 (排除自己)
            if(empty($error_msg)) {
                $chk_code = $conn->query("SELECT course_id FROM Courses WHERE course_code = '{$conn->real_escape_string($code)}' AND course_id != $cid");
                if($chk_code && $chk_code->num_rows > 0) {
                    $error_msg = "【代碼重複】課程代碼「{$code}」已被其他課程使用，請更換！";
                }
            }

            // 衝堂檢查 (排除自己)
            if(empty($error_msg)) {
                $conflict = checkConflict($conn, $sem, $sch_day, $sch_periods, $room, $tid, $cid);
                if($conflict) $error_msg = $conflict;
            }

            // 執行結果判定
            if(empty($error_msg)) {
                $upd = $conn->prepare("UPDATE Courses SET course_code=?, course_name=?, teacher_id=?, capacity=?, schedule=?, room=? WHERE course_id=?");
                $upd->bind_param("ssiissi", $code, $name, $tid, $cap, $sch, $room, $cid);
                if ($upd->execute()) {
                    echo "<div class='card' style='background:#d4edda; border-left:4px solid #28a745;'><strong>✓ 成功：</strong>課程「{$name}」資料已更新，且通過排課檢查。</div>";
                }
            } else {
                echo "<div class='card' style='background:#f8d7da; border-left:4px solid #dc3545;'><strong>✗ 修改失敗：</strong><br>{$error_msg}</div>";
            }
        }

        // --- 處理 3：刪除課程 ---
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_course'])) {
            $del_id = intval($_POST['delete_course_id']);
            $conn->query("DELETE FROM Courses WHERE course_id = $del_id");
            echo "<div class='card' style='background:#d4edda; border-left:4px solid #28a745;'><strong>✓ 成功：</strong>課程已刪除。</div>";
        }

        // 準備教師選單
        $teachers = $conn->query("SELECT teacher_id, name FROM Teachers");
        $teacher_options = "";
        while ($t = $teachers->fetch_assoc()) {
            $teacher_options .= "<option value='{$t['teacher_id']}'>" . htmlspecialchars($t['name']) . "</option>";
        }

        // ==========================================
        // UI: 新增課程按鈕與表單
        // ==========================================
        echo "<button class='btn' style='background:#28a745; margin-bottom:15px;' onclick='toggleAddForm()'>＋ 新增課程</button>";

        echo "<form id='addCourseForm' method='POST' style='display:none; background:#f4f6f9; padding:20px; border-radius:5px; margin-bottom:20px; grid-template-columns: 1fr 1fr; gap:15px;'>";
        echo "<div style='grid-column: span 2;'><h4 style='margin-top:0; color:#28a745;'>📝 新增課程</h4></div>";
        echo "<div><label>課程代碼：</label><input type='text' name='course_code' placeholder='不可與現存代碼重複' required></div>";
        echo "<div><label>課程名稱：</label><input type='text' name='course_name' required></div>";
        echo "<div><label>授課教師：</label><select name='teacher_id' required><option value=''>請選擇教師...</option>$teacher_options</select></div>";
        // ✨ 動態讀取上課教室選單
        echo "<div><label>上課教室：</label><select name='room' required><option value=''>-- 請選擇上課教室 --</option>";
        $rm_res = $conn->query("SELECT room_name FROM Rooms WHERE room_type='上課教室' ORDER BY room_name ASC");
        while($rm = $rm_res->fetch_assoc()) {
            echo "<option value='".htmlspecialchars($rm['room_name'])."'>".htmlspecialchars($rm['room_name'])."</option>";
        }
        echo "</select></div>";
        echo "<div><label>修課人數上限：</label><input type='number' name='capacity' value='50' min='1' required></div>";
        
        // 排課 UI
        echo "<div style='grid-column: span 2; background:#fff; padding:15px; border:1px solid #ced4da; border-radius:5px;'>";
        echo "<label style='color:#007bff; font-weight:bold; margin-bottom:10px; display:block;'>📅 課程排程設定：</label>";
        echo "<div style='display:flex; align-items:center; gap:15px;'>";
        echo "<select name='sch_day' required style='padding:8px; border-radius:4px; border:1px solid #ccc;'>
                <option value=''>-- 選擇星期 --</option>
                <option value='一'>星期一</option><option value='二'>星期二</option><option value='三'>星期三</option>
                <option value='四'>星期四</option><option value='五'>星期五</option>
              </select>";
        echo "<div style='display:flex; flex-wrap:wrap; gap:10px; align-items:center;'>";
        for ($i=1; $i<=14; $i++) {
            echo "<label style='cursor:pointer; background:#e9ecef; padding:5px 10px; border-radius:4px;'><input type='checkbox' name='sch_periods[]' value='$i'> 第 $i 節</label>";
        }
        echo "</div></div></div>";

        echo "<div style='grid-column: span 2;'><button type='submit' name='add_course' class='btn' style='background:#007bff; width:100%; font-size:1.05em;'>確認新增</button></div>";
        echo "</form>";

        // ==========================================
        // UI: 課程列表
        // ==========================================
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
            echo "<td style='padding:10px; font-weight:bold;'>" . htmlspecialchars($c['course_code']) . "</td>";
            echo "<td style='padding:10px;'>" . htmlspecialchars($c['course_name']) . "</td>";
            echo "<td style='padding:10px;'>" . htmlspecialchars($c['teacher_name'] ?? '未指派') . "</td>";
            echo "<td style='padding:10px; font-weight:bold; color:#d35400;'>" . htmlspecialchars($c['schedule']) . "</td>";
            echo "<td style='padding:10px; color:#17a2b8;'>" . htmlspecialchars($c['room']) . "</td>";
            echo "<td style='padding:10px;'>" . htmlspecialchars($c['capacity']) . "</td>";
            
            echo "<td style='padding:10px; display:flex; gap:5px;'>";
            echo "<button type='button' class='btn' style='background:#ffc107; color:#333; padding:5px 10px; font-size:0.9em;' 
                    data-id='{$c['course_id']}' 
                    data-code='" . htmlspecialchars($c['course_code'], ENT_QUOTES) . "' 
                    data-name='" . htmlspecialchars($c['course_name'], ENT_QUOTES) . "' 
                    data-tid='{$c['teacher_id']}' 
                    data-cap='{$c['capacity']}' 
                    data-sch='" . htmlspecialchars($c['schedule'], ENT_QUOTES) . "' 
                    data-room='" . htmlspecialchars($c['room'], ENT_QUOTES) . "' 
                    onclick='openEditModal(this)'>修改</button>";

            echo "<form method='POST' style='margin:0;'>
                    <input type='hidden' name='delete_course_id' value='{$c['course_id']}'>
                    <button type='submit' name='delete_course' class='btn' style='background:#dc3545; padding:5px 10px; font-size:0.9em;' onclick='return confirm(\"確定刪除此課程？相關的選課紀錄也將一併刪除！\");'>刪除</button>
                  </form>";
            echo "</td></tr>";
        }
        echo "</table>";
        
        ?>
        <div id="editCourseModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:9999; justify-content:center; align-items:center;">
            <div style="background:#fff; width:90%; max-width:650px; border-radius:8px; box-shadow:0 5px 15px rgba(0,0,0,0.3); overflow:hidden;">
                <div style="background:#ffc107; color:#333; padding:15px 20px; font-weight:bold; font-size:1.2em; display:flex; justify-content:space-between; align-items:center;">
                    <span>✏️ 修改課程資訊</span>
                    <span style="cursor:pointer; font-size:1.5em; line-height:1;" onclick="closeEditModal()">&times;</span>
                </div>
                <form method="POST" style="padding:20px; display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                    <input type="hidden" name="course_id" id="edit_course_id">
                    <div><label>課程代碼：</label><input type="text" name="course_code" id="edit_course_code" required></div>
                    <div><label>課程名稱：</label><input type="text" name="course_name" id="edit_course_name" required></div>
                    <div><label>授課教師：</label>
                        <select name="teacher_id" id="edit_teacher_id" required>
                            <option value=''>請選擇教師...</option>
                            <?php echo $teacher_options; ?>
                        </select>
                    </div>
                    <div><label>上課教室：</label>
                        <select name='room' id="edit_room" required>
                            <option value=''>-- 請選擇上課教室 --</option>
                            <?php
                            $rm_res2 = $conn->query("SELECT room_name FROM Rooms WHERE room_type='上課教室' ORDER BY room_name ASC");
                            while($rm2 = $rm_res2->fetch_assoc()) {
                                echo "<option value='".htmlspecialchars($rm2['room_name'])."'>".htmlspecialchars($rm2['room_name'])."</option>";
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div style="grid-column: span 2; background:#f8f9fa; padding:15px; border:1px solid #ced4da; border-radius:5px;">
                        <label style="color:#007bff; font-weight:bold; margin-bottom:10px; display:block;">📅 課程排程設定：</label>
                        <div style="display:flex; align-items:flex-start; gap:15px;">
                            <select name="sch_day" id="edit_sch_day" required style="padding:8px; border-radius:4px; border:1px solid #ccc;">
                                <option value="">-- 選擇星期 --</option>
                                <option value="一">星期一</option><option value="二">星期二</option><option value="三">星期三</option>
                                <option value="四">星期四</option><option value="五">星期五</option>
                            </select>
                            <div id="edit_checkboxes" style="display:flex; flex-wrap:wrap; gap:8px; align-items:center;">
                                <?php 
                                for ($i=1; $i<=14; $i++) {
                                    echo "<label style='cursor:pointer; background:#e2e3e5; padding:4px 8px; border-radius:4px; font-size:0.9em;'><input type='checkbox' name='sch_periods[]' value='$i'> 第 $i 節</label>";
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                    
                    <div style="grid-column: span 2;"><label>修課人數上限：</label><input type="number" name="capacity" id="edit_capacity" min="1" required></div>
                    
                    <div style="grid-column: span 2; text-align:right; margin-top:10px;">
                        <button type="button" class="btn" style="background:#6c757d; margin-right:10px;" onclick="closeEditModal()">取消</button>
                        <button type="submit" name="update_course" class="btn" style="background:#28a745;">儲存修改</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
        function toggleAddForm() {
            var form = document.getElementById('addCourseForm');
            form.style.display = (form.style.display === 'none') ? 'grid' : 'none';
        }

        function openEditModal(btn) {
            document.getElementById('edit_course_id').value = btn.getAttribute('data-id');
            document.getElementById('edit_course_code').value = btn.getAttribute('data-code');
            document.getElementById('edit_course_name').value = btn.getAttribute('data-name');
            document.getElementById('edit_teacher_id').value = btn.getAttribute('data-tid');
            document.getElementById('edit_room').value = btn.getAttribute('data-room');
            document.getElementById('edit_capacity').value = btn.getAttribute('data-cap');
            
            let sch = btn.getAttribute('data-sch');
            let daySelect = document.getElementById('edit_sch_day');
            let checkboxes = document.querySelectorAll('#edit_checkboxes input[type="checkbox"]');
            
            daySelect.value = '';
            checkboxes.forEach(cb => cb.checked = false);

            if (sch && sch.includes(' ')) {
                let parts = sch.split(' ');
                daySelect.value = parts[0]; 
                
                let periods = parts[1].split(','); 
                periods.forEach(p => {
                    let targetCb = document.querySelector(`#edit_checkboxes input[value="${p.trim()}"]`);
                    if (targetCb) targetCb.checked = true;
                });
            }
            
            document.getElementById('editCourseModal').style.display = 'flex';
        }

        function closeEditModal() {
            document.getElementById('editCourseModal').style.display = 'none';
        }
        
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