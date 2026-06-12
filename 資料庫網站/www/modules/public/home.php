<?php
// 判斷是否為管理員
$is_admin = (isset($_SESSION['role']) && $_SESSION['role'] == 'Admin');

// --- 處理管理員發布新消息 (含圖片上傳) ---
if ($is_admin && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_news'])) {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $image_path = null;
    $upload_msg = ""; // 儲存上傳狀態的訊息

    // 處理圖片上傳
    if (isset($_FILES['news_image']) && $_FILES['news_image']['name'] != '') {
        if ($_FILES['news_image']['error'] == 0) {
            $upload_dir = 'uploads/news/';
            
            // 若資料夾不存在則自動建立，並檢查是否成功
            if (!is_dir($upload_dir)) {
                if(!@mkdir($upload_dir, 0777, true)) {
                    $upload_msg = "<div style='color:red; margin-top:10px;'>⚠️ 錯誤：無法建立 {$upload_dir} 資料夾，請手動新增該資料夾並設定寫入權限！</div>";
                }
            }
            
            $file_ext = strtolower(pathinfo($_FILES['news_image']['name'], PATHINFO_EXTENSION));
            $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array($file_ext, $allowed_exts)) {
                $new_filename = uniqid('news_') . '.' . $file_ext;
                $target_file = $upload_dir . $new_filename;
                
                // 嘗試將暫存檔搬移到目標資料夾
                if (move_uploaded_file($_FILES['news_image']['tmp_name'], $target_file)) {
                    $image_path = $target_file;
                    $upload_msg = "<div style='color:green; margin-top:10px;'>✓ 圖片上傳且儲存成功！</div>";
                } else {
                    $upload_msg = "<div style='color:red; margin-top:10px;'>⚠️ 錯誤：無法將圖片移至 {$upload_dir} (通常是資料夾權限不足)。</div>";
                }
            } else {
                $upload_msg = "<div style='color:#ffc107; margin-top:10px;'>⚠️ 警告：圖片格式僅支援 JPG, PNG, GIF。</div>";
            }
        } else {
            // 擷取 PHP 上傳錯誤代碼
            $err_code = $_FILES['news_image']['error'];
            if ($err_code == 1 || $err_code == 2) {
                $upload_msg = "<div style='color:red; margin-top:10px;'>⚠️ 錯誤：圖片檔案太大！超過了伺服器 (php.ini) 預設的 2MB 限制。</div>";
            } else {
                $upload_msg = "<div style='color:red; margin-top:10px;'>⚠️ 錯誤：圖片上傳失敗，未知錯誤代碼：{$err_code}</div>";
            }
        }
    }

    if ($title && $content) {
        $stmt = $conn->prepare("INSERT INTO News (title, content, image_path) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $title, $content, $image_path);
        if ($stmt->execute()) {
            echo "<div class='card' style='background:#d4edda; border-left:4px solid #28a745;'><strong>✓ 成功：</strong>最新消息已發布！{$upload_msg}</div>";
        }
    }
}

// --- 處理管理員刪除消息 ---
if ($is_admin && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_news'])) {
    $del_id = intval($_POST['news_id']);
    $get_img = $conn->query("SELECT image_path FROM News WHERE news_id = $del_id");
    if ($row = $get_img->fetch_assoc()) {
        if (!empty($row['image_path']) && file_exists($row['image_path'])) {
            unlink($row['image_path']); // 刪除實體檔案
        }
    }
    $conn->query("DELETE FROM News WHERE news_id = $del_id");
    echo "<div class='card' style='background:#d4edda; border-left:4px solid #28a745;'><strong>✓ 成功：</strong>消息與圖片已刪除！</div>";
}
?>

<div class="card" style="background: linear-gradient(to right, #f8f9fa, #e9ecef); border-left: 5px solid #2c3e50;">
    <h2 style='color: #2c3e50; margin-top:0;'>🏠 歡迎瀏覽資工系入口網</h2>
    <p style='color: #555; line-height: 1.6; margin-bottom:0;'>請從左側選單選擇功能，或點擊右上角登入系統。</p>
</div>

<?php if ($is_admin): ?>
<div class="card" style="border: 1px solid #17a2b8; border-top: 4px solid #17a2b8; background: #f4fcfe;">
    <h4 style="margin-top:0; color:#17a2b8;">📢 發布最新消息</h4>
    <form method="POST" enctype="multipart/form-data" style="display:flex; flex-direction:column; gap:12px;">
        <input type="text" name="title" placeholder="消息標題 (必填)" required style="padding:10px; border:1px solid #ccc; border-radius:4px; font-size:1em;">
        <textarea name="content" rows="4" placeholder="消息內容 (必填)" required style="padding:10px; border:1px solid #ccc; border-radius:4px; resize:vertical; font-size:1em; font-family:inherit;"></textarea>
        <div style="background: #fff; padding: 10px; border: 1px dashed #17a2b8; border-radius: 4px;">
            <label style="color:#555; font-weight:bold; cursor:pointer;">
                🖼️ 上傳附圖 (選填，建議 2MB 以下)：<br>
                <input type="file" name="news_image" accept="image/jpeg, image/png, image/gif" style="margin-top:8px;">
            </label>
        </div>
        <button type="submit" name="add_news" class="btn" style="background:#17a2b8; align-self:flex-start; padding:8px 25px; font-size:1.05em;">📤 發布消息</button>
    </form>
</div>
<?php endif; ?>

<div style="margin-top: 30px;">
    <h3 style="color: #2c3e50; border-bottom: 2px solid #343a40; padding-bottom: 10px;">📰 系所最新消息</h3>
    
    <?php
    $news_list = $conn->query("SELECT * FROM News ORDER BY created_at DESC");
    
    if ($news_list && $news_list->num_rows > 0) {
        echo "<div style='display:flex; flex-direction:column; gap:20px; margin-top:20px;'>";
        while ($news = $news_list->fetch_assoc()) {
            $date = date('Y-m-d H:i', strtotime($news['created_at']));
            
            echo "<div class='card' style='margin-bottom:0; box-shadow:0 3px 10px rgba(0,0,0,0.08); border-left: 4px solid #007bff; overflow:hidden;'>";
            
            echo "<div style='display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:15px; border-bottom:1px solid #eee; padding-bottom:10px;'>";
            echo "<div>";
            echo "<h4 style='margin:0 0 8px 0; color:#0056b3; font-size:1.3em;'>" . htmlspecialchars($news['title']) . "</h4>";
            echo "<span style='color:#888; font-size:0.9em;'>🕒 發布時間：{$date}</span>";
            echo "</div>";
            
            if ($is_admin) {
                echo "<form method='POST' style='margin:0;' onsubmit='return confirm(\"確定要刪除這則消息嗎？\");'>";
                echo "<input type='hidden' name='news_id' value='{$news['news_id']}'>";
                echo "<button type='submit' name='delete_news' class='btn' style='background:#dc3545; padding:5px 12px; font-size:0.9em;'>🗑️ 刪除</button>";
                echo "</form>";
            }
            echo "</div>";
            
            echo "<div style='line-height:1.7; color:#333; font-size:1.05em; margin-bottom:15px;'>";
            echo nl2br(htmlspecialchars($news['content']));
            echo "</div>";
            
            // 取消前端的 file_exists 嚴格檢查，如果圖片破圖會直接顯示破圖圖示，幫助除錯路徑問題
            if (!empty($news['image_path'])) {
                echo "<div style='margin-top:15px; text-align:center; background:#f8f9fa; padding:10px; border-radius:6px;'>";
                echo "<img src='" . htmlspecialchars($news['image_path']) . "' alt='附圖' style='max-width:100%; max-height:450px; border-radius:4px; box-shadow:0 2px 5px rgba(0,0,0,0.15);'>";
                echo "</div>";
            }
            
            echo "</div>";
        }
        echo "</div>";
    } else {
        echo "<div style='background:#f8f9fa; padding:40px; text-align:center; color:#999; border-radius:8px; border:1px dashed #ccc;'>目前尚無最新消息發布。</div>";
    }
    ?>
</div>