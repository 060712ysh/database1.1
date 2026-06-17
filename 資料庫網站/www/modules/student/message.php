<div class="card">
    <h2 style="color: #2c3e50; border-bottom: 2px solid #1976d2; padding-bottom: 10px;">💬 聯絡系辦</h2>
    <p style="color: #555;">有任何課程、選課或系統問題，都可以透過此留言板詢問，系辦人員會盡快為您解答。</p>

    <?php
    if(!isset($_SESSION['role']) || $_SESSION['role'] != 'Student') {
        echo "<p style='color:red;'>只有學生可以使用此功能。</p>";
    } else {
        $student_uid = intval($_SESSION['user_id']);

        // --- 處理留言發送 ---
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_msg'])) {
            $content = trim($_POST['content']);
            if ($content) {
                $ins = $conn->prepare("INSERT INTO Messages (sender_id, content) VALUES (?, ?)");
                $ins->bind_param("is", $student_uid, $content);
                if ($ins->execute()) {
                    echo "<div class='card' style='background:#d4edda; border-left:4px solid #28a745;'><strong>✓ 成功：</strong>您的留言已送出，請留意後續回覆！</div>";
                }
            }
        }
        ?>

        <form method="POST" style="background: #f4f6f9; padding: 20px; border-radius: 8px; border: 1px solid #ddd; margin-bottom: 30px;">
            <label style="font-weight: bold; color: #444; display: block; margin-bottom: 10px;">📝 新增留言：</label>
            <textarea name="content" rows="4" required placeholder="請在此輸入您的問題..." style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; resize: vertical; margin-bottom: 15px; font-family: inherit;"></textarea>
            <button type="submit" name="send_msg" class="btn" style="background: #1976d2; padding: 8px 25px; font-size: 1.05em;">📤 送出留言</button>
        </form>

        <h3 style="color: #2c3e50; border-bottom: 1px solid #ddd; padding-bottom: 8px;">📬 我的留言紀錄</h3>

        <?php
        // 撈取學生的歷史留言
        $msgs = $conn->query("SELECT * FROM Messages WHERE sender_id = $student_uid ORDER BY created_at DESC");

        if ($msgs && $msgs->num_rows > 0) {
            echo "<div style='display: flex; flex-direction: column; gap: 20px; margin-top: 15px;'>";
            while ($m = $msgs->fetch_assoc()) {
                $is_replied = !empty($m['reply_content']);
                $date = date('Y-m-d H:i', strtotime($m['created_at']));

                echo "<div style='background: #fff; border: 1px solid #e0e0e0; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.02);'>";
                
                // 標題列 (時間 + 狀態標籤)
                echo "  <div style='background: #f8f9fa; padding: 12px 15px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;'>";
                echo "      <span style='color: #666; font-size: 0.9em;'>發送時間：{$date}</span>";
                if ($is_replied) {
                    echo "  <span style='background: #28a745; color: #fff; padding: 4px 12px; border-radius: 12px; font-size: 0.85em; font-weight: bold;'>✓ 已回覆</span>";
                } else {
                    echo "  <span style='background: #ffc107; color: #333; padding: 4px 12px; border-radius: 12px; font-size: 0.85em; font-weight: bold;'>⏳ 等待回覆中</span>";
                }
                echo "  </div>";

                // 學生留言內容
                echo "  <div style='padding: 15px;'>";
                echo "      <div style='color: #444; font-weight: bold; margin-bottom: 8px;'>📝 您的留言：</div>";
                echo "      <div style='color: #333; line-height: 1.6; border-left: 3px solid #ccc; padding-left: 10px; margin-left: 5px; white-space: pre-wrap;'>" . htmlspecialchars($m['content']) . "</div>";
                echo "  </div>";

                // 系辦回覆內容 (若有)
                if ($is_replied) {
                    echo "  <div style='background: #eaf4ff; padding: 15px; border-top: 1px solid #cce4ff; margin: 0 15px 15px 15px; border-radius: 6px;'>";
                    echo "      <div style='color: #1976d2; font-weight: bold; margin-bottom: 8px;'>🏢 系辦管理員回覆：</div>";
                    echo "      <div style='color: #333; line-height: 1.6; white-space: pre-wrap;'>" . htmlspecialchars($m['reply_content']) . "</div>";
                    echo "  </div>";
                }

                echo "</div>";
            }
            echo "</div>";
        } else {
            echo "<div style='background: #f8f9fa; padding: 30px; text-align: center; color: #999; border-radius: 8px; border: 1px dashed #ccc;'>目前尚無任何留言紀錄。</div>";
        }
        ?>
        
        <script>
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
        </script>
        
    <?php } ?>
</div>