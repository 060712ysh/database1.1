﻿<div class="card">
    <h2 style="color: #2c3e50; border-bottom: 2px solid #1976d2; padding-bottom: 10px;">💬 留言回覆管理</h2>
    <p style="color: #555;">您可以在此查看並回覆學生透過「聯絡系辦」發送的私密留言。</p>
    
    <?php
    if(!isset($_SESSION['role']) || $_SESSION['role'] != 'Admin') {
        echo "<p style='color:red;'>只有管理員可以使用此功能。</p>";
    } else {
        // --- 處理回覆提交 ---
        if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_reply'])) {
            $message_id = intval($_POST['message_id']);
            $reply_content = trim($_POST['reply_content'] ?? '');
            
            if($reply_content) {
                $update = $conn->prepare("UPDATE Messages SET reply_content = ? WHERE message_id = ?");
                $update->bind_param("si", $reply_content, $message_id);
                if ($update->execute()) {
                    echo "<div class='card' style='background:#d4edda; border-left:4px solid #28a745;'><strong>✓ 成功：</strong>回覆已順利送出給該名學生！</div>";
                }
                $update->close();
            }
        }
        
        echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 25px; margin-top: 20px;'>";

        // ==========================================
        // 區塊 1：列出待回覆的留言 (加上獨立捲動軸)
        // ==========================================
        echo "<div style='background: #fdfdfe; padding: 20px; border-radius: 8px; border: 1px solid #e0e0e0; box-shadow: 0 2px 8px rgba(0,0,0,0.02); display: flex; flex-direction: column; max-height: 700px;'>";
        echo "  <h3 style='color: #d35400; margin-top: 0; border-bottom: 1px dashed #ccc; padding-bottom: 10px; flex-shrink: 0;'>⏳ 待回覆的留言</h3>";
        
        echo "  <div style='overflow-y: auto; padding-right: 10px; flex-grow: 1;'>"; // 開啟捲動區塊

        $pending_messages = $conn->query(
            "SELECT m.message_id, u.username, s.name as student_name, s.student_id, 
                    m.content, m.created_at
             FROM Messages m
             JOIN Users u ON m.sender_id = u.id
             LEFT JOIN Students s ON u.id = s.user_id
             WHERE m.reply_content IS NULL
             ORDER BY m.created_at ASC" // 待回覆通常先顯示舊的 (先問先答)
        );
        
        if($pending_messages->num_rows > 0) {
            while($msg = $pending_messages->fetch_assoc()) {
                $display_name = htmlspecialchars($msg['student_name'] ?? $msg['username']);
                $display_id = htmlspecialchars($msg['student_id'] ?? $msg['username']);
                $time = date('Y-m-d H:i', strtotime($msg['created_at']));

                echo "<div style='border: 1px solid #ffc107; background: #fffcf5; padding: 15px; border-radius: 6px; margin-bottom: 15px;'>";
                echo "  <div style='display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;'>";
                echo "      <strong style='color: #333;'>👤 {$display_name} ({$display_id})</strong>";
                echo "      <span style='color: #888; font-size: 0.85em;'>{$time}</span>";
                echo "  </div>";
                echo "  <p style='margin: 10px 0; color: #444; line-height: 1.6; white-space: pre-wrap;'>" . htmlspecialchars($msg['content']) . "</p>";
                
                echo "  <form method='POST' style='margin-top: 15px; border-top: 1px dashed #ffeeba; padding-top: 10px;'>";
                echo "      <input type='hidden' name='message_id' value='" . $msg['message_id'] . "'>";
                echo "      <textarea name='reply_content' rows='3' required style='width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; resize: vertical;' placeholder='在此輸入回覆內容...'></textarea>";
                echo "      <button type='submit' name='submit_reply' class='btn' style='margin-top: 10px; background: #17a2b8; width: 100%; font-weight: bold;'>📤 送出回覆</button>";
                echo "  </form>";
                echo "</div>";
            }
        } else {
            echo "<div style='text-align: center; color: #999; padding: 30px 0;'>太棒了！目前沒有待回覆的留言 🎉</div>";
        }
        echo "  </div>"; // 結束捲動區塊
        echo "</div>"; // 結束區塊 1
        
        // ==========================================
        // 區塊 2：列出所有已回覆的留言 (移除 LIMIT，加上獨立捲動軸)
        // ==========================================
        echo "<div style='background: #f4f6f9; padding: 20px; border-radius: 8px; border: 1px solid #e0e0e0; display: flex; flex-direction: column; max-height: 700px;'>";
        echo "  <h3 style='color: #28a745; margin-top: 0; border-bottom: 1px dashed #ccc; padding-bottom: 10px; flex-shrink: 0;'>✅ 所有已回覆紀錄</h3>";

        echo "  <div style='overflow-y: auto; padding-right: 10px; flex-grow: 1;'>"; // 開啟捲動區塊

        // 移除 LIMIT 5，顯示所有歷史紀錄
        $replied_messages = $conn->query(
            "SELECT m.message_id, u.username, s.name as student_name, s.student_id,
                    m.content, m.reply_content, m.created_at
             FROM Messages m
             JOIN Users u ON m.sender_id = u.id
             LEFT JOIN Students s ON u.id = s.user_id
             WHERE m.reply_content IS NOT NULL
             ORDER BY m.created_at DESC"
        );
        
        if($replied_messages->num_rows > 0) {
            while($msg = $replied_messages->fetch_assoc()) {
                $display_name = htmlspecialchars($msg['student_name'] ?? $msg['username']);
                $display_id = htmlspecialchars($msg['student_id'] ?? $msg['username']);
                $time = date('Y-m-d H:i', strtotime($msg['created_at']));

                echo "<div style='border: 1px solid #ddd; background: #fff; padding: 15px; border-radius: 6px; margin-bottom: 15px;'>";
                echo "  <div style='display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;'>";
                echo "      <strong style='color: #333;'>👤 {$display_name} ({$display_id})</strong>";
                echo "      <span style='color: #888; font-size: 0.85em;'>{$time}</span>";
                echo "  </div>";
                echo "  <p style='margin: 10px 0; color: #555; line-height: 1.5; white-space: pre-wrap;'>" . htmlspecialchars($msg['content']) . "</p>";
                
                echo "  <div style='background: #e8f4fd; padding: 12px; border-left: 4px solid #1976d2; border-radius: 4px; margin-top: 10px;'>";
                echo "      <div style='font-size: 0.85em; color: #1976d2; font-weight: bold; margin-bottom: 5px;'>系辦回覆：</div>";
                echo "      <div style='margin: 0; color: #333; line-height: 1.5; white-space: pre-wrap;'>" . htmlspecialchars($msg['reply_content']) . "</div>";
                echo "  </div>";
                echo "</div>";
            }
        } else {
            echo "<div style='text-align: center; color: #999; padding: 30px 0;'>尚無回覆紀錄</div>";
        }
        echo "  </div>"; // 結束捲動區塊
        echo "</div>"; // 結束區塊 2
        
        echo "</div>"; // 結束 Grid 排版

        // 🌟 核心防呆：防止 F5 重新整理導致表單重複提交
        echo "<script>
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
        </script>";
    }
    ?>
</div>