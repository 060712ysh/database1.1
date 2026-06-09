<div class="card">
    <h2>✉️ 聯絡系辦中心 </h2>
    <p style="color:#666; font-size:0.9em;">有任何問題嗎？請在此撰寫留言，系辦管理員會盡快回覆您。</p>
    
    <?php
    if(!isset($_SESSION['user_id'])) {
        echo "<p style='color:red;'>請先登入</p>";
    } else {
        $user_id = $_SESSION['user_id'];
        
        // 處理信件發送
        if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_message'])) {
            $content = trim($_POST['content']);
            if($content) {
                $ins = $conn->prepare("INSERT INTO Messages (sender_id, content) VALUES (?, ?)");
                $ins->bind_param("is", $user_id, $content);
                if($ins->execute()) {
                    echo "<div class='card' style='background:#d4edda; border-left:4px solid #28a745; margin-bottom:15px; padding:15px;'><strong>✓ 成功：</strong>信件已成功寄出給系辦！請留意下方的回覆狀態。</div>";
                }
            }
        }
        
        // --- 寫信表單區塊 ---
        echo "<form method='POST' style='background:#f8f9fa; padding:20px; border:1px solid #dee2e6; border-radius:8px; margin-bottom:30px;'>";
        echo "<h4 style='margin-top:0; color:#007bff; margin-bottom:15px;'>✏️ 撰寫留言</h4>";
        echo "<textarea name='content' rows='5' style='width:100%; box-sizing:border-box; border-radius:4px; padding:10px; border:1px solid #ced4da; margin-bottom:15px; resize:vertical; font-family:inherit;' placeholder='請輸入您想詢問系辦的問題、或是請假說明...' required></textarea>";
        echo "<button type='submit' name='submit_message' class='btn' style='background:#007bff; padding:8px 20px;'>📤 送出留言</button>";
        echo "</form>";
        
        // --- 信件紀錄列表區塊 ---
        echo "<h4 style='color:#495057; border-bottom:2px solid #e9ecef; padding-bottom:10px;'>📥 我的留言紀錄</h4>";
        
        $msgs = $conn->prepare("SELECT content, reply_content, created_at FROM Messages WHERE sender_id = ? ORDER BY created_at DESC");
        $msgs->bind_param("i", $user_id);
        $msgs->execute();
        $res = $msgs->get_result();
        
        if($res->num_rows == 0) {
            echo "<p style='color:#888; text-align:center; padding:20px; background:#f4f6f9; border-radius:5px;'>目前還沒有任何留言紀錄。</p>";
        } else {
            while($m = $res->fetch_assoc()) {
                $time = date('Y-m-d H:i', strtotime($m['created_at']));
                $has_reply = !empty($m['reply_content']);
                
                // 動態狀態標籤
                $status_badge = $has_reply ? 
                    "<span style='background:#28a745; color:#fff; padding:4px 10px; border-radius:12px; font-size:0.85em; font-weight:bold;'>✓ 已回覆</span>" : 
                    "<span style='background:#ffc107; color:#333; padding:4px 10px; border-radius:12px; font-size:0.85em; font-weight:bold;'>⏳ 等待回覆中</span>";
                
                echo "<div style='border:1px solid #e9ecef; border-radius:8px; margin-bottom:20px; box-shadow:0 1px 3px rgba(0,0,0,0.05); overflow:hidden;'>";
                
                // 標題列：時間與狀態
                echo "<div style='background:#f4f6f9; padding:12px 15px; border-bottom:1px solid #e9ecef; display:flex; justify-content:space-between; align-items:center;'>";
                echo "<div><strong style='color:#495057;'>發送時間：</strong> <span style='color:#666;'>$time</span></div>";
                echo "<div>$status_badge</div>";
                echo "</div>";
                
                // 信件內容區
                echo "<div style='padding:20px;'>";
                echo "<div style='color:#333; line-height:1.6; margin-bottom:15px;'><strong style='color:#495057;'>📝 您的留言：</strong><br><div style='margin-top:8px; padding-left:10px; border-left:3px solid #ccc;'>" . nl2br(htmlspecialchars($m['content'])) . "</div></div>";
                
                // 系統回覆區 (若有回覆才顯示)
                if($has_reply) {
                    echo "<div style='background:#e8f4fd; border:1px solid #b8daff; padding:15px; border-radius:6px; margin-top:20px;'>";
                    echo "<strong style='color:#0056b3;'>🏢 系辦管理員回覆：</strong><br>";
                    echo "<div style='margin-top:8px; color:#333; line-height:1.6;'>" . nl2br(htmlspecialchars($m['reply_content'])) . "</div>";
                    echo "</div>";
                }
                
                echo "</div>"; // end padding div
                echo "</div>"; // end card div
            }
        }
    }
    ?>
</div>