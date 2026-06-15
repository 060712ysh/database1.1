﻿<div class="card" style="max-width: 500px; margin: 40px auto;">
    <h2 style="margin-top:0; text-align: center;">🔐 系統登入</h2>
    <form method="POST" action="index.php?page=login">
        <label for="username">帳號：</label>
        <input type="text" id="username" name="username" required placeholder="輸入帳號 (admin/teacher)">
        
        <label for="password">密碼：</label>
        <input type="password" id="password" name="password" required placeholder="輸入密碼 (任意)">
        
        <button type="submit" name="submit" style="width:100%; margin-top: 10px;">登入</button>
        
        <div style="margin-top: 15px; padding: 10px; background: #f0f8ff; border-left: 4px solid #007bff; border-radius: 4px; font-size: 0.9em;">
            <strong>測試帳號：</strong><br>
            <strong>管理員：</strong> admin / admin<br>
            <strong>教師：</strong> t00002 / admin<br>
            <strong>學生：</strong> s00005 / admin<br>
            <em>所有測試帳號密碼均為 admin</em>
        </div>
    </form>
</div>
<?php
if(isset($_POST['submit'])){
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    if (empty($username) || empty($password)) {
        echo "<div class='card' style='background: #fff3cd; border-left: 4px solid #ffc107; margin-top: 10px;'>";
        echo "<strong>⚠️ 錯誤：</strong> 帳號和密碼不能為空";
        echo "</div>";
    } else {
        // 從資料庫查詢帳號
        $stmt = $conn->prepare("SELECT id, username, role, password_hash FROM Users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            // 驗證密碼 (使用簡單比對，正式系統應使用password_verify)
            if ($password == 'admin' || password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                
                // 取得學生或教師ID
                if ($user['role'] == 'Student') {
                    $stmt2 = $conn->prepare("SELECT student_id FROM Students WHERE user_id = ?");
                    $stmt2->bind_param("i", $user['id']);
                    $stmt2->execute();
                    $result2 = $stmt2->get_result();
                    if ($result2->num_rows > 0) {
                        $student = $result2->fetch_assoc();
                        $_SESSION['student_id'] = $student['student_id'];
                    }
                } else if ($user['role'] == 'Teacher') {
                    $stmt2 = $conn->prepare("SELECT teacher_id FROM Teachers WHERE user_id = ?");
                    $stmt2->bind_param("i", $user['id']);
                    $stmt2->execute();
                    $result2 = $stmt2->get_result();
                    if ($result2->num_rows > 0) {
                        $teacher = $result2->fetch_assoc();
                        $_SESSION['teacher_id'] = $teacher['teacher_id'];
                    }
                }
                
                header("Location: index.php");
                exit();
            } else {
                echo "<div class='card' style='background: #fff3cd; border-left: 4px solid #ffc107; margin-top: 10px;'>";
                echo "<strong>⚠️ 錯誤：</strong> 密碼不正確";
                echo "</div>";
            }
        } else {
            echo "<div class='card' style='background: #fff3cd; border-left: 4px solid #ffc107; margin-top: 10px;'>";
            echo "<strong>⚠️ 錯誤：</strong> 帳號不存在";
            echo "</div>";
        }
        $stmt->close();
    }
}
?>
