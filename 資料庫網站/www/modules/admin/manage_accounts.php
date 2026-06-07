<div class="card">
    <h2>👥 系統帳號管理</h2>
    <p>建立管理員、教師或學生帳號，並統一管理使用者的「真實姓名」與「職稱」。</p>
    
    <?php
    if($_SESSION['role'] != 'Admin') {
        echo "<p style='color:red;'>只有管理員可以使用此功能。</p>";
    } else {
        // --- 處理 1：新增帳號 ---
        if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_account'])) {
            $role = trim($_POST['role'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $password = trim($_POST['password'] ?? '');
            $name = trim($_POST['name'] ?? '');
            $title = trim($_POST['title'] ?? '');
            
            if($username && $password && $name && in_array($role, ['Student', 'Teacher', 'Admin'])) {
                $check = $conn->prepare("SELECT id FROM Users WHERE username = ?");
                $check->bind_param("s", $username);
                $check->execute();
                
                if($check->get_result()->num_rows == 0) {
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $insert = $conn->prepare("INSERT INTO Users (username, password_hash, role) VALUES (?, ?, ?)");
                    $insert->bind_param("sss", $username, $password_hash, $role);
                    $insert->execute();
                    $user_id = $conn->insert_id;
                    
                    if($role == 'Teacher') {
                        $insert_t = $conn->prepare("INSERT INTO Teachers (user_id, name, title, department) VALUES (?, ?, ?, '資訊工程學系')");
                        $insert_t->bind_param("iss", $user_id, $name, $title);
                        $insert_t->execute();
                    } else if($role == 'Student') {
                        $student_id = 'B' . $user_id . date('s');
                        $insert_s = $conn->prepare("INSERT INTO Students (student_id, user_id, name, enrollment_year) VALUES (?, ?, ?, 2024)");
                        $insert_s->bind_param("sis", $student_id, $user_id, $name);
                        $insert_s->execute();
                    } else if($role == 'Admin') {
                        $insert_a = $conn->prepare("INSERT INTO Admins (user_id, name, title) VALUES (?, ?, ?)");
                        $insert_a->bind_param("iss", $user_id, $name, $title);
                        $insert_a->execute();
                    }
                    echo "<div class='card' style='background:#d4edda; border-left:4px solid #28a745;'><strong>✓ 成功：</strong>帳號已新增 ($name)</div>";
                } else {
                    echo "<div class='card' style='background:#f8d7da; border-left:4px solid #dc3545;'><strong>✗ 失敗：</strong>登入帳號已存在</div>";
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
        // 畫面顯示區塊
        // ==========================================
        echo "<form method='POST' style='background:#f4f6f9; padding: 15px; border-radius: 5px; margin-bottom: 20px;'>";
        echo "<div style='display:grid; grid-template-columns: 1fr 1fr 1fr; gap:15px;'>";
        echo "<div><label>帳號角色：</label><select name='role'><option value='Student'>學生 (Student)</option><option value='Teacher'>教師 (Teacher)</option><option value='Admin'>管理員 (Admin)</option></select></div>";
        echo "<div><label>登入帳號：</label><input type='text' name='username' required></div>";
        echo "<div><label>登入密碼：</label><input type='text' name='password' required></div>";
        echo "<div><label>真實姓名：</label><input type='text' name='name' required></div>";
        echo "<div><label>職稱 (學生免填)：</label><input type='text' name='title' placeholder='如：系辦助理、教授'></div>";
        echo "<div style='display:flex; align-items:flex-end;'><button type='submit' name='add_account' class='btn' style='width:100%;'>＋ 建立新帳號</button></div>";
        echo "</div>";
        echo "</form>";
        
        // 帳號清單 (包含動態更新表單)
        echo "<table style='width:100%; border-collapse: collapse; font-size:0.95em;'>";
        echo "<tr style='background:#f4f6f9;'><th style='padding:10px;'>ID</th><th style='padding:10px;'>登入帳號</th><th style='padding:10px;'>身分</th><th style='padding:10px;'>真實姓名</th><th style='padding:10px;'>職稱</th><th style='padding:10px;'>操作</th></tr>";
        
        // 使用 LEFT JOIN 一次撈出所有人的姓名與職稱
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
        
        while($u = $users->fetch_assoc()) {
            echo "<tr style='border-bottom:1px solid #eee;'>";
            echo "<form method='POST' style='margin:0;'>";
            echo "<input type='hidden' name='user_id' value='".$u['id']."'>";
            echo "<input type='hidden' name='user_role' value='".$u['role']."'>";
            
            echo "<td style='padding:10px;'>" . $u['id'] . "</td>";
            echo "<td style='padding:10px;'>" . htmlspecialchars($u['username']) . "</td>";
            echo "<td style='padding:10px;'>" . $u['role'] . "</td>";
            
            // 姓名輸入框
            echo "<td style='padding:10px;'><input type='text' name='new_name' value='" . htmlspecialchars($u['real_name'] ?? '') . "' style='width:100px; padding:6px; margin:0;' required></td>";
            
            // 職稱輸入框 (學生不顯示)
            if ($u['role'] == 'Student') {
                echo "<td style='padding:10px;'><span style='color:#999; font-size:0.9em;'>不適用</span></td>";
            } else {
                echo "<td style='padding:10px;'><input type='text' name='new_title' value='" . htmlspecialchars($u['job_title'] ?? '') . "' style='width:100px; padding:6px; margin:0;'></td>";
            }
            
            // 操作按鈕
            echo "<td style='padding:10px; display:flex; gap:5px;'>";
            echo "<button type='submit' name='update_account' class='btn' style='background:#17a2b8; padding:5px 10px; font-size:0.9em;'>更新</button>";
            echo "<button type='submit' name='delete_account' class='btn' style='background:#dc3545; padding:5px 10px; font-size:0.9em;' onclick='return confirm(\"確定刪除此帳號？\");'>刪除</button>";
            echo "</td>";
            
            echo "</form>";
            echo "</tr>";
        }
        echo "</table>";
    }
    ?>
</div>