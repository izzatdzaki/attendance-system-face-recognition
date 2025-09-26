-- =====================================================
-- SCRIPT SQL UNTUK MEMBUAT DATABASE SISTEM ABSENSI
-- =====================================================
-- Database: absensi_face
-- Dibuat berdasarkan analisis semua script dalam proyek
-- =====================================================

-- Buat database jika belum ada
CREATE DATABASE IF NOT EXISTS absensi_face CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE absensi_face;

-- =====================================================
-- 1. TABEL ADMIN
-- =====================================================
-- Untuk login admin panel
CREATE TABLE IF NOT EXISTS tbl_admin (
    id INT(11) NOT NULL AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 2. TABEL USER
-- =====================================================
-- Untuk menyimpan data pengguna dan descriptor wajah
CREATE TABLE IF NOT EXISTS tbl_user (
    id INT(11) NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    jabatan VARCHAR(100) DEFAULT NULL,
    NIP VARCHAR(20) DEFAULT NULL,
    face_descriptor TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_name (name),
    INDEX idx_nip (NIP)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 3. TABEL ATTENDANCE
-- =====================================================
-- Untuk menyimpan data absensi
CREATE TABLE IF NOT EXISTS tbl_attendance (
    id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    attendance_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (user_id) REFERENCES tbl_user(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_attendance_time (attendance_time),
    INDEX idx_user_time (user_id, attendance_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 4. INSERT DATA ADMIN DEFAULT
-- =====================================================
-- Username: admin, Password: admin123
-- PENTING: Ganti password ini setelah login pertama!
INSERT INTO tbl_admin (username, password) VALUES 
('admin', '$2y$12$83H3QPvzm7G30ejTVzpjhed5aesGLNxr7RqDUqLdKweAJcdhz6twq');

-- =====================================================
-- 5. INSERT DATA USER CONTOH (OPSIONAL)
-- =====================================================
-- Uncomment baris di bawah jika ingin menambah data contoh
-- INSERT INTO tbl_user (name, jabatan, NIP, face_descriptor) VALUES 
-- ('John Doe', 'Dosen', '1234567890', '[]'),
-- ('Jane Smith', 'Staff', '0987654321', '[]');

-- =====================================================
-- INFORMASI PENTING:
-- =====================================================
-- 1. Password default admin: admin123
-- 2. Pastikan untuk mengganti password setelah login pertama
-- 3. face_descriptor berisi data JSON array dari face-api.js
-- 4. Semua tabel menggunakan charset utf8mb4 untuk mendukung emoji
-- 5. Foreign key constraint akan menghapus attendance jika user dihapus
-- =====================================================

-- Tampilkan struktur tabel yang telah dibuat
SHOW TABLES;
DESCRIBE tbl_admin;
DESCRIBE tbl_user;
DESCRIBE tbl_attendance;

-- =====================================================
-- SCRIPT SELESAI
-- =====================================================