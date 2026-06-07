﻿﻿<div class="card">
    <h2>🏢 教室預約審核與日誌</h2>
    
    <?php
    if($_SESSION['role'] != 'Admin') {
        echo "<p style='color:red;'>無權限。</p>";
    } else {
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            $res_id = intval($_POST['reservation_id']);
            if(isset($_POST['approve'])) {
                $conn->query("UPDATE Reservations SET status = 'Approved' WHERE reservation_id = $res_id");
                echo "<div class='card' style='background:#d4edda; border-left:4px solid #28a745;'>已核准</div>";
            } else if(isset($_POST['reject'])) {
                $reason = trim($_POST['reject_reason']);
                $stmt = $conn->prepare("UPDATE Reservations SET status = 'Rejected', reject_reason = ? WHERE reservation_id = ?");
                $stmt->bind_param("si", $reason, $res_id);
                $stmt->execute();
                echo "<div class='card' style='background:#f8d7da; border-left:4px solid #dc3545;'>已拒絕</div>";
            }
        }
        
        echo "<h4 style='color:#007bff;'>⏳ 待審核清單</h4>";
        $pending = $conn->query("SELECT r.*, s.name FROM Reservations r JOIN Students s ON r.student_id = s.student_id WHERE r.status = 'Pending' ORDER BY r.start_time");
        if($pending->num_rows > 0) {
            echo "<table style='width:100%; border-collapse:collapse; margin-bottom:30px;'><tr style='background:#f4f6f9;'><th style='padding:10px;'>借用人</th><th style='padding:10px;'>空間</th><th style='padding:10px;'>事由</th><th style='padding:10px;'>時間</th><th style='padding:10px;'>操作</th></tr>";
            while($r = $pending->fetch_assoc()) {
                $time = date('m/d H:i', strtotime($r['start_time'])) . " - " . date('H:i', strtotime($r['end_time']));
                echo "<tr style='border-bottom:1px solid #eee;'>";
                echo "<td style='padding:10px;'>{$r['name']} ({$r['student_id']})</td><td style='padding:10px;'>{$r['room_name']}</td><td style='padding:10px;'>{$r['purpose']}</td><td style='padding:10px;'>$time</td>";
                echo "<td style='padding:10px;'><form method='POST' style='display:flex; gap:5px; align-items:center;'><input type='hidden' name='reservation_id' value='{$r['reservation_id']}'>";
                echo "<button type='submit' name='approve' class='btn' style='background:#28a745;'>核准</button>";
                echo "<input type='text' name='reject_reason' placeholder='退件理由' style='width:120px; padding:6px; margin:0;'>";
                echo "<button type='submit' name='reject' class='btn' style='background:#dc3545;'>退件</button></form></td></tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color:#666;'>目前無待審核案件。</p><hr>";
        }
        
        echo "<h4 style='color:#6c757d;'>📖 審核日誌 (歷史紀錄)</h4>";
        $logs = $conn->query("SELECT r.*, s.name FROM Reservations r JOIN Students s ON r.student_id = s.student_id WHERE r.status != 'Pending' ORDER BY r.start_time DESC LIMIT 20");
        echo "<table style='width:100%; border-collapse:collapse; opacity:0.9;'><tr style='background:#f4f6f9;'><th style='padding:10px;'>借用人</th><th style='padding:10px;'>空間</th><th style='padding:10px;'>狀態</th><th style='padding:10px;'>備註 / 退件理由</th></tr>";
        while($r = $logs->fetch_assoc()) {
            $color = $r['status'] == 'Approved' ? 'green' : 'red';
            $status = $r['status'] == 'Approved' ? '已核准' : '已退件';
            echo "<tr style='border-bottom:1px solid #eee;'>";
            echo "<td style='padding:10px;'>{$r['name']}</td><td style='padding:10px;'>{$r['room_name']}</td>";
            echo "<td style='padding:10px; color:$color; font-weight:bold;'>$status</td>";
            echo "<td style='padding:10px;'>".htmlspecialchars($r['reject_reason'] ?? '-')."</td></tr>";
        }
        echo "</table>";
    }
    ?>
</div>