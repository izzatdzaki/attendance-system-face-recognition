/*
 Navicat Premium Dump SQL

 Source Server         : localhostt
 Source Server Type    : MySQL
 Source Server Version : 80030 (8.0.30)
 Source Host           : localhost:3306
 Source Schema         : absensi_face

 Target Server Type    : MySQL
 Target Server Version : 80030 (8.0.30)
 File Encoding         : 65001

 Date: 26/09/2025 15:49:48
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for tbl_admin
-- ----------------------------
DROP TABLE IF EXISTS `tbl_admin`;
CREATE TABLE `tbl_admin`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `username`(`username` ASC) USING BTREE,
  INDEX `idx_username`(`username` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of tbl_admin
-- ----------------------------
INSERT INTO `tbl_admin` VALUES (1, 'admin', '$2y$12$83H3QPvzm7G30ejTVzpjhed5aesGLNxr7RqDUqLdKweAJcdhz6twq', '2025-09-26 10:38:26', '2025-09-26 10:41:57');

-- ----------------------------
-- Table structure for tbl_attendance
-- ----------------------------
DROP TABLE IF EXISTS `tbl_attendance`;
CREATE TABLE `tbl_attendance`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `attendance_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `tanggal_absen` date NOT NULL,
  `jam_datang` time NULL DEFAULT NULL,
  `jam_pulang` time NULL DEFAULT NULL,
  `status_absen` enum('datang','pulang','lengkap') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT 'datang',
  `jam_lembur_mulai` time NULL DEFAULT NULL,
  `jam_lembur_selesai` time NULL DEFAULT NULL,
  `status_lembur` enum('tidak_lembur','lembur_mulai','lembur_selesai') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT 'tidak_lembur',
  `user_latitude` decimal(10, 8) NULL DEFAULT NULL,
  `user_longitude` decimal(11, 8) NULL DEFAULT NULL,
  `location_verified` tinyint(1) NULL DEFAULT 0,
  `location_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_user_date`(`user_id` ASC, `tanggal_absen` ASC) USING BTREE,
  INDEX `idx_attendance_location`(`user_latitude` ASC, `user_longitude` ASC) USING BTREE,
  CONSTRAINT `tbl_attendance_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `tbl_user` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 9 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of tbl_attendance
-- ----------------------------

-- ----------------------------
-- Table structure for tbl_attendance_backup
-- ----------------------------
DROP TABLE IF EXISTS `tbl_attendance_backup`;
CREATE TABLE `tbl_attendance_backup`  (
  `id` int NOT NULL DEFAULT 0,
  `user_id` int NOT NULL,
  `attendance_time` datetime NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` datetime NULL DEFAULT CURRENT_TIMESTAMP,
  `jam_datang` time NULL DEFAULT NULL COMMENT 'Waktu datang (jam masuk)',
  `jam_pulang` time NULL DEFAULT NULL COMMENT 'Waktu pulang (jam keluar)',
  `status_absen` enum('datang','pulang','lengkap') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT 'datang' COMMENT 'Status absensi',
  `tanggal_absen` date NULL DEFAULT NULL COMMENT 'Tanggal absensi',
  `jam_lembur_mulai` time NULL DEFAULT NULL,
  `jam_lembur_selesai` time NULL DEFAULT NULL,
  `status_lembur` enum('tidak_lembur','lembur_mulai','lembur_selesai') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT 'tidak_lembur',
  `user_longitude` decimal(11, 8) NULL DEFAULT NULL COMMENT 'Longitude user saat absen',
  `location_verified` tinyint(1) NULL DEFAULT 0 COMMENT '1 = lokasi valid, 0 = tidak valid'
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of tbl_attendance_backup
-- ----------------------------

-- ----------------------------
-- Table structure for tbl_location_settings
-- ----------------------------
DROP TABLE IF EXISTS `tbl_location_settings`;
CREATE TABLE `tbl_location_settings`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `location_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT 'Nama lokasi',
  `latitude` decimal(10, 8) NOT NULL COMMENT 'Latitude lokasi',
  `longitude` decimal(11, 8) NOT NULL COMMENT 'Longitude lokasi',
  `radius_meters` int NOT NULL DEFAULT 100 COMMENT 'Radius dalam meter',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 = aktif, 0 = nonaktif',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_location_active`(`is_active` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 5 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of tbl_location_settings
-- ----------------------------

-- ----------------------------
-- Table structure for tbl_user
-- ----------------------------
DROP TABLE IF EXISTS `tbl_user`;
CREATE TABLE `tbl_user`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `jabatan` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `NIP` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `face_descriptor` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_name`(`name` ASC) USING BTREE,
  INDEX `idx_nidn`(`NIP` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 8 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of tbl_user
-- ----------------------------

SET FOREIGN_KEY_CHECKS = 1;
