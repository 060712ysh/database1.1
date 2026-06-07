<div class="card" style="display:flex; flex-direction:column; height: 80vh;">
    <h2>💬 聯絡系辦中心</h2>
    <p style="color:#666; font-size:0.9em;">有任何問題嗎？請在此留下訊息，系辦管理員會盡快回覆您。</p>
    
    <?php
    if(!isset($_SESSION['user_id'])) {
        echo "<p style='color:red;'>請先登入</p>";
    } else {
        $user_id = $_SESSION['user_id'];
        
        if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_message'])) {
            $content = trim($_POST['content']);
            if($content) {
                $ins = $conn->prepare("INSERT INTO Messages (sender_id, content) VALUES (?, ?)");
                $ins->bind_param("is", $user_id, $content);
                $ins->execute();
            }
        }
        
        // --- 聊天對話框區域 ---
        echo "<div style='flex:1; background:#e5ddd5; border-radius:8px; padding:20px; overflow-y:auto; display:flex; flex-direction:column; gap:15px; margin-bottom:15px;'>";
        
        $msgs = $conn->prepare("SELECT content, reply_content, created_at FROM Messages WHERE sender_id = ? ORDER BY created_at ASC");
        $msgs->bind_param("i", $user_id);
        $msgs->execute();
        $res = $msgs->get_result();
        
        if($res->num_rows == 0) {
            echo "<div style='text-align:center; color:#888; margin-top:20px;'>目前還沒有任何對話紀錄，傳個訊息給系辦吧！</div>";
        }
        
        while($m = $res->fetch_assoc()) {
            $time = date('m/d H:i', strtotime($m['created_at']));
            
            // 學生發言 (靠右、藍色)
            echo "<div style='align-self: flex-end; max-width: 75%;'>";
            echo "<div style='background:#dcf8c6; padding:10px 15px; border-radius:15px 15px 0 15px; box-shadow:0 1px 1px rgba(0,0,0,0.1); color:#333; word-break:break-all;'>";
            echo nl2br(htmlspecialchars($m['content']));
            echo "</div>";
            echo "<div style='text-align:right; font-size:0.75em; color:#888; margin-top:3px;'>我 - $time</div>";
            echo "</div>";
            
            // 系辦回覆 (靠左、白色)
            if(!empty($m['reply_content'])) {
                echo "<div style='align-self: flex-start; max-width: 75%;'>";
                echo "<div style='background:#fff; padding:10px 15px; border-radius:15px 15px 15px 0; box-shadow:0 1px 1px rgba(0,0,0,0.1); color:#333; word-break:break-all;'>";
                echo nl2br(htmlspecialchars($m['reply_content']));
                echo "</div>";
                echo "<div style='font-size:0.75em; color:#888; margin-top:3px;'>🏢 系辦管理員回覆</div>";
                echo "</div>";
            }
        }
        echo "</div>";
        // --- 聊天對話框區域結束 ---
        
        // 底部輸入框
        echo "<form method='POST' style='display:flex; gap:10px;'>";
        echo "<textarea name='content' rows='2' style='flex:1; border-radius:20px; padding:10px 15px; border:1px solid #ccc; resize:none;' placeholder='請輸入訊息...' required></textarea>";
        echo "<button type='submit' name='submit_message' class='btn' style='border-radius:20px; padding:0 25px; background:#007bff;'>送出</button>";
        echo "</form>";
    }
    ?>
</div>