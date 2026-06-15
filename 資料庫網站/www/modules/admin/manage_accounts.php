<div class="card">
    <h2>👥 系統帳號管理</h2>
    <p>建立管理員、教師或學生帳號。系統將依身分<strong style="color:#28a745;">自動配發學號(sXXXXX)或教師代號(tXXXXX)</strong>作為登入帳號，預設密碼皆為 123456。</p>
    
    <?php
    if(!isset($_SESSION['role']) || $_SESSION['role'] != 'Admin') {
        echo "<p style='color:red;'>只有管理員可以使用此功能。</p>";
    } else {
        $admin_uid = intval($_SESSION['user_id']); // 用於操作日誌

        // --- 處理 1：新增帳號 (自動配發帳號碼) ---
        if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_account'])) {
            $role = trim($_POST['role'] ?? '');
            $name = trim($_POST['name'] ?? '');
            $title = trim($_POST['title'] ?? '');
            $default_password = '123456';
            
            if($name && in_array($role, ['Student', 'Teacher', 'Admin'])) {
                $password_hash = password_hash($default_password, PASSWORD_DEFAULT);
                
                // 先插入一筆帶有暫時性帳號名稱的資料，以安全取得唯一的 AUTO_INCREMENT id
                $insert = $conn->prepare("INSERT INTO Users (username, password_hash, role) VALUES ('pending_sync', ?, ?)");
                $insert->bind_param("ss", $password_hash, $role);
                
                if ($insert->execute()) {
                    $user_id = $conn->insert_id;
                    
                    // 🚀【核心邏輯】依據分配到的 id 自動產生規範格式 (s00001, t00001, a00001)
                    $prefix = 's';
                    if ($role == 'Teacher') $prefix = 't';
                    if ($role == 'Admin') $prefix = 'a';
                    
                    $auto_username = $prefix . str_pad($user_id, 5, '0', STR_PAD_LEFT);
                    
                    // 將正式生成的學號/工號更新回 Users 表格
                    $conn->query("UPDATE Users SET username = '$auto_username' WHERE id = $user_id");
                    
                    $sub_success = true;
                    if($role == 'Teacher') {
                        $insert_t = $conn->prepare("INSERT INTO Teachers (user_id, name, title, department) VALUES (?, ?, ?, '資訊工程學系')");
                        $insert_t->bind_param("iss", $user_id, $name, $title);
                        $sub_success = $insert_t->execute();
                    } else if($role == 'Student') {
                        // 學生學號與其登入帳號字串完全合一 (s0000X)
                        $insert_s = $conn->prepare("INSERT INTO Students (student_id, user_id, name, enrollment_year) VALUES (?, ?, ?, 2024)");
                        $insert_s->bind_param("sis", $auto_username, $user_id, $name);
                        $sub_success = $insert_s->execute();
                    } else if($role == 'Admin') {
                        $insert_a = $conn->prepare("INSERT INTO Admins (user_id, name, title) VALUES (?, ?, ?)");
                        $insert_a->bind_param("iss", $user_id, $name, $title);
                        $sub_success = $insert_a->execute();
                    }

                    if ($sub_success) {
                        // 📝 寫入系統操作日誌
                        $log_desc = "自動配發並建立了新帳號：{$auto_username}，真實姓名: {$name}，身份: {$role}";
                        $log_stmt = $conn->prepare("INSERT INTO AdminLogs (user_id, action_type, description) VALUES (?, '帳號建立', ?)");
                        if ($log_stmt) {
                            $log_stmt->bind_param("is", $admin_uid, $log_desc);
                            $log_stmt->execute();
                            $log_stmt->close();
                        }
                        echo "<div class='card' style='background:#d4edda; border-left:4px solid #28a745;'><strong>✓ 成功：</strong>帳號已成功自動建立！配發帳號為：<strong style='font-size:1.1em; color:#0056b3;'>{$auto_username}</strong></div>";
                    } else {
                        $conn->query("DELETE FROM Users WHERE id = $user_id");
                        echo "<div class='card' style='background:#f8d7da; border-left:4px solid #dc3545;'><strong>✗ 失敗：</strong>子欄位寫入失敗 (" . $conn->error . ")</div>";
                    }
                } else {
                    echo "<div class='card' style='background:#f8d7da; border-left:4px solid #dc3545;'><strong>✗ 失敗：</strong>系統主表格寫入異常 (" . $conn->error . ")</div>";
                }
            } else {
                echo "<div class='card' style='background:#fff3cd; border-left:4px solid #ffc107;'>請填寫完整資訊！</div>";
            }
        }
        
        // --- 處理 2：更新姓名與職稱 ---
        if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_account'])) {
            $u_id = intval($_POST['user_id']);
            $u_role = $_POST['user_role'];
            $new_name = trim($_POST['new_name']);
            $new_title = trim($_POST['new_title'] ?? '');
            
            if($new_name) {
                if($u_role == 'Teacher') {
                    $upd = $conn->prepare("UPDATE Teachers SET name=?, title=? WHERE user_id=?");
                    $upd->bind_param("ssi", $new_name, $new_title, $u_id);
                    $upd->execute();
                } else if($u_role == 'Admin') {
                    $upd = $conn->prepare("UPDATE Admins SET name=?, title=? WHERE user_id=?");
                    $upd->bind_param("ssi", $new_name, $new_title, $u_id);
                    $upd->execute();
                } else if($u_role == 'Student') {
                    $upd = $conn->prepare("UPDATE Students SET name=? WHERE user_id=?");
                    $upd->bind_param("si", $new_name, $u_id);
                    $upd->execute();
                }
                echo "<div class='card' style='background:#d4edda; border-left:4px solid #28a745;'><strong>✓ 成功：</strong>已更新使用者資料</div>";
            }
        }

        // --- 處理 3：刪除帳號 ---
        if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_account'])) {
            $del_id = intval($_POST['user_id']);
            $conn->query("DELETE FROM Users WHERE id = $del_id");
            echo "<div class='card' style='background:#d4edda; border-left:4px solid #28a745;'><strong>✓ 成功：</strong>帳號已刪除</div>";
        }
        
        // ==========================================
        // 畫面顯示區塊 (精簡版表單)
        // ==========================================
        echo "<form id='addAccountForm' method='POST' style='background:#f4f6f9; padding: 15px; border-radius: 5px; margin-bottom: 20px;'>";
        echo "<div style='display:grid; grid-template-columns: 1fr 1.5fr 1.5fr auto; gap:15px; align-items:flex-end;'>";
        echo "<div><label>帳號角色：</label><select name='role'><option value='Student'>學生 (Student)</option><option value='Teacher'>教師 (Teacher)</option><option value='Admin'>管理員 (Admin)</option></select></div>";
        echo "<div><label>真實姓名：</label><input type='text' name='name' placeholder='請輸入本名' required></div>";
        echo "<div><label>職稱 (學生免填)：</label><input type='text' name='title' placeholder='如：助理教授、系辦秘書'></div>";
        echo "<div><button type='submit' name='add_account' class='btn' style='width:100%; background:#28a745;'>＋ 立即自動配發建立</button></div>";
        echo "</div>";
        echo "</form>";
        
        // 撈取帳號清單
        $users = $conn->query("
            SELECT u.id, u.username, u.role, 
                   COALESCE(t.name, s.name, a.name) AS real_name, 
                   COALESCE(t.title, a.title) AS job_title 
            FROM Users u 
            LEFT JOIN Teachers t ON u.id = t.user_id 
            LEFT JOIN Students s ON u.id = s.user_id 
            LEFT JOIN Admins a ON u.id = a.user_id 
            ORDER BY u.role, u.id
        ");
        
        if (!$users) {
            echo "<div class='card' style='background:#f8d7da; color:#dc3545;'>⚠️ 無法載入帳號清單: " . htmlspecialchars($conn->error) . "</div>";
        } else {
            echo "<table style='width:100%; border-collapse: collapse; font-size:0.95em;'>";
            echo "<tr style='background:#f4f6f9;'><th style='padding:10px; width:8%;'>ID</th><th style='padding:10px; width:22%;'>配發登入帳號(學工號)</th><th style='padding:10px; width:15%;'>身分</th><th style='padding:10px; width:20%;'>真實姓名</th><th style='padding:10px; width:20%;'>職稱</th><th style='padding:10px; width:15%;'>操作</th></tr>";
            
            while($u = $users->fetch_assoc()) {
                echo "<tr style='border-bottom:1px solid #eee;'>";
                echo "<form method='POST' style='margin:0;'>";
                echo "<input type='hidden' name='user_id' value='".$u['id']."'>";
                echo "<input type='hidden' name='user_role' value='".$u['role']."'>";
                
                echo "<td style='padding:10px;'>" . $u['id'] . "</td>";
                echo "<td style='padding:10px; font-weight:bold; color:#0056b3;'>" . htmlspecialchars($u['username']) . "</td>";
                echo "<td style='padding:10px;'>" . $u['role'] . "</td>";
                
                echo "<td style='padding:10px;'><input type='text' name='new_name' value='" . htmlspecialchars($u['real_name'] ?? '') . "' style='width:120px; padding:6px; margin:0;' required></td>";
                
                if ($u['role'] == 'Student') {
                    echo "<td style='padding:10px;'><span style='color:#999; font-size:0.9em;'>不適用</span></td>";
                } else {
                    echo "<td style='padding:10px;'><input type='text' name='new_title' value='" . htmlspecialchars($u['job_title'] ?? '') . "' style='width:120px; padding:6px; margin:0;'></td>";
                }
                
                echo "<td style='padding:10px; display:flex; gap:5px;'>";
                echo "<button type='submit' name='update_account' class='btn' style='background:#17a2b8; padding:5px 10px; font-size:0.9em;'>更新</button>";
                echo "<button type='submit' name='delete_account' class='btn' style='background:#dc3545; padding:5px 10px; font-size:0.9em;' onclick='return confirm(\"確定刪除此帳號？\");'>刪除</button>";
                echo "</td>";
                
                echo "</form>";
                echo "</tr>";
            }
            echo "</table>";
        }
    }
    ?>
</div>