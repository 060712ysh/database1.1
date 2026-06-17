﻿﻿﻿<div class="card">
    <h2>💬 留言回復</h2>
    
    <?php
    if($_SESSION['role'] != 'Admin') {
        echo "<p style='color:red;'>只有管理員可以使用此功能。</p>";
    } else {
        // 處理回覆提交
        if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_reply'])) {
            $message_id = intval($_POST['message_id']);
            $reply_content = trim($_POST['reply_content'] ?? '');
            
            if($reply_content) {
                $update = $conn->prepare("UPDATE Messages SET reply_content = ? WHERE message_id = ?");
                $update->bind_param("si", $reply_content, $message_id);
                $update->execute();
                echo "<div class='card' style='background:#d4edda; border-left:4px solid #28a745;'><strong>✓ 成功：</strong>回覆已送出</div>";
                $update->close();
            }
        }
        
        // 列出待回覆的留言
        $pending_messages = $conn->query(
            "SELECT m.message_id, u.username, s.name as student_name, s.student_id, 
                    m.content, m.created_at
             FROM Messages m
             JOIN Users u ON m.sender_id = u.id
             LEFT JOIN Students s ON u.id = s.user_id
             WHERE m.reply_content IS NULL
             ORDER BY m.created_at DESC"
        );
        
        if($pending_messages->num_rows > 0) {
            while($msg = $pending_messages->fetch_assoc()) {
                echo "<div style='border:1px solid #ddd; padding:15px; border-radius:5px; margin-bottom:15px;'>";
                echo "<div style='display:flex; justify-content:space-between;'>";
                echo "<strong>留言人：" . htmlspecialchars($msg['student_id'] ?? $msg['username']) . " " . htmlspecialchars($msg['student_name'] ?? $msg['username']) . " (" . date('Y-m-d H:i', strtotime($msg['created_at'])) . ")</strong>";
                echo "<span style='color:orange;'>待回覆</span>";
                echo "</div>";
                echo "<p style='margin:10px 0;'>" . htmlspecialchars($msg['content']) . "</p>";
                echo "<hr style='border:0; border-top:1px dashed #eee;'>";
                echo "<form method='POST'>";
                echo "<input type='hidden' name='message_id' value='" . $msg['message_id'] . "'>";
                echo "<textarea name='reply_content' rows='2' style='width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;' placeholder='在此輸入回覆內容...'></textarea>";
                echo "<button type='submit' name='submit_reply' class='btn' style='margin-top:5px; background:#007bff; padding:5px 10px;'>送出回覆</button>";
                echo "</form>";
                echo "</div>";
            }
        } else {
            echo "<p style='color:#999;'>暫無待回覆的留言</p>";
        }
        
        // 列出已回覆的留言
        $replied_messages = $conn->query(
            "SELECT m.message_id, u.username, s.name as student_name, s.student_id,
                    m.content, m.reply_content, m.created_at
             FROM Messages m
             JOIN Users u ON m.sender_id = u.id
             LEFT JOIN Students s ON u.id = s.user_id
             WHERE m.reply_content IS NOT NULL
             ORDER BY m.created_at DESC
             LIMIT 5"
        );
        
        if($replied_messages->num_rows > 0) {
            echo "<h4>已回覆的留言</h4>";
            while($msg = $replied_messages->fetch_assoc()) {
                echo "<div style='border:1px solid #ddd; padding:15px; border-radius:5px; margin-bottom:15px;'>";
                echo "<div style='display:flex; justify-content:space-between;'>";
                echo "<strong>留言人：" . htmlspecialchars($msg['student_id'] ?? $msg['username']) . " " . htmlspecialchars($msg['student_name'] ?? $msg['username']) . " (" . date('Y-m-d H:i', strtotime($msg['created_at'])) . ")</strong>";
                echo "<span style='color:green;'>已回覆</span>";
                echo "</div>";
                echo "<p style='margin:10px 0;'>" . htmlspecialchars($msg['content']) . "</p>";
                echo "<div style='background:#e8f4fd; padding:10px; border-left:4px solid #007bff; margin-top:10px;'>";
                echo "<p style='margin:0; font-size:0.9em;'><strong>管理員回覆：</strong> " . htmlspecialchars($msg['reply_content']) . "</p>";
                echo "</div>";
                echo "</div>";
            }
        }
    }
    ?>
</div>
