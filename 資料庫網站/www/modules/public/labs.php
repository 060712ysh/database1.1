<div class="card">
    <h2>🔬 實驗室與研究展示</h2>
    <p>以下為本系各教授帶領之實驗室簡介與學術研究方向。</p>
    
    <?php
    // 撈取有填寫實驗室名稱的老師資料
    $labs = $conn->query("SELECT name, lab_name, lab_info FROM Teachers WHERE lab_name IS NOT NULL AND lab_name != '' ORDER BY teacher_id");
    
    if ($labs->num_rows > 0) {
        while($lab = $labs->fetch_assoc()) {
            echo "<div style='border: 1px solid #ddd; border-left: 5px solid #6f42c1; border-radius: 4px; padding: 20px; margin-bottom: 20px; background: #fff;'>";
            echo "<h3 style='margin-top: 0; color: #6f42c1; display: flex; align-items: center; justify-content: space-between;'>";
            echo htmlspecialchars($lab['lab_name']);
            echo "<span style='font-size: 0.8em; color: #666; font-weight: normal;'>指導教授：" . htmlspecialchars($lab['name']) . "</span>";
            echo "</h3>";
            echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 4px;'>";
            echo "<strong>研究方向與簡介：</strong><br>";
            echo nl2br(htmlspecialchars($lab['lab_info'] ?? '尚未提供詳細簡介。'));
            echo "</div>";
            echo "</div>";
        }
    } else {
        echo "<div class='card' style='background: #f8f9fa;'><p style='text-align:center; color:#666;'>目前尚無實驗室資訊</p></div>";
    }
    ?>
</div>