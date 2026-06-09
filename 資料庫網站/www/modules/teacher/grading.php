<div class="card">
    <h2>💯 班級成績批次登錄與加權</h2>
    
    <?php
    if(!isset($_SESSION['teacher_id'])) {
        echo "<p style='color:red;'>您不是教師，無法使用此功能。</p>";
    } else {
        $teacher_id = $_SESSION['teacher_id'];
        
        // 取得當前選擇的課程 ID (支援下拉選單 onchange 或 儲存送出的 POST)
        $selected_course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : 0;
        
        // --- 處理儲存成績與權重 ---
        if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_grades'])) {
            $w_assign = intval($_POST['weight_assignment']);
            $w_mid = intval($_POST['weight_midterm']);
            $w_fin = intval($_POST['weight_final']);
            
            // 防呆機制：確保權重總和為 100
            if (($w_assign + $w_mid + $w_fin) == 100) {
                
                // 1. 更新這堂課專屬的權重設定
                $upd_course = $conn->prepare("UPDATE Courses SET weight_assignment=?, weight_midterm=?, weight_final=? WHERE course_id=?");
                $upd_course->bind_param("iiii", $w_assign, $w_mid, $w_fin, $selected_course_id);
                $upd_course->execute();
                
                // 2. 批次更新所有學生的成績
                if(isset($_POST['grades']) && is_array($_POST['grades'])) {
                    foreach($_POST['grades'] as $eid => $scores) {
                        $s_a = $scores['assign'] !== '' ? floatval($scores['assign']) : null;
                        $s_m = $scores['mid'] !== '' ? floatval($scores['mid']) : null;
                        $s_f = $scores['fin'] !== '' ? floatval($scores['fin']) : null;
                        
                        // 依照最新權重計算總成績
                        $total = null;
                        if($s_a !== null || $s_m !== null || $s_f !== null) {
                            $total = 0;
                            if($s_a !== null) $total += $s_a * ($w_assign / 100);
                            if($s_m !== null) $total += $s_m * ($w_mid / 100);
                            if($s_f !== null) $total += $s_f * ($w_fin / 100);
                        }
                        
                        $upd_grade = $conn->prepare("UPDATE Enrollments SET assignment_scores=?, midterm_score=?, final_score=?, total_score=? WHERE enrollment_id=?");
                        $upd_grade->bind_param("ddddi", $s_a, $s_m, $s_f, $total, $eid);
                        $upd_grade->execute();
                    }
                }
                echo "<div class='card' style='background:#d4edda; border-left:4px solid #28a745; margin-bottom:15px;'><strong>✓ 成功：</strong>成績已依據設定權重保存完畢！</div>";
            } else {
                echo "<div class='card' style='background:#f8d7da; border-left:4px solid #dc3545; margin-bottom:15px;'><strong>✗ 錯誤：</strong>加權總和必須等於 100%！目前總和為 " . ($w_assign + $w_mid + $w_fin) . "%，儲存失敗。</div>";
            }
        }

        // --- 顯示課程選擇下拉選單 ---
        $courses = $conn->query("SELECT course_id, course_code, course_name, weight_assignment, weight_midterm, weight_final FROM Courses WHERE teacher_id = $teacher_id AND semester = '113-1'");
        
        echo "<form method='POST' style='background:#f4f6f9; padding:15px; border-radius:5px; margin-bottom:20px; display:flex; align-items:center; gap:10px;'>";
        echo "<label>選擇課程：</label>";
        echo "<select name='course_id' onchange='this.form.submit()' style='padding:5px;'>";
        echo "<option value='0'>請選擇要評分的課程...</option>";
        
        $current_weights = ['a' => 30, 'm' => 30, 'f' => 40]; // 預設權重
        
        while($c = $courses->fetch_assoc()) {
            $selected = ($c['course_id'] == $selected_course_id) ? 'selected' : '';
            echo "<option value='{$c['course_id']}' $selected>{$c['course_code']} - " . htmlspecialchars($c['course_name']) . "</option>";
            
            // 抓出這堂課目前的權重
            if ($c['course_id'] == $selected_course_id) {
                $current_weights['a'] = $c['weight_assignment'];
                $current_weights['m'] = $c['weight_midterm'];
                $current_weights['f'] = $c['weight_final'];
            }
        }
        echo "</select>";
        echo "<noscript><button type='submit' class='btn' style='background:#007bff;'>載入名單</button></noscript>";
        echo "</form>";

        // --- 顯示名單與成績輸入框 ---
        if ($selected_course_id > 0) {
            echo "<form method='POST'>";
            echo "<input type='hidden' name='course_id' value='$selected_course_id'>";
            echo "<input type='hidden' name='save_grades' value='1'>";
            
            // 權重設定區 (動態帶入資料庫中的最新值)
            echo "<div style='background:#e8f4fd; padding:15px; border-radius:5px; border:1px solid #b8daff; margin-bottom:15px; display:flex; align-items:center; gap:15px;'>";
            echo "<strong style='color:#0056b3;'>⚙️ 總成績加權設定：</strong>";
            echo "<span>平時 (%)：<input type='number' name='weight_assignment' value='{$current_weights['a']}' style='width:60px; padding:3px;' required></span>";
            echo "<span>期中 (%)：<input type='number' name='weight_midterm' value='{$current_weights['m']}' style='width:60px; padding:3px;' required></span>";
            echo "<span>期末 (%)：<input type='number' name='weight_final' value='{$current_weights['f']}' style='width:60px; padding:3px;' required></span>";
            echo "</div>";

            // 取得選課名單
            $enrollments = $conn->query("
                SELECT e.*, s.name as student_name, s.student_id as student_no 
                FROM Enrollments e 
                JOIN Students s ON e.student_id = s.student_id 
                WHERE e.course_id = $selected_course_id 
                ORDER BY s.student_id
            ");

            if ($enrollments->num_rows > 0) {
                echo "<table style='width:100%; border-collapse:collapse; margin-bottom:20px; text-align:left;'>";
                echo "<tr style='background:#f4f6f9; border-bottom:2px solid #ddd;'><th style='padding:10px;'>學號</th><th style='padding:10px;'>姓名</th><th style='padding:10px;'>平時成績</th><th style='padding:10px;'>期中成績</th><th style='padding:10px;'>期末成績</th><th style='padding:10px;'>當前總成績</th></tr>";
                
                while($e = $enrollments->fetch_assoc()) {
                    $eid = $e['enrollment_id'];
                    $total = $e['total_score'] !== null ? round($e['total_score'], 2) : '0';
                    $total_color = $total >= 60 ? '#007bff' : '#dc3545';
                    
                    echo "<tr style='border-bottom:1px solid #eee;'>";
                    echo "<td style='padding:10px;'>" . htmlspecialchars($e['student_no']) . "</td>";
                    echo "<td style='padding:10px;'>" . htmlspecialchars($e['student_name']) . "</td>";
                    
                    // 輸入框
                    echo "<td style='padding:10px;'><input type='number' step='0.01' name='grades[$eid][assign]' value='{$e['assignment_scores']}' style='width:80px; padding:5px;'></td>";
                    echo "<td style='padding:10px;'><input type='number' step='0.01' name='grades[$eid][mid]' value='{$e['midterm_score']}' style='width:80px; padding:5px;'></td>";
                    echo "<td style='padding:10px;'><input type='number' step='0.01' name='grades[$eid][fin]' value='{$e['final_score']}' style='width:80px; padding:5px;'></td>";
                    
                    // 總成績顯示
                    echo "<td style='padding:10px; font-weight:bold; color:$total_color;'>" . $total . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
                echo "<button type='submit' class='btn' style='background:#28a745; padding:10px 20px; font-size:1em;'>💾 儲存並計算加權總成績</button>";
            } else {
                echo "<p style='color:#999; padding:20px; background:#f9f9f9; text-align:center;'>目前尚無學生選修此課程。</p>";
            }
            echo "</form>";
        }
    }
    ?>
</div>