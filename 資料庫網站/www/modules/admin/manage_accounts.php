<div class="card">
    <h2>👥 系統帳號管理</h2>
    <p>建立管理員、教師或學生帳號。系統將依身分<strong style="color:#28a745;">自動配發學號(sXXXXX)或教師代號(tXXXXX)</strong>作為登入帳號，預設密碼皆為 123456。</p>
    
    <?php
    if(!isset($_SESSION['role']) || $_SESSION['role'] != 'Admin') {
        echo "<p style='color:red;'>只有管理員可以使用此功能。</p>";
    } else {
        $admin_uid = intval($_SESSION['user_id']); // 用於操作日誌

        // --- 處理 1：新增帳號 (🚀 自動判斷最大號碼並依序遞增) ---
        if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_account'])) {
            $role = trim($_POST['role'] ?? '');
            $name = trim($_POST['name'] ?? '');
            $title = trim($_POST['title'] ?? '');
            $default_password = '123456';
            
            if($name && in_array($role, ['Student', 'Teacher', 'Admin'])) {
                
                // 1. 決定字首
                $prefix = 's';
                if ($role == 'Teacher') $prefix = 't';
                if ($role == 'Admin') $prefix = 'a';

                // 2. 尋找該身分目前最大的帳號數字
                $q_max = $conn->prepare("SELECT username FROM Users WHERE role = ? ORDER BY username DESC LIMIT 1");
                $q_max->bind_param("s", $role);
                $q_max->execute();
                $res_max = $q_max->get_result();
                
                if ($res_max->num_rows > 0) {
                    $max_user = $res_max->fetch_assoc()['username'];
                    // 擷取字首後面的數字並轉為整數 (例如 's00002' -> 2)
                    $max_num = intval(substr($max_user, 1));
                    $next_num = $max_num + 1;
                } else {
                    // 若該身分還沒有任何帳號，從 1 開始
                    $next_num = 1;
                }
                
                // 3. 組合出新的帳號字串 (例如 s00003)
                $auto_username = $prefix . str_pad($next_num, 5, '0', STR_PAD_LEFT);
                $password_hash = password_hash($default_password, PASSWORD_DEFAULT);
                
                // 4. 正式寫入 Users 資料表
                $insert = $conn->prepare("INSERT INTO Users (username, password_hash, role) VALUES (?, ?, ?)");
                $insert->bind_param("sss", $auto_username, $password_hash, $role);
                
                if ($insert->execute()) {
                    $new_user_id = $conn->insert_id;
                    $sub_success = false;
                    
                    // 5. 寫入對應的詳細資料表
                    if($role == 'Student') {
                        $ins_s = $conn->prepare("INSERT INTO Students (user_id, student_id, name, enrollment_year) VALUES (?, ?, ?, YEAR(CURDATE()))");
                        $ins_s->bind_param("iss", $new_user_id, $auto_username, $name);
                        $sub_success = $ins_s->execute();
                    } else if($role == 'Teacher') {
                        $ins_t = $conn->prepare("INSERT INTO Teachers (user_id, name, title) VALUES (?, ?, ?)");
                        $ins_t->bind_param("iss", $new_user_id, $name, $title);
                        $sub_success = $ins_t->execute();
                    } else if($role == 'Admin') {
                        $ins_a = $conn->prepare("INSERT INTO Admins (user_id, name, title) VALUES (?, ?, ?)");
                        $ins_a->bind_param("iss", $new_user_id, $name, $title);
                        $sub_success = $ins_a->execute();
                    }
                    
                    if ($sub_success) {
                        @$conn->query("INSERT INTO AdminLogs (user_id, action_type, description) VALUES ($admin_uid, '帳號建立', '自動配發建立了 {$role} 帳號：{$auto_username} ({$name})')");
                        echo "<div class='card' style='background:#d4edda; border-left:4px solid #28a745;'><strong>✓ 成功：</strong>已自動配發並建立帳號 <strong style='font-size:1.1em; color:#0056b3;'>[{$auto_username}]</strong> ({$name})</div>";
                    } else {
                        // 若子表寫入失敗，撤銷主表紀錄防呆
                        $conn->query("DELETE FROM Users WHERE id = $new_user_id");
                        echo "<div class='card' style='background:#f8d7da; border-left:4px solid #dc3545;'><strong>✗ 失敗：</strong>詳細資料寫入異常。</div>";
                    }
                }
            } else {
                echo "<div class='card' style='background:#fff3cd; border-left:4px solid #ffc107;'>請填寫完整資訊！</div>";
            }
        }

        // --- 處理 2：修改帳號資訊 (除密碼與權限外) ---
        if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_account'])) {
            $edit_id = intval($_POST['edit_user_id']);
            $new_username = trim($_POST['edit_username']);
            $new_name = trim($_POST['edit_name']);
            $new_title = trim($_POST['edit_title']);
            
            $chk = $conn->prepare("SELECT id FROM Users WHERE username = ? AND id != ?");
            $chk->bind_param("si", $new_username, $edit_id);
            $chk->execute();
            if($chk->get_result()->num_rows > 0) {
                 echo "<div class='card' style='background:#f8d7da; border-left:4px solid #dc3545;'><strong>⚠️ 錯誤：</strong>修改失敗，登入帳號 [{$new_username}] 已被其他人使用！</div>";
            } else {
                $upd_u = $conn->prepare("UPDATE Users SET username = ? WHERE id = ?");
                $upd_u->bind_param("si", $new_username, $edit_id);
                $upd_u->execute();
                
                $q_role = $conn->query("SELECT role FROM Users WHERE id = $edit_id");
                $r_role = $q_role->fetch_assoc()['role'];
                
                if($r_role == 'Student') {
                    $upd_s = $conn->prepare("UPDATE Students SET student_id = ?, name = ? WHERE user_id = ?");
                    $upd_s->bind_param("ssi", $new_username, $new_name, $edit_id);
                    $upd_s->execute();
                } else if($r_role == 'Teacher') {
                    $upd_t = $conn->prepare("UPDATE Teachers SET name = ?, title = ? WHERE user_id = ?");
                    $upd_t->bind_param("ssi", $new_name, $new_title, $edit_id);
                    $upd_t->execute();
                } else if($r_role == 'Admin') {
                    $upd_a = $conn->prepare("UPDATE Admins SET name = ?, title = ? WHERE user_id = ?");
                    $upd_a->bind_param("ssi", $new_name, $new_title, $edit_id);
                    $upd_a->execute();
                }
                
                @$conn->query("INSERT INTO AdminLogs (user_id, action_type, description) VALUES ($admin_uid, '帳號修改', '修改了系統帳號 ID: {$edit_id} 的個人資訊')");
                echo "<div class='card' style='background:#d4edda; border-left:4px solid #28a745;'><strong>✓ 成功：</strong>帳號資訊已成功更新！</div>";
            }
        }

        // --- 處理 3：重設密碼 ---
        if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reset_password'])) {
            $reset_id = intval($_POST['reset_user_id']);
            $new_hash = password_hash('123456', PASSWORD_DEFAULT);
            $conn->query("UPDATE Users SET password_hash = '$new_hash' WHERE id = $reset_id");
            
            @$conn->query("INSERT INTO AdminLogs (user_id, action_type, description) VALUES ($admin_uid, '密碼重設', '將帳號 ID: {$reset_id} 的密碼重設為預設值')");
            echo "<div class='card' style='background:#d4edda; border-left:4px solid #28a745;'><strong>✓ 成功：</strong>該帳號的密碼已安全重設為 123456！</div>";
        }

        // --- 處理 4：刪除帳號 ---
        if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_account'])) {
            $del_id = intval($_POST['delete_id']);
            if($del_id == $admin_uid) {
                echo "<div class='card' style='background:#f8d7da; border-left:4px solid #dc3545;'><strong>⚠️ 錯誤：</strong>您不能刪除您自己目前登入的帳號！</div>";
            } else {
                $conn->query("DELETE FROM Users WHERE id = $del_id");
                @$conn->query("INSERT INTO AdminLogs (user_id, action_type, description) VALUES ($admin_uid, '帳號刪除', '刪除了系統帳號 ID: {$del_id}')");
                echo "<div class='card' style='background:#d4edda; border-left:4px solid #28a745;'><strong>✓ 成功：</strong>帳號已永久刪除！</div>";
            }
        }
        ?>
        
        <div style="background:#f4f6f9; padding:20px; border-radius:8px; border:1px solid #ddd; margin-bottom:20px;">
            <h3 style="margin-top:0; color:#333;">➕ 新增系統帳號</h3>
            <form method="POST" style="display:grid; grid-template-columns: 1fr 1.5fr 1.5fr auto; gap:15px; align-items:flex-end;">
                <div>
                    <label style="font-weight:bold; color:#555;">帳號角色：</label>
                    <select name="role" required style="width:100%; padding:10px; border:1px solid #ccc; border-radius:4px;">
                        <option value="Student">學生 (Student)</option>
                        <option value="Teacher">教師 (Teacher)</option>
                        <option value="Admin">管理員 (Admin)</option>
                    </select>
                </div>
                <div>
                    <label style="font-weight:bold; color:#555;">真實姓名：</label>
                    <input type="text" name="name" placeholder="請輸入本名" required style="width:100%; padding:10px; border:1px solid #ccc; border-radius:4px; box-sizing:border-box;">
                </div>
                <div>
                    <label style="font-weight:bold; color:#555;">職稱 <span style="font-size:0.85em; color:#888;">(若為學生免填)</span>：</label>
                    <input type="text" name="title" placeholder="如：助理教授、系辦秘書" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:4px; box-sizing:border-box;">
                </div>
                <div>
                    <button type="submit" name="add_account" class="btn" style="background:#28a745; padding:10px 20px; font-weight:bold;">＋ 自動配發建立</button>
                </div>
            </form>
        </div>
        
        <table style="width:100%; border-collapse:collapse; text-align:left;">
            <tr style="background:#343a40; color:#fff;">
                <th style="padding:12px 10px;">ID</th>
                <th style="padding:12px 10px;">登入帳號</th>
                <th style="padding:12px 10px;">權限角色</th>
                <th style="padding:12px 10px;">真實姓名</th>
                <th style="padding:12px 10px;">職稱</th>
                <th style="padding:12px 10px; width:150px;">操作管理</th>
            </tr>
            <?php
            $users = $conn->query("
                SELECT u.id, u.username, u.role, 
                       COALESCE(a.name, t.name, s.name) AS real_name,
                       COALESCE(a.title, t.title) AS job_title
                FROM Users u
                LEFT JOIN Admins a ON u.id = a.user_id AND u.role = 'Admin'
                LEFT JOIN Teachers t ON u.id = t.user_id AND u.role = 'Teacher'
                LEFT JOIN Students s ON u.id = s.user_id AND u.role = 'Student'
                ORDER BY u.role, u.id DESC
            ");
            
            while($u = $users->fetch_assoc()) {
                $js_username = htmlspecialchars($u['username'], ENT_QUOTES);
                $js_name = htmlspecialchars($u['real_name'] ?? '', ENT_QUOTES);
                $js_title = htmlspecialchars($u['job_title'] ?? '', ENT_QUOTES);
                
                $role_badge = $u['role'] == 'Admin' ? "<span style='color:#dc3545; font-weight:bold;'>{$u['role']}</span>" : $u['role'];
                $display_title = !empty($u['job_title']) ? htmlspecialchars($u['job_title']) : "<span style='color:#aaa;'>無</span>";
                
                echo "<tr style='border-bottom:1px solid #eee;' onmouseover=\"this.style.background='#f9f9f9'\" onmouseout=\"this.style.background='#fff'\">";
                echo "<td style='padding:12px 10px; color:#666;'>{$u['id']}</td>";
                echo "<td style='padding:12px 10px; font-weight:bold; color:#0056b3;'>{$u['username']}</td>";
                echo "<td style='padding:12px 10px;'>{$role_badge}</td>";
                echo "<td style='padding:12px 10px;'>" . htmlspecialchars($u['real_name'] ?? '') . "</td>";
                echo "<td style='padding:12px 10px;'>" . $display_title . "</td>";
                echo "<td style='padding:12px 10px; display:flex; gap:8px;'>";
                
                // 修改按鈕 (觸發彈窗)
                echo "<button type='button' class='btn' style='background:#17a2b8; padding:5px 12px; font-size:0.9em;' onclick='openEditModal({$u['id']}, \"{$js_username}\", \"{$js_name}\", \"{$js_title}\")'>修改</button>";
                
                // 刪除按鈕
                echo "<form method='POST' style='margin:0;' onsubmit='return confirm(\"⚠️ 警告：確定要永久刪除此帳號與其所有關聯資料嗎？\");'>";
                echo "<input type='hidden' name='delete_id' value='{$u['id']}'>";
                echo "<button type='submit' name='delete_account' class='btn' style='background:#dc3545; padding:5px 12px; font-size:0.9em;'>刪除</button>";
                echo "</form>";
                
                echo "</td></tr>";
            }
            ?>
        </table>

        <div id="editModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); align-items:center; justify-content:center; z-index:1050; backdrop-filter:blur(3px);">
            <div style="background:#fff; padding:30px; border-radius:12px; width:450px; max-width:90%; box-shadow:0 10px 30px rgba(0,0,0,0.2);">
                <h3 style="margin-top:0; color:#2c3e50; border-bottom:2px solid #17a2b8; padding-bottom:10px;">✏️ 修改帳號資訊</h3>
                
                <form method="POST">
                    <input type="hidden" name="edit_user_id" id="modal_user_id">
                    
                    <div style="margin-bottom:15px;">
                        <label style="font-weight:bold; color:#444; display:block; margin-bottom:5px;">登入帳號 <span style="font-size:0.85em; color:#888;">(若需手動調整可更改)</span>：</label>
                        <input type="text" name="edit_username" id="modal_username" required style="width:100%; padding:10px; border:1px solid #ccc; border-radius:4px; box-sizing:border-box;">
                    </div>
                    
                    <div style="margin-bottom:15px;">
                        <label style="font-weight:bold; color:#444; display:block; margin-bottom:5px;">真實姓名：</label>
                        <input type="text" name="edit_name" id="modal_name" required style="width:100%; padding:10px; border:1px solid #ccc; border-radius:4px; box-sizing:border-box;">
                    </div>
                    
                    <div style="margin-bottom:20px;">
                        <label style="font-weight:bold; color:#444; display:block; margin-bottom:5px;">職稱 <span style="font-size:0.85em; color:#888;">(若該身分為學生則無效)</span>：</label>
                        <input type="text" name="edit_title" id="modal_title" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:4px; box-sizing:border-box;">
                    </div>
                    
                    <div style="display:flex; gap:15px;">
                        <button type="submit" name="edit_account" class="btn" style="background:#007bff; flex:1; padding:10px; font-size:1.05em;">💾 儲存修改</button>
                        <button type="button" class="btn" style="background:#6c757d; flex:1; padding:10px; font-size:1.05em;" onclick="closeEditModal()">✖ 取消</button>
                    </div>
                </form>
                
                <hr style="margin:25px 0 20px 0; border:none; border-top:1px dashed #ccc;">
                
                <form method="POST" onsubmit="return confirm('⚠️ 確認操作：您確定要將此帳號的密碼重設為預設值【123456】嗎？');">
                    <input type="hidden" name="reset_user_id" id="modal_reset_id">
                    <button type="submit" name="reset_password" class="btn" style="background:#ffc107; color:#333; font-weight:bold; width:100%; padding:12px; font-size:1.05em; transition:0.2s;" onmouseover="this.style.transform='scale(1.02)'" onmouseout="this.style.transform='scale(1)'">
                        🔑 一鍵重設密碼為 123456
                    </button>
                </form>
            </div>
        </div>

        <script>
        function openEditModal(id, username, name, title) {
            document.getElementById('modal_user_id').value = id;
            document.getElementById('modal_reset_id').value = id; 
            document.getElementById('modal_username').value = username;
            document.getElementById('modal_name').value = name;
            document.getElementById('modal_title').value = title;
            document.getElementById('editModal').style.display = 'flex';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }
        
        window.onclick = function(event) {
            var modal = document.getElementById('editModal');
            if (event.target == modal) {
                closeEditModal();
            }
        }
        </script>
        <?php
    }
    ?>
</div>