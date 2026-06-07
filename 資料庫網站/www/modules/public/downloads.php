<div class="card">
    <h2>📥 系所表單與檔案下載區</h2>
    <p>此區提供系辦公室上傳之各項法規、申請表單與講義下載。</p>
    
    <table style='width:100%; text-align:left; border-collapse: collapse; margin-top:20px;'>
        <tr style='border-bottom:2px solid #343a40; background:#f4f6f9;'>
            <th style='padding:10px; width:30%;'>檔案名稱</th>
            <th style='padding:10px; width:40%;'>備註說明</th>
            <th style='padding:10px; width:15%;'>上傳日期</th>
            <th style='padding:10px; width:15%;'>操作</th>
        </tr>
        <?php
        // 改為從資料庫撈取檔案與備註紀錄
        $files = $conn->query("SELECT * FROM SystemFiles ORDER BY uploaded_at DESC");
        $has_files = false;
        
        while($f = $files->fetch_assoc()) {
            $has_files = true;
            $filepath = 'uploads/' . $f['filename'];
            
            // 雙重保險：確保資料庫裡有紀錄，且實體檔案也沒有遺失
            if(file_exists($filepath)) {
                $filesize_str = round(filesize($filepath) / 1024, 2) . ' KB';
                echo "<tr style='border-bottom:1px solid #e0e0e0;'>";
                echo "<td style='padding:10px; word-break:break-all; font-weight:bold; color:#2c3e50;'>" . htmlspecialchars($f['filename']) . "<br><span style='font-size:0.8em; color:#999; font-weight:normal;'>大小: {$filesize_str}</span></td>";
                echo "<td style='padding:10px; color:#007bff; font-weight:500;'>" . htmlspecialchars($f['remark']) . "</td>";
                echo "<td style='padding:10px; color:#888; font-size:0.9em;'>" . date('Y-m-d', strtotime($f['uploaded_at'])) . "</td>";
                echo "<td style='padding:10px;'><a href='" . $filepath . "' class='btn' style='background:#17a2b8; text-decoration:none;' download>📥 下載檔案</a></td>";
                echo "</tr>";
            }
        }
        
        if (!$has_files) {
            echo "<tr><td colspan='4' style='padding:20px; text-align:center; color:#999;'>目前尚無檔案可供下載</td></tr>";
        }
        ?>
    </table>
</div>