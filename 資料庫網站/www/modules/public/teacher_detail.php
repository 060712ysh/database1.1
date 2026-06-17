<?php
$teacher_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
// 取得當前模式，預設為 profile (簡歷)
$view = isset($_GET['view']) ? $_GET['view'] : 'profile'; 

// 取得老師基本資料
$stmt = $conn->prepare("SELECT * FROM Teachers WHERE teacher_id = ?");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$teacher = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$teacher) {
    echo "<div class='card'><h2>⚠️ 查無此教師</h2><p>找不到該教師的資料。</p></div>";
} else {
?>
<div class="card" style="max-width: 1200px; margin: 0 auto; box-sizing: border-box;">
    
    <div style="display: flex; justify-content: space-between; align-items: flex-end; border-bottom: 2px solid #1976d2; padding-bottom: 15px; margin-bottom: 20px;">
        <div style="display: flex; align-items: center; gap: 20px;">
            <?php
            // 唯一的大頭照渲染區塊
            $avatar = (!empty($teacher['avatar_path']) && file_exists($teacher['avatar_path'])) ? $teacher['avatar_path'] : '';
            if ($avatar) {
                echo "<img src='{$avatar}' style='width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 2px solid #1976d2;'>";
            } else {
                $f_char = function_exists('mb_substr') ? mb_substr($teacher['name'], 0, 1, 'UTF-8') : '👤';
                echo "<div style='width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg, #17a2b8, #007bff); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: bold; border: 2px solid #1976d2;'>{$f_char}</div>";
            }
            ?>
            <div>
                <h2 style="margin: 0; color: #2c3e50; font-size: 2em;"><?php echo htmlspecialchars($teacher['name']); ?> <span style="font-size: 0.6em; color: #666; font-weight: normal;"><?php echo htmlspecialchars($teacher['title']); ?></span></h2>
                <?php 
                // 智慧拼接電話與分機
                $phone_disp = !empty($teacher['phone']) ? htmlspecialchars($teacher['phone']) : '未提供';
                $ext_disp = !empty($teacher['extension']) ? " 分機 " . htmlspecialchars($teacher['extension']) : '';
                ?>
                <p style="margin: 5px 0 0 0; color: #555;">📞 電話: <?php echo $phone_disp . $ext_disp; ?> | ✉️ 信箱: <?php echo htmlspecialchars($teacher['email'] ?? '未提供'); ?></p>
            </div>
        </div>
        <a href="index.php?page=faculty" class="btn" style="background: #6c757d; padding: 8px 20px; border-radius: 20px; text-decoration: none;">🔙 返回師資</a>
    </div>

    <div style="display: flex; gap: 15px; margin-bottom: 30px; border-bottom: 1px solid #ddd; padding-bottom: 15px;">
        <a href="index.php?page=teacher_detail&id=<?php echo $teacher_id; ?>&view=profile" 
           style="text-decoration: none; padding: 8px 25px; border-radius: 25px; font-weight: bold; transition: 0.2s; <?php echo $view == 'profile' ? 'background: #17a2b8; color: #fff; box-shadow: 0 2px 6px rgba(23,162,184,0.4);' : 'background: #f4f6f9; color: #666; border: 1px solid #ddd;'; ?>">
           👤 個人經歷與學術
        </a>
        <a href="index.php?page=teacher_detail&id=<?php echo $teacher_id; ?>&view=schedule" 
           style="text-decoration: none; padding: 8px 25px; border-radius: 25px; font-weight: bold; transition: 0.2s; <?php echo $view == 'schedule' ? 'background: #28a745; color: #fff; box-shadow: 0 2px 6px rgba(40,167,69,0.4);' : 'background: #f4f6f9; color: #666; border: 1px solid #ddd;'; ?>">
           📅 授課課表與大綱
        </a>
    </div>

    <?php if ($view == 'profile'): ?>
        <style>
            details.custom-accordion { margin-bottom: 10px; }
            details.custom-accordion summary {
                display: flex; align-items: center; cursor: pointer;
                padding: 8px 12px; background: #f8f9fa; border-radius: 6px;
                font-weight: bold; color: #333; font-size: 1.05em;
                list-style: none; user-select: none; transition: background 0.2s;
            }
            details.custom-accordion summary:hover { background: #e9ecef; }
            details.custom-accordion summary::-webkit-details-marker { display: none; }
            
            .accordion-icon {
                display: inline-flex; align-items: center; justify-content: center;
                width: 24px; height: 24px; background: #2196F3; color: white;
                border-radius: 4px; margin-right: 12px; font-size: 14px;
                transition: transform 0.2s; flex-shrink: 0;
            }
            details.custom-accordion[open] .accordion-icon { transform: rotate(90deg); }
            
            .accordion-content { padding: 15px 15px 15px 45px; color: #444; line-height: 1.7; font-size: 0.95em; }
            .accordion-content ol { margin: 0; padding-left: 20px; }
            .accordion-content li { margin-bottom: 10px; }
        </style>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 40px;">
            <div style="background: #f8f9fa; padding: 25px; border-radius: 8px; border-left: 4px solid #17a2b8; height: fit-content;">
                <h3 style="color: #17a2b8; margin-top: 0; border-bottom: 1px solid #dee2e6; padding-bottom: 10px;">經歷與專長</h3>
                <div style="line-height: 1.8; color: #444;">
                    <p><strong>🏢 實驗室：</strong> <?php echo htmlspecialchars($teacher['lab_name'] ?? '尚未填寫'); ?></p>
                    <p><strong>🔬 研究方向：</strong><br><?php echo nl2br(htmlspecialchars($teacher['lab_info'] ?? '尚未填寫')); ?></p>
                    <p><strong>🏫 校內經歷：</strong><br><?php echo nl2br(htmlspecialchars($teacher['teaching_experience'] ?? '尚未填寫')); ?></p>
                    <p><strong>🏢 校外經歷：</strong><br><?php echo nl2br(htmlspecialchars($teacher['external_experience'] ?? '尚未填寫')); ?></p>
                    <p><strong>🕒 請益時間 (Office Hours)：</strong> <span style="color:#d35400; font-weight:bold;"><?php echo htmlspecialchars($teacher['office_hours'] ?? '未提供'); ?></span></p>
                </div>
            </div>

            <div>
                <?php
                // 學術榮譽
                $honors = $conn->query("SELECT * FROM AcademicHonors WHERE teacher_id = $teacher_id ORDER BY award_year DESC");
                if ($honors->num_rows > 0) {
                    echo "<h3 style='color: #28a745; border-bottom: 1px solid #eee; padding-bottom: 8px; margin-top: 0;'>🏆 學術榮譽</h3>";
                    echo "<details class='custom-accordion' open>";
                    echo "  <summary><span class='accordion-icon'>❯</span> 學術榮譽紀錄 (" . $honors->num_rows . ")</summary>";
                    echo "  <div class='accordion-content'><ol>";
                    while($h = $honors->fetch_assoc()) {
                        echo "      <li><strong>{$h['award_year']}</strong> / " . htmlspecialchars($h['honor_name']) . " / " . htmlspecialchars($h['awarding_body']) . "</li>";
                    }
                    echo "  </ol></div></details>";
                    echo "<div style='margin-bottom: 30px;'></div>";
                }

                // 論文與計畫
                $pubs = $conn->query("SELECT * FROM Publications WHERE teacher_id = $teacher_id ORDER BY work_type, publish_year DESC");
                if ($pubs->num_rows > 0) {
                    echo "<h3 style='color: #6f42c1; border-bottom: 1px solid #eee; padding-bottom: 8px;'>📑 論文與參與計畫</h3>";
                    $grouped_pubs = [];
                    while($p = $pubs->fetch_assoc()) {
                        $grouped_pubs[$p['work_type']][] = $p;
                    }
                    
                    $is_first = true;
                    foreach($grouped_pubs as $type => $items) {
                        $count = count($items);
                        $open_attr = $is_first ? "open" : ""; 
                        echo "<details class='custom-accordion' {$open_attr}>";
                        echo "  <summary><span class='accordion-icon'>❯</span> " . htmlspecialchars($type) . " ({$count})</summary>";
                        echo "  <div class='accordion-content'><ol>";
                        foreach($items as $item) {
                            $authors_str = !empty($item['authors']) ? " / 作者：" . htmlspecialchars($item['authors']) : "";
                            echo "      <li>" . htmlspecialchars($item['publish_year']) . " / " . htmlspecialchars($item['title']) . $authors_str . "</li>";
                        }
                        echo "  </ol></div></details>";
                        $is_first = false;
                    }
                }
                
                if ($honors->num_rows == 0 && $pubs->num_rows == 0) {
                    echo "<div style='color:#999; padding:20px; text-align:center; border:1px dashed #ccc; border-radius:8px;'>目前尚無榮譽與著作紀錄。</div>";
                }
                ?>
            </div>
        </div>

    <?php else: ?>
        <div>
            <?php
            $courses_res = $conn->query("SELECT * FROM Courses WHERE teacher_id = $teacher_id ORDER BY course_code");
            $course_list = [];
            
            $timetable = [];
            for ($d = 1; $d <= 5; $d++) {
                for ($p = 1; $p <= 14; $p++) {
                    $timetable[$d][$p] = null;
                }
            }
            $day_map = ['一'=>1, '二'=>2, '三'=>3, '四'=>4, '五'=>5];
            
            if ($courses_res && $courses_res->num_rows > 0) {
                while($c = $courses_res->fetch_assoc()) {
                    $course_list[] = $c;
                    $sch = trim($c['schedule']);
                    if (!empty($sch) && strpos($sch, ' ') !== false) {
                        $parts = explode(' ', $sch);
                        if (count($parts) >= 2) {
                            $d_num = $day_map[$parts[0]] ?? 0;
                            $periods = explode(',', $parts[1]);
                            foreach($periods as $p) {
                                $p = trim($p);
                                if ($d_num && $d_num <= 5 && is_numeric($p) && $p >= 1 && $p <= 14) {
                                    $timetable[$d_num][$p] = [
                                        'name' => $c['course_name'],
                                        'room' => $c['room']
                                    ];
                                }
                            }
                        }
                    }
                }
                
                $days_label = [1=>'星期一', 2=>'星期二', 3=>'星期三', 4=>'星期四', 5=>'星期五'];
                echo "<div style='overflow-x: auto; margin-bottom: 40px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);'>";
                echo "<table style='width: 100%; min-width: 600px; border-collapse: collapse; text-align: center; font-size: 0.95em;'>";
                echo "<tr style='background: #343a40; color: #fff;'>";
                echo "<th style='padding: 12px; border: 1px solid #454d55; width: 60px;'>節次</th>";
                foreach ($days_label as $d_label) echo "<th style='padding: 12px; border: 1px solid #454d55; width: 18%;'>$d_label</th>";
                echo "</tr>";
                
                for ($i = 1; $i <= 14; $i++) {
                    $bg = ($i % 2 == 0) ? '#f8f9fa' : '#ffffff';
                    echo "<tr style='background: {$bg};'>";
                    echo "<td style='padding: 10px; border: 1px solid #dee2e6; font-weight: bold; color: #495057;'>第 {$i} 節</td>";
                    
                    for ($d = 1; $d <= 5; $d++) {
                        echo "<td style='padding: 10px; border: 1px solid #dee2e6; vertical-align: top;'>";
                        $cell = $timetable[$d][$i];
                        if ($cell) {
                            echo "<div style='background: #e8f4fd; border-left: 4px solid #28a745; padding: 8px; border-radius: 4px; text-align: left; font-size: 0.95em; box-shadow: 0 1px 3px rgba(0,0,0,0.05);'>";
                            echo "<strong style='color: #1976d2; display: block; margin-bottom: 4px;'>" . htmlspecialchars($cell['name']) . "</strong>";
                            echo "<span style='color: #666; font-size: 0.85em;'>📍 " . htmlspecialchars($cell['room'] ?? '未定') . "</span>";
                            echo "</div>";
                        }
                        echo "</td>";
                    }
                    echo "</tr>";
                }
                echo "</table></div>";

                echo "<h4 style='color: #17a2b8; margin-bottom: 15px; font-size: 1.3em;'>📘 授課大綱查詢</h4>";
                echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 15px;'>";
                foreach ($course_list as $c) {
                    echo "<div style='border: 1px solid #e0e0e0; border-left: 4px solid #17a2b8; border-radius: 6px; padding: 18px; background: #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.02);'>";
                    echo "<div style='display: flex; justify-content: space-between; align-items: center;'>";
                    echo "  <div>";
                    echo "      <h4 style='margin: 0 0 5px 0; color: #2c3e50; font-size: 1.2em;'>" . htmlspecialchars($c['course_code']) . " - " . htmlspecialchars($c['course_name']) . "</h4>";
                    echo "      <span style='font-size: 0.9em; color: #666;'>修課人數上限: {$c['capacity']} 人</span>";
                    echo "  </div>";
                    echo "  <a href='index.php?page=syllabus_detail&id={$c['course_id']}' style='background: #17a2b8; color: #fff; padding: 8px 18px; border-radius: 20px; text-decoration: none; font-size: 0.9em; transition: 0.2s;' onmouseover=\"this.style.background='#138496'\" onmouseout=\"this.style.background='#17a2b8'\">檢視大綱與配分 ➜</a>";
                    echo "</div>";
                    echo "</div>";
                }
                echo "</div>";

            } else {
                echo "<div style='background: #f8f9fa; padding: 40px; border-radius: 8px; border: 1px dashed #ccc; text-align: center; color: #777;'>";
                echo "  <div style='font-size: 3.5em; margin-bottom: 15px;'>☕</div>";
                echo "  <div style='font-size: 1.2em;'>本學期暫無排定課程。</div>";
                echo "</div>";
            }
            ?>
        </div>
    <?php endif; ?>
</div>
<?php } ?>