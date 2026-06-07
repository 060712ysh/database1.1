<div class="card">
    <h2>📝 個人簡歷與學術維護</h2>
    <p>更新您的聯絡資訊、請益時間、實驗室與學術經歷，這些資料將公開於師資陣容頁面。</p>
    
    <?php
    if(!isset($_SESSION['teacher_id'])) {
        echo "<p style='color:red;'>您不是教師，無法使用此功能。</p>";
    } else {
        $teacher_id = $_SESSION['teacher_id'];
        
        // 處理表單提交
        if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
            $name = trim($_POST['name'] ?? '');
            $title = trim($_POST['title'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $office_hours = trim($_POST['office_hours'] ?? '');
            $lab_name = trim($_POST['lab_name'] ?? '');
            $lab_info = trim($_POST['lab_info'] ?? '');
            $academic_honors = trim($_POST['academic_honors'] ?? '');
            $papers = trim($_POST['papers'] ?? '');
            
            if($name) {
                $update = $conn->prepare(
                    "UPDATE Teachers SET name=?, title=?, phone=?, email=?, office_hours=?, lab_name=?, lab_info=?, academic_honors=?, papers=? WHERE teacher_id=?"
                );
                $update->bind_param("sssssssssi", $name, $title, $phone, $email, $office_hours, $lab_name, $lab_info, $academic_honors, $papers, $teacher_id);
                $update->execute();
                echo "<div class='card' style='background:#d4edda; border-left:4px solid #28a745;'><strong>✓ 成功：</strong>個人學術資料已更新</div>";
                $update->close();
            }
        }
        
        // 取得現有資料
        $profile = $conn->prepare("SELECT * FROM Teachers WHERE teacher_id = ?");
        $profile->bind_param("i", $teacher_id);
        $profile->execute();
        $p = $profile->get_result()->fetch_assoc();
        $profile->close();
        
        echo "<form method='POST' action=''>";
        echo "<div style='display: grid; grid-template-columns: 1fr 1fr; gap: 15px;'>";
        echo "<div><label>姓名：</label><input type='text' name='name' value='" . htmlspecialchars($p['name'] ?? '') . "' required></div>";
        echo "<div><label>職稱：</label><input type='text' name='title' value='" . htmlspecialchars($p['title'] ?? '') . "' required></div>";
        echo "<div><label>聯絡電話：</label><input type='text' name='phone' value='" . htmlspecialchars($p['phone'] ?? '') . "'></div>";
        echo "<div><label>電子信箱：</label><input type='email' name='email' value='" . htmlspecialchars($p['email'] ?? '') . "'></div>";
        echo "<div style='grid-column: span 2;'><label>請益時間 (Office Hours)：</label><input type='text' name='office_hours' placeholder='例如：每週二 14:00 - 16:00' value='" . htmlspecialchars($p['office_hours'] ?? '') . "'></div>";
        
        echo "<div style='grid-column: span 2;'><hr style='margin: 10px 0;'></div>";
        
        echo "<div><label>實驗室名稱：</label><input type='text' name='lab_name' placeholder='例如：智慧雲端實驗室' value='" . htmlspecialchars($p['lab_name'] ?? '') . "'></div>";
        echo "<div style='grid-column: span 2;'><label>實驗室簡介與研究方向：</label><textarea name='lab_info' rows='3'>" . htmlspecialchars($p['lab_info'] ?? '') . "</textarea></div>";
        
        echo "<div style='grid-column: span 2;'><hr style='margin: 10px 0;'></div>";
        
        echo "<div style='grid-column: span 2;'><label>學術榮譽：</label><textarea name='academic_honors' rows='3'>" . htmlspecialchars($p['academic_honors'] ?? '') . "</textarea></div>";
        echo "<div style='grid-column: span 2;'><label>論文與著作：</label><textarea name='papers' rows='4'>" . htmlspecialchars($p['papers'] ?? '') . "</textarea></div>";
        echo "</div>";
        
        echo "<button type='submit' name='update_profile' class='btn' style='margin-top: 15px; background: #007bff;'>💾 儲存所有資料</button>";
        echo "</form>";
    }
    ?>
</div>