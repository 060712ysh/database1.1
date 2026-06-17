<?php
// 取得當前角色
$current_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'Guest';
$is_admin = ($current_role == 'Admin');
$is_teacher = ($current_role == 'Teacher');
$is_frontend_user = ($current_role == 'Student' || $current_role == 'Guest'); 

// --- 核心防呆：全自動排序正規化函式 ---
if (!function_exists('reorderNews')) {
    function reorderNews($conn, $target_id = 0, $new_pos = 0) {
        $check = $conn->query("SHOW COLUMNS FROM News LIKE 'sort_order'");
        if ($check->num_rows == 0) return; 

        $ids = [];
        $res = $conn->query("SELECT news_id FROM News ORDER BY sort_order ASC, created_at DESC");
        while($r = $res->fetch_assoc()) {
            if ($r['news_id'] != $target_id) $ids[] = $r['news_id'];
        }
        
        if ($target_id > 0 && $new_pos > 0) {
            $insert_index = max(0, min(count($ids), $new_pos - 1));
            array_splice($ids, $insert_index, 0, [$target_id]);
        }
        
        foreach($ids as $idx => $id) {
            $seq = $idx + 1;
            $conn->query("UPDATE News SET sort_order = $seq WHERE news_id = $id");
        }
    }
}

// --- 處理管理員發布新消息 ---
if ($is_admin && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_news'])) {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $sort_order = isset($_POST['sort_order']) ? intval($_POST['sort_order']) : 0; 
    $image_path = null;
    $upload_msg = ""; 
    $admin_uid = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;

    if (isset($_FILES['news_image']) && $_FILES['news_image']['name'] != '') {
        if ($_FILES['news_image']['error'] == 0) {
            $upload_dir = 'uploads/news/';
            if (!is_dir($upload_dir)) { @mkdir($upload_dir, 0777, true); }
            $file_ext = strtolower(pathinfo($_FILES['news_image']['name'], PATHINFO_EXTENSION));
            if (in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                $new_filename = uniqid('news_') . '.' . $file_ext;
                $target_file = $upload_dir . $new_filename;
                if (move_uploaded_file($_FILES['news_image']['tmp_name'], $target_file)) {
                    $image_path = $target_file;
                    $upload_msg = "<div style='color:green; margin-top:10px;'>✓ 圖片上傳成功！</div>";
                }
            } else {
                $upload_msg = "<div style='color:#ffc107; margin-top:10px;'>⚠️ 警告：不支援的圖片格式。</div>";
            }
        }
    }

    if ($title && $content) {
        $check_col = $conn->query("SHOW COLUMNS FROM News LIKE 'sort_order'");
        if ($check_col && $check_col->num_rows > 0) {
            $stmt = $conn->prepare("INSERT INTO News (title, content, image_path, sort_order) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sssi", $title, $content, $image_path, $sort_order);
        } else {
            $stmt = $conn->prepare("INSERT INTO News (title, content, image_path) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $title, $content, $image_path);
        }
        
        if ($stmt->execute()) {
            $new_id = $stmt->insert_id;
            reorderNews($conn, $new_id, $sort_order);
            
            $log_stmt = $conn->prepare("INSERT INTO AdminLogs (user_id, action_type, description) VALUES (?, '最新消息', ?)");
            if ($log_stmt) {
                $log_desc = "發布了首頁消息: {$title}";
                $log_stmt->bind_param("is", $admin_uid, $log_desc);
                $log_stmt->execute();
            }
            echo "<div class='card' style='background:#d4edda; border-left:4px solid #28a745;'><strong>✓ 成功：</strong>最新消息已發布！{$upload_msg}</div>";
        }
    }
}

// --- 處理管理員更新消息順序 ---
if ($is_admin && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_sort'])) {
    $news_id = intval($_POST['news_id']);
    $new_sort = intval($_POST['sort_order']);
    reorderNews($conn, $news_id, $new_sort);
    echo "<div class='card' style='background:#d4edda; border-left:4px solid #28a745;'><strong>✓ 成功：</strong>消息順序已完美更新！</div>";
}

// --- 處理管理員刪除消息 ---
if ($is_admin && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_news'])) {
    $del_id = intval($_POST['news_id']);
    $get_img = $conn->query("SELECT title, image_path FROM News WHERE news_id = $del_id");
    if ($row = $get_img->fetch_assoc()) {
        if (!empty($row['image_path']) && file_exists($row['image_path'])) unlink($row['image_path']); 
        $conn->query("DELETE FROM News WHERE news_id = $del_id");
        reorderNews($conn); 
        echo "<div class='card' style='background:#d4edda; border-left:4px solid #28a745;'><strong>✓ 成功：</strong>消息與圖片已移除！</div>";
    }
}

// 取得所有消息資料與數量
$query_sql = "SELECT * FROM News ORDER BY created_at DESC";
$check_col = $conn->query("SHOW COLUMNS FROM News LIKE 'sort_order'");
if ($check_col && $check_col->num_rows > 0) {
    $query_sql = "SELECT * FROM News ORDER BY sort_order ASC, created_at DESC";
}
$news_result = $conn->query($query_sql);
$news_array = [];
if ($news_result && $news_result->num_rows > 0) {
    while ($row = $news_result->fetch_assoc()) $news_array[] = $row;
}
$total_news = count($news_array);
?>

<div class="card" style="background: linear-gradient(to right, #f8f9fa, #e9ecef); border-left: 5px solid #2c3e50;">
    <h2 style='color: #2c3e50; margin-top:0;'>🏠 歡迎瀏覽資工系入口網</h2>
    <p style='color: #555; line-height: 1.6; margin-bottom:0;'>掌握系所最新動態，了解師資陣容與各實驗室研究方向。</p>
</div>

<?php if ($is_frontend_user): ?>
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 30px; margin-top: 20px;">
    <a href="index.php?page=faculty" style="display: flex; flex-direction: column; align-items: center; justify-content: center; background: #fff; padding: 35px 20px; border-radius: 12px; text-decoration: none; color: #333; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border-bottom: 4px solid #17a2b8; transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 8px 25px rgba(0,0,0,0.1)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(0,0,0,0.05)';">
        <div style="font-size: 3.5em; margin-bottom: 15px;">👨‍🏫</div>
        <h3 style="margin: 0 0 10px 0; color: #17a2b8; font-size: 1.4em;">探索師資陣容</h3>
        <p style="margin: 0; color: #777; font-size: 0.95em; text-align: center;">瀏覽本系專業教授團隊、聯絡資訊與歷年著作</p>
    </a>
    <a href="index.php?page=labs" style="display: flex; flex-direction: column; align-items: center; justify-content: center; background: #fff; padding: 35px 20px; border-radius: 12px; text-decoration: none; color: #333; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border-bottom: 4px solid #6f42c1; transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 8px 25px rgba(0,0,0,0.1)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(0,0,0,0.05)';">
        <div style="font-size: 3.5em; margin-bottom: 15px;">🔬</div>
        <h3 style="margin: 0 0 10px 0; color: #6f42c1; font-size: 1.4em;">實驗室與研究</h3>
        <p style="margin: 0; color: #777; font-size: 0.95em; text-align: center;">了解各實驗室尖端研究方向與學術領域成果</p>
    </a>
    <a href="index.php?page=downloads" style="display: flex; flex-direction: column; align-items: center; justify-content: center; background: #fff; padding: 35px 20px; border-radius: 12px; text-decoration: none; color: #333; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border-bottom: 4px solid #28a745; transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 8px 25px rgba(0,0,0,0.1)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(0,0,0,0.05)';">
        <div style="font-size: 3.5em; margin-bottom: 15px;">📥</div>
        <h3 style="margin: 0 0 10px 0; color: #28a745; font-size: 1.4em;">表單與檔案下載</h3>
        <p style="margin: 0; color: #777; font-size: 0.95em; text-align: center;">取得修業規章、學生實習與畢業相關申請表單</p>
    </a>
</div>
<?php endif; ?>

<?php if ($is_admin): ?>
<div class="card" style="border: 1px solid #17a2b8; border-top: 4px solid #17a2b8; background: #f4fcfe;">
    <h4 style="margin-top:0; color:#17a2b8;">📢 發布最新消息</h4>
    <form method="POST" enctype="multipart/form-data" style="display:flex; flex-direction:column; gap:12px;">
        <input type="text" name="title" placeholder="消息標題 (必填)" required style="padding:10px; border:1px solid #ccc; border-radius:4px; font-size:1em;">
        <textarea name="content" rows="4" placeholder="消息內容 (必填)" required style="padding:10px; border:1px solid #ccc; border-radius:4px; resize:vertical; font-size:1em; font-family:inherit;"></textarea>
        
        <div style="display:flex; gap:15px; align-items:center;">
            <div style="flex:1; background: #fff; padding: 10px; border: 1px dashed #17a2b8; border-radius: 4px;">
                <label style="color:#555; font-weight:bold; cursor:pointer; font-size:0.95em;">
                    🖼️ 上傳附圖 (選填，限 JPG, PNG)：<br>
                    <input type="file" name="news_image" accept="image/jpeg, image/png, image/gif" style="margin-top:8px;">
                </label>
            </div>
            
            <div style="background: #fff; padding: 10px; border: 1px solid #ccc; border-radius: 4px; display:flex; flex-direction:column; justify-content:center;">
                <label style="color:#333; font-weight:bold; font-size:0.9em; margin-bottom:5px;">↕️ 發布位置設定</label>
                <select name="sort_order" style="padding:6px; border:1px solid #aaa; border-radius:4px; cursor:pointer;">
                    <?php 
                    $next_sort = $total_news + 1;
                    for($i = 1; $i <= $next_sort; $i++) {
                        $label = "第 {$i} 順位";
                        if ($i == 1) $label .= " (最置頂)";
                        if ($i == $next_sort) $label .= " (預設/最底)";
                        $selected = ($i == $next_sort) ? 'selected' : '';
                        echo "<option value='{$i}' {$selected}>{$label}</option>";
                    }
                    ?>
                </select>
            </div>
        </div>
        <button type="submit" name="add_news" class="btn" style="background:#17a2b8; align-self:flex-start; padding:8px 25px; font-size:1.05em;">📤 發布消息</button>
    </form>
</div>
<?php endif; ?>

<div style="margin-top: 40px;">
    <?php
    if (empty($news_array)) {
        echo "<h3 style='color: #2c3e50; border-bottom: 2px solid #343a40; padding-bottom: 10px;'>📰 系所最新消息</h3>";
        echo "<div style='background:#f8f9fa; padding:40px; text-align:center; color:#999; border-radius:8px; border:1px dashed #ccc;'>目前尚無最新消息發布。</div>";
    } else {
        
        // ✨ 修改點：將老師 ($is_teacher) 也加入顯示跑馬燈的條件中！
        if ($is_frontend_user || $is_teacher) {
            echo "<h3 style='color: #2c3e50; margin-bottom: 20px; font-size:1.8em; text-align:center;'>📢 <span style='border-bottom: 3px solid #1976d2; padding-bottom:5px;'>系所最新消息</span></h3>";
            ?>
            <style>
                .news-carousel { position: relative; width: 100%; overflow: hidden; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.08); background: #fff; border: 1px solid #eaeaea; }
                .carousel-inner { display: flex; transition: transform 0.6s cubic-bezier(0.25, 0.8, 0.25, 1); }
                .carousel-item { min-width: 100%; padding: 40px 60px 60px 60px; box-sizing: border-box; display: flex; flex-direction: column; justify-content: center; background: linear-gradient(135deg, #ffffff 0%, #f4f9ff 100%); }
                .carousel-control { position: absolute; top: 50%; transform: translateY(-50%); background: rgba(0,0,0,0.4); color: #fff; border: none; width: 45px; height: 45px; border-radius: 50%; cursor: pointer; font-size: 1.5em; display: flex; align-items: center; justify-content: center; transition: 0.3s; z-index: 999; pointer-events: auto; }
                .carousel-control:hover { background: #1976d2; }
                .carousel-control.prev { left: 15px; }
                .carousel-control.next { right: 15px; }
                .carousel-indicators { position: absolute; bottom: 15px; left: 50%; transform: translateX(-50%); display: flex; gap: 8px; z-index: 999; pointer-events: auto; }
                .carousel-dot { width: 10px; height: 10px; background: #ccc; border-radius: 50%; cursor: pointer; transition: 0.3s; }
                .carousel-dot.active { background: #1976d2; width: 25px; border-radius: 5px; }
                .news-scroll-content { max-height: 180px; overflow-y: auto; padding-right: 15px; color: #555; line-height: 1.7; font-size: 1.05em; white-space: pre-wrap; }
                .news-scroll-content::-webkit-scrollbar { width: 6px; }
                .news-scroll-content::-webkit-scrollbar-thumb { background: #ccc; border-radius: 3px; }
            </style>

            <div class='news-carousel' id='newsCarousel'>
                <div class='carousel-inner' id='carouselInner'>
                <?php
                foreach ($news_array as $news) {
                    $date = date('Y-m-d', strtotime($news['created_at']));
                    echo "<div class='carousel-item'>";
                    echo "  <div style='display: flex; flex-wrap: wrap; gap: 30px; align-items: center;'>";
                    echo "    <div style='flex: 1 1 300px;'>";
                    echo "      <span style='display: inline-block; background: #e8f4fd; color: #1976d2; padding: 4px 10px; border-radius: 4px; font-size: 0.85em; font-weight: bold; margin-bottom: 10px;'>📅 發布日期：{$date}</span>";
                    echo "      <h2 style='margin: 0 0 15px 0; color: #2c3e50; font-size: 1.6em; line-height: 1.3;'>" . htmlspecialchars($news['title']) . "</h2>";
                    echo "      <div class='news-scroll-content'>" . htmlspecialchars($news['content']) . "</div>";
                    echo "    </div>";

                    if (!empty($news['image_path'])) {
                        echo "  <div style='flex: 1 1 300px; text-align: center;'>";
                        echo "      <img src='" . htmlspecialchars($news['image_path']) . "' alt='附圖' style='max-width: 100%; max-height: 250px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); object-fit: contain;'>";
                        echo "  </div>";
                    }
                    echo "  </div>";
                    echo "</div>"; 
                }
                ?>
                </div>

                <?php
                $slide_count = count($news_array);
                if ($slide_count > 1) {
                    echo "<button type='button' class='carousel-control prev' id='btnPrev'>❮</button>";
                    echo "<button type='button' class='carousel-control next' id='btnNext'>❯</button>";
                    echo "<div class='carousel-indicators'>";
                    for ($i = 0; $i < $slide_count; $i++) {
                        echo "<div class='carousel-dot' data-index='{$i}'></div>";
                    }
                    echo "</div>";
                }
                ?>
            </div>

            <script>
            (function() {
                const inner = document.getElementById('carouselInner');
                if (!inner) return;

                const dots = document.querySelectorAll('.carousel-dot');
                const btnPrev = document.getElementById('btnPrev');
                const btnNext = document.getElementById('btnNext');
                const carousel = document.getElementById('newsCarousel');

                const totalSlides = document.querySelectorAll('.carousel-item').length;
                let currentIndex = 0;
                let timer = null;

                function showSlide(index) {
                    if (totalSlides <= 1) return;
                    
                    if (index >= totalSlides) currentIndex = 0;
                    else if (index < 0) currentIndex = totalSlides - 1;
                    else currentIndex = index;

                    inner.style.transform = `translateX(-${currentIndex * 100}%)`;
                    
                    dots.forEach(dot => dot.classList.remove('active'));
                    if(dots[currentIndex]) dots[currentIndex].classList.add('active');
                }

                function moveSlide(step) {
                    showSlide(currentIndex + step);
                    resetTimer();
                }

                function resetTimer() {
                    if (totalSlides <= 1) return;
                    if (timer) clearInterval(timer);
                    timer = setInterval(() => { showSlide(currentIndex + 1); }, 5000); 
                }

                if (btnPrev) {
                    btnPrev.onclick = function(e) {
                        e.preventDefault();
                        moveSlide(-1);
                    };
                }

                if (btnNext) {
                    btnNext.onclick = function(e) {
                        e.preventDefault();
                        moveSlide(1);
                    };
                }

                dots.forEach(dot => {
                    dot.onclick = function(e) {
                        e.preventDefault();
                        showSlide(parseInt(this.getAttribute('data-index')));
                        resetTimer();
                    };
                });

                if (totalSlides > 1) {
                    showSlide(0);
                    resetTimer();
                    if(carousel) {
                        carousel.onmouseenter = function() { clearInterval(timer); };
                        carousel.onmouseleave = function() { resetTimer(); };
                    }
                }
            })();
            </script>
            <?php
        } 
        // 🌟 模式 B：後台管理員 -> 維持原版列表供修改與刪除
        else if ($is_admin) {
            echo "<h3 style='color: #2c3e50; border-bottom: 2px solid #343a40; padding-bottom: 10px;'>📰 系所最新消息管理</h3>";
            echo "<div style='display:flex; flex-direction:column; gap:20px; margin-top:20px;'>";
            
            foreach ($news_array as $news) {
                $date = date('Y-m-d H:i', strtotime($news['created_at']));
                $sort_val = isset($news['sort_order']) ? $news['sort_order'] : 1;
                
                echo "<div class='card' style='margin-bottom:0; box-shadow:0 3px 10px rgba(0,0,0,0.08); border-left: 4px solid #007bff; overflow:hidden;'>";
                echo "<div style='display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:15px; border-bottom:1px solid #eee; padding-bottom:10px;'>";
                echo "<div>";
                echo "<h4 style='margin:0 0 8px 0; color:#0056b3; font-size:1.3em;'>" . htmlspecialchars($news['title']) . "</h4>";
                echo "<span style='color:#888; font-size:0.9em;'>🕒 發布時間：{$date}</span>";
                echo "</div>";
                
                if ($is_admin) {
                    echo "<form method='POST' style='margin:0; display:flex; align-items:center; gap:8px;'>";
                    echo "<input type='hidden' name='news_id' value='{$news['news_id']}'>";
                    echo "<label style='font-size:0.9em; color:#555; margin:0;'>位置:</label>";
                    
                    echo "<select name='sort_order' style='padding:4px; border:1px solid #ccc; border-radius:4px; cursor:pointer;'>";
                    for($i = 1; $i <= $total_news; $i++) {
                        $selected = ($i == $sort_val) ? 'selected' : '';
                        echo "<option value='{$i}' {$selected}>第 {$i} 順位</option>";
                    }
                    echo "</select>";

                    echo "<button type='submit' name='update_sort' class='btn' style='background:#28a745; padding:4px 10px; font-size:0.85em;'>更新</button>";
                    echo "<button type='submit' name='delete_news' class='btn' style='background:#dc3545; padding:4px 10px; font-size:0.85em;' onclick='return confirm(\"確定要刪除這則消息嗎？\");'>🗑️ 刪除</button>";
                    echo "</form>";
                }
                echo "</div>";
                echo "<div style='line-height:1.7; color:#333; font-size:1.05em; margin-bottom:15px; white-space:pre-wrap;'>" . htmlspecialchars($news['content']) . "</div>";
                
                if (!empty($news['image_path'])) {
                    echo "<div style='margin-top:15px; text-align:center; background:#f8f9fa; padding:10px; border-radius:6px;'>";
                    echo "<img src='" . htmlspecialchars($news['image_path']) . "' alt='附圖' style='max-width:100%; max-height:450px; border-radius:4px; box-shadow:0 2px 5px rgba(0,0,0,0.15);'>";
                    echo "</div>";
                }
                echo "</div>";
            }
            echo "</div>";
        }
    }
    ?>
</div>