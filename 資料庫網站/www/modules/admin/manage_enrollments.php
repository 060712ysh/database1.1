<div class="card">
    <h2>📝 學生選課與退選審核</h2>
    <p>管理員可在此審核學生的加退選申請，並掌握全校加退選系統的開放狀態。</p>

    <?php
    if ($_SESSION['role'] != 'Admin') {
        echo "<p style='color:red;'>無權限。</p>";
    } else {
        $admin_uid = intval($_SESSION['user_id']);

        // --- 處理 1：切換加退選系統開關 ---
        // 取得當前狀態
        $status_query = $conn->query("SELECT setting_value FROM SystemSettings WHERE setting_key = 'enrollment_status'");
        $current_status = $status_query->fetch_assoc()['setting_value'] ?? 'open';

        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['toggle_enrollment'])) {
            $new_status = ($current_status == 'open') ? 'closed' : 'open';
            $conn->query("UPDATE SystemSettings SET setting_value = '$new_status' WHERE setting_key = 'enrollment_status'");
            
            // 寫入操作日誌
            $log_desc = "將全校加退選系統狀態更改為：" . ($new_status == 'open' ? '【開放】' : '【關閉】');
            $log_stmt = $conn->prepare("INSERT INTO AdminLogs (user_id, action_type, description) VALUES (?, '選課狀態設定', ?)");
            $log_stmt->bind_param("is", $admin_uid, $log_desc);
            $log_stmt->execute();

            $current_status = $new_status; // 更新變數以利下方 UI 顯示
            echo "<div class='card' style='background:#d4edda; border-left:4px solid #28a745;'><strong>✓ 成功：</strong>加退選狀態已更新為" . ($new_status == 'open' ? '開放' : '關閉') . "！</div>";
        }

        // --- 處理 2：審核申請單 ---
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action_taken'])) {
            $req_id = intval($_POST['request_id']);
            $action_taken = $_POST['action_taken']; 
            
            $req_query = $conn->query("SELECT * FROM CourseRequests WHERE request_id = $req_id");
            if ($req = $req_query->fetch_assoc()) {
                $sid = $req['student_id'];
                $cid = $req['course_id'];
                $req_action = $req['action'];
                
                if ($action_taken == 'Approve') {
                    if ($req_action == 'Add') {
                        $ins = $conn->prepare("INSERT IGNORE INTO Enrollments (student_id, course_id) VALUES (?, ?)");
                        $ins->bind_param("si", $sid, $cid);
                        $ins->execute();
                    } else if ($req_action == 'Drop') {
                        $del = $conn->prepare("DELETE FROM Enrollments WHERE student_id = ? AND course_id = ?");
                        $del->bind_param("si", $sid, $cid);
                        $del->execute();
                    }
                    $conn->query("UPDATE CourseRequests SET status = 'Approved' WHERE request_id = $req_id");
                    
                    // 寫入日誌
                    $action_zh = ($req_action == 'Add') ? '加選' : '退選';
                    $log_desc = "核准了學生 {$sid} 的課程 ({$cid}) {$action_zh}申請。";
                    $conn->query("INSERT INTO AdminLogs (user_id, action_type, description) VALUES ($admin_uid, '選課審核', '$log_desc')");

                    echo "<div class='card' style='background:#d4edda; border-left:4px solid #28a745;'><strong>✓ 成功：</strong>已核准申請，課表已更新！</div>";
                } else if ($action_taken == 'Reject') {
                    $conn->query("UPDATE CourseRequests SET status = 'Rejected' WHERE request_id = $req_id");
                    
                    // 寫入日誌
                    $action_zh = ($req_action == 'Add') ? '加選' : '退選';
                    $log_desc = "駁回了學生 {$sid} 的課程 ({$cid}) {$action_zh}申請。";
                    $conn->query("INSERT INTO AdminLogs (user_id, action_type, description) VALUES ($admin_uid, '選課審核', '$log_desc')");

                    echo "<div class='card' style='background:#f8d7da; border-left:4px solid #dc3545;'><strong>✓ 成功：</strong>已駁回該申請。</div>";
                }
            }
        }

        // ==========================================
        // 畫面顯示區塊
        // ==========================================
        
        // 1. 系統狀態開關 UI
        $is_open = ($current_status === 'open');
        echo "<div style='display:flex; justify-content:space-between; align-items:center; background:#f4f6f9; padding:20px; border-radius:8px; margin-bottom:30px; border-left: 6px solid " . ($is_open ? '#28a745' : '#dc3545') . ";'>";
        echo "<div>";
        echo "<h3 style='margin:0 0 5px 0; color:#333;'>⚙️ 系統加退選狀態控制</h3>";
        if ($is_open) {
            echo "<p style='margin:0; color:#666;'>目前狀態：<span style='color:#28a745; font-weight:bold; font-size:1.1em;'>🟢 開放中</span> (學生可正常送出加退選申請單)</p>";
        } else {
            echo "<p style='margin:0; color:#666;'>目前狀態：<span style='color:#dc3545; font-weight:bold; font-size:1.1em;'>🔴 已關閉</span> (學生無法送出任何新申請)</p>";
        }
        echo "</div>";
        
        echo "<form method='POST' style='margin:0;'>";
        echo "<input type='hidden' name='toggle_enrollment' value='1'>";
        if ($is_open) {
            echo "<button type='submit' class='btn' style='background:#dc3545; font-size:1.05em; padding:10px 20px;' onclick='return confirm(\"確定要【關閉】加退選系統嗎？學生將無法再申請。\");'>🔴 關閉加退選系統</button>";
        } else {
            echo "<button type='submit' class='btn' style='background:#28a745; font-size:1.05em; padding:10px 20px;' onclick='return confirm(\"確定要【開放】加退選系統嗎？\");'>🟢 開放加退選系統</button>";
        }
        echo "</form>";
        echo "</div>";

        // 2. 待審核清單 UI
        echo "<h4 style='color:#007bff;'>⏳ 待審核申請</h4>";
        $pending = $conn->query("
            SELECT cr.*, s.name as student_name, c.course_code, c.course_name 
            FROM CourseRequests cr
            JOIN Students s ON cr.student_id = s.student_id
            JOIN Courses c ON cr.course_id = c.course_id
            WHERE cr.status = 'Pending' ORDER BY cr.request_date ASC
        ");

        if ($pending->num_rows > 0) {
            echo "<table style='width:100%; border-collapse:collapse; margin-bottom:30px;'><tr style='background:#f4f6f9;'><th style='padding:10px;'>申請時間</th><th style='padding:10px;'>學生</th><th style='padding:10px;'>課程</th><th style='padding:10px;'>動作</th><th style='padding:10px;'>審核</th></tr>";
            while ($r = $pending->fetch_assoc()) {
                $action_text = $r['action'] == 'Add' ? "<span style='color:green; font-weight:bold;'>➕ 申請加選</span>" : "<span style='color:red; font-weight:bold;'>❌ 申請退選</span>";
                echo "<tr style='border-bottom:1px solid #eee;'>";
                echo "<td style='padding:10px; color:#666;'>" . $r['request_date'] . "</td>";
                echo "<td style='padding:10px;'>" . htmlspecialchars($r['student_name']) . " ({$r['student_id']})</td>";
                echo "<td style='padding:10px;'>" . htmlspecialchars($r['course_code'] . " " . $r['course_name']) . "</td>";
                echo "<td style='padding:10px;'>{$action_text}</td>";
                echo "<td style='padding:10px;'><form method='POST' style='margin:0; display:flex; gap:5px;'>
                        <input type='hidden' name='request_id' value='{$r['request_id']}'>
                        <button type='submit' name='action_taken' value='Approve' class='btn' style='background:#28a745; padding:5px 10px;'>核准</button>
                        <button type='submit' name='action_taken' value='Reject' class='btn' style='background:#dc3545; padding:5px 10px;'>駁回</button>
                      </form></td></tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color:#999; padding:15px; background:#f8f9fa;'>目前沒有待審核的選課申請。</p>";
        }
    }
    ?>
</div>