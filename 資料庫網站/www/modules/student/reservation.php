<div style="display:grid; grid-template-columns: 1fr 1fr; gap: 20px;">
    <div class="card">
        <h2>🏢 空間預約申請</h2>
        
        <?php
        if(!isset($_SESSION['student_id'])) {
            echo "<p style='color:red;'>無權限。</p>";
        } else {
            $student_id = $_SESSION['student_id'];
            
            if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_reservation'])) {
                $room = trim($_POST['room_name']);
                $purp = trim($_POST['purpose']);
                $start = $_POST['start_time'];
                $end = $_POST['end_time'];
                
                if($room && $purp && $start && $end) {
                    $ins = $conn->prepare("INSERT INTO Reservations (student_id, room_name, purpose, start_time, end_time) VALUES (?, ?, ?, ?, ?)");
                    $ins->bind_param("sssss", $student_id, $room, $purp, $start, $end);
                    $ins->execute();
                    echo "<div class='card' style='background:#d4edda; border-left:4px solid #28a745;'><strong>✓ 成功：</strong>申請已送出，請等待系辦審核</div>";
                }
            }
            
            echo "<form method='POST' style='background:#f4f6f9; padding:15px; border-radius:5px;'>";
            echo "<label>借用空間 (如：資工館 R102)：</label><input type='text' name='room_name' required>";
            echo "<label>借用事由：</label><input type='text' name='purpose' placeholder='例如：專題小組討論' required>";
            echo "<label>開始時間：</label><input type='datetime-local' name='start_time' required>";
            echo "<label>結束時間：</label><input type='datetime-local' name='end_time' required>";
            echo "<button type='submit' name='submit_reservation' class='btn' style='width:100%;'>送出審核申請單</button></form>";
            
            echo "<hr>";
            echo "<h4>📝 我的申請紀錄</h4>";
            $res = $conn->prepare("SELECT * FROM Reservations WHERE student_id = ? ORDER BY start_time DESC");
            $res->bind_param("s", $student_id);
            $res->execute();
            $result = $res->get_result();
            
            if($result->num_rows > 0) {
                while($r = $result->fetch_assoc()) {
                    $color = $r['status'] == 'Approved' ? 'green' : ($r['status'] == 'Rejected' ? 'red' : 'orange');
                    $status_tw = $r['status'] == 'Approved' ? '核准' : ($r['status'] == 'Rejected' ? '退件' : '審核中');
                    echo "<div style='border-left:3px solid $color; padding:10px; background:#fff; margin-bottom:10px; box-shadow:0 1px 3px rgba(0,0,0,0.1);'>";
                    echo "<strong>{$r['room_name']}</strong> <span style='color:$color; font-size:0.8em;'>[$status_tw]</span><br>";
                    echo "<span style='font-size:0.9em; color:#666;'>" . date('m/d H:i', strtotime($r['start_time'])) . " ~ " . date('H:i', strtotime($r['end_time'])) . "</span>";
                    if($r['status'] == 'Rejected' && !empty($r['reject_reason'])) {
                        echo "<br><span style='color:red; font-size:0.85em;'>退件理由：{$r['reject_reason']}</span>";
                    }
                    echo "</div>";
                }
            } else {
                echo "<p style='color:#999;'>尚無紀錄</p>";
            }
        }
        ?>
    </div>

    <div class="card" style="background: #fafafa;">
        <h2 style="color: #17a2b8;">📅 近期空間佔用狀況</h2>
        <p style="font-size:0.9em; color:#666;">以下為未來一週內<strong>「已被核准借用」</strong>的時段，申請時請避開這些時間。</p>
        
        <?php
        $occupied = $conn->query("
            SELECT room_name, purpose, start_time, end_time 
            FROM Reservations 
            WHERE status = 'Approved' AND end_time >= NOW()
            ORDER BY start_time ASC LIMIT 15
        ");
        
        if($occupied->num_rows > 0) {
            while($occ = $occupied->fetch_assoc()) {
                $start_date = date('m/d (D)', strtotime($occ['start_time']));
                $time_range = date('H:i', strtotime($occ['start_time'])) . " ~ " . date('H:i', strtotime($occ['end_time']));
                echo "<div style='background:#fff; border:1px solid #eee; padding:10px; margin-bottom:8px; border-radius:4px;'>";
                echo "<strong style='color:#e67e22;'>📍 {$occ['room_name']}</strong><br>";
                echo "<span style='color:#34495e; font-weight:bold;'>$start_date $time_range</span><br>";
                echo "<span style='color:#999; font-size:0.85em;'>佔用事由：{$occ['purpose']}</span>";
                echo "</div>";
            }
        } else {
            echo "<div style='text-align:center; padding:30px 0; color:#27ae60;'>";
            echo "<h3>🎉 太棒了！</h3><p>未來一週內目前無人借用空間，時段非常充裕！</p></div>";
        }
        ?>
    </div>
</div>