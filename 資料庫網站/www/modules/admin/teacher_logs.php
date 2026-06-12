<div class="card">
    <h2>📜 教師更新日誌 (Audit Log)</h2>
    <p>此區記錄所有教師更新「個人簡歷、學術榮譽、著作與計畫」的操作歷程，方便管理員追蹤公開資訊的異動。</p>

    <?php
    if ($_SESSION['role'] != 'Admin') {
        echo "<p style='color:red;'>無權限。</p>";
    } else {
        // 從資料庫撈取日誌，並關聯 Teachers 表取得老師姓名
        $logs = $conn->query("
            SELECT l.*, t.name AS teacher_name 
            FROM TeacherLogs l 
            JOIN Teachers t ON l.teacher_id = t.teacher_id 
            ORDER BY l.created_at DESC 
            LIMIT 100
        ");

        if ($logs->num_rows > 0) {
            echo "<table style='width:100%; border-collapse:collapse; text-align:left;'>";
            echo "<tr style='background:#f4f6f9; border-bottom:2px solid #343a40;'>
                    <th style='padding:12px 10px; width:18%;'>異動時間</th>
                    <th style='padding:12px 10px; width:15%;'>教師姓名</th>
                    <th style='padding:12px 10px; width:17%;'>資料類別</th>
                    <th style='padding:12px 10px; width:50%;'>異動細節</th>
                  </tr>";

            while ($r = $logs->fetch_assoc()) {
                // 根據不同的動作給予不同的顏色標籤
                $type = $r['action_type'];
                $type_color = "#6c757d"; 
                if ($type == '基本資料更新') $type_color = "#007bff";
                if ($type == '學術榮譽') $type_color = "#28a745";
                if ($type == '著作與計畫') $type_color = "#6f42c1";

                $badge = "<span style='background:{$type_color}; color:#fff; padding:3px 8px; border-radius:12px; font-size:0.85em;'>{$type}</span>";

                echo "<tr style='border-bottom:1px solid #eee;' onmouseover=\"this.style.background='#f9f9f9'\" onmouseout=\"this.style.background='#fff'\">";
                echo "<td style='padding:12px 10px; color:#666; font-size:0.9em;'>" . $r['created_at'] . "</td>";
                echo "<td style='padding:12px 10px; font-weight:bold; color:#2c3e50;'>" . htmlspecialchars($r['teacher_name']) . "</td>";
                echo "<td style='padding:12px 10px;'>" . $badge . "</td>";
                echo "<td style='padding:12px 10px; color:#444;'>" . htmlspecialchars($r['description']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            echo "<p style='color:#999; font-size:0.85em; margin-top:15px; text-align:right;'>※ 系統預設僅顯示最近 100 筆紀錄。</p>";
        } else {
            echo "<div style='background:#f8f9fa; padding:20px; text-align:center; color:#999; border-radius:8px;'>目前尚無任何教師的更新紀錄。</div>";
        }
    }
    ?>
</div>