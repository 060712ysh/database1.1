<div class="header">
    <div style="flex: 1;">
        <h3 style="margin: 0;">資工系師生入口網</h3>
    </div>
    <div style="display: flex; gap: 15px; align-items: center;">
        <?php if(isset($_SESSION['user_id'])): ?>
            <?php
                // 動態查詢使用者的真實姓名
                $display_name = $_SESSION['username']; // 預設顯示帳號防呆
                if(isset($conn)) {
                    $uid = intval($_SESSION['user_id']);
                    $role_type = $_SESSION['role'];
                    $table = '';
                    
                    if($role_type == 'Admin') $table = 'Admins';
                    elseif($role_type == 'Teacher') $table = 'Teachers';
                    elseif($role_type == 'Student') $table = 'Students';
                    
                    if($table) {
                        $name_query = $conn->query("SELECT name FROM $table WHERE user_id = $uid");
                        if($name_query && $n_row = $name_query->fetch_assoc()) {
                            $display_name = $n_row['name'];
                        }
                    }
                }
            ?>
            <span style="font-weight: bold;">歡迎, <?php echo htmlspecialchars($display_name); ?> 
            (<?php echo htmlspecialchars($_SESSION['role']); ?>)</span>
            <a href="logout.php" class="btn" style="margin: 0; background: #dc3545;">登出</a>
        <?php else: ?>
            <a href="index.php?page=login" class="btn" style="margin: 0;">系統登入</a>
        <?php endif; ?>
    </div>
</div>