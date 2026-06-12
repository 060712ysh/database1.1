﻿<?php
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
            } else {
                $module_path = '';
                $page_name = basename($page); 

                // ⚠️ 這裡把 'home' 加入到公開頁面的陣列裡，讓系統去載入 modules/public/home.php
                if (in_array($page_name, ['home', 'faculty', 'labs', 'teacher_detail', 'downloads'])) {
                    $module_path = 'modules/public/' . $page_name . '.php';
                } else if ($role == 'Admin' && in_array($page_name, ['manage_accounts', 'manage_courses', 'manage_enrollments', 'review_reservations', 'review_messages', 'manage_files', 'view_database', 'teacher_logs'])) {
                    $module_path = 'modules/admin/' . $page_name . '.php';
                } else if ($role == 'Teacher' && in_array($page_name, ['profile', 'syllabus', 'grading'])) {
                    $module_path = 'modules/teacher/' . $page_name . '.php';
                } else if ($role == 'Student' && in_array($page_name, ['course_selection', 'my_schedule', 'reservation', 'message'])) {
                    $module_path = 'modules/student/' . $page_name . '.php';
                }

                // 檢查檔案是否存在並載入
                if ($module_path != '' && file_exists($module_path)) {
                    include $module_path;
                } else {
                    echo "<div class='card' style='border-left: 4px solid #dc3545;'><h2>⚠️ 發生錯誤</h2><p>您請求的頁面不存在，或您沒有權限瀏覽此頁面。</p></div>";
                }
            }
            ?>
        </div>
    </div>
</body>
</html>