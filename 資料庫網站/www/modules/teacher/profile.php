<div class="card">
    <h2>📝 個人簡歷與學術維護</h2>
    <p>更新您的基本聯絡資訊，並逐筆管理您的學術榮譽與著作計畫。</p>
    
    <?php
    if(!isset($_SESSION['teacher_id'])) {
        echo "<p style='color:red;'>您不是教師，無法使用此功能。</p>";
    } else {
        $teacher_id = $_SESSION['teacher_id'];
        
        // 取得現有基本資料 (確保能抓到老師最新的本名)
        $profile = $conn->prepare("SELECT * FROM Teachers WHERE teacher_id = ?");
        $profile->bind_param("i", $teacher_id);
        $profile->execute();
        $p = $profile->get_result()->fetch_assoc();
        
        // --- 處理 1：基本資料更新 ---
        if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
            $phone = trim($_POST['phone'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $office_hours = trim($_POST['office_hours'] ?? '');
            $lab_name = trim($_POST['lab_name'] ?? '');
            $lab_info = trim($_POST['lab_info'] ?? '');
            
            $update = $conn->prepare("UPDATE Teachers SET phone=?, email=?, office_hours=?, lab_name=?, lab_info=? WHERE teacher_id=?");
            $update->bind_param("sssssi", $phone, $email, $office_hours, $lab_name, $lab_info, $teacher_id);
            $update->execute();
            echo "<div class='card' style='background:#d4edda; border-left:4px solid #28a745;'><strong>✓ 成功：</strong>聯絡資訊與實驗室資料已更新</div>";
            $profile->execute();
            $p = $profile->get_result()->fetch_assoc();
        }

        // --- 處理 2：新增/刪除學術榮譽 ---
        if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_honor'])) {
            $h_name = trim($_POST['honor_name']);
            $h_body = trim($_POST['awarding_body']);
            $h_year = intval($_POST['award_year']);
            if($h_name) {
                $ins = $conn->prepare("INSERT INTO AcademicHonors (teacher_id, honor_name, awarding_body, award_year) VALUES (?, ?, ?, ?)");
                $ins->bind_param("issi", $teacher_id, $h_name, $h_body, $h_year);
                $ins->execute();
            }
        }
        if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_honor'])) {
            $del_id = intval($_POST['honor_id']);
            $conn->query("DELETE FROM AcademicHonors WHERE honor_id = $del_id AND teacher_id = $teacher_id");
        }

        // --- 處理 3：新增/刪除著作與計畫 ---
        if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_pub'])) {
            $p_type = trim($_POST['work_type']);
            $p_title = trim($_POST['title']);
            $other_authors = trim($_POST['other_authors']); // 只接收其他作者
            $p_year = trim($_POST['publish_year']);
            
            if($p_title && $p_type) {
                // 【關鍵修正】資料庫裡只存「其他作者」的字串
                $ins = $conn->prepare("INSERT INTO Publications (teacher_id, work_type, title, authors, publish_year) VALUES (?, ?, ?, ?, ?)");
                $ins->bind_param("issss", $teacher_id, $p_type, $p_title, $other_authors, $p_year);
                $ins->execute();
            }
        }
        if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_pub'])) {
            $del_id = intval($_POST['work_id']);
            $conn->query("DELETE FROM Publications WHERE work_id = $del_id AND teacher_id = $teacher_id");
        }
        
        // ==========================================
        // 畫面顯示區塊
        // ==========================================
        echo "<hr style='margin:20px 0;'>";
        
        // [區塊 1] 基本資料表單
        echo "<h3 style='color:#007bff;'>👤 基本資料與實驗室</h3>";
        echo "<form method='POST' style='background:#f4f6f9; padding:20px; border-radius:5px; display: grid; grid-template-columns: 1fr 1fr; gap: 15px;'>";
        echo "<div><label>姓名：</label><div style='padding:8px; background:#e9ecef; border-radius:4px; color:#495057; border:1px solid #ced4da;'>" . htmlspecialchars($p['name'] ?? '') . " <span style='font-size:0.8em; color:#999;'>(修改請洽系辦)</span></div></div>";
        echo "<div><label>職稱：</label><div style='padding:8px; background:#e9ecef; border-radius:4px; color:#495057; border:1px solid #ced4da;'>" . htmlspecialchars($p['title'] ?? '') . " <span style='font-size:0.8em; color:#999;'>(修改請洽系辦)</span></div></div>";
        echo "<div><label>聯絡電話：</label><input type='text' name='phone' value='" . htmlspecialchars($p['phone'] ?? '') . "'></div>";
        echo "<div><label>電子信箱：</label><input type='email' name='email' value='" . htmlspecialchars($p['email'] ?? '') . "'></div>";
        echo "<div style='grid-column: span 2;'><label>請益時間 (Office Hours)：</label><input type='text' name='office_hours' value='" . htmlspecialchars($p['office_hours'] ?? '') . "'></div>";
        echo "<div><label>實驗室名稱：</label><input type='text' name='lab_name' value='" . htmlspecialchars($p['lab_name'] ?? '') . "'></div>";
        echo "<div style='grid-column: span 2;'><label>實驗室簡介與研究方向：</label><textarea name='lab_info' rows='2'>" . htmlspecialchars($p['lab_info'] ?? '') . "</textarea></div>";
        echo "<div style='grid-column: span 2;'><button type='submit' name='update_profile' class='btn' style='background:#007bff;'>💾 儲存聯絡與實驗室資料</button></div>";
        echo "</form>";

        // [區塊 2] 學術榮譽管理
        echo "<h3 style='color:#28a745; margin-top:30px;'>🏆 學術榮譽管理</h3>";
        echo "<div style='background:#f8fff9; padding:20px; border:1px solid #c3e6cb; border-radius:5px;'>";
        echo "<form method='POST' style='display:flex; gap:10px; margin-bottom:15px;'>";
        echo "<input type='text' name='honor_name' placeholder='獎項名稱 (必填)' required style='flex:2; margin:0;'>";
        echo "<input type='text' name='awarding_body' placeholder='頒發機構' style='flex:1; margin:0;'>";
        echo "<input type='number' name='award_year' placeholder='年份(如:2023)' style='width:100px; margin:0;'>";
        echo "<button type='submit' name='add_honor' class='btn' style='background:#28a745;'>➕ 新增榮譽</button>";
        echo "</form>";
        $honors = $conn->query("SELECT * FROM AcademicHonors WHERE teacher_id = $teacher_id ORDER BY award_year DESC");
        echo "<table style='width:100%; border-collapse:collapse;'><tr style='background:#e2eadb;'><th style='padding:8px;'>年份</th><th style='padding:8px;'>獎項名稱</th><th style='padding:8px;'>頒發機構</th><th style='padding:8px; width:80px;'>操作</th></tr>";
        while($h = $honors->fetch_assoc()) {
            echo "<tr style='border-bottom:1px solid #ddd;'>";
            echo "<td style='padding:8px;'>" . $h['award_year'] . "</td><td style='padding:8px;'>" . htmlspecialchars($h['honor_name']) . "</td><td style='padding:8px;'>" . htmlspecialchars($h['awarding_body']) . "</td>";
            echo "<td style='padding:8px;'><form method='POST' style='margin:0;'><input type='hidden' name='honor_id' value='{$h['honor_id']}'><button type='submit' name='delete_honor' class='btn' style='background:#dc3545; padding:4px 8px; font-size:12px;'>刪除</button></form></td>";
            echo "</tr>";
        }
        echo "</table></div>";

        // [區塊 3] 著作與計畫管理
        echo "<h3 style='color:#6f42c1; margin-top:30px;'>📑 論文與參與計畫管理</h3>";
        echo "<div style='background:#f9f8ff; padding:20px; border:1px solid #d6c3e6; border-radius:5px;'>";
        echo "<form method='POST' style='display:flex; flex-wrap:wrap; gap:10px; margin-bottom:15px;'>";
        echo "<select name='work_type' required style='width:200px; margin:0;'>";
        $types = ['發表期刊論文', '會議論文', '專書及技術報告', '國科會計畫', '產學合作計畫', '校外獎勵及指導學生獲獎', '校內獎勵及指導學生獲獎', '校內外演講', '專書論文', '教材與作品', '其他相關研究'];
        echo "<option value=''>-- 選擇著作/計畫類型 --</option>";
        foreach($types as $t) echo "<option value='$t'>$t</option>";
        echo "</select>";
        echo "<input type='text' name='publish_year' placeholder='發表時間(如: 2023-05)' style='width:150px; margin:0;'>";
        echo "<input type='text' name='title' placeholder='標題 / 計畫名稱 (必填)' required style='flex:1; min-width:250px; margin:0;'>";
        echo "<input type='text' name='other_authors' placeholder='其他作者 (不含您自己)' style='flex:1; min-width:200px; margin:0;'>";
        echo "<button type='submit' name='add_pub' class='btn' style='background:#6f42c1;'>➕ 新增紀錄</button>";
        echo "</form>";
        
        $pubs = $conn->query("SELECT * FROM Publications WHERE teacher_id = $teacher_id ORDER BY work_type, publish_year DESC");
        echo "<table style='width:100%; border-collapse:collapse;'><tr style='background:#e8e2ea;'><th style='padding:8px;'>類型</th><th style='padding:8px;'>時間</th><th style='padding:8px;'>標題</th><th style='padding:8px;'>作者</th><th style='padding:8px; width:80px;'>操作</th></tr>";
        while($p_row = $pubs->fetch_assoc()) {
            // 【關鍵修正】動態組合：老師最新本名 + 其他作者
            $display_authors = htmlspecialchars($p['name']); 
            if (!empty($p_row['authors'])) {
                $display_authors .= ', ' . htmlspecialchars($p_row['authors']);
            }
            
            echo "<tr style='border-bottom:1px solid #ddd;'>";
            echo "<td style='padding:8px;'>" . htmlspecialchars($p_row['work_type']) . "</td><td style='padding:8px;'>" . htmlspecialchars($p_row['publish_year']) . "</td>";
            echo "<td style='padding:8px;'>" . htmlspecialchars($p_row['title']) . "</td><td style='padding:8px; color:#007bff; font-weight:500;'>" . $display_authors . "</td>";
            echo "<td style='padding:8px;'><form method='POST' style='margin:0;'><input type='hidden' name='work_id' value='{$p_row['work_id']}'><button type='submit' name='delete_pub' class='btn' style='background:#dc3545; padding:4px 8px; font-size:12px;'>刪除</button></form></td>";
            echo "</tr>";
        }
        echo "</table></div>";
    }
    ?>
</div>