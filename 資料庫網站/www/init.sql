-- 資料庫網頁設計 初始化 SQL 腳本
-- 最終極修正版：自動配發學號(sXXXXX)/教師代號(tXXXXX)格式之全表格對齊腳本

-- =====================================================
-- 1. 建立資料表 Schema
-- =====================================================

-- 系統設定表 (控制全域開關)
CREATE TABLE SystemSettings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value VARCHAR(255) NOT NULL,
    description TEXT
);

-- 用戶/角色資料表
CREATE TABLE Users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('Admin', 'Teacher', 'Student') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 專屬管理員資料表
CREATE TABLE Admins (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    title VARCHAR(100),
    FOREIGN KEY (user_id) REFERENCES Users(id) ON DELETE CASCADE
);

-- 教師資料表
CREATE TABLE Teachers (
    teacher_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    title VARCHAR(100),
    avatar_path VARCHAR(255) DEFAULT NULL COMMENT '老師頭像圖片路徑',
    department VARCHAR(100) DEFAULT '資訊工程學系',
    phone VARCHAR(20),
    extension VARCHAR(10) DEFAULT NULL COMMENT '分機號碼', -- ✨ 新增的分機號碼欄位
    email VARCHAR(100),
    office_hours VARCHAR(100) DEFAULT NULL COMMENT '請益時間',
    lab_name VARCHAR(100) DEFAULT NULL COMMENT '實驗室名稱',
    lab_info TEXT DEFAULT NULL COMMENT '實驗室簡介與研究方向',
    teaching_experience TEXT DEFAULT NULL COMMENT '校內教學經歷',
    external_experience TEXT DEFAULT NULL COMMENT '校外經歷',
    FOREIGN KEY (user_id) REFERENCES Users(id) ON DELETE CASCADE
);

-- 學術榮譽資料表
CREATE TABLE AcademicHonors (
    honor_id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    honor_name VARCHAR(255) NOT NULL,
    awarding_body VARCHAR(255),
    award_year INT,
    FOREIGN KEY (teacher_id) REFERENCES Teachers(teacher_id) ON DELETE CASCADE
);

-- 著作與參與計畫資料表
CREATE TABLE Publications (
    work_id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    work_type VARCHAR(100) NOT NULL,
    title VARCHAR(255) NOT NULL,
    authors TEXT COMMENT '其他作者(不含教師本人)',
    publish_year VARCHAR(50),
    FOREIGN KEY (teacher_id) REFERENCES Teachers(teacher_id) ON DELETE CASCADE
);

-- 系統操作日誌
CREATE TABLE AdminLogs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action_type VARCHAR(50) NOT NULL,
    description TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Users(id) ON DELETE CASCADE
);

-- 教師操作日誌 (配合 profile.php 紀錄異動)
CREATE TABLE TeacherLogs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    action_type VARCHAR(50) NOT NULL,
    description TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES Teachers(teacher_id) ON DELETE CASCADE
);

-- 學生資料表 (學號長度擴大以與登入帳號一致)
CREATE TABLE Students (
    student_id VARCHAR(50) PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    enrollment_year INT,
    FOREIGN KEY (user_id) REFERENCES Users(id) ON DELETE CASCADE
);

-- 課程資料表
CREATE TABLE Courses (
    course_id INT AUTO_INCREMENT PRIMARY KEY,
    course_code VARCHAR(20) NOT NULL,
    course_name VARCHAR(100) NOT NULL,
    semester VARCHAR(10) NOT NULL,
    teacher_id INT,
    capacity INT NOT NULL DEFAULT 50,
    schedule VARCHAR(100),
    room VARCHAR(50) DEFAULT NULL,
    syllabus TEXT,
    weight_assignment INT NOT NULL DEFAULT 30,
    weight_midterm INT NOT NULL DEFAULT 30,
    weight_final INT NOT NULL DEFAULT 40,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES Teachers(teacher_id) ON DELETE SET NULL
);

-- 選課申請表
CREATE TABLE CourseRequests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) NOT NULL,
    course_id INT NOT NULL,
    action ENUM('Add', 'Drop') NOT NULL,
    status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES Students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES Courses(course_id) ON DELETE CASCADE
);

-- 選課與成績關聯表
CREATE TABLE Enrollments (
    enrollment_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50),
    course_id INT,
    assignment_scores DECIMAL(5, 2) DEFAULT NULL,
    midterm_score DECIMAL(5, 2) DEFAULT NULL,
    final_score DECIMAL(5, 2) DEFAULT NULL,
    total_score DECIMAL(5, 2) DEFAULT NULL,
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY(student_id, course_id),
    FOREIGN KEY (student_id) REFERENCES Students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES Courses(course_id) ON DELETE CASCADE
);

-- 預約空間紀錄表
CREATE TABLE Reservations (
    reservation_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) NOT NULL,
    room_name VARCHAR(50) NOT NULL,
    purpose TEXT NOT NULL,
    reserve_date DATE NOT NULL,
    start_period INT NOT NULL,
    end_period INT NOT NULL,
    status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    reject_reason TEXT DEFAULT NULL,
    FOREIGN KEY (student_id) REFERENCES Students(student_id) ON DELETE CASCADE
);

-- 留言板
CREATE TABLE Messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    content TEXT NOT NULL,
    reply_content TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_public BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (sender_id) REFERENCES Users(id) ON DELETE CASCADE
);

-- 系統檔案紀錄表
CREATE TABLE SystemFiles (
    file_id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    remark TEXT,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 最新消息資料表
CREATE TABLE News (
    news_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    image_path VARCHAR(255) DEFAULT NULL,
    sort_order INT DEFAULT 0 COMMENT '顯示順序(數字越小越前面)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 🏫 全校教室與討論室空間管理表
CREATE TABLE Rooms (
    room_id INT AUTO_INCREMENT PRIMARY KEY,
    room_name VARCHAR(100) NOT NULL UNIQUE COMMENT '教室或討論室名稱',
    room_type ENUM('上課教室', '討論室') NOT NULL COMMENT '空間分類',
    capacity INT DEFAULT 0 COMMENT '容納人數'
);

-- =====================================================
-- 2. 預設資料插入
-- =====================================================

-- 插入系統初始設定 (加退選預設為開放)
-- 插入系統初始設定 
INSERT INTO SystemSettings (setting_key, setting_value, description) VALUES
('enrollment_status', 'open', '加退選開放狀態 (open/closed)'),
('system_name', '資工系線上入口網', '系統預設名稱'),
('semester_current', '113-1', '當前學期設定'),
('max_credits', '25', '學生學期學分上限'),
('maintenance_mode', 'false', '系統維護模式開關');

-- 🚀 使用密碼 '123456' 的 Hash 值，建立足量帳號
INSERT INTO Users (id, username, password_hash, role) VALUES
(1, 'admin', '$2y$10$N9qo8uLOickgx2ZMRZoHKe6m95iXt/tBKkjlzYF5ZlIzGTlqx7Upu', 'Admin'),
(2, 'a00001', '$2y$10$N9qo8uLOickgx2ZMRZoHKe6m95iXt/tBKkjlzYF5ZlIzGTlqx7Upu', 'Admin'),
(3, 'a00002', '$2y$10$N9qo8uLOickgx2ZMRZoHKe6m95iXt/tBKkjlzYF5ZlIzGTlqx7Upu', 'Admin'),
(4, 'a00003', '$2y$10$N9qo8uLOickgx2ZMRZoHKe6m95iXt/tBKkjlzYF5ZlIzGTlqx7Upu', 'Admin'),
(5, 'a00004', '$2y$10$N9qo8uLOickgx2ZMRZoHKe6m95iXt/tBKkjlzYF5ZlIzGTlqx7Upu', 'Admin'),
(6, 't00001', '$2y$10$N9qo8uLOickgx2ZMRZoHKe6m95iXt/tBKkjlzYF5ZlIzGTlqx7Upu', 'Teacher'),
(7, 't00002', '$2y$10$N9qo8uLOickgx2ZMRZoHKe6m95iXt/tBKkjlzYF5ZlIzGTlqx7Upu', 'Teacher'),
(8, 't00003', '$2y$10$N9qo8uLOickgx2ZMRZoHKe6m95iXt/tBKkjlzYF5ZlIzGTlqx7Upu', 'Teacher'),
(9, 't00004', '$2y$10$N9qo8uLOickgx2ZMRZoHKe6m95iXt/tBKkjlzYF5ZlIzGTlqx7Upu', 'Teacher'),
(10, 't00005', '$2y$10$N9qo8uLOickgx2ZMRZoHKe6m95iXt/tBKkjlzYF5ZlIzGTlqx7Upu', 'Teacher'),
(11, 't00006', '$2y$10$N9qo8uLOickgx2ZMRZoHKe6m95iXt/tBKkjlzYF5ZlIzGTlqx7Upu', 'Teacher'),
(12, 's00001', '$2y$10$N9qo8uLOickgx2ZMRZoHKe6m95iXt/tBKkjlzYF5ZlIzGTlqx7Upu', 'Student'),
(13, 's00002', '$2y$10$N9qo8uLOickgx2ZMRZoHKe6m95iXt/tBKkjlzYF5ZlIzGTlqx7Upu', 'Student'),
(14, 's00003', '$2y$10$N9qo8uLOickgx2ZMRZoHKe6m95iXt/tBKkjlzYF5ZlIzGTlqx7Upu', 'Student'),
(15, 's00004', '$2y$10$N9qo8uLOickgx2ZMRZoHKe6m95iXt/tBKkjlzYF5ZlIzGTlqx7Upu', 'Student'),
(16, 's00005', '$2y$10$N9qo8uLOickgx2ZMRZoHKe6m95iXt/tBKkjlzYF5ZlIzGTlqx7Upu', 'Student');

-- 管理員資料
INSERT INTO Admins (user_id, name, title) VALUES 
(1, '系統主管理員', '系辦主任'),
(2, '王秘書', '行政專員'),
(3, '李助教', '系統維護員'),
(4, '林工讀生', '系辦工讀生'),
(5, '張專員', '教務管理員');

-- 教師資料 (匯入圖片中的真實教授名單，並遵守 # 分機格式)
INSERT INTO Teachers (teacher_id, user_id, name, title, phone, extension, email, office_hours, lab_name, lab_info, teaching_experience) VALUES
(1, 6, '張志宏', '專任教授', '04-24517250', '#3701', 'chchang@example.edu.tw', '每週三 14:00 - 16:00', '系統架構實驗室', '致力於作業系統效能最佳化。', '作業系統(一)'),
(2, 7, '王益文', '副教授', '04-24517250', '#3702', 'ywwang@example.edu.tw', '每週四 10:00 - 12:00', '嵌入式系統實驗室', '專注於微處理機開發。', '微處理機系統、微處理機系統實習'),
(3, 8, '薛念林', '專任教授', '04-24517250', '#3703', 'nlshe@example.edu.tw', '每週五 15:00 - 17:00', '軟體工程實驗室', '軟體測試與品質保證研究。', '軟體測試'),
(4, 9, '李俊宏', '副教授', '04-24517250', '#3704', 'chlee@example.edu.tw', '每週二 13:00 - 15:00', '資料探勘實驗室', '資料探勘與軟體開發實務。', '軟體工程開發實務、資料探勘導論'),
(5, 10, '蔡明翰', '助理教授', '04-24517250', '#3705', 'mhtasi@example.edu.tw', '每週二 09:00 - 11:00', '多媒體運算實驗室', '數位影像處理技術研究。', '數位影像處理'),
(6, 11, '陳烈武', '副教授', '04-24517250', '#3706', 'lwchen@example.edu.tw', '每週一 14:00 - 16:00', '網路通訊實驗室', '寬頻網路與資安研究。', '網路程式設計');

-- 學生資料
INSERT INTO Students (student_id, user_id, name, enrollment_year) VALUES
('s00001', 12, '鄧向勛', 2024),
('s00002', 13, '張小偉', 2024),
('s00003', 14, '林美玲', 2023),
('s00004', 15, '陳建宏', 2023),
('s00005', 16, '李雅婷', 2022);

-- 插入教室與討論室資料 (匯入圖片中的真實上課教室)
INSERT INTO Rooms (room_name, room_type, capacity) VALUES
('V003教室(共善樓)', '上課教室', 80),
('資電B02', '上課教室', 70),
('資電118(電腦實習室)', '上課教室', 60),
('資電234(電腦實習室)', '上課教室', 60),
('資電330(電腦實習室)', '上課教室', 60),
('資電504', '上課教室', 50),
('資電114', '上課教室', 50),
('討論室 101 (4人)', '討論室', 4),
('討論室 102 (4人)', '討論室', 4),
('討論室 103 (6人)', '討論室', 6),
('討論室 201 (8人)', '討論室', 8),
('討論室 202 (10人)', '討論室', 10);

-- 課程開課資料 (匯入圖片中的課程，將節次簡化為單日以利課表網格渲染)
INSERT INTO Courses (course_code, course_name, semester, teacher_id, capacity, schedule, room) VALUES
('IECS3001', '作業系統(一)', '113-1', 1, 60, '三 3,4', '資電B02'),
('IECS2012', '微處理機系統', '113-1', 2, 60, '四 3,4', 'V003教室(共善樓)'),
('IECS2013', '微處理機系統實習', '113-1', 2, 60, '五 8,9,10', '資電118(電腦實習室)'),
('IECS3045', '軟體測試', '113-1', 3, 60, '五 2,3,4', '資電118(電腦實習室)'),
('IECS3046', '軟體工程開發實務', '113-1', 4, 60, '二 6,7,8', '資電234(電腦實習室)'),
('IECS3047', '資料探勘導論', '113-1', 4, 60, '三 6,7,8', '資電330(電腦實習室)'),
('IECS4011', '數位影像處理', '113-1', 5, 60, '二 2,3,4', 'V003教室(共善樓)'),
('IECS3042', '網路程式設計', '113-1', 6, 60, '一 11,12', '資電118(電腦實習室)');

-- 學術榮譽預設資料
INSERT INTO AcademicHonors (teacher_id, honor_name, awarding_body, award_year) VALUES
(1, '111年度教學傑出獎', '教育部', 2022),
(2, '優秀年輕學者研究計畫', '國科會', 2021),
(3, '第28屆大專校院資訊應用服務創新競賽-特優', '台北市電腦公會', 2023),
(4, '產學合作績優獎', '逢甲大學', 2023),
(5, 'Best Presentation Award', 'ICIET 2024', 2024),
(6, '優良導師獎', '逢甲大學', 2022);

-- 著作與參與計畫預設資料
INSERT INTO Publications (teacher_id, work_type, title, authors, publish_year) VALUES
(1, '發表期刊論文', '作業系統核心之效能分析與最佳化', '張三、李四', '2023-05'),
(2, '國科會計畫', '微處理機低功耗架構研究', '', '2023'),
(3, '產學合作計畫', '智慧醫療大數據平台在藥局的應用', '大樹藥局', '2024'),
(4, '會議論文', '以資料探勘預測學生學習成效', '林五', '2023-11'),
(5, '校外獎勵及指導學生獲獎', '第十屆 Apple 移動應用創新賽', '李柏森', '2025'),
(6, '會議論文', '寬頻網路資安防禦機制', '', '2022-08');

-- 選課正式紀錄 
INSERT INTO Enrollments (student_id, course_id, assignment_scores, midterm_score, final_score, total_score) VALUES
('s00001', 1, 85, 90, 88, 88),
('s00001', 2, 70, 65, 80, 72),
('s00001', 3, 90, 85, 95, 90),
('s00002', 4, 88, 77, 85, 83),
('s00003', 5, 92, 88, 90, 90),
('s00004', 6, 75, 80, 78, 78);

-- 選課申請紀錄 (加退選審核中)
INSERT INTO CourseRequests (student_id, course_id, action, status) VALUES
('s00001', 4, 'Add', 'Pending'),
('s00002', 1, 'Add', 'Pending'),
('s00003', 2, 'Add', 'Pending'),
('s00004', 3, 'Add', 'Pending'),
('s00005', 5, 'Add', 'Approved'),
('s00005', 6, 'Drop', 'Rejected');

-- 空間預約紀錄
INSERT INTO Reservations (student_id, room_name, purpose, reserve_date, start_period, end_period, status, reject_reason) VALUES
('s00001', '討論室 101 (4人)', '專題小組討論', DATE_ADD(CURDATE(), INTERVAL 2 DAY), 3, 5, 'Approved', NULL),
('s00002', '討論室 103 (6人)', '期中考複習', DATE_ADD(CURDATE(), INTERVAL 3 DAY), 7, 8, 'Pending', NULL),
('s00003', '討論室 102 (4人)', '競賽討論', DATE_ADD(CURDATE(), INTERVAL 1 DAY), 2, 4, 'Approved', NULL),
('s00004', '討論室 201 (8人)', '系學會開會', DATE_ADD(CURDATE(), INTERVAL 5 DAY), 10, 12, 'Rejected', '該時段設備維護中'),
('s00005', '討論室 202 (10人)', '課外研討', DATE_ADD(CURDATE(), INTERVAL 4 DAY), 5, 6, 'Pending', NULL),
('s00001', '討論室 101 (4人)', '專題報告演練', DATE_ADD(CURDATE(), INTERVAL 6 DAY), 6, 8, 'Pending', NULL);

-- 留言板預設資料
INSERT INTO Messages (sender_id, content, reply_content, is_public) VALUES
(12, '請問下學期「資料庫系統」會開設進階班嗎？', '目前系辦正在統計修課意願，預計下個月中旬會公告。', TRUE),
(13, '教室的冷氣好像不涼了。', '已通知工友前往查看，謝謝回報！', TRUE),
(14, '選課系統的密碼忘記了怎麼辦？', '請帶著學生證至系辦由專人為您重設為 123456。', FALSE),
(15, '請問獎學金申請到什麼時候？', '截止日期為下週五下午五點，請盡快繳交書面資料。', TRUE),
(16, '這學期的體育課可以抵免嗎？', '請參閱教務處最新抵免規章。', TRUE),
(12, '系統很好用！', '感謝同學的回饋！', TRUE);

-- 最新消息預設資料
INSERT INTO News (title, content, image_path, sort_order) VALUES
('🎉 歡迎來到資工系全新升級版入口網！', '提供更清晰的師資陣容，以及全新圖形化介面的選課與空間預約系統！', NULL, 1),
('📅 113學年度第1學期 選課時程公告', '初選時間為 9/1 至 9/10，請同學務必留意時間。', NULL, 2),
('🏆 狂賀！本系教授榮獲教學傑出獎', '恭喜張志宏教授榮獲本校教學傑出獎。', NULL, 3),
('🔧 系統維護公告 (預定於週末進行)', '本週末將進行主機升級，選課系統將暫停服務 4 小時。', NULL, 4),
('💼 企業實習說明會開始報名', '台積電與聯發科實習說明會，請大三同學踴躍參加。', NULL, 5),
('🎓 畢業專題展 即將盛大開幕', '誠摯邀請系上師生一同來參與本屆大四學生的心血結晶展示！', NULL, 6);

-- 系統初期操作日誌測試資料
INSERT INTO AdminLogs (user_id, action_type, description) VALUES
(1, '系統設定', '系統初始化：加退選功能預設為 [開放]。'),
(1, '帳號建立', '匯入 113-1 學期資工系教授與學生名單。'),
(2, '最新消息', '發布了選課時程公告。'),
(3, '空間異動', '新增了共善樓與資電館的電腦實習教室。'),
(4, '留言回覆', '回覆了關於冷氣報修的學生留言。'),
(5, '帳號建立', '手動建立了一批新的系統管理員帳號。');