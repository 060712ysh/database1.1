-- 資料庫網頁設計 初始化 SQL 腳本
-- 根據 readme.md 規範設計 Schema

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

-- 教師資料表 (已移除舊版的文字欄位，改用關聯表)
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
    FOREIGN KEY (user_id) REFERENCES Users(id) ON DELETE CASCADE
);

-- 1. 學術榮譽資料表
CREATE TABLE AcademicHonors (
    honor_id INT AUTO_INCREMENT PRIMARY KEY COMMENT '榮譽編號',
    teacher_id INT NOT NULL COMMENT '教師編號',
    honor_name VARCHAR(255) NOT NULL COMMENT '獎項名稱',
    awarding_body VARCHAR(255) COMMENT '頒發機構',
    award_year INT COMMENT '獲獎年份',
    FOREIGN KEY (teacher_id) REFERENCES Teachers(teacher_id) ON DELETE CASCADE
);

-- 2. 著作與參與計畫資料表
CREATE TABLE Publications (
    work_id INT AUTO_INCREMENT PRIMARY KEY COMMENT '著作編號',
    teacher_id INT NOT NULL COMMENT '教師編號',
    work_type VARCHAR(100) NOT NULL COMMENT '著作類型 (如：期刊論文、會議論文...)',
    title VARCHAR(255) NOT NULL COMMENT '標題',
    authors TEXT COMMENT '作者',
    publish_year VARCHAR(50) COMMENT '發表日期/出版年份',
    FOREIGN KEY (teacher_id) REFERENCES Teachers(teacher_id) ON DELETE CASCADE
);

-- 學生資料表
CREATE TABLE Students (
    student_id VARCHAR(20) PRIMARY KEY, -- 學號
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
    semester VARCHAR(10) NOT NULL, -- 如：113-1
    teacher_id INT,
    capacity INT NOT NULL DEFAULT 50, -- 修課人數上限
    schedule VARCHAR(100), -- 上課時間
    room VARCHAR(50) DEFAULT NULL COMMENT '上課教室',
    syllabus TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES Teachers(teacher_id) ON DELETE SET NULL
);

-- 選課與成績關聯表 (橋接表)
CREATE TABLE Enrollments (
    enrollment_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(20),
    course_id INT,
    assignment_scores DECIMAL(5, 2) DEFAULT NULL, -- 平時作業
    midterm_score DECIMAL(5, 2) DEFAULT NULL, -- 期中成績
    final_score DECIMAL(5, 2) DEFAULT NULL, -- 期末成績
    total_score DECIMAL(5, 2) DEFAULT NULL, -- 總成績
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY(student_id, course_id),
    FOREIGN KEY (student_id) REFERENCES Students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES Courses(course_id) ON DELETE CASCADE
);

-- 預約教室紀錄
CREATE TABLE Reservations (
    reservation_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(20) NOT NULL,
    room_name VARCHAR(50) NOT NULL,
    purpose TEXT NOT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    reject_reason TEXT DEFAULT NULL COMMENT '拒絕理由',
    FOREIGN KEY (student_id) REFERENCES Students(student_id) ON DELETE CASCADE
);

-- 留言板
CREATE TABLE Messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL, -- 參考 Users.id
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
    remark TEXT COMMENT '檔案備註說明',
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- 預設資料插入
-- =====================================================

-- 插入測試帳號
INSERT INTO Users (username, password_hash, role) VALUES
('admin', '$2y$10$N9qo8uLOickgx2ZMRZoHKe6m95iXt/tBKkjlzYF5ZlIzGTlqx7Upu', 'Admin'),
('teacher_wang', '$2y$10$N9qo8uLOickgx2ZMRZoHKe6m95iXt/tBKkjlzYF5ZlIzGTlqx7Upu', 'Teacher'),
('teacher_lin', '$2y$10$N9qo8uLOickgx2ZMRZoHKe6m95iXt/tBKkjlzYF5ZlIzGTlqx7Upu', 'Teacher'),
('teacher_chen', '$2y$10$N9qo8uLOickgx2ZMRZoHKe6m95iXt/tBKkjlzYF5ZlIzGTlqx7Upu', 'Teacher'),
('student_001', '$2y$10$N9qo8uLOickgx2ZMRZoHKe6m95iXt/tBKkjlzYF5ZlIzGTlqx7Upu', 'Student'),
('student_002', '$2y$10$N9qo8uLOickgx2ZMRZoHKe6m95iXt/tBKkjlzYF5ZlIzGTlqx7Upu', 'Student'),
('student_003', '$2y$10$N9qo8uLOickgx2ZMRZoHKe6m95iXt/tBKkjlzYF5ZlIzGTlqx7Upu', 'Student');

-- 幫系統預設的 admin 帳號 (user_id=1) 補上資料
INSERT INTO Admins (user_id, name, title) VALUES (1, '系統管理員', '系辦主任');

-- 插入教師資訊
INSERT INTO Teachers (teacher_id, user_id, name, title, phone, email, office_hours, lab_name, lab_info) VALUES
(1, 2, '王大明', '專任教授', '02-23456789 ext 1234', 'prof.wang@example.edu.tw', '每週一 14:00 - 16:00', '智慧雲端實驗室 (AI Cloud Lab)', '本實驗室致力於深度學習與雲端運算架構之研究，著重於如何透過分散式運算提升 AI 模型訓練效率。'),
(2, 3, '林教授', '副教授', '02-23456789 ext 1235', 'lin.db@example.edu.tw', '每週三 10:00 - 12:00', '資料探勘實驗室 (Data Mining Lab)', '專注於巨量資料分析、推薦系統及資料探勘技術，並與業界合作解決實際場域之數據問題。'),
(3, 4, '陳教授', '助理教授', '02-23456789 ext 1236', 'chen.algo@example.edu.tw', '每週四 15:00 - 17:00', NULL, NULL);

-- 插入學術榮譽測試資料
INSERT INTO AcademicHonors (teacher_id, honor_name, awarding_body, award_year) VALUES
(1, '111年度教學傑出獎', '教育部', 2022),
(1, '最佳學術著作獎', '台灣資訊學會', 2023),
(2, '優秀年輕學者', '國科會', 2021);

-- 插入著作與計畫測試資料 (只保留其他作者)
INSERT INTO Publications (teacher_id, work_type, title, authors, publish_year) VALUES
(1, '發表期刊論文', '雲端運算架構分析與最佳化', '張三', '2023-05'),
(1, '會議論文', '深度學習在邊緣運算之應用', '李四', '2022-11'),
(1, '國科會計畫', '分散式人工智慧系統開發', '', '2023'),
(2, '國科會計畫', '巨量資料探勘技術研究', '', '2023'),
(2, '發表期刊論文', '基於圖神經網絡之推薦系統', '王五', '2023-08');

-- 插入學生資訊
INSERT INTO Students (student_id, user_id, name, enrollment_year) VALUES
('B110001', 5, '李小華', 2022),
('B110002', 6, '張偉', 2022),
('B110003', 7, '王小明', 2023);

-- 插入課程資訊
INSERT INTO Courses (course_code, course_name, semester, teacher_id, capacity, schedule, room) VALUES
('CS101', '計算機概論', '113-1', 1, 50, '一 2,3,4', '資工系館 R101'),
('CS305', '資料庫系統', '113-1', 2, 40, '二 2,3,4', '資工系館 R102'),
('CS201', '演算法', '113-1', 3, 50, '三 5,6,7', '資工系館 R201');

-- 插入選課紀錄
INSERT INTO Enrollments (student_id, course_id, assignment_scores, midterm_score, final_score, total_score) VALUES
('B110001', 1, 85, 90, 88, 88),
('B110002', 1, 70, 65, 80, 72),
('B110001', 3, NULL, 85, NULL, NULL),
('B110003', 2, 90, 88, 92, 90);