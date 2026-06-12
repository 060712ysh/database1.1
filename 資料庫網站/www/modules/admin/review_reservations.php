﻿<div class="card">
    <h2>📋 空間預約審核</h2>
    <p>審核學生提交的討論室空間借用申請。核准後，該時段將會對其他學生顯示為佔用狀態。</p>
    
    <?php
    if($_SESSION['role'] != 'Admin') {
        echo "<p style='color:red;'>只有管理員可以使用此功能。</p>";
    } else {
        // 處理審核動作
        if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['process_reservation'])) {
            $res_id = intval($_POST['reservation_id']);
            $action = $_POST['action']; // 'Approve' or 'Reject'
            $reason = trim($_POST['reject_reason'] ?? '');
            
            if($action == 'Approve') {
                $conn->query("UPDATE Reservations SET status = 'Approved' WHERE reservation_id = $res_id");
                echo "<div class='card' style='background:#d4edda; border-left:4px solid #28a745;'><strong>✓ 成功：</strong>已核准該預約！</div>";
            } else if($action == 'Reject') {
                $upd = $conn->prepare("UPDATE Reservations SET status = 'Rejected', reject_reason = ? WHERE reservation_id = ?");
                $upd->bind_param("si", $reason, $res_id);
                $upd->execute();
                echo "<div class='card' style='background:#f8d7da; border-left:4px solid #dc3545;'><strong>✓ 成功：</strong>已駁回該預約。</div>";
            }
        }
        
        // 取得待審核清單 (Pending)
        $pending = $conn->query("
            SELECT r.*, s.name as student_name 
            FROM Reservations r 
            JOIN Students s ON r.student_id = s.student_id 
            WHERE r.status = 'Pending' 
            ORDER BY r.reserve_date ASC, r.start_period ASC
        ");
        
        echo "<h3 style='color:#007bff;'>⏳ 待審核申請</h3>";
        if($pending->num_rows > 0) {
            echo "<table style='width:100%; border-collapse: collapse; margin-bottom:30px;'>";
            echo "<tr style='background:#f4f6f9; border-bottom:2px solid #ccc;'><th style='padding:10px;'>申請人</th><th style='padding:10px;'>借用空間</th><th style='padding:10px;'>借用日期與節次</th><th style='padding:10px;'>事由</th><th style='padding:10px;'>審核操作</th></tr>";
            
            while($p = $pending->fetch_assoc()) {
                echo "<tr style='border-bottom:1px solid #eee;'>";
                echo "<td style='padding:10px;'>" . htmlspecialchars($p['student_name']) . "<br><span style='color:#888; font-size:0.85em;'>" . $p['student_id'] . "</span></td>";
                echo "<td style='padding:10px; font-weight:bold; color:#2c3e50;'>" . htmlspecialchars($p['room_name']) . "</td>";
                
                // 顯示日期與節次
                echo "<td style='padding:10px;'>";
                echo "<span style='color:#d35400; font-weight:bold;'>" . htmlspecialchars($p['reserve_date']) . "</span><br>";
                echo "第 {$p['start_period']} 節 ~ 第 {$p['end_period']} 節";
                echo "</td>";
                
                echo "<td style='padding:10px;'>" . htmlspecialchars($p['purpose']) . "</td>";
                echo "<td style='padding:10px;'>";
                echo "<form method='POST' style='display:flex; flex-direction:column; gap:5px; margin:0;'>";
                echo "<input type='hidden' name='reservation_id' value='{$p['reservation_id']}'>";
                echo "<div style='display:flex; gap:5px;'>";
                echo "<button type='submit' name='action' value='Approve' class='btn' style='background:#28a745; padding:5px 10px;'>核准</button>";
                echo "<button type='submit' name='action' value='Reject' class='btn' style='background:#dc3545; padding:5px 10px;'>駁回</button>";
                echo "</div>";
                echo "<input type='text' name='reject_reason' placeholder='若駁回請填寫理由' style='padding:5px; font-size:0.9em; width:100px;'>";
                echo "</form>";
                echo "</td></tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color:#999; padding:15px; background:#f8f9fa;'>目前沒有待審核的預約申請。</p>";
        }
        
        // 取得近期已核准清單 (Approved)
        echo "<h3 style='color:#28a745; margin-top:40px;'>✅ 近期已核准清單</h3>";
        $approved = $conn->query("
            SELECT r.*, s.name as student_name 
            FROM Reservations r 
            JOIN Students s ON r.student_id = s.student_id 
            WHERE r.status = 'Approved' AND r.reserve_date >= CURDATE() 
            ORDER BY r.reserve_date ASC, r.start_period ASC 
            LIMIT 20
        ");
        
        if($approved->num_rows > 0) {
            echo "<table style='width:100%; border-collapse: collapse; font-size:0.95em;'>";
            echo "<tr style='background:#f8fff9; border-bottom:2px solid #c3e6cb;'><th style='padding:10px;'>申請人</th><th style='padding:10px;'>空間</th><th style='padding:10px;'>日期與節次</th><th style='padding:10px;'>事由</th></tr>";
            
            while($a = $approved->fetch_assoc()) {
                echo "<tr style='border-bottom:1px solid #eee;'>";
                echo "<td style='padding:10px;'>" . htmlspecialchars($a['student_name']) . " (" . $a['student_id'] . ")</td>";
                echo "<td style='padding:10px;'>" . htmlspecialchars($a['room_name']) . "</td>";
                echo "<td style='padding:10px;'>" . htmlspecialchars($a['reserve_date']) . " (第 {$a['start_period']}~{$a['end_period']} 節)</td>";
                echo "<td style='padding:10px;'>" . htmlspecialchars($a['purpose']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color:#999;'>近期無已核准的預約。</p>";
        }
    }
    ?>
</div>