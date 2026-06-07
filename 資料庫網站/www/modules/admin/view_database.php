<div class="card">
    <h2>🗄️ 系統資料庫總覽 </h2>
    <p>此頁面僅供管理員檢視系統底層所有資料表的原始內容與當前狀態。若內容過長，將滑鼠停留在該欄位即可查看完整文字。</p>

    <?php
    if ($_SESSION['role'] != 'Admin') {
        echo "<p style='color:red;'>無權限。只有管理員可以瀏覽此頁面。</p>";
    } else {
        // 取得資料庫中所有的表名 (自動抓取最新結構，包含剛建好的新表)
        $tables = [];
        $tables_result = $conn->query("SHOW TABLES");
        
        if ($tables_result) {
            while ($row = $tables_result->fetch_row()) {
                $tables[] = $row[0];
            }
            $tables_result->free(); // 釋放記憶體
        }

        if (!empty($tables)) {
            foreach ($tables as $table_name) {
                echo "<h3 style='margin-top: 30px; color: #007bff; border-bottom: 2px solid #007bff; padding-bottom: 5px; display: inline-block;'>📑 資料表：{$table_name}</h3>";

                // 查詢該表的所有資料
                $data_result = $conn->query("SELECT * FROM `{$table_name}`");

                if (!$data_result) {
                    echo "<p style='color:red; background:#f8d7da; padding:10px;'>讀取 {$table_name} 時發生錯誤: " . $conn->error . "</p>";
                    continue; 
                }

                if ($data_result->num_rows > 0) {
                    echo "<div style='overflow-x: auto; background: #fff; padding: 10px; border: 1px solid #ddd; border-radius: 4px;'>";
                    echo "<table style='width:100%; text-align:left; border-collapse: collapse; font-size: 0.9em; white-space: nowrap;'>";
                    echo "<tr style='background:#f4f6f9;'>";

                    // 動態取得並印出欄位名稱 (表頭)
                    $fields = $data_result->fetch_fields();
                    foreach ($fields as $field) {
                        echo "<th style='padding:8px 12px; border: 1px solid #dee2e6; color: #495057;'>" . htmlspecialchars($field->name) . "</th>";
                    }
                    echo "</tr>";

                    // 印出每一筆資料內容
                    while ($data_row = $data_result->fetch_assoc()) {
                        echo "<tr style='border-bottom:1px solid #e0e0e0; background: #fff;' onmouseover=\"this.style.background='#f9f9f9'\" onmouseout=\"this.style.background='#fff'\">";
                        foreach ($data_row as $key => $value) {
                            if ($value === null) {
                                // NULL 值顯示為灰色斜體
                                echo "<td style='padding:8px 12px; border: 1px solid #dee2e6; color: #999; font-style: italic;'>NULL</td>";
                            } else {
                                $safe_val = htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
                                
                                // 【升級亮點】利用 CSS 的 text-overflow 處理過長文字，並加入 title 屬性供滑鼠懸停查看
                                // max-width: 200px 確保欄位不會無限伸長撐破版面
                                echo "<td style='padding:8px 12px; border: 1px solid #dee2e6; color: #333; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;' title='{$safe_val}'>" . $safe_val . "</td>";
                            }
                        }
                        echo "</tr>";
                    }
                    echo "</table>";
                    echo "</div>";
                } else {
                    echo "<div style='background: #f8f9fa; padding: 10px; border-radius: 4px; border: 1px solid #ddd;'>";
                    echo "<span style='color:#999;'>此資料表目前沒有任何資料。</span>";
                    echo "</div>";
                }
                $data_result->free();
            }
        } else {
            echo "<p style='color:red;'>無法讀取資料庫結構，或資料庫中沒有任何表單。</p>";
        }
    }
    ?>
</div>