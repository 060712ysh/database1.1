<?php
// 動態取得使用者的真實姓名
$display_name = isset($_SESSION['username']) ? $_SESSION['username'] : '使用者'; // 預設為登入帳號

if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    $uid = intval($_SESSION['user_id']);
    $role = $_SESSION['role'];
    
    // 根據不同身分去對應的資料表撈取真實姓名
    if ($role == 'Admin') {
        $q = $conn->query("SELECT name FROM Admins WHERE user_id = $uid");
        if ($q && $r = $q->fetch_assoc()) $display_name = $r['name'];
    } elseif ($role == 'Teacher') {
        $q = $conn->query("SELECT name FROM Teachers WHERE user_id = $uid");
        if ($q && $r = $q->fetch_assoc()) $display_name = $r['name'];
    } elseif ($role == 'Student') {
        $q = $conn->query("SELECT name FROM Students WHERE user_id = $uid");
        if ($q && $r = $q->fetch_assoc()) $display_name = $r['name'];
    }
}
?>

<div style="background: #1976d2; color: #fff; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; border-radius: 0; margin-bottom: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
    <h2 style="margin: 0; font-size: 1.4em; text-shadow: 1px 1px 2px rgba(0,0,0,0.2);">💻 資工系線上入口網</h2>
    
    <div style="display: flex; align-items: center; gap: 15px;">
        <?php if (isset($_SESSION['user_id'])): ?>
            <span style="color: #f8f9fa;">👤 歡迎，<strong><?php echo htmlspecialchars($display_name); ?></strong> (<?php echo $_SESSION['role']; ?>)</span>
            
            <a href="index.php?page=change_password" style="color: #fff; text-decoration: none; font-weight: bold; border: 1px solid #fff; padding: 4px 10px; border-radius: 4px; background: rgba(255, 255, 255, 0.15); transition: 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.15)'">🔑 更改密碼</a>
            
            <a href="logout.php" style="background: #dc3545; color: white; text-decoration: none; padding: 5px 12px; border-radius: 4px; font-weight: bold; box-shadow: 0 2px 4px rgba(0,0,0,0.15);">🚪 登出</a>
        <?php else: ?>
            <a href="index.php?page=login" style="background: #007bff; color: white; text-decoration: none; padding: 5px 15px; border-radius: 4px; font-weight: bold;">系統登入</a>
        <?php endif; ?>
    </div>
</div>