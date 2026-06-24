<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>

<div class="card">
    <h2>📝 個人簡歷與學術維護</h2>
    <p>更新您的基本聯絡資訊，並逐筆管理您的學術榮譽與著作計畫。</p>
    
    <?php
    if(!isset($_SESSION['teacher_id'])) {
        echo "<p style='color:red;'>您不是教師，無法使用此功能。</p>";
    } else {
        $teacher_id = $_SESSION['teacher_id'];
        
        $profile = $conn->prepare("SELECT * FROM Teachers WHERE teacher_id = ?");
        $profile->bind_param("i", $teacher_id);
        $profile->execute();
        $p = $profile->get_result()->fetch_assoc();
        
        // --- 處理 1：基本資料與頭像更新 ---
        if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
            $phone = trim($_POST['phone'] ?? '');
            $extension = trim($_POST['extension'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $office_hours = trim($_POST['office_hours'] ?? '');
            $lab_name = trim($_POST['lab_name'] ?? '');
            $lab_info = trim($_POST['lab_info'] ?? '');
            $teaching_exp = trim($_POST['teaching_experience'] ?? '');
            $external_exp = trim($_POST['external_experience'] ?? ''); 
            
            $avatar_path = $p['avatar_path'];
            $upload_msg = "";
            $error_msg = "";

            // ✨ 新增：電話與分機格式驗證防呆
            // 電話：支援 09xx-xxxxxx 或市話 02-xxxxxxxx
            if ($phone != '' && !preg_match('/^(09\d{2}-?\d{6}|0\d{1,2}-?\d{6,8})$/', $phone)) {
                $error_msg .= "⚠️ 聯絡電話格式不正確 (請使用如 0912-345678 或 02-23456789)。<br>";
            }
            // 分機：限制必定為 # 開頭加 4 碼數字
            if ($extension != '' && !preg_match('/^#[0-9]{4}$/', $extension)) {
                $error_msg .= "⚠️ 分機號碼格式不正確 (必須為 # 加上 4 碼數字，如 #1234)。<br>";
            }

            if ($error_msg != "") {
                echo "<div class='card' style='background:#f8d7da; border-left:4px solid #dc3545; line-height:1.6;'>{$error_msg}</div>";
            } else {
                // 處理前端剪裁完傳回的 Base64 圖片資料
                if (!empty($_POST['cropped_avatar'])) {
                    $base64_data = $_POST['cropped_avatar'];
                    if (preg_match('/^data:image\/(\w+);base64,/', $base64_data, $type)) {
                        $image_type = strtolower($type[1]);
                        $clean_data = substr($base64_data, strpos($base64_data, ',') + 1);
                        $decoded_image = base64_decode($clean_data);

                        if ($decoded_image !== false) {
                            $upload_dir = 'uploads/avatars/';
                            if (!is_dir($upload_dir)) { @mkdir($upload_dir, 0777, true); }
                            if (!empty($p['avatar_path']) && file_exists($p['avatar_path'])) { @unlink($p['avatar_path']); }
                            
                            $new_filename = uniqid('avatar_') . '.' . $image_type;
                            $avatar_path = $upload_dir . $new_filename;
                            file_put_contents($avatar_path, $decoded_image);
                            $upload_msg = "（頭像剪裁設定成功！）";
                        }
                    }
                }
                
                // 動態判斷系統是否已建立 extension 欄位，避免剛更新時報錯
                $check_col = $conn->query("SHOW COLUMNS FROM Teachers LIKE 'extension'");
                if ($check_col && $check_col->num_rows > 0) {
                    $update = $conn->prepare("UPDATE Teachers SET phone=?, extension=?, email=?, office_hours=?, lab_name=?, lab_info=?, teaching_experience=?, external_experience=?, avatar_path=? WHERE teacher_id=?");
                    $update->bind_param("sssssssssi", $phone, $extension, $email, $office_hours, $lab_name, $lab_info, $teaching_exp, $external_exp, $avatar_path, $teacher_id);
                } else {
                    $update = $conn->prepare("UPDATE Teachers SET phone=?, email=?, office_hours=?, lab_name=?, lab_info=?, teaching_experience=?, external_experience=?, avatar_path=? WHERE teacher_id=?");
                    $update->bind_param("ssssssssi", $phone, $email, $office_hours, $lab_name, $lab_info, $teaching_exp, $external_exp, $avatar_path, $teacher_id);
                }
                
                if ($update->execute()) {
                    @$conn->query("INSERT INTO TeacherLogs (teacher_id, action_type, description) VALUES ($teacher_id, '基本資料更新', '更新了聯絡資訊與經歷。')");
                    echo "<div class='card' style='background:#d4edda; border-left:4px solid #28a745;'><strong>✓ 成功：</strong>個人資料已更新完成！ {$upload_msg}</div>";
                }
                $profile->execute();
                $p = $profile->get_result()->fetch_assoc();
            }
        }

        // --- 處理 2：新增/刪除學術榮譽 ---
        if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_honor'])) {
            $h_name = trim($_POST['honor_name']);
            $h_body = trim($_POST['awarding_body']);
            $h_year = intval($_POST['award_year']);
            if($h_name) {
                $ins = $conn->prepare("INSERT INTO AcademicHonors (teacher_id, honor_name, awarding_body, award_year) VALUES (?, ?, ?, ?)");
                $ins->bind_param("issi", $teacher_id, $h_name, $h_body, $h_year);
                $ins->execute();
            }
        }
        if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_honor'])) {
            $del_id = intval($_POST['honor_id']);
            $conn->query("DELETE FROM AcademicHonors WHERE honor_id = $del_id AND teacher_id = $teacher_id");
        }

        // --- 處理 3：新增/刪除著作與計畫 ---
        if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_pub'])) {
            $p_type = trim($_POST['work_type']);
            $p_title = trim($_POST['title']);
            $other_authors = trim($_POST['other_authors']);
            $p_year = trim($_POST['publish_year']);
            
            if($p_title && $p_type) {
                $ins = $conn->prepare("INSERT INTO Publications (teacher_id, work_type, title, authors, publish_year) VALUES (?, ?, ?, ?, ?)");
                $ins->bind_param("issss", $teacher_id, $p_type, $p_title, $other_authors, $p_year);
                $ins->execute();
            }
        }
        if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_pub'])) {
            $del_id = intval($_POST['work_id']);
            $conn->query("DELETE FROM Publications WHERE work_id = $del_id AND teacher_id = $teacher_id");
        }
        
        echo "<hr style='margin:20px 0;'>";
        
        // [區塊 1] 基本資料與頭像表單
        echo "<h3 style='color:#007bff;'>👤 基本資料與經歷</h3>";
        echo "<form id='profileForm' method='POST' style='background:#f4f6f9; padding:20px; border-radius:5px; display: grid; grid-template-columns: 1fr 1fr; gap: 15px;'>";
        
        echo "<div style='grid-column: span 2; background: #fff; padding: 20px; border-radius: 6px; border: 1px dashed #007bff;'>";
        echo "  <div style='display: flex; align-items: center; gap: 30px; flex-wrap: wrap;'>";
        if (!empty($p['avatar_path']) && file_exists($p['avatar_path'])) {
            echo "      <img src='{$p['avatar_path']}' style='width: 90px; height: 90px; border-radius: 50%; object-fit: cover; border: 2px solid #007bff;'>";
        } else {
            $f_char = function_exists('mb_substr') ? mb_substr($p['name'], 0, 1, 'UTF-8') : '👤';
            echo "      <div style='width: 90px; height: 90px; border-radius: 50%; background: linear-gradient(135deg, #17a2b8, #007bff); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: bold; border: 2px solid #007bff;'>{$f_char}</div>";
        }
        echo "      <div style='flex: 1; min-width: 250px;'>";
        echo "          <label style='font-weight: bold; display: block; margin-bottom: 5px;'>📷 上傳並調整個人頭像：</label>";
        echo "          <input type='file' id='avatarInput' accept='image/jpeg, image/png, image/gif'>";
        echo "          <small style='color: #666; display: block; margin-top: 5px;'>選擇檔案後，可在下方即時調整大小與上下左右位置</small>";
        echo "      </div>";
        echo "  </div>";
        
        echo "  <div id='cropperWrapper' style='display: none; margin-top: 20px; border-top: 1px dashed #ddd; padding-top: 15px;'>";
        echo "      <p style='color: #d35400; font-weight: bold; font-size: 0.9em; margin-bottom: 10px;'>🖱️ 調整教學：滑鼠左鍵拖曳可移動位置 ｜ 滾輪或雙指滑動可縮放大小</p>";
        echo "      <div style='max-width: 400px; max-height: 300px; background: #f0f0f0; overflow: hidden;'>";
        echo "          <img id='imageWorkspace' style='max-width: 100%; display: block;'>";
        echo "      </div>";
        echo "  </div>";
        echo "  <input type='hidden' name='cropped_avatar' id='croppedAvatarData'>";
        echo "</div>";

        echo "<div><label>姓名：</label><div style='padding:8px; background:#e9ecef; border-radius:4px; color:#495057; border:1px solid #ced4da;'>" . htmlspecialchars($p['name'] ?? '') . " <span style='font-size:0.8em; color:#999;'>(修改請洽系辦)</span></div></div>";
        echo "<div><label>職稱：</label><div style='padding:8px; background:#e9ecef; border-radius:4px; color:#495057; border:1px solid #ced4da;'>" . htmlspecialchars($p['title'] ?? '') . " <span style='font-size:0.8em; color:#999;'>(修改請洽系辦)</span></div></div>";
        
        // ✨ 新增欄位區塊：將聯絡電話、分機號碼對稱排版
        echo "<div><label>聯絡電話：</label><input type='text' name='phone' value='" . htmlspecialchars($p['phone'] ?? '') . "' placeholder='例: 0912-345678 或 02-23456789'></div>";
        $ext_val = isset($p['extension']) ? htmlspecialchars($p['extension']) : '';
        echo "<div><label>分機號碼：</label><input type='text' name='extension' value='{$ext_val}' placeholder='例: #1234'></div>";
        
        echo "<div><label>電子信箱：</label><input type='email' name='email' value='" . htmlspecialchars($p['email'] ?? '') . "'></div>";
        echo "<div><label>請益時間 (Office Hours)：</label><input type='text' name='office_hours' value='" . htmlspecialchars($p['office_hours'] ?? '') . "'></div>";
        
        echo "<div style='grid-column: span 2;'><label>實驗室名稱：</label><input type='text' name='lab_name' value='" . htmlspecialchars($p['lab_name'] ?? '') . "'></div>";
        echo "<div style='grid-column: span 2;'><label>實驗室簡介與研究方向：</label><textarea name='lab_info' rows='2'>" . htmlspecialchars($p['lab_info'] ?? '') . "</textarea></div>";
        echo "<div style='grid-column: span 2;'><label>🏫 校內教學經歷：</label><textarea name='teaching_experience' rows='3'>" . htmlspecialchars($p['teaching_experience'] ?? '') . "</textarea></div>";
        echo "<div style='grid-column: span 2;'><label>🏢 校外經歷：</label><textarea name='external_experience' rows='3'>" . htmlspecialchars($p['external_experience'] ?? '') . "</textarea></div>";
        
        echo "<div style='grid-column: span 2;'><button type='submit' name='update_profile' class='btn' style='background:#007bff;'>💾 儲存基本資料與頭像</button></div>";
        echo "</form>";

        // [區塊 2] 學術榮譽管理
        echo "<h3 style='color:#28a745; margin-top:30px;'>🏆 學術榮譽管理</h3>";
        echo "<div style='background:#f8fff9; padding:20px; border:1px solid #c3e6cb; border-radius:5px;'>";
        echo "<form method='POST' style='display:flex; gap:10px; margin-bottom:15px;'>";
        echo "<input type='text' name='honor_name' placeholder='獎項名稱 (必填)' required style='flex:2; margin:0;'>";
        echo "<input type='text' name='awarding_body' placeholder='頒發機構' style='flex:1; margin:0;'>";
        echo "<input type='number' name='award_year' placeholder='年份(如:2023)' style='width:100px; margin:0;'>";
        echo "<button type='submit' name='add_honor' class='btn' style='background:#28a745;'>➕ 新增榮譽</button>";
        echo "</form>";
        $honors = $conn->query("SELECT * FROM AcademicHonors WHERE teacher_id = $teacher_id ORDER BY award_year DESC");
        echo "<table style='width:100%; border-collapse:collapse;'><tr style='background:#e2eadb;'><th style='padding:8px;'>年份</th><th style='padding:8px;'>獎項名稱</th><th style='padding:8px;'>頒發機構</th><th style='padding:8px; width:80px;'>操作</th></tr>";
        while($h = $honors->fetch_assoc()) {
            echo "<tr style='border-bottom:1px solid #ddd;'><td style='padding:8px;'>" . $h['award_year'] . "</td><td style='padding:8px;'>" . htmlspecialchars($h['honor_name']) . "</td><td style='padding:8px;'>" . htmlspecialchars($h['awarding_body']) . "</td><td style='padding:8px;'><form method='POST' style='margin:0;'><input type='hidden' name='honor_id' value='{$h['honor_id']}'><button type='submit' name='delete_honor' class='btn' style='background:#dc3545; padding:4px 8px; font-size:12px;'>刪除</button></form></td></tr>";
        }
        echo "</table></div>";

        // [區塊 3] 著作與計畫管理
        echo "<h3 style='color:#6f42c1; margin-top:30px;'>📑 論文與參與計畫管理</h3>";
        echo "<div style='background:#f9f8ff; padding:20px; border:1px solid #d6c3e6; border-radius:5px;'>";
        echo "<form method='POST' style='display:flex; flex-wrap:wrap; gap:10px; margin-bottom:15px;'>";
        // ✨ 加入 id='pub_type'
        echo "<select name='work_type' id='pub_type' required style='width:200px; margin:0;'>";
        $types = ['發表期刊論文', '會議論文', '專書及技術報告', '國科會計畫', '產學合作計畫', '校外獎勵及指導學生獲獎', '校內獎勵及指導學生獲獎', '校內外演講', '專書論文', '教材與作品', '其他相關研究'];
        echo "<option value=''>-- 選擇著作/計畫類型 --</option>";
        foreach($types as $t) echo "<option value='$t'>$t</option>";
        echo "</select>";
        // ✨ 加入 id='inp_year'
        echo "<input type='text' name='publish_year' id='inp_year' placeholder='發表時間(如: 2023-05)' style='width:150px; margin:0;'>";
        // ✨ 加入 id='inp_title'
        echo "<input type='text' name='title' id='inp_title' placeholder='標題 / 計畫名稱 (必填)' required style='flex:1; min-width:250px; margin:0;'>";
        // ✨ 加入 id='inp_authors'
        echo "<input type='text' name='other_authors' id='inp_authors' placeholder='其他作者 (不含您自己)' style='flex:1; min-width:200px; margin:0;'>";
        echo "<button type='submit' name='add_pub' class='btn' style='background:#6f42c1;'>➕ 新增紀錄</button>";
        echo "</form>";
        $pubs = $conn->query("SELECT * FROM Publications WHERE teacher_id = $teacher_id ORDER BY work_type, publish_year DESC");
        echo "<table style='width:100%; border-collapse:collapse;'><tr style='background:#e8e2ea;'><th style='padding:8px;'>類型</th><th style='padding:8px;'>時間</th><th style='padding:8px;'>標題</th><th style='padding:8px;'>作者</th><th style='padding:8px; width:80px;'>操作</th></tr>";
        while($p_row = $pubs->fetch_assoc()) {
            $display_authors = htmlspecialchars($p['name']); 
            if (!empty($p_row['authors'])) $display_authors .= ', ' . htmlspecialchars($p_row['authors']);
            echo "<tr style='border-bottom:1px solid #ddd;'><td style='padding:8px;'>" . htmlspecialchars($p_row['work_type']) . "</td><td style='padding:8px;'>" . htmlspecialchars($p_row['publish_year']) . "</td><td style='padding:8px;'>" . htmlspecialchars($p_row['title']) . "</td><td style='padding:8px; color:#007bff; font-weight:500;'>" . $display_authors . "</td><td style='padding:8px;'><form method='POST' style='margin:0;'><input type='hidden' name='work_id' value='{$p_row['work_id']}'><button type='submit' name='delete_pub' class='btn' style='background:#dc3545; padding:4px 8px; font-size:12px;'>刪除</button></form></td></tr>";
        }
        echo "</table></div>";
    }
    ?>
</div>

<script>
let cropperContext = null;
const avatarInput = document.getElementById('avatarInput');
const imageWorkspace = document.getElementById('imageWorkspace');
const cropperWrapper = document.getElementById('cropperWrapper');
const profileForm = document.getElementById('profileForm');

if(avatarInput) {
    avatarInput.addEventListener('change', function(e) {
        const files = e.target.files;
        if (files && files.length > 0) {
            const file = files[0];
            const reader = new FileReader();
            reader.onload = function(event) {
                imageWorkspace.src = event.target.result;
                cropperWrapper.style.display = 'block';
                if (cropperContext) { cropperContext.destroy(); }
                cropperContext = new Cropper(imageWorkspace, { aspectRatio: 1, viewMode: 1, background: false, autoCropArea: 0.9 });
            };
            reader.readAsDataURL(file);
        }
    });
}
if(profileForm) {
    profileForm.addEventListener('submit', function(e) {
        if (cropperContext) {
            const canvas = cropperContext.getCroppedCanvas({ width: 300, height: 300 });
            document.getElementById('croppedAvatarData').value = canvas.toDataURL('image/jpeg', 0.85);
        }
    });
}

const pType = document.getElementById('pub_type');
const iYear = document.getElementById('inp_year');
const iTitle = document.getElementById('inp_title');
const iAuth = document.getElementById('inp_authors');

if(pType && iYear && iTitle && iAuth) {
    pType.addEventListener('change', function() {
        const val = this.value;
        if (val.includes('計畫')) {
            iTitle.placeholder = '📝 計畫名稱 (必填，如：產學研發計畫)';
            iAuth.placeholder = '🤝 擔任角色/合作單位 (如：主持人/科技部)';
            iYear.placeholder = '⏳ 執行期間 (如：112.08-113.07)';
        } else if (val.includes('獎')) {
            iTitle.placeholder = '🏆 獎項或競賽名稱 (必填)';
            iAuth.placeholder = '🎓 獲獎人/指導學生 (如：李大明)';
            iYear.placeholder = '📅 獲獎年度 (如：2024)';
        } else if (val.includes('演講')) {
            iTitle.placeholder = '🎤 演講題目/邀請單位 (必填)';
            iAuth.placeholder = '📌 備註 (如：Keynote Speaker)';
            iYear.placeholder = '📅 演講日期 (如：2023-10-15)';
        } else {
            iTitle.placeholder = '標題 / 計畫名稱 (必填)';
            iAuth.placeholder = '其他作者 (不含您自己)';
            iYear.placeholder = '發表時間(如: 2023-05)';
        }
    });
    // 網頁載入時自動執行一次
    pType.dispatchEvent(new Event('change'));
}
</script>