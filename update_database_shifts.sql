-- =====================================================
-- UPDATE DATABASE UNTUK SISTEM SHIFT JAM KERJA
-- =====================================================
-- Menambahkan tabel untuk master data shift dan update tabel user
-- =====================================================

USE absensi_face;

-- =====================================================
-- 1. TABEL MASTER SHIFT
-- =====================================================
CREATE TABLE IF NOT EXISTS tbl_shifts (
    id INT(11) NOT NULL AUTO_INCREMENT,
    shift_name VARCHAR(50) NOT NULL,
    department VARCHAR(50) NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    is_overnight TINYINT(1) DEFAULT 0 COMMENT '1 jika shift melewati tengah malam',
    tolerance_minutes INT DEFAULT 15 COMMENT 'Toleransi keterlambatan dalam menit',
    description TEXT DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_department (department),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 2. UPDATE TABEL USER - TAMBAH KOLOM SHIFT
-- =====================================================
ALTER TABLE tbl_user 
ADD COLUMN shift_id INT(11) DEFAULT NULL AFTER jabatan,
ADD FOREIGN KEY (shift_id) REFERENCES tbl_shifts(id) ON DELETE SET NULL ON UPDATE CASCADE;

-- =====================================================
-- 3. TABEL LOG ABSENSI DETAIL
-- =====================================================
CREATE TABLE IF NOT EXISTS tbl_attendance_log (
    id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    shift_id INT(11) NOT NULL,
    attendance_date DATE NOT NULL,
    check_in DATETIME DEFAULT NULL,
    check_out DATETIME DEFAULT NULL,
    status ENUM('on_time', 'late', 'absent', 'early_out') DEFAULT 'absent',
    late_minutes INT DEFAULT 0,
    work_hours DECIMAL(4,2) DEFAULT 0,
    notes TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (user_id) REFERENCES tbl_user(id) ON DELETE CASCADE,
    FOREIGN KEY (shift_id) REFERENCES tbl_shifts(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_date (user_id, attendance_date),
    INDEX idx_date (attendance_date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 4. INSERT DATA SHIFT BERDASARKAN PERMINTAAN
-- =====================================================

-- SHIFT NAKES
INSERT INTO tbl_shifts (shift_name, department, start_time, end_time, is_overnight, tolerance_minutes, description) VALUES
('Pagi', 'Nakes', '08:00:00', '14:00:00', 0, 15, 'Shift pagi untuk tenaga kesehatan'),
('Sore', 'Nakes', '14:00:00', '21:00:00', 0, 15, 'Shift sore untuk tenaga kesehatan'),
('Malam', 'Nakes', '21:00:00', '08:00:00', 1, 15, 'Shift malam untuk tenaga kesehatan (melewati tengah malam)');

-- JAM KANTOR
INSERT INTO tbl_shifts (shift_name, department, start_time, end_time, is_overnight, tolerance_minutes, description) VALUES
('Kantor Full', 'Kantor', '08:00:00', '16:00:00', 0, 10, 'Jam kerja kantor penuh 8 jam'),
('Kantor Half', 'Kantor', '08:00:00', '12:00:00', 0, 10, 'Jam kerja kantor setengah hari');

-- CLEANING
INSERT INTO tbl_shifts (shift_name, department, start_time, end_time, is_overnight, tolerance_minutes, description) VALUES
('Pagi', 'Cleaning', '07:00:00', '15:00:00', 0, 10, 'Shift pagi untuk cleaning service');

-- PRAMUSAJI
INSERT INTO tbl_shifts (shift_name, department, start_time, end_time, is_overnight, tolerance_minutes, description) VALUES
('Pagi', 'Pramusaji', '06:00:00', '12:00:00', 0, 10, 'Shift pagi pramusaji'),
('Pagi-Sore', 'Pramusaji', '09:00:00', '17:00:00', 0, 10, 'Shift pagi-sore pramusaji'),
('Sore', 'Pramusaji', '12:00:00', '20:00:00', 0, 10, 'Shift sore pramusaji');

-- SECURITY
INSERT INTO tbl_shifts (shift_name, department, start_time, end_time, is_overnight, tolerance_minutes, description) VALUES
('Pagi', 'Security', '07:00:00', '14:00:00', 0, 5, 'Shift pagi security'),
('Sore', 'Security', '14:00:00', '21:00:00', 0, 5, 'Shift sore security'),
('Malam', 'Security', '21:00:00', '07:00:00', 1, 5, 'Shift malam security (melewati tengah malam)');

-- =====================================================
-- 5. VIEW UNTUK LAPORAN ABSENSI
-- =====================================================
CREATE VIEW vw_attendance_report AS
SELECT 
    u.id as user_id,
    u.name,
    u.jabatan,
    u.NIP,
    s.shift_name,
    s.department,
    s.start_time,
    s.end_time,
    s.is_overnight,
    al.attendance_date,
    al.check_in,
    al.check_out,
    al.status,
    al.late_minutes,
    al.work_hours,
    al.notes
FROM tbl_user u
LEFT JOIN tbl_shifts s ON u.shift_id = s.id
LEFT JOIN tbl_attendance_log al ON u.id = al.user_id
WHERE s.is_active = 1;

-- =====================================================
-- TAMPILKAN STATUS TABEL
-- =====================================================
SHOW TABLES;
SELECT 'Shifts created:' as info, COUNT(*) as total FROM tbl_shifts;
DESCRIBE tbl_shifts;
DESCRIBE tbl_attendance_log;

-- =====================================================
-- SCRIPT UPDATE SELESAI
-- =====================================================