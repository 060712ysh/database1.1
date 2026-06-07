<div class="card">
    <h2>👥 系統帳號管理</h2>
    <p>建立管理員、教師或學生帳號，並同時設定真實姓名與職稱。</p>
    
    <?php
    if($_SESSION['role'] != 'Admin') {
        echo "<p style='color:red;'>只有管理員可以使用此功能。</p>";
    } else {
        // 處理新增帳號
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
                    // 1. 新增 Users
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $insert = $conn->prepare("INSERT INTO Users (username, password_hash, role) VALUES (?, ?, ?)");
                    $insert->bind_param("sss", $username, $password_hash, $role);
                    $insert->execute();
                    $user_id = $conn->insert_id;
                    
                    // 2. 依照身分寫入對應的資料表
                    if($role == 'Teacher') {
                        $insert_t = $conn->prepare("INSERT INTO Teachers (user_id, name, title, department) VALUES (?, ?, ?, '資訊工程學系')");
                        $insert_t->bind_param("iss", $user_id, $name, $title);
                        $insert_t->execute();
                    } else if($role == 'Student') {
                        $student_id = 'B' . $user_id . date('s'); // 簡易產生學號
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
        
        // 處理刪除
        if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_account'])) {
            $del_id = intval($_POST['user_id']);
            $conn->query("DELETE FROM Users WHERE id = $del_id");
            echo "<div class='card' style='background:#d4edda; border-left:4px solid #28a745;'><strong>✓ 成功：</strong>帳號已刪除</div>";
        }
        
        // 新增帳號表單
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
        
        // 列出所有帳號
        echo "<table style='width:100%; border-collapse: collapse;'>";
        echo "<tr style='background:#f4f6f9;'><th style='padding:10px;'>ID</th><th style='padding:10px;'>登入帳號</th><th style='padding:10px;'>身分</th><th style='padding:10px;'>操作</th></tr>";
        $users = $conn->query("SELECT id, username, role FROM Users ORDER BY role, id");
        while($u = $users->fetch_assoc()) {
            echo "<tr style='border-bottom:1px solid #eee;'>";
            echo "<td style='padding:10px;'>" . $u['id'] . "</td><td style='padding:10px;'>" . htmlspecialchars($u['username']) . "</td><td style='padding:10px;'>" . $u['role'] . "</td>";
            echo "<td style='padding:10px;'><form method='POST'><input type='hidden' name='user_id' value='".$u['id']."'><button type='submit' name='delete_account' class='btn' style='background:#dc3545;' onclick='return confirm(\"確定刪除？\");'>刪除</button></form></td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    ?>
</div>