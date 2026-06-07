﻿﻿<?php
session_start();
require_once 'db_connect.php';

// Check role
$role = isset($_SESSION['role']) ? $_SESSION['role'] : 'Guest';

// Routing
$page = isset($_GET['page']) ? $_GET['page'] : 'home';
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>資工系入口網</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="sidebar">
            <h2>🧭 選單導覽</h2> 
            <?php
            if ($role == 'Admin') {
                include 'includes/sidebar_admin.php';
            } elseif ($role == 'Teacher') {
                include 'includes/sidebar_teacher.php';
            } elseif ($role == 'Student') {
                include 'includes/sidebar_student.php';
            } else {
                include 'includes/sidebar_public.php';
            }
            ?>
        </div>

        <div class="main-content">
            <?php include 'includes/header.php'; ?>

            <div class="content-area">
                <?php
                // Simple router
                if ($page == 'login') {
                    include 'login.php';
                } else if ($page == 'home') {
                    echo "<div class='card'><h2>歡迎瀏覽資工系入口網</h2><p>請從左側選單選擇功能，或點擊右上角登入系統。</p></div>";
                } else {
                    $module_path = '';
                    $page_name = basename($page); 

                    // ⚠️ 這裡加入了 'teacher_detail'，讓系統可以讀取老師專頁
                    if (in_array($page_name, ['faculty', 'labs', 'teacher_detail', 'downloads'])) {
                        $module_path = 'modules/public/' . $page_name . '.php';
                    } else if ($role == 'Admin' && in_array($page_name, ['manage_accounts', 'manage_courses', 'review_reservations', 'review_messages', 'manage_files', 'view_database'])) {
                        $module_path = 'modules/admin/' . $page_name . '.php';
                    } else if ($role == 'Teacher' && in_array($page_name, ['profile', 'syllabus', 'grading'])) {
                        $module_path = 'modules/teacher/' . $page_name . '.php';
                    } else if ($role == 'Student' && in_array($page_name, ['course_selection', 'my_schedule', 'reservation', 'message'])) {
                        $module_path = 'modules/student/' . $page_name . '.php';
                    }

                    if (file_exists($module_path)) {
                        include $module_path;
                    } else {
                        echo "<div class='card'><h2>發生錯誤</h2><p>您請求的頁面不存在，或您沒有權限瀏覽此頁面。</p></div>";
                    }
                }
                ?>
            </div>
        </div>
</body>
</html>
