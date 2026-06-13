<?php
$course_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// 查詢完整課程與教師資訊
$stmt = $conn->prepare("
    SELECT c.*, t.name as teacher_name, t.email as teacher_email 
    FROM Courses c 
    LEFT JOIN Teachers t ON c.teacher_id = t.teacher_id 
    WHERE c.course_id = ?
");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$course) {
    echo "<div class='card'><h2>⚠️ 查無此課程</h2><p>找不到該課程的教學大綱。</p></div>";
} else {
?>
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #007bff; padding-bottom: 10px; margin-bottom: 20px;">
        <h2 style="margin: 0; color: #2c3e50;">📘 課程教學大綱詳細檢視</h2>
        <button onclick="history.back()" class="btn" style="background: #6c757d;">🔙 返回上一頁</button>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 20px;">
        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 5px solid #007bff; height: fit-content;">
            <h3 style="margin-top: 0; color: #007bff; font-size:1.4em;"><?php echo htmlspecialchars($course['course_name']); ?></h3>
            <p><strong>課程代碼：</strong> <?php echo htmlspecialchars($course['course_code']); ?></p>
            <p><strong>開課學期：</strong> <?php echo htmlspecialchars($course['semester']); ?></p>
            <p><strong>授課教師：</strong> <?php echo htmlspecialchars($course['teacher_name'] ?? '未指派'); ?></p>
            <?php if(!empty($course['teacher_email'])): ?>
                <p><strong>教師信箱：</strong> <span style="font-size:0.9em; color:#555;"><?php echo htmlspecialchars($course['teacher_email']); ?></span></p>
            <?php endif; ?>
            <p><strong>上課時間：</strong> <span style="color:#d35400; font-weight:bold;"><?php echo htmlspecialchars($course['schedule']); ?></span></p>
            <p><strong>上課教室：</strong> <span style="color:#28a745; font-weight:bold;"><?php echo htmlspecialchars($course['room'] ?? '未定'); ?></span></p>
            <p><strong>修課人數上限：</strong> <?php echo htmlspecialchars($course['capacity']); ?> 人</p>
            
            <div style="margin-top: 20px; padding-top: 15px; border-top: 1px dashed #ccc; font-size: 0.9em; color: #666;">
                <strong>📊 成績加權配分比：</strong><br>
                平時成績：<?php echo $course['weight_assignment']; ?>%<br>
                期中成績：<?php echo $course['weight_midterm']; ?>%<br>
                期末成績：<?php echo $course['weight_final']; ?>%
            </div>
        </div>

        <div style="background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #ddd;">
            <h4 style="margin-top: 0; color: #333; border-bottom: 1px solid #eee; padding-bottom: 8px;">📋 課程內容與進度規劃</h4>
            <div style="line-height: 1.8; color: #444; font-size: 1.05em; white-space: pre-wrap;">
                <?php 
                if (!empty($course['syllabus'])) {
                    echo nl2br(htmlspecialchars($course['syllabus']));
                } else {
                    echo "<span style='color:#999; font-style:italic;'>授課老師目前尚未編寫或上傳本課程之詳細大綱。</span>";
                }
                ?>
            </div>
        </div>
    </div>
</div>
<?php } ?>