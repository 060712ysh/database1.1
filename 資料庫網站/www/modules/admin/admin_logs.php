<div class="card">
    <h2>📜 系統操作日誌 (Audit Log)</h2>
    <p>此日誌詳實記錄系統內管理員所執行的關鍵操作 (包含最新消息發布、帳號增建、空間預約加退選審核)，以資稽核。</p>

    <?php
    if ($_SESSION['role'] != 'Admin') {
        echo "<p style='color:red;'>無權限瀏覽此頁面。</p>";
    } else {
        // 從 AdminLogs 關聯到 Users 與 Admins 撈出操作者的本名
        $logs = $conn->query("
            SELECT l.*, COALESCE(a.name, u.username) AS operator_name, u.role
            FROM AdminLogs l 
            JOIN Users u ON l.user_id = u.id
            LEFT JOIN Admins a ON u.id = a.user_id
            ORDER BY l.created_at DESC 
            LIMIT 100
        ");

        if ($logs && $logs->num_rows > 0) {
            echo "<table style='width:100%; border-collapse:collapse; text-align:left;'>";
            echo "<tr style='background:#f4f6f9; border-bottom:2px solid #343a40;'>
                    <th style='padding:12px 10px; width:18%;'>動作時間</th>
                    <th style='padding:12px 10px; width:18%;'>操作人員</th>
                    <th style='padding:12px 10px; width:18%;'>功能類別</th>
                    <th style='padding:12px 10px; width:46%;'>異動摘要說明</th>
                  </tr>";

            while ($r = $logs->fetch_assoc()) {
                $type = $r['action_type'];
                $badge_bg = "#6c757d"; 
                if ($type == '最新消息') $badge_bg = "#17a2b8";
                if ($type == '空間預約審核') $badge_bg = "#28a745";
                if ($type == '帳號建立') $badge_bg = "#007bff";

                $badge = "<span style='background:{$badge_bg}; color:#fff; padding:3px 9px; border-radius:12px; font-size:0.85em; font-weight:500;'>{$type}</span>";

                echo "<tr style='border-bottom:1px solid #eee;' onmouseover=\"this.style.background='#f9f9f9'\" onmouseout=\"this.style.background='#fff'\">";
                echo "<td style='padding:12px 10px; color:#666; font-size:0.9em;'>" . $r['created_at'] . "</td>";
                echo "<td style='padding:12px 10px;'><strong>" . htmlspecialchars($r['operator_name']) . "</strong> <span style='font-size:0.8em; color:#999;'>({$r['role']})</span></td>";
                echo "<td style='padding:12px 10px;'>{$badge}</td>";
                echo "<td style='padding:12px 10px; color:#333; line-height:1.4;'>" . htmlspecialchars($r['description']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<div style='background:#f8f9fa; padding:20px; text-align:center; color:#999; border-radius:8px;'>目前尚無任何關鍵系統操作紀錄。</div>";
        }
    }
    ?>
</div>