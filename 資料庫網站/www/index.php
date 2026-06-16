﻿<?php
// 🌟 核心防護：開啟輸出緩衝，徹底解決登入/登出 header() 跳轉時的白畫面錯誤
ob_start(); 
session_start();
require_once 'db_connect.php';

// 取得當前身分與頁面
$role = isset($_SESSION['role']) ? $_SESSION['role'] : 'Guest';
$page = isset($_GET['page']) ? $_GET['page'] : 'home';

// 判斷是否為後台管理模式
$is_backend = ($role == 'Admin' || $role == 'Teacher');

// 路由邏輯
$module_path = '';
if ($page == 'login') {
    $module_path = 'login.php';
} else {
    $page_name = basename($page); 
    // 定義各身分允許存取的檔案白名單
    $public_pages = ['home', 'faculty', 'labs', 'teacher_detail', 'downloads', 'syllabus_detail', 'change_password'];
    $admin_pages = ['manage_accounts', 'manage_courses', 'manage_enrollments', 'review_reservations', 'review_messages', 'manage_files', 'view_database', 'admin_logs'];
    $teacher_pages = ['profile', 'syllabus', 'grading'];
    $student_pages = ['course_selection', 'my_schedule', 'reservation', 'message'];

    if (in_array($page_name, $public_pages)) {
        $module_path = 'modules/public/' . $page_name . '.php';
    } else if ($role == 'Admin' && in_array($page_name, $admin_pages)) {
        $module_path = 'modules/admin/' . $page_name . '.php';
    } else if ($role == 'Teacher' && in_array($page_name, $teacher_pages)) {
        $module_path = 'modules/teacher/' . $page_name . '.php';
    } else if ($role == 'Student' && in_array($page_name, $student_pages)) {
        $module_path = 'modules/student/' . $page_name . '.php';
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>資工系線上入口網</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* =========================================
           ✨ 前台專屬樣式 (上方導覽列佈局)
           ========================================= */
        body { margin: 0; padding: 0; background-color: #f4f7f6; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; display: flex; flex-direction: column; min-height: 100vh; }
        .frontend-nav { background: #1976d2; box-shadow: 0 2px 10px rgba(0,0,0,0.1); position: sticky; top: 0; z-index: 1000; }
        
        /* 將寬度擴增到 1400px 以容納學生選單 */
        .frontend-nav-container { max-width: 1400px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; padding: 0 20px; height: 70px; gap: 20px; }
        
        /* 強制文字不換行、不壓縮 */
        .frontend-logo { color: #fff; font-size: 1.5em; font-weight: bold; text-decoration: none; display: flex; align-items: center; gap: 10px; text-shadow: 1px 1px 2px rgba(0,0,0,0.2); white-space: nowrap; flex-shrink: 0; }
        
        /* 如果螢幕太小，允許選單可以左右滑動，但不擠壓文字 */
        .frontend-menu { display: flex; gap: 5px; flex-wrap: nowrap; overflow-x: auto; scrollbar-width: none; }
        .frontend-menu::-webkit-scrollbar { display: none; }
        .frontend-menu a { color: rgba(255,255,255,0.85); text-decoration: none; padding: 8px 14px; border-radius: 20px; font-weight: 500; transition: all 0.2s ease; white-space: nowrap; flex-shrink: 0; font-size: 0.95em; }
        .frontend-menu a:hover, .frontend-menu a.active { background: rgba(255,255,255,0.2); color: #fff; }
        
        /* 確保右側登入/身分區塊不折行 */
        .frontend-user-panel { display: flex; align-items: center; gap: 15px; white-space: nowrap; flex-shrink: 0; }
        
        .frontend-content { max-width: 1400px; width: 100%; margin: 40px auto; padding: 0 20px; flex: 1; box-sizing: border-box; }
        .frontend-footer { background: #2c3e50; color: #a0aec0; text-align: center; padding: 20px 0; font-size: 0.9em; margin-top: auto; }

        /* =========================================
           ✨ 後台專屬樣式 (側邊欄佈局)
           ========================================= */
        .backend-wrapper { display: flex; min-height: 100vh; width: 100%; }
        .backend-sidebar { width: 250px; background: #343a40; color: #fff; flex-shrink: 0; display: flex; flex-direction: column; box-shadow: 2px 0 5px rgba(0,0,0,0.1); z-index: 10; }
        
        /* 重塑側邊欄按鈕的「格子」外觀 */
        .backend-sidebar h2 { font-size: 1.3em; padding: 20px; margin: 0; background: #212529; color: #fff; text-align: center; letter-spacing: 2px; }
        .backend-sidebar a { display: block; padding: 15px 25px; color: #c2c7d0; text-decoration: none; font-size: 1.05em; border-bottom: 1px solid rgba(255,255,255,0.05); transition: background 0.2s, color 0.2s; }
        .backend-sidebar a:hover { background: #495057; color: #fff; border-left: 4px solid #17a2b8; padding-left: 21px; }
        .backend-sidebar hr { border: none; border-top: 1px solid rgba(255,255,255,0.1); margin: 0; }
        
        /* 避免 main-content 內容過寬撐破版面 */
        .backend-main { flex: 1; display: flex; flex-direction: column; background: #f8f9fa; overflow-x: hidden; min-width: 0; }
    </style>
</head>
<body>

    <?php if ($is_backend): ?>
        <div class="backend-wrapper">
            <div class="backend-sidebar">
                <h2>🧭 系統後台</h2> 
                <?php
                if ($role == 'Admin') {
                    include 'includes/sidebar_admin.php';
                } elseif ($role == 'Teacher') {
                    include 'includes/sidebar_teacher.php';
                }
                ?>
            </div>
            <div class="backend-main">
                <?php include 'includes/header.php'; ?>
                <div class="content-area" style="padding: 20px;">
                    <?php
                    if ($module_path != '' && file_exists($module_path)) {
                        include $module_path;
                    } else {
                        echo "<div class='card' style='border-left: 4px solid #dc3545;'><h2>⚠️ 發生錯誤</h2><p>您請求的頁面不存在，或您沒有權限瀏覽此頁面。</p></div>";
                    }
                    ?>
                </div>
            </div>
        </div>

    <?php else: ?>
        <nav class="frontend-nav">
            <div class="frontend-nav-container">
                <a href="index.php?page=home" class="frontend-logo">
                    <span>💻</span> 資訊工程學系
                </a>
                
                <div class="frontend-menu">
                    <a href="index.php?page=home" class="<?php echo $page=='home'?'active':''; ?>">首頁公告</a>
                    <a href="index.php?page=faculty" class="<?php echo $page=='faculty'?'active':''; ?>">師資陣容</a>
                    <a href="index.php?page=labs" class="<?php echo $page=='labs'?'active':''; ?>">實驗室</a>
                    
                    <?php if ($role == 'Student'): ?>
                        <a href="index.php?page=course_selection" class="<?php echo $page=='course_selection'?'active':''; ?>">線上選課</a>
                        <a href="index.php?page=my_schedule" class="<?php echo $page=='my_schedule'?'active':''; ?>">我的課表</a>
                        <a href="index.php?page=reservation" class="<?php echo $page=='reservation'?'active':''; ?>">空間預約</a>
                        <a href="index.php?page=message" class="<?php echo $page=='message'?'active':''; ?>">聯絡系辦</a>
                    <?php endif; ?>
                    
                    <a href="index.php?page=downloads" class="<?php echo $page=='downloads'?'active':''; ?>">表單下載</a>
                </div>

                <div class="frontend-user-panel">
                    <?php if ($role == 'Student'): ?>
                        <?php
                        // 動態撈取學生真實姓名
                        $uid = intval($_SESSION['user_id']);
                        $s_name = $_SESSION['username'] ?? '同學';
                        $q = $conn->query("SELECT name FROM Students WHERE user_id = $uid");
                        if ($q && $r = $q->fetch_assoc()) $s_name = $r['name'];
                        ?>
                        <div style="color: #fff; font-size: 0.9em; display:flex; align-items:center; gap:10px;">
                            <div style="background: rgba(255,255,255,0.2); padding: 5px 12px; border-radius: 20px;">
                                👤 <?php echo htmlspecialchars($s_name); ?>
                            </div>
                            <a href="index.php?page=change_password" style="color: #fff; text-decoration: none; padding: 4px 8px;">🔑 密碼</a>
                            <a href="logout.php" style="color: #ffc107; font-weight: bold; text-decoration: none; border: 1px solid #ffc107; padding: 4px 10px; border-radius: 4px; transition: 0.2s;" onmouseover="this.style.background='#ffc107'; this.style.color='#1976d2';" onmouseout="this.style.background='transparent'; this.style.color='#ffc107';">登出</a>
                        </div>
                    <?php else: ?>
                        <a href="index.php?page=login" style="background: #fff; color: #1976d2; padding: 8px 20px; border-radius: 25px; text-decoration: none; font-weight: bold; transition: 0.2s; box-shadow: 0 2px 5px rgba(0,0,0,0.2);" onmouseover="this.style.transform='scale(1.05)';" onmouseout="this.style.transform='scale(1)';">系統登入</a>
                    <?php endif; ?>
                </div>
            </div>
        </nav>

        <div class="frontend-content">
            <?php 
            if ($module_path != '' && file_exists($module_path)) {
                include $module_path;
            } else {
                echo "<div class='card' style='border-left: 4px solid #dc3545;'><h2>⚠️ 發生錯誤</h2><p>您請求的頁面不存在，或您沒有權限瀏覽此頁面。</p></div>";
            }
            ?>
        </div>
        
        <footer class="frontend-footer">
            <p style="margin: 0;">&copy; <?php echo date('Y'); ?> 資訊工程學系線上入口網. All rights reserved.</p>
        </footer>
    <?php endif; ?>

</body>
</html>
<?php 
// 🌟 輸出緩衝結束並送出內容
ob_end_flush(); 
?>