<div class="card">
    <h2>📝 學生選課與退選審核</h2>
    <p>管理員可在此審核學生的加退選申請，核准後將自動更新至學生的正式課表中。</p>

    <?php
    if ($_SESSION['role'] != 'Admin') {
        echo "<p style='color:red;'>無權限。</p>";
    } else {
        // 【修正】將判斷條件改為 action_taken，正確對應下方按鈕的 name 屬性
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action_taken'])) {
            $req_id = intval($_POST['request_id']);
            $action_taken = $_POST['action_taken']; // 接收 'Approve' 或 'Reject'
            
            // 取得該申請單的詳細資訊
            $req_query = $conn->query("SELECT * FROM CourseRequests WHERE request_id = $req_id");
            if ($req = $req_query->fetch_assoc()) {
                $sid = $req['student_id'];
                $cid = $req['course_id'];
                $req_action = $req['action'];
                
                if ($action_taken == 'Approve') {
                    if ($req_action == 'Add') {
                        // 核准加選 -> 寫入 Enrollments
                        $ins = $conn->prepare("INSERT IGNORE INTO Enrollments (student_id, course_id) VALUES (?, ?)");
                        $ins->bind_param("si", $sid, $cid);
                        $ins->execute();
                    } else if ($req_action == 'Drop') {
                        // 核准退選 -> 從 Enrollments 刪除
                        $del = $conn->prepare("DELETE FROM Enrollments WHERE student_id = ? AND course_id = ?");
                        $del->bind_param("si", $sid, $cid);
                        $del->execute();
                    }
                    $conn->query("UPDATE CourseRequests SET status = 'Approved' WHERE request_id = $req_id");
                    echo "<div class='card' style='background:#d4edda; border-left:4px solid #28a745;'><strong>✓ 成功：</strong>已核准申請，課表已更新！</div>";
                } else if ($action_taken == 'Reject') {
                    $conn->query("UPDATE CourseRequests SET status = 'Rejected' WHERE request_id = $req_id");
                    echo "<div class='card' style='background:#f8d7da; border-left:4px solid #dc3545;'><strong>✓ 成功：</strong>已駁回該申請。</div>";
                }
            }
        }

        // 待審核清單
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