<div class="card">
    <h2>📁 系統檔案中心</h2>
    <p>上傳供全系師生下載之申請表單、規章法規或公共檔案，並可標註說明。</p>
    
    <?php
    if($_SESSION['role'] != 'Admin') {
        echo "<p style='color:red;'>無權限。</p>";
    } else {
        // 處理上傳與寫入資料庫
        if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file']) && isset($_POST['upload'])) {
            $upload_dir = 'uploads/';
            if(!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            
            if ($_FILES['file']['error'] === UPLOAD_ERR_OK) {
                $filename = basename($_FILES['file']['name']);
                $filepath = $upload_dir . $filename;
                $remark = trim($_POST['remark'] ?? '');
                
                if(move_uploaded_file($_FILES['file']['tmp_name'], $filepath)) {
                    $stmt = $conn->prepare("INSERT INTO SystemFiles (filename, remark) VALUES (?, ?)");
                    $stmt->bind_param("ss", $filename, $remark);
                    $stmt->execute();
                    echo "<div class='card' style='background:#d4edda; border-left:4px solid #28a745;'><strong>✓ 成功：</strong>檔案與備註已上傳</div>";
                } else {
                    echo "<div class='card' style='background:#f8d7da; border-left:4px solid #dc3545;'><strong>✗ 錯誤：</strong>檔案寫入失敗。</div>";
                }
            } else {
                echo "<div class='card' style='background:#f8d7da; border-left:4px solid #dc3545;'><strong>✗ 上傳失敗 (代碼 {$_FILES['file']['error']})</strong></div>";
            }
        }
        
        // 處理刪除 (同時刪除實體檔案與資料庫紀錄)
        if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_file_id'])) {
            $file_id = intval($_POST['delete_file_id']);
            
            $get_file = $conn->prepare("SELECT filename FROM SystemFiles WHERE file_id = ?");
            $get_file->bind_param("i", $file_id);
            $get_file->execute();
            $res = $get_file->get_result();
            if($row = $res->fetch_assoc()) {
                $filepath = 'uploads/' . $row['filename'];
                if(file_exists($filepath)) unlink($filepath); // 刪除資料夾裡的檔案
                
                $conn->query("DELETE FROM SystemFiles WHERE file_id = $file_id"); // 刪除資料庫紀錄
                echo "<div class='card' style='background:#d4edda; border-left:4px solid #28a745;'><strong>✓ 成功：</strong>檔案已刪除</div>";
            }
        }
        
        // 上傳表單
        echo "<form method='POST' enctype='multipart/form-data' style='background:#f4f6f9; padding: 15px; border-radius: 5px; margin-bottom: 20px; display:flex; flex-direction:column; gap:10px;'>";
        echo "<div><label>選擇檔案：</label><input type='file' name='file' required style='margin:0; background:#fff; padding:8px;'></div>";
        echo "<div><label>檔案備註 / 說明：</label><input type='text' name='remark' placeholder='例如：113學年度最新碩士班畢業門檻規定...' style='margin:0;' required></div>";
        echo "<div><button type='submit' name='upload' class='btn' style='background:#28a745;'>開始上傳</button></div>";
        echo "</form>";
        
        // 檔案列表 (從資料庫讀取)
        echo "<table style='width:100%; border-collapse:collapse;'><tr style='background:#f4f6f9;'><th style='padding:10px;'>檔名</th><th style='padding:10px;'>檔案備註</th><th style='padding:10px;'>上傳時間</th><th style='padding:10px;'>操作</th></tr>";
        $files = $conn->query("SELECT * FROM SystemFiles ORDER BY uploaded_at DESC");
        while($f = $files->fetch_assoc()) {
            $filepath = 'uploads/' . $f['filename'];
            echo "<tr style='border-bottom:1px solid #eee;'>";
            echo "<td style='padding:10px; word-break:break-all;'>" . htmlspecialchars($f['filename']) . "</td>";
            echo "<td style='padding:10px; color:#007bff; font-weight:bold;'>" . htmlspecialchars($f['remark']) . "</td>";
            echo "<td style='padding:10px; color:#888; font-size:0.9em;'>" . date('Y-m-d H:i', strtotime($f['uploaded_at'])) . "</td>";
            echo "<td style='padding:10px; display:flex; gap:5px;'>";
            echo "<a href='$filepath' class='btn' style='background:#6c757d; text-decoration:none;' download>下載</a>";
            echo "<form method='POST' style='margin:0;'><input type='hidden' name='delete_file_id' value='{$f['file_id']}'><button type='submit' class='btn' style='background:#dc3545;' onclick='return confirm(\"確定刪除此檔案？\");'>刪除</button></form>";
            echo "</td></tr>";
        }
        echo "</table>";
    }
    ?>
</div>