<div class="card">
    <h2>👨‍🏫 師資陣容</h2>
    <p>歡迎認識資訊工程學系的專業師資團隊。</p>
    
    <div style="display: flex; gap: 20px; flex-wrap: wrap; margin-top:20px;">
    <?php
    $teachers = $conn->query("SELECT teacher_id, name, title, email, phone FROM Teachers ORDER BY teacher_id");
    while($teacher = $teachers->fetch_assoc()) {
        echo "<div style='border: 1px solid #ddd; border-radius: 8px; padding: 15px; width: 30%; background: #fff;'>";
        echo "<h3 style='margin-top:0; color: #2c3e50;'>" . htmlspecialchars($teacher['name']) . "</h3>";
        echo "<p><strong>職稱：</strong> " . htmlspecialchars($teacher['title'] ?? '(未設定)') . "</p>";
        echo "<p><strong>信箱：</strong> " . htmlspecialchars($teacher['email'] ?? '(未設定)') . "</p>";
        echo "<p><strong>電話：</strong> " . htmlspecialchars($teacher['phone'] ?? '(未設定)') . "</p>";
        // 加入按鈕，並透過 GET 傳遞 teacher_id
        echo "<a href='index.php?page=teacher_detail&id=" . $teacher['teacher_id'] . "' class='btn' style='margin-top: 10px; width: 100%; text-align: center; box-sizing: border-box; background: #17a2b8;'>查看個人專頁</a>";
        echo "</div>";
    }
    ?>
    </div>
</div>