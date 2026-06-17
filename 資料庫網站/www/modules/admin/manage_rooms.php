<div class="card">
    <h2>🏫 全校教室與討論室空間管理</h2>
    <p>管理員可在此動態新增、修改或移除系上的教室與討論室。此處管理的空間將會即時連動至「教師開課教室」與「學生討論室空間預約」的下拉選單中。</p>

    <?php
    if (!isset($_SESSION['role']) || $_SESSION['role'] != 'Admin') {
        echo "<p style='color:red;'>只有管理員可以使用此功能。</p>";
    } else {
        $admin_uid = intval($_SESSION['user_id']); 

        // --- 處理 1：新增教室/討論室 ---
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_room'])) {
            $room_name = trim($_POST['room_name'] ?? '');
            $room_type = trim($_POST['room_type'] ?? '');
            $capacity = intval($_POST['capacity'] ?? 0);

            if ($room_name && in_array($room_type, ['上課教室', '討論室'])) {
                $chk = $conn->prepare("SELECT room_id FROM Rooms WHERE room_name = ?");
                $chk->bind_param("s", $room_name);
                $chk->execute();
                if ($chk->get_result()->num_rows > 0) {
                    echo "<div class='card' style='background:#f8d7da; border-left:4px solid #dc3545;'><strong>⚠️ 錯誤：</strong>空間名稱 [{$room_name}] 已經存在！</div>";
                } else {
                    $insert = $conn->prepare("INSERT INTO Rooms (room_name, room_type, capacity) VALUES (?, ?, ?)");
                    $insert->bind_param("ssi", $room_name, $room_type, $capacity);
                    if ($insert->execute()) {
                        @$conn->query("INSERT INTO AdminLogs (user_id, action_type, description) VALUES ($admin_uid, '空間異動', '新增了{$room_type}：{$room_name} (容納 {$capacity} 人)')");
                        echo "<div class='card' style='background:#d4edda; border-left:4px solid #28a745;'><strong>✓ 成功：</strong>已成功建立新空間 [{$room_name}]！</div>";
                    }
                }
            }
        }

        // --- 處理 2：修改教室/討論室 ---
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_room'])) {
            $edit_id = intval($_POST['edit_room_id']);
            $edit_name = trim($_POST['edit_room_name']);
            $edit_type = trim($_POST['edit_room_type']);
            $edit_capacity = intval($_POST['edit_capacity']);

            $chk = $conn->prepare("SELECT room_id FROM Rooms WHERE room_name = ? AND room_id != ?");
            $chk->bind_param("si", $edit_name, $edit_id);
            $chk->execute();
            if ($chk->get_result()->num_rows > 0) {
                 echo "<div class='card' style='background:#f8d7da; border-left:4px solid #dc3545;'><strong>⚠️ 錯誤：</strong>修改失敗，空間名稱 [{$edit_name}] 已經被使用了！</div>";
            } else {
                 $upd = $conn->prepare("UPDATE Rooms SET room_name=?, room_type=?, capacity=? WHERE room_id=?");
                 $upd->bind_param("ssii", $edit_name, $edit_type, $edit_capacity, $edit_id);
                 if ($upd->execute()) {
                     @$conn->query("INSERT INTO AdminLogs (user_id, action_type, description) VALUES ($admin_uid, '空間異動', '修改了空間資訊：{$edit_name}')");
                     echo "<div class='card' style='background:#d4edda; border-left:4px solid #28a745;'><strong>✓ 成功：</strong>空間資訊已成功更新！</div>";
                 }
            }
        }

        // --- 處理 3：刪除教室/討論室 ---
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_room'])) {
            $del_id = intval($_POST['room_id']);
            $get_name = $conn->query("SELECT room_name, room_type FROM Rooms WHERE room_id = $del_id");
            if ($row = $get_name->fetch_assoc()) {
                $conn->query("DELETE FROM Rooms WHERE room_id = $del_id");
                @$conn->query("INSERT INTO AdminLogs (user_id, action_type, description) VALUES ($admin_uid, '空間異動', '移除了{$row['room_type']}：{$row['room_name']}')");
                echo "<div class='card' style='background:#d4edda; border-left:4px solid #28a745;'><strong>✓ 成功：</strong>該空間已成功移除！</div>";
            }
        }
        ?>

        <style>
            .perfect-align-form { display: flex; align-items: flex-end; gap: 15px; flex-wrap: wrap; margin: 0; }
            .perfect-align-form .form-group { display: flex; flex-direction: column; justify-content: flex-end; margin: 0; padding: 0; }
            .perfect-align-form label { margin: 0 0 8px 0 !important; font-weight: bold; color: #555; font-size: 14px; line-height: 1; display: block; }
            .perfect-align-form input, .perfect-align-form select, .perfect-align-form button {
                height: 42px !important; 
                padding: 0 15px !important; 
                margin: 0 !important; 
                box-sizing: border-box !important; 
                border: 1px solid #ccc; 
                border-radius: 4px; 
                font-size: 15px !important; 
                outline: none; 
            }
            .perfect-align-form button {
                border: 1px solid #28a745 !important; 
                background: #28a745 !important; 
                color: #fff !important; 
                cursor: pointer; 
                font-weight: bold;
                transition: 0.2s;
            }
            .perfect-align-form button:hover { background: #218838 !important; }
        </style>

        <div style="background:#f4f6f9; padding:20px; border-radius:8px; border:1px solid #ddd; margin-bottom:25px;">
            <h3 style="margin-top:0; color:#333; margin-bottom:20px;">➕ 新增空間設施</h3>
            <form method="POST" class="perfect-align-form">
                <div class="form-group" style="flex: 2; min-width: 200px;">
                    <label>空間/教室名稱：</label>
                    <input type="text" name="room_name" placeholder="例：資工系館 R302" required>
                </div>
                <div class="form-group" style="flex: 1.5; min-width: 150px;">
                    <label>空間分類：</label>
                    <select name="room_type" required>
                        <option value="上課教室">上課教室 (排課)</option>
                        <option value="討論室">討論室 (預約)</option>
                    </select>
                </div>
                <div class="form-group" style="flex: 1; min-width: 120px;">
                    <label>容納人數上限：</label>
                    <input type="number" name="capacity" value="4" min="1" required style="text-align:center;">
                </div>
                <div class="form-group" style="flex: none;">
                    <button type="submit" name="add_room">新增設施</button>
                </div>
            </form>
        </div>

        <h3 style="color:#2c3e50; border-bottom:2px solid #1976d2; padding-bottom:8px;">📋 目前系所空間清單</h3>
        <table style="width:100%; border-collapse:collapse; text-align:left; margin-top:15px;">
            <tr style="background:#343a40; color:#fff;">
                <th style="padding:12px 10px; width:10%;">空間 ID</th>
                <th style="padding:12px 10px; width:40%;">空間名稱</th>
                <th style="padding:12px 10px; width:20%;">分類標籤</th>
                <th style="padding:12px 10px; width:15%;">容納人數</th>
                <th style="padding:12px 10px; width:15%;">操作管理</th>
            </tr>
            <?php
            $rooms = $conn->query("SELECT * FROM Rooms ORDER BY room_type DESC, room_name ASC");
            if ($rooms && $rooms->num_rows > 0) {
                while ($r = $rooms->fetch_assoc()) {
                    $badge_color = ($r['room_type'] == '上課教室') ? '#17a2b8' : '#6f42c1';
                    $type_badge = "<span style='background:{$badge_color}; color:#fff; padding:3px 10px; border-radius:12px; font-size:0.85em; font-weight:500;'>{$r['room_type']}</span>";
                    
                    $js_name = htmlspecialchars($r['room_name'], ENT_QUOTES);
                    $js_type = htmlspecialchars($r['room_type'], ENT_QUOTES);
                    $js_cap = intval($r['capacity']);
                    
                    echo "<tr style='border-bottom:1px solid #eee;' onmouseover=\"this.style.background='#f9f9f9'\" onmouseout=\"this.style.background='#fff'\">";
                    echo "<td style='padding:12px 10px; color:#666;'>{$r['room_id']}</td>";
                    echo "<td style='padding:12px 10px; font-weight:bold; color:#2c3e50;'>".htmlspecialchars($r['room_name'])."</td>";
                    echo "<td style='padding:12px 10px;'>{$type_badge}</td>";
                    echo "<td style='padding:12px 10px; font-weight:500;'>{$r['capacity']} 人</td>";
                    echo "<td style='padding:12px 10px; display:flex; gap:8px;'>";
                    
                    echo "<button type='button' class='btn' style='background:#17a2b8; padding:5px 12px; font-size:0.9em; height:auto;' onclick='openRoomEditModal({$r['room_id']}, \"{$js_name}\", \"{$js_type}\", {$js_cap})'>修改</button>";

                    echo "  <form method='POST' style='margin:0;' onsubmit='return confirm(\"⚠️ 確定要刪除這個空間嗎？\");'>";
                    echo "      <input type='hidden' name='room_id' value='{$r['room_id']}'>";
                    echo "      <button type='submit' name='delete_room' class='btn' style='background:#dc3545; padding:5px 12px; font-size:0.9em; height:auto;'>移除</button>";
                    echo "  </form>";
                    echo "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='5' style='padding:20px; text-align:center; color:#999;'>目前系統內無任何教室空間設定。</td></tr>";
            }
            ?>
        </table>

        <div id="editRoomModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); align-items:center; justify-content:center; z-index:1050; backdrop-filter:blur(3px);">
            <div style="background:#fff; padding:30px; border-radius:12px; width:400px; max-width:90%; box-shadow:0 10px 30px rgba(0,0,0,0.2);">
                <h3 style="margin-top:0; color:#2c3e50; border-bottom:2px solid #17a2b8; padding-bottom:10px;">✏️ 修改空間資訊</h3>
                <form method="POST">
                    <input type="hidden" name="edit_room_id" id="modal_room_id">
                    <div style="margin-bottom:15px;">
                        <label style="font-weight:bold; color:#444; display:block; margin-bottom:5px;">空間名稱：</label>
                        <input type="text" name="edit_room_name" id="modal_room_name" required style="width:100%; padding:10px; border:1px solid #ccc; border-radius:4px; box-sizing:border-box;">
                    </div>
                    <div style="margin-bottom:15px;">
                        <label style="font-weight:bold; color:#444; display:block; margin-bottom:5px;">空間分類：</label>
                        <select name="edit_room_type" id="modal_room_type" required style="width:100%; padding:10px; border:1px solid #ccc; border-radius:4px; box-sizing:border-box;">
                            <option value="上課教室">上課教室 (供排課使用)</option>
                            <option value="討論室">討論室 (供學生預約使用)</option>
                        </select>
                    </div>
                    <div style="margin-bottom:25px;">
                        <label style="font-weight:bold; color:#444; display:block; margin-bottom:5px;">容納人數上限：</label>
                        <input type="number" name="edit_capacity" id="modal_room_capacity" min="1" required style="width:100%; padding:10px; border:1px solid #ccc; border-radius:4px; box-sizing:border-box;">
                    </div>
                    <div style="display:flex; gap:15px;">
                        <button type="submit" name="edit_room" class="btn" style="background:#007bff; flex:1; padding:10px; font-size:1.05em;">💾 儲存</button>
                        <button type="button" class="btn" style="background:#6c757d; flex:1; padding:10px; font-size:1.05em;" onclick="closeRoomEditModal()">✖ 取消</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
        function openRoomEditModal(id, name, type, cap) {
            document.getElementById('modal_room_id').value = id;
            document.getElementById('modal_room_name').value = name;
            document.getElementById('modal_room_type').value = type;
            document.getElementById('modal_room_capacity').value = cap;
            document.getElementById('editRoomModal').style.display = 'flex';
        }
        function closeRoomEditModal() {
            document.getElementById('editRoomModal').style.display = 'none';
        }
        window.onclick = function(event) {
            var modal = document.getElementById('editRoomModal');
            if (event.target == modal) { closeRoomEditModal(); }
        }
        </script>
    <?php
    }
    ?>
</div>