<div style="max-width: 1200px; margin: 0 auto; padding: 20px;">
    <div style="border-bottom: 2px solid #1976d2; margin-bottom: 30px; padding-bottom: 10px; display: flex; justify-content: space-between; align-items: flex-end;">
        <h2 style="margin: 0; color: #2c3e50; font-size: 2em;">👨‍🏫 師資陣容</h2>
        <span style="color: #666; font-weight: 500;">Faculty Members</span>
    </div>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(450px, 1fr)); gap: 40px;">
    <?php
    $teachers = $conn->query("SELECT teacher_id, name, title, email, phone, avatar_path, lab_info FROM Teachers ORDER BY teacher_id");
    
    while($teacher = $teachers->fetch_assoc()) {
        echo "<div style='display: flex; gap: 25px; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.03); transition: transform 0.2s, box-shadow 0.2s;' onmouseover=\"this.style.boxShadow='0 5px 15px rgba(0,0,0,0.08)'; this.style.transform='translateY(-2px)';\" onmouseout=\"this.style.boxShadow='0 2px 10px rgba(0,0,0,0.03)'; this.style.transform='translateY(0)';\">";
        
        // 左側：相片
        echo "<div style='flex-shrink: 0; width: 150px; height: 190px;'>";
        if (!empty($teacher['avatar_path']) && file_exists($teacher['avatar_path'])) {
            echo "  <img src='" . htmlspecialchars($teacher['avatar_path']) . "' alt='" . htmlspecialchars($teacher['name']) . "' style='width: 100%; height: 100%; object-fit: cover; border-radius: 4px; border: 1px solid #eaeaea;'>";
        } else {
            $first_char = function_exists('mb_substr') ? mb_substr($teacher['name'], 0, 1, 'UTF-8') : '👤';
            echo "  <div style='width: 100%; height: 100%; border-radius: 4px; background: linear-gradient(135deg, #f8f9fa, #e9ecef); color: #adb5bd; display: flex; align-items: center; justify-content: center; font-size: 4rem; font-weight: bold; border: 1px solid #eaeaea; user-select: none;'>{$first_char}</div>";
        }
        echo "</div>";

        // 右側：文字資訊
        echo "<div style='flex-grow: 1; display: flex; flex-direction: column; justify-content: flex-start;'>";
        echo "  <h3 style='margin: 0 0 10px 0; color: #2c3e50; font-size: 1.6em; letter-spacing: 1px;'>" . htmlspecialchars($teacher['name']) . "</h3>";
        
        echo "  <div style='font-size: 0.95em; color: #444; line-height: 1.8;'>";
        echo "      <div style='margin-bottom: 5px; font-weight: 600; color: #333;'>" . htmlspecialchars($teacher['title'] ?? '') . "</div>";
        
        $phone_str = !empty($teacher['phone']) ? htmlspecialchars($teacher['phone']) : '(未設定)';
        echo "      <div><strong>分機：</strong> {$phone_str}</div>";
        
        $email_str = !empty($teacher['email']) ? htmlspecialchars($teacher['email']) : '(未設定)';
        echo "      <div style='word-break: break-all;'><strong>信箱：</strong> {$email_str}</div>";
        
        $research = !empty($teacher['lab_info']) ? htmlspecialchars(strip_tags($teacher['lab_info'])) : '尚未提供';
        echo "      <div style='margin-top: 5px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; text-overflow: ellipsis;' title='{$research}'><strong>研究專長：</strong> {$research}</div>";
        echo "  </div>";
        
        // ✨ 修改點：加入 &view=profile 與 &view=schedule 參數
        echo "  <div style='margin-top: auto; padding-top: 15px; display: flex; gap: 10px;'>";
        echo "      <a href='index.php?page=teacher_detail&id=" . $teacher['teacher_id'] . "&view=profile' style='flex: 1; text-align: center; display: inline-block; padding: 6px 0; border: 1px solid #17a2b8; color: #17a2b8; border-radius: 20px; text-decoration: none; font-size: 0.9em; transition: 0.2s;' onmouseover=\"this.style.background='#17a2b8'; this.style.color='#fff';\" onmouseout=\"this.style.background='transparent'; this.style.color='#17a2b8';\">查看簡歷 ➜</a>";
        echo "      <a href='index.php?page=teacher_detail&id=" . $teacher['teacher_id'] . "&view=schedule' style='flex: 1; text-align: center; display: inline-block; padding: 6px 0; border: 1px solid #28a745; color: #28a745; border-radius: 20px; text-decoration: none; font-size: 0.9em; transition: 0.2s;' onmouseover=\"this.style.background='#28a745'; this.style.color='#fff';\" onmouseout=\"this.style.background='transparent'; this.style.color='#28a745';\">📅 課表時間</a>";
        echo "  </div>";
        
        echo "</div>"; 
        echo "</div>"; 
    }
    ?>
    </div>
</div>