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
    department VARCHAR(100) DEFAULT '資訊工程學系',
    phone VARCHAR(20),
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- 2. 預設資料插入
-- =====================================================

-- 插入系統初始設定 (加退選預設為開放)
INSERT INTO SystemSettings (setting_key, setting_value, description) VALUES
('enrollment_status', 'open', '加退選開放狀態 (open/closed)');

-- 🚀 修正：預設帳號與流水號對齊 (密碼皆為 123456)
-- 系統主管理員維持為 admin 方便登入，其餘依身分配發代號
INSERT INTO Users (id, username, password_hash, role) VALUES
(1, 'admin', '$2y$10$N9qo8uLOickgx2ZMRZoHKe6m95iXt/tBKkjlzYF5ZlIzGTlqx7Upu', 'Admin'),
(2, 't00002', '$2y$10$N9qo8uLOickgx2ZMRZoHKe6m95iXt/tBKkjlzYF5ZlIzGTlqx7Upu', 'Teacher'),
(3, 't00003', '$2y$10$N9qo8uLOickgx2ZMRZoHKe6m95iXt/tBKkjlzYF5ZlIzGTlqx7Upu', 'Teacher'),
(4, 't00004', '$2y$10$N9qo8uLOickgx2ZMRZoHKe6m95iXt/tBKkjlzYF5ZlIzGTlqx7Upu', 'Teacher'),
(5, 's00005', '$2y$10$N9qo8uLOickgx2ZMRZoHKe6m95iXt/tBKkjlzYF5ZlIzGTlqx7Upu', 'Student'),
(6, 's00006', '$2y$10$N9qo8uLOickgx2ZMRZoHKe6m95iXt/tBKkjlzYF5ZlIzGTlqx7Upu', 'Student'),
(7, 's00007', '$2y$10$N9qo8uLOickgx2ZMRZoHKe6m95iXt/tBKkjlzYF5ZlIzGTlqx7Upu', 'Student');

-- 管理員資料
INSERT INTO Admins (user_id, name, title) VALUES (1, '系統管理員', '系辦主任');

-- 教師資料
INSERT INTO Teachers (teacher_id, user_id, name, title, phone, email, office_hours, lab_name, lab_info, teaching_experience, external_experience) VALUES
(1, 2, '王大明', '專任教授', '02-23456789 ext 1234', 'prof.wang@example.edu.tw', '每週一 14:00 - 16:00', '智慧雲端實驗室', '本實驗室致力於深度學習與雲端運算研究。', '112學年度 計算機概論', '110年-至今 台灣人工智慧協會 理事'),
(2, 3, '林教授', '副教授', '02-23456789 ext 1235', 'lin.db@example.edu.tw', '每週三 10:00 - 12:00', '資料探勘實驗室', '專注於巨量資料分析與推薦系統。', '112學年度 資料庫系統', NULL),
(3, 4, '陳教授', '助理教授', '02-23456789 ext 1236', 'chen.algo@example.edu.tw', '每週開 15:00 - 17:00', NULL, NULL, '112學年度 演算法', NULL);

-- 學生資料 (🚀 學號與登入代號完美對齊)
INSERT INTO Students (student_id, user_id, name, enrollment_year) VALUES
('s00005', 5, '李小華', 2022),
('s00006', 6, '張偉', 2022),
('s00007', 7, '王小明', 2023);

-- 學術榮譽預設資料
INSERT INTO AcademicHonors (teacher_id, honor_name, awarding_body, award_year) VALUES
(1, '111年度教學傑出獎', '教育部', 2022),
(2, '優秀年輕學者研究計畫', '國科會', 2021);

-- 著作與參與計畫預設資料
INSERT INTO Publications (teacher_id, work_type, title, authors, publish_year) VALUES
(1, '發表期刊論文', '雲端運算架構分析與最佳化', '張三、李四', '2023-05'),
(2, '國科會計畫', '巨量資料探勘技術研究', '', '2023');

-- 空間預約紀錄預設資料 (🚀 連動學號變更)
INSERT INTO Reservations (student_id, room_name, purpose, reserve_date, start_period, end_period, status) VALUES
('s00005', '討論室 101 (4人)', '專題小組討論與進度報告', DATE_ADD(CURDATE(), INTERVAL 2 DAY), 3, 5, 'Approved'),
('s00006', '討論室 103 (6人)', '演算法期中考複習讀書會', DATE_ADD(CURDATE(), INTERVAL 3 DAY), 7, 8, 'Pending');

-- 留言板預設資料
INSERT INTO Messages (sender_id, content, reply_content, is_public) VALUES
(5, '請問下學期「資料庫系統」會開設進階班嗎？', '目前系辦正在統計修課意願，預計下個月中旬會公告。', TRUE);

-- 最新消息預設資料
INSERT INTO News (title, content, image_path) VALUES
('🎉 歡迎來到資工系全新升級版入口網！', '提供更清晰的師資陣容，以及全新圖形化介面的選課與空間預約系統！', NULL);

-- 課程開課資料
INSERT INTO Courses (course_code, course_name, semester, teacher_id, capacity, schedule, room) VALUES
('CS101', '計算機概論', '113-1', 1, 50, '一 2,3,4', '資工系館 R101'),
('CS305', '資料庫系統', '113-1', 2, 40, '二 2,3,4', '資工系館 R102'),
('CS201', '演算法', '113-1', 3, 50, '三 5,6,7', '資工系館 R201');

-- 選課正式紀錄 (🚀 連動學號變更)
INSERT INTO Enrollments (student_id, course_id, assignment_scores, midterm_score, final_score, total_score) VALUES
('s00005', 1, 85, 90, 88, 88),
('s00006', 1, 70, 65, 80, 72),
('s00007', 2, 90, 88, 92, 90);

-- 選課申請紀錄 (🚀 連動學號變更)
INSERT INTO CourseRequests (student_id, course_id, action, status) VALUES
('s00007', 1, 'Add', 'Pending');

-- 系統初期操作日誌測試資料
INSERT INTO AdminLogs (user_id, action_type, description) VALUES
(1, '系統設定', '系統初始化：加退選功能預設為 [開放]。');