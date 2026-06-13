-- 資料庫網頁設計 初始化 SQL 腳本
-- 最終極修正版：包含所有進階經歷、選課審核、動態權重、空間節次、最新消息與系統操作日誌

-- =====================================================
-- 1. 建立資料表 Schema
-- =====================================================

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

-- 教師資料表 (包含校內外經歷)
CREATE TABLE Teachers (
    teacher_id INT PRIMARY KEY,
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

-- 系統操作日誌 (由 TeacherLogs 升級為 AdminLogs，記錄管理員核心操作)
CREATE TABLE AdminLogs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL COMMENT '操作者的 Users.id',
    action_type VARCHAR(50) NOT NULL COMMENT '動作分類 (如：空間審核, 最新消息, 帳號建立)',
    description TEXT NOT NULL COMMENT '異動細節',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Users(id) ON DELETE CASCADE
);

-- 學生資料表
CREATE TABLE Students (
    student_id VARCHAR(20) PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    enrollment_year INT,
    FOREIGN KEY (user_id) REFERENCES Users(id) ON DELETE CASCADE
);

-- 課程資料表 (包含專屬加權設定)
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
    weight_assignment INT NOT NULL DEFAULT 30 COMMENT '平時成績權重',
    weight_midterm INT NOT NULL DEFAULT 30 COMMENT '期中成績權重',
    weight_final INT NOT NULL DEFAULT 40 COMMENT '期末成績權重',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES Teachers(teacher_id) ON DELETE SET NULL
);

-- 選課申請表 (加退選審核機制)
CREATE TABLE CourseRequests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(20) NOT NULL,
    course_id INT NOT NULL,
    action ENUM('Add', 'Drop') NOT NULL COMMENT 'Add=加選, Drop=退選',
    status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES Students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES Courses(course_id) ON DELETE CASCADE
);

-- 選課與成績關聯表
CREATE TABLE Enrollments (
    enrollment_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(20),
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
    student_id VARCHAR(20) NOT NULL,
    room_name VARCHAR(50) NOT NULL,
    purpose TEXT NOT NULL,
    reserve_date DATE NOT NULL COMMENT '借用日期',
    start_period INT NOT NULL COMMENT '開始節次 (1-14)',
    end_period INT NOT NULL COMMENT '結束節次 (1-14)',
    status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    reject_reason TEXT DEFAULT NULL,
    FOREIGN KEY (student_id) REFERENCES Students(student_id) ON DELETE CASCADE
);

-- 留言板 (信件工單模式)
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

-- 最新消息資料表 (首頁佈告欄)
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

-- 插入測試帳號 (密碼皆為 123456)
INSERT INTO Users (username, password_hash, role) VALUES
('admin', '$2y$10$N9qo8uLOickgx2ZMRZoHKe6m95iXt/tBKkjlzYF5ZlIzGTlqx7Upu', 'Admin'),
('teacher_wang', '$2y$10$N9qo8uLOickgx2ZMRZoHKe6m95iXt/tBKkjlzYF5ZlIzGTlqx7Upu', 'Teacher'),
('teacher_lin', '$2y$10$N9qo8uLOickgx2ZMRZoHKe6m95iXt/tBKkjlzYF5ZlIzGTlqx7Upu', 'Teacher'),
('teacher_chen', '$2y$10$N9qo8uLOickgx2ZMRZoHKe6m95iXt/tBKkjlzYF5ZlIzGTlqx7Upu', 'Teacher'),
('student_001', '$2y$10$N9qo8uLOickgx2ZMRZoHKe6m95iXt/tBKkjlzYF5ZlIzGTlqx7Upu', 'Student'),
('student_002', '$2y$10$N9qo8uLOickgx2ZMRZoHKe6m95iXt/tBKkjlzYF5ZlIzGTlqx7Upu', 'Student'),
('student_003', '$2y$10$N9qo8uLOickgx2ZMRZoHKe6m95iXt/tBKkjlzYF5ZlIzGTlqx7Upu', 'Student');

-- 管理員基本資料
INSERT INTO Admins (user_id, name, title) VALUES (1, '系統管理員', '系辦主任');

-- 教師資訊 (包含校內外教學經歷)
INSERT INTO Teachers (teacher_id, user_id, name, title, phone, email, office_hours, lab_name, lab_info, teaching_experience, external_experience) VALUES
(1, 2, '王大明', '專任教授', '02-23456789 ext 1234', 'prof.wang@example.edu.tw', '每週一 14:00 - 16:00', '智慧雲端實驗室 (AI Cloud Lab)', '本實驗室致力於深度學習與雲端運算架構之研究，著重於如何透過分散式運算提升 AI 模型訓練效率。', '112學年度 計算機概論\n111學年度 雲端運算實務', '110年-至今 台灣人工智慧協會 理事\n108年-110年 知名科技公司 AI資深顧問'),
(2, 3, '林教授', '副教授', '02-23456789 ext 1235', 'lin.db@example.edu.tw', '每週三 10:00 - 12:00', '資料探勘實驗室 (Data Mining Lab)', '專注於巨量資料分析、推薦系統及資料探勘技術，並與業界合作解決實際場域之數據問題。', '112學年度 資料庫系統\n112學年度 巨量資料分析', NULL),
(3, 4, '陳教授', '助理教授', '02-23456789 ext 1236', 'chen.algo@example.edu.tw', '每週四 15:00 - 17:00', NULL, NULL, '112學年度 演算法', NULL);

-- 學術榮譽紀錄
INSERT INTO AcademicHonors (teacher_id, honor_name, awarding_body, award_year) VALUES
(1, '111年度教學傑出獎', '教育部', 2022),
(1, '最佳學術著作獎', '台灣資訊學會', 2023),
(2, '優秀年輕學者', '國科會', 2021);

-- 著作與計畫資料
INSERT INTO Publications (teacher_id, work_type, title, authors, publish_year) VALUES
(1, '發表期刊論文', '雲端運算架構分析與最佳化', '張三', '2023-05'),
(1, '會議論文', '深度學習在邊緣運算之應用', '李四', '2022-11'),
(1, '國科會計畫', '分散式人工智慧系統開發', '', '2023'),
(2, '國科會計畫', '巨量資料探勘技術研究', '', '2023'),
(2, '發表期刊論文', '基於圖神經網絡之推薦系統', '王五', '2023-08');

-- 系統初期操作日誌測試資料
INSERT INTO AdminLogs (user_id, action_type, description) VALUES
(1, '帳號建立', '系統初始化：成功建立預設管理員與三位教師帳號。'),
(1, '最新消息', '系統初始化：發布了歡迎消息與選課公告。');

-- 學生資訊
INSERT INTO Students (student_id, user_id, name, enrollment_year) VALUES
('B110001', 5, '李小華', 2022),
('B110002', 6, '張偉', 2022),
('B110003', 7, '王小明', 2023);

-- 課程開課資料
INSERT INTO Courses (course_code, course_name, semester, teacher_id, capacity, schedule, room) VALUES
('CS101', '計算機概論', '113-1', 1, 50, '一 2,3,4', '資工系館 R101'),
('CS305', '資料庫系統', '113-1', 2, 40, '二 2,3,4', '資工系館 R102'),
('CS201', '演算法', '113-1', 3, 50, '三 5,6,7', '資工系館 R201');

-- 選課正式紀錄
INSERT INTO Enrollments (student_id, course_id, assignment_scores, midterm_score, final_score, total_score) VALUES
('B110001', 1, 85, 90, 88, 88),
('B110002', 1, 70, 65, 80, 72),
('B110001', 3, NULL, 85, NULL, NULL),
('B110003', 2, 90, 88, 92, 90);

-- 選課申請
INSERT INTO CourseRequests (student_id, course_id, action, status) VALUES
('B110003', 1, 'Add', 'Pending'),
('B110002', 1, 'Drop', 'Pending');

-- 最新消息
INSERT INTO News (title, content, image_path) VALUES
('歡迎來到資工系新版入口網！', '本系統已全面升級，提供最新師資陣容、實驗室資訊，以及更便利的學生線上選課系統。', NULL),
('113學年度第1學期 選課公告', '請各位同學注意，本學期加退選時間將於下週五截止，請盡速登入後至「線上自主選課系統」提交申請，逾期不予受理。', NULL);