<?php
$teacher_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// 取得老師所有詳細資料
$stmt = $conn->prepare("SELECT * FROM Teachers WHERE teacher_id = ?");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$teacher = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$teacher) {
    echo "<div class='card'><h2>查無此教師</h2><p>找不到該教師的資料。</p></div>";
} else {
?>
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <h2 style="margin: 0; color: #2c3e50;">👨‍🏫 <?php echo htmlspecialchars($teacher['name']); ?> <?php echo htmlspecialchars($teacher['title']); ?></h2>
        <a href="index.php?page=faculty" class="btn" style="background: #6c757d;">返回師資陣容</a>
    </div>
    <hr style="border-top: 2px solid #eee;">
    
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #17a2b8;">
            <h4 style="margin-top: 0; color: #17a2b8;">📞 聯絡與請益資訊</h4>
            <p><strong>所屬單位：</strong> <?php echo htmlspecialchars($teacher['department']); ?></p>
            <p><strong>電子信箱：</strong> <?php echo htmlspecialchars($teacher['email']); ?></p>
            <p><strong>聯絡電話：</strong> <?php echo htmlspecialchars($teacher['phone']); ?></p>
            <div style="margin-top: 15px; padding-top: 15px; border-top: 1px dashed #ccc;">
                <p style="margin: 0;"><strong>💬 請益時間 (Office Hours)：</strong></p>
                <p style="color: #28a745; font-weight: bold; margin: 5px 0 0 0; font-size: 1.1em;">
                    <?php echo htmlspecialchars($teacher['office_hours'] ?? '請先透過信箱預約'); ?>
                </p>
            </div>
        </div>
        
        <div style="display: flex; flex-direction: column; gap: 15px;">
            <div style="background: #fff; padding: 15px; border-radius: 8px; border: 1px solid #ddd;">
                <h4 style="margin-top: 0; color: #ffc107; text-shadow: 1px 1px 1px rgba(0,0,0,0.1);">🏆 學術榮譽</h4>
                <p style="margin-bottom: 0; line-height: 1.6;">
                    <?php echo nl2br(htmlspecialchars($teacher['academic_honors'] ?? '尚無資料')); ?>
                </p>
            </div>
            
            <div style="background: #fff; padding: 15px; border-radius: 8px; border: 1px solid #ddd; border-left: 4px solid #6f42c1;">
                <h4 style="margin-top: 0; color: #6f42c1;">🔬 帶領實驗室</h4>
                <?php if (!empty($teacher['lab_name'])): ?>
                    <p><strong><?php echo htmlspecialchars($teacher['lab_name']); ?></strong></p>
                    <p style="margin-bottom: 0; font-size: 0.95em; color: #555;">
                        <?php echo nl2br(htmlspecialchars($teacher['lab_info'])); ?>
                    </p>
                <?php else: ?>
                    <p style="margin-bottom: 0; color: #999;">尚未登錄實驗室資訊</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div style="margin-top: 30px;">
        <h4 style="color: #007bff; border-bottom: 2px solid #007bff; padding-bottom: 5px; display: inline-block;">📑 論文與著作</h4>
        <div style="background: #fff; border: 1px solid #ddd; padding: 20px; border-radius: 4px; line-height: 1.8;">
            <?php echo nl2br(htmlspecialchars($teacher['papers'] ?? '尚無資料')); ?>
        </div>
    </div>

    <div style="margin-top: 30px;">
        <h4 style="color: #007bff; border-bottom: 2px solid #007bff; padding-bottom: 5px; display: inline-block;">📚 本學期授課大綱</h4>
        
        <?php
        // 撈取該老師本學期(113-1)的課程與大綱
        $courses = $conn->prepare("SELECT course_code, course_name, schedule, syllabus FROM Courses WHERE teacher_id = ? AND semester = '113-1'");
        $courses->bind_param("i", $teacher_id);
        $courses->execute();
        $courses_result = $courses->get_result();
        
        if ($courses_result->num_rows > 0) {
            while ($c = $courses_result->fetch_assoc()) {
                echo "<div style='border: 1px solid #17a2b8; border-left: 5px solid #17a2b8; border-radius: 4px; padding: 15px; margin-bottom: 15px; background: #fff;'>";
                echo "<h4 style='margin: 0 0 10px 0; color: #2c3e50;'>" . htmlspecialchars($c['course_code']) . " - " . htmlspecialchars($c['course_name']) . "</h4>";
                echo "<p style='margin: 0 0 10px 0; font-size: 0.9em; color: #666;'><strong>上課時間：</strong> " . htmlspecialchars($c['schedule']) . "</p>";
                echo "<div style='background: #f4f6f9; padding: 15px; border-radius: 4px; border: 1px solid #e9ecef;'>";
                echo "<strong style='color: #495057;'>課程大綱與計畫：</strong><br><br>";
                echo nl2br(htmlspecialchars($c['syllabus'] ?? '老師尚未上傳教學大綱。'));
                echo "</div>";
                echo "</div>";
            }
        } else {
            echo "<p style='color: #666; background: #f8f9fa; padding: 15px; border-radius: 4px;'>本學期暫無排定課程。</p>";
        }
        $courses->close();
        ?>
    </div>
</div>
<?php } ?>