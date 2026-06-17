<div class="card">
    <h2>🏢 空間預約申請</h2>
    <p>提供學生預約系上討論室進行專題討論或課業研討。請依據「日期與節次」進行預約。</p>
    
    <?php
    // 統一使用系統標準的 role 與 username 驗證
    if(!isset($_SESSION['role']) || $_SESSION['role'] != 'Student') {
        echo "<p style='color:red;'>只有學生可以使用此功能。</p>";
    } else {
        $student_id = $_SESSION['username']; // 學生的登入帳號即為學號 (如 s00001)
        
        // ✨ 架構升級：全自動向資料庫撈取目前管理員開放的所有「討論室」空間
        $available_rooms = [];
        $rooms_db_query = $conn->query("SELECT room_name FROM Rooms WHERE room_type = '討論室' ORDER BY room_name ASC");
        if ($rooms_db_query && $rooms_db_query->num_rows > 0) {
            while ($room_row = $rooms_db_query->fetch_assoc()) {
                $available_rooms[] = $room_row['room_name'];
            }
        } else {
            // 萬一資料表全空時的緊急安全備用防線
            $available_rooms = ['討論室 101 (4人)']; 
        }

        // --- 處理 1：預約表單送出 ---
        if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_reservation'])) {
            $room = trim($_POST['room_name']);
            $purpose = trim($_POST['purpose']);
            $date = $_POST['reserve_date'];
            $start_p = intval($_POST['start_period']);
            $end_p = intval($_POST['end_period']);
            
            if ($start_p > $end_p) {
                echo "<div class='card' style='background:#f8d7da; border-left:4px solid #dc3545;'><strong>✗ 錯誤：</strong>結束節次不能早於開始節次！</div>";
            } elseif (!in_array($room, $available_rooms)) {
                echo "<div class='card' style='background:#f8d7da; border-left:4px solid #dc3545;'><strong>✗ 錯誤：</strong>無效的空間選擇！</div>";
            } else {
                // 檢查是否與現有(且未被拒絕)的預約時段衝突
                // 衝突條件：日期相同、空間相同，且 (新開始 <= 舊結束 AND 新結束 >= 舊開始)
                $check = $conn->prepare("SELECT reservation_id FROM Reservations WHERE room_name = ? AND reserve_date = ? AND status != 'Rejected' AND (start_period <= ? AND end_period >= ?)");
                $check->bind_param("ssii", $room, $date, $end_p, $start_p);
                $check->execute();
                
                if($check->get_result()->num_rows > 0) {
                    echo "<div class='card' style='background:#fff3cd; border-left:4px solid #ffc107;'><strong>⚠️ 抱歉：</strong>該空間在您選擇的時段內已被預約或正在審核中，請選擇其他節次或空間。</div>";
                } else {
                    $ins = $conn->prepare("INSERT INTO Reservations (student_id, room_name, purpose, reserve_date, start_period, end_period) VALUES (?, ?, ?, ?, ?, ?)");
                    $ins->bind_param("ssssii", $student_id, $room, $purpose, $date, $start_p, $end_p);
                    $ins->execute();
                    echo "<div class='card' style='background:#d4edda; border-left:4px solid #28a745;'><strong>✓ 成功：</strong>預約申請已送出，請靜候系辦審核！</div>";
                }
            }
        }

        // --- 處理 2：取消預約 (新增功能) ---
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cancel_reservation'])) {
            $cancel_id = intval($_POST['reservation_id']);
            $conn->query("DELETE FROM Reservations WHERE reservation_id = $cancel_id AND student_id = '$student_id' AND status = 'Pending'");
            echo "<div class='card' style='background:#d4edda; border-left:4px solid #28a745;'><strong>✓ 取消成功：</strong>已為您撤銷該筆預約申請。</div>";
        }
        
        echo "<div style='display: grid; grid-template-columns: 1fr 1fr; gap: 20px;'>";
        
        // ==========================================
        // 左欄：申請表單與紀錄
        // ==========================================
        echo "<div>";
        echo "<form method='POST' style='background:#f4f6f9; padding:20px; border-radius:8px; margin-bottom:20px; border:1px solid #ddd;'>";
        
        echo "<div style='margin-bottom:15px;'><label style='display:block; margin-bottom:5px; font-weight:bold; color:#444;'>借用空間：</label><select name='room_name' required style='width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;'>";
        foreach($available_rooms as $r) { echo "<option value='" . htmlspecialchars($r) . "'>" . htmlspecialchars($r) . "</option>"; }
        echo "</select></div>";
        
        echo "<div style='margin-bottom:15px;'><label style='display:block; margin-bottom:5px; font-weight:bold; color:#444;'>借用事由：</label><input type='text' name='purpose' placeholder='例如：專題小組討論' required style='width:100%; padding:8px; border:1px solid #ccc; border-radius:4px; box-sizing:border-box;'></div>";
        
        echo "<div style='margin-bottom:15px;'><label style='display:block; margin-bottom:5px; font-weight:bold; color:#444;'>借用日期：</label><input type='date' name='reserve_date' min='" . date('Y-m-d') . "' required style='width:100%; padding:8px; border:1px solid #ccc; border-radius:4px; box-sizing:border-box;'></div>";
        
        echo "<div style='display:flex; gap:10px; margin-bottom:20px;'>";
        echo "<div style='flex:1;'><label style='display:block; margin-bottom:5px; font-weight:bold; color:#444;'>開始節次：</label><select name='start_period' required style='width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;'>";
        for($i=1; $i<=14; $i++) echo "<option value='$i'>第 $i 節</option>";
        echo "</select></div>";
        
        echo "<div style='flex:1;'><label style='display:block; margin-bottom:5px; font-weight:bold; color:#444;'>結束節次：</label><select name='end_period' required style='width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;'>";
        for($i=1; $i<=14; $i++) echo "<option value='$i'>第 $i 節</option>";
        echo "</select></div>";
        echo "</div>";
        
        echo "<button type='submit' name='submit_reservation' class='btn' style='background:#007bff; width:100%; font-size:1.05em; font-weight:bold; padding:10px;'>送出審核申請單</button>";
        echo "</form>";

        // 我的申請紀錄
        echo "<h4 style='color:#2c3e50; border-bottom:2px solid #343a40; padding-bottom:8px;'>📝 我的申請紀錄</h4>";
        $my_reqs = $conn->prepare("SELECT * FROM Reservations WHERE student_id = ? ORDER BY reserve_date DESC, start_period DESC");
        $my_reqs->bind_param("s", $student_id);
        $my_reqs->execute();
        $res = $my_reqs->get_result();
        
        if($res->num_rows > 0) {
            echo "<ul style='padding-left:0; list-style:none; line-height:1.6; color:#444;'>";
            while($req = $res->fetch_assoc()) {
                $status_color = $req['status'] == 'Approved' ? 'green' : ($req['status'] == 'Rejected' ? 'red' : '#ffc107');
                $status_text = $req['status'] == 'Approved' ? '✅ 核准' : ($req['status'] == 'Rejected' ? '❌ 駁回' : '⏳ 審核中');
                $reject_msg = $req['status'] == 'Rejected' && $req['reject_reason'] ? " <div style='color:red; font-size:0.9em; margin-top:4px;'>(理由: " . htmlspecialchars($req['reject_reason']) . ")</div>" : "";
                
                echo "<li style='margin-bottom:15px; border-bottom:1px solid #eee; padding-bottom:15px;'>";
                echo "<div style='font-size:1.05em; color:#333; margin-bottom:5px;'><strong>" . htmlspecialchars($req['reserve_date']) . " (第 {$req['start_period']}~{$req['end_period']} 節)</strong></div>";
                echo "空間：" . htmlspecialchars($req['room_name']) . " | 事由：" . htmlspecialchars($req['purpose']) . "<br>";
                
                echo "<div style='display:flex; align-items:center; gap:10px; margin-top:5px;'>";
                echo "狀態：<strong style='color:$status_color;'>$status_text</strong>";
                
                // ✨ 新增：若為審核中，顯示取消按鈕
                if ($req['status'] == 'Pending') {
                    echo "<form method='POST' style='margin:0;' onsubmit='return confirm(\"確定要取消這筆申請嗎？\");'>";
                    echo "<input type='hidden' name='reservation_id' value='{$req['reservation_id']}'>";
                    echo "<button type='submit' name='cancel_reservation' style='background:none; border:none; color:#dc3545; text-decoration:underline; cursor:pointer; padding:0; font-size:0.95em;'>取消申請</button>";
                    echo "</form>";
                }
                echo "</div>";
                echo $reject_msg;
                echo "</li>";
            }
            echo "</ul>";
        } else {
            echo "<div style='background:#f8f9fa; padding:20px; text-align:center; color:#999; border-radius:8px; border:1px dashed #ccc;'>尚無紀錄</div>";
        }
        echo "</div>"; // end 左欄

        // ==========================================
        // 右欄：近期空間佔用狀況
        // ==========================================
        echo "<div style='background:#f8f9fa; padding:20px; border-radius:8px; border:1px solid #e9ecef; height:fit-content;'>";
        echo "<h4 style='color:#17a2b8; margin-top:0;'>📅 近期空間佔用狀況</h4>";
        echo "<p style='font-size:0.9em; color:#666;'>以下為未來一週內「已被核准借用」的時段，申請時請避開這些時間。</p>";
        
        $occupied = $conn->query("SELECT room_name, reserve_date, start_period, end_period FROM Reservations WHERE reserve_date >= CURDATE() AND reserve_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND status = 'Approved' ORDER BY reserve_date ASC, start_period ASC");
        
        if($occupied->num_rows > 0) {
            echo "<ul style='padding-left:20px; line-height:1.8; font-size:0.95em;'>";
            while($occ = $occupied->fetch_assoc()) {
                echo "<li style='margin-bottom:8px;'><strong style='color:#d35400;'>" . htmlspecialchars($occ['reserve_date']) . "</strong>：<br>";
                echo "🔹 " . htmlspecialchars($occ['room_name']) . " <span style='color:#007bff; font-weight:bold;'>(第 {$occ['start_period']}~{$occ['end_period']} 節)</span></li>";
            }
            echo "</ul>";
        } else {
            echo "<div style='text-align:center; padding:30px 0;'>";
            echo "<h4 style='color:#28a745; margin-bottom:5px;'>🎉 太棒了！</h4>";
            echo "<p style='color:#28a745; margin:0;'>未來一週內目前無人借用空間，時段非常充裕！</p>";
            echo "</div>";
        }
        echo "</div>"; // end 右欄
        
        echo "</div>"; // end grid
        ?>
        
        <script>
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
        </script>
        
    <?php } ?>
</div>