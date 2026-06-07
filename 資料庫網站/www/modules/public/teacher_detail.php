<?php
$teacher_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// 取得老師基本資料
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
            
            <?php if (!empty($teacher['lab_name'])): ?>
            <div style="margin-top: 15px; padding-top: 15px; border-top: 1px dashed #ccc;">
                <h4 style="margin: 0 0 5px 0; color: #6f42c1;">🔬 <?php echo htmlspecialchars($teacher['lab_name']); ?></h4>
                <p style="margin: 0; font-size: 0.9em; color: #555;">
                    <?php echo nl2br(htmlspecialchars($teacher['lab_info'])); ?>
                </p>
            </div>
            <?php endif; ?>
        </div>
        
        <div style="background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #ddd; border-left: 4px solid #ffc107;">
            <h4 style="margin-top: 0; color: #d39e00;">🏆 學術榮譽紀錄</h4>
            <ul style="padding-left: 20px; margin-bottom: 0; line-height: 1.6; color:#444;">
                <?php
                $honors = $conn->query("SELECT * FROM AcademicHonors WHERE teacher_id = $teacher_id ORDER BY award_year DESC");
                if($honors->num_rows > 0) {
                    while($h = $honors->fetch_assoc()) {
                        $year_str = $h['award_year'] ? "[{$h['award_year']}] " : "";
                        $body_str = $h['awarding_body'] ? " - " . htmlspecialchars($h['awarding_body']) : "";
                        echo "<li><strong>{$year_str}</strong>" . htmlspecialchars($h['honor_name']) . $body_str . "</li>";
                    }
                } else {
                    echo "<li style='color:#999; list-style:none;'>尚無登錄紀錄</li>";
                }
                ?>
            </ul>
        </div>
    </div>

    <div style="margin-top: 30px;">
        <h3 style="text-align: center; color: #333; margin-bottom: 20px;">論文及參與計畫</h3>
        
        <?php
        // 將所有著作撈出，並依據 work_type 進行陣列分組
        $pubs_result = $conn->query("SELECT * FROM Publications WHERE teacher_id = $teacher_id ORDER BY publish_year DESC");
        $grouped_pubs = [];
        while($row = $pubs_result->fetch_assoc()) {
            $grouped_pubs[$row['work_type']][] = $row;
        }
        
        if(!empty($grouped_pubs)) {
            echo "<div style='display:flex; flex-direction:column; gap:10px;'>";
            foreach($grouped_pubs as $type => $items) {
                $count = count($items);
                echo "<div style='background:#f4f9ff; border:1px solid #b8daff; border-radius:8px; overflow:hidden;'>";
                // 分類標題 (還原截圖的藍色箭頭與數量標示)
                echo "<div style='background:#007bff; color:white; padding:10px 15px; font-size:1.1em; font-weight:bold; display:flex; align-items:center;'>";
                echo "<span style='margin-right:10px;'>❯</span> {$type} ({$count})";
                echo "</div>";
                // 該分類下的項目列表
                echo "<div style='padding:15px; background:#fff;'>";
                echo "<ul style='margin:0; padding-left:20px; line-height:1.8; color:#444;'>";
                foreach($items as $item) {
                    $year_str = $item['publish_year'] ? "({$item['publish_year']}) " : "";
                    
                    // 【關鍵修正】動態組合：老師最新本名 + 其他作者
                    $display_authors = htmlspecialchars($teacher['name']);
                    if (!empty($item['authors'])) {
                        $display_authors .= ', ' . htmlspecialchars($item['authors']);
                    }
                    $author_str = " - 作者：" . $display_authors;
                    
                    echo "<li>{$year_str}<strong>" . htmlspecialchars($item['title']) . "</strong>{$author_str}</li>";
                }
                echo "</ul>";
                echo "</div>";
                echo "</div>";
            }
            echo "</div>";
        } else {
            echo "<p style='text-align:center; color:#999; padding:20px; background:#f8f9fa; border-radius:8px;'>尚無相關研究與計畫資料</p>";
        }
        ?>
    </div>

    <div style="margin-top: 30px;">
        <h4 style="color: #007bff; border-bottom: 2px solid #007bff; padding-bottom: 5px; display: inline-block;">📚 本學期授課大綱</h4>
        <?php
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
                echo "</div></div>";
            }
        } else {
            echo "<p style='color: #666; background: #f8f9fa; padding: 15px; border-radius: 4px;'>本學期暫無排定課程。</p>";
        }
        $courses->close();
        ?>
    </div>
</div>
<?php } ?>