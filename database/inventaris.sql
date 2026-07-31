 -- ============================================================
--  INVENTARIS SEKOLAH - Complete Database Schema + Dummy Data
--  Compatible: MySQL 5.7+ / MariaDB 10.3+
--  Charset: utf8mb4
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+07:00";

-- ------------------------------------------------------------
-- Create & Use Database
-- ------------------------------------------------------------
CREATE DATABASE IF NOT EXISTS `inventaris_sekolah`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `inventaris_sekolah`;

-- ============================================================
-- TABLE: users
-- ============================================================
CREATE TABLE `users` (
  `id`           INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `username`     VARCHAR(50)     NOT NULL UNIQUE,
  `password_hash`VARCHAR(255)    NOT NULL,
  `nama_lengkap` VARCHAR(100)    NOT NULL,
  `role`         ENUM('admin','viewer') NOT NULL DEFAULT 'viewer',
  `created_at`   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: labs
-- ============================================================
CREATE TABLE `labs` (
  `id`            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `kode_lab`      VARCHAR(20)   NOT NULL UNIQUE,
  `nama`          VARCHAR(100)  NOT NULL,
  `deskripsi`     TEXT,
  `lantai`        TINYINT       NOT NULL DEFAULT 1,
  `kapasitas`     TINYINT       NOT NULL DEFAULT 30,
  `luas_m2`       DECIMAL(6,2),
  `grid_cols`     TINYINT UNSIGNED NOT NULL DEFAULT 8,
  `grid_rows`     TINYINT UNSIGNED NOT NULL DEFAULT 6,
  `pintu_posisi`  VARCHAR(20)   NOT NULL DEFAULT 'kiri-bawah',
  `is_active`     TINYINT(1)    NOT NULL DEFAULT 1,
  `created_at`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: asset_types
-- ============================================================
CREATE TABLE `asset_types` (
  `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `kode`        VARCHAR(10)   NOT NULL UNIQUE,   -- PC, MSE, KB, TV, PRJ, AC
  `nama`        VARCHAR(50)   NOT NULL,
  `icon`        VARCHAR(10)   NOT NULL DEFAULT '📦', -- emoji fallback
  `is_active`   TINYINT(1)    NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: assets  (semua aset fisik)
-- ============================================================
CREATE TABLE `assets` (
  `id`              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `lab_id`          INT UNSIGNED    NOT NULL,
  `asset_type_id`   INT UNSIGNED    NOT NULL,
  `kode_aset`       VARCHAR(30)     NOT NULL UNIQUE,
  `nama`            VARCHAR(100)    NOT NULL,
  `merk`            VARCHAR(100),
  `model`           VARCHAR(100),
  `serial_number`   VARCHAR(100),
  `harga`           DECIMAL(15,2)   NOT NULL DEFAULT 0.00,
  `tahun_beli`      YEAR            NOT NULL,
  `kondisi`         ENUM('baik','rusak','maintenance') NOT NULL DEFAULT 'baik',
  `keterangan`      TEXT,
  `posisi_x`        TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `posisi_y`        TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `rotasi`          SMALLINT        NOT NULL DEFAULT 0,
  `nama_custom`     VARCHAR(100)    NULL DEFAULT NULL,
  `is_active`       TINYINT(1)      NOT NULL DEFAULT 1,
  `created_at`      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`lab_id`)        REFERENCES `labs`(`id`)        ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`asset_type_id`) REFERENCES `asset_types`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: asset_specs  (spesifikasi dinamis per aset)
-- ============================================================
CREATE TABLE `asset_specs` (
  `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `asset_id`    INT UNSIGNED  NOT NULL,
  `spec_key`    VARCHAR(50)   NOT NULL,   -- CPU, RAM, Storage, OS, dll
  `spec_value`  VARCHAR(255)  NOT NULL,
  `urutan`      TINYINT       NOT NULL DEFAULT 0, -- urutan tampil di UI
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_asset_spec` (`asset_id`, `spec_key`),
  FOREIGN KEY (`asset_id`) REFERENCES `assets`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: kondisi_logs  (history perubahan kondisi aset)
-- ============================================================
CREATE TABLE `kondisi_logs` (
  `id`              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `asset_id`        INT UNSIGNED    NOT NULL,
  `kondisi_lama`    ENUM('baik','rusak','maintenance') NOT NULL,
  `kondisi_baru`    ENUM('baik','rusak','maintenance') NOT NULL,
  `catatan`         TEXT,
  `diubah_oleh`     INT UNSIGNED    NOT NULL,   -- FK ke users.id
  `created_at`      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`asset_id`)    REFERENCES `assets`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`diubah_oleh`) REFERENCES `users`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- INDEXES  (query performance)
-- ============================================================
CREATE INDEX `idx_assets_lab`       ON `assets`(`lab_id`);
CREATE INDEX `idx_assets_type`      ON `assets`(`asset_type_id`);
CREATE INDEX `idx_assets_kondisi`   ON `assets`(`kondisi`);
CREATE INDEX `idx_specs_asset`      ON `asset_specs`(`asset_id`);
CREATE INDEX `idx_log_asset`        ON `kondisi_logs`(`asset_id`);
CREATE INDEX `idx_log_created`      ON `kondisi_logs`(`created_at`);

-- ============================================================
-- DUMMY DATA
-- ============================================================

-- ------------------------------------------------------------
-- Users  (password: admin123  → bcrypt hash)
-- ------------------------------------------------------------
INSERT INTO `users` (`username`, `password_hash`, `nama_lengkap`, `role`) VALUES
('admin',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator',         'admin'),
('petugas1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Budi Santoso',          'admin'),
('viewer1',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Siti Rahayu',           'viewer');
-- NOTE: semua password dummy = "password" (hash Laravel default bcrypt)
-- Ganti hash ini dengan: password_hash('admin123', PASSWORD_BCRYPT)

-- ------------------------------------------------------------
-- Asset Types
-- ------------------------------------------------------------
INSERT INTO `asset_types` (`kode`, `nama`, `icon`) VALUES
('PC',   'Personal Computer', '🖥️'),
('MSE',  'Mouse',             '🖱️'),
('KB',   'Keyboard',          '⌨️'),
('TV',   'TV Monitor',        '📺'),
('PRJ',  'Proyektor',         '📽️'),
('AC',   'Air Conditioner',   '❄️'),
('DESK', 'Meja Siswa',        '🪑'),
('CHR',  'Kursi',             '💺'),
('TDK',  'Meja Guru',         '🏫'),
('WBD',  'Papan Tulis',       '📋'),
('FAN',  'Kipas Angin',       '🌀'),
('CAB',  'Lemari/Kabinet',    '🗄️');

-- ------------------------------------------------------------
-- Labs (5 Lab)
-- ------------------------------------------------------------
INSERT INTO `labs` (`kode_lab`, `nama`, `deskripsi`, `lantai`, `kapasitas`, `luas_m2`, `grid_cols`, `grid_rows`, `pintu_posisi`) VALUES
('LAB-A', 'Lab Komputer A', 'Lab utama RPL dengan spesifikasi terbaru',         1, 36, 72.00, 8,  6, 'kiri-bawah'),
('LAB-B', 'Lab Komputer B', 'Lab multimedia dan desain grafis',                 1, 30, 60.00, 8,  6, 'kanan-bawah'),
('LAB-C', 'Lab Komputer C', 'Lab jaringan dan server',                          2, 24, 48.00, 6,  5, 'kiri-bawah'),
('LAB-D', 'Lab Komputer D', 'Lab cadangan dan praktikum dasar',                 2, 32, 64.00, 8,  7, 'tengah-bawah'),
('LAB-E', 'Lab Komputer E', 'Lab terbaru, digunakan untuk ujian CBT',           3, 40, 80.00, 10, 8, 'kiri-atas');

-- ============================================================
-- ASSETS & SPECS - LAB A  (18 PC, 18 Mouse, 18 KB, 1 TV, 1 PRJ, 2 AC)
-- ============================================================

-- PC Lab A (18 unit, posisi grid 6 kolom x 3 baris)
INSERT INTO `assets` (`lab_id`,`asset_type_id`,`kode_aset`,`nama`,`merk`,`model`,`serial_number`,`harga`,`tahun_beli`,`kondisi`,`keterangan`,`posisi_x`,`posisi_y`) VALUES
(1,1,'PC-A01','PC Student 01','Lenovo','ThinkCentre M70s','SN-LEN-A001',8500000,2022,'baik',NULL,0,0),
(1,1,'PC-A02','PC Student 02','Lenovo','ThinkCentre M70s','SN-LEN-A002',8500000,2022,'baik',NULL,1,0),
(1,1,'PC-A03','PC Student 03','Lenovo','ThinkCentre M70s','SN-LEN-A003',8500000,2022,'rusak','RAM slot 2 error',2,0),
(1,1,'PC-A04','PC Student 04','Lenovo','ThinkCentre M70s','SN-LEN-A004',8500000,2022,'baik',NULL,3,0),
(1,1,'PC-A05','PC Student 05','Lenovo','ThinkCentre M70s','SN-LEN-A005',8500000,2022,'baik',NULL,4,0),
(1,1,'PC-A06','PC Student 06','Lenovo','ThinkCentre M70s','SN-LEN-A006',8500000,2022,'baik',NULL,5,0),
(1,1,'PC-A07','PC Student 07','Lenovo','ThinkCentre M70s','SN-LEN-A007',8500000,2022,'baik',NULL,0,1),
(1,1,'PC-A08','PC Student 08','Lenovo','ThinkCentre M70s','SN-LEN-A008',8500000,2022,'maintenance','Reinstall OS',1,1),
(1,1,'PC-A09','PC Student 09','Lenovo','ThinkCentre M70s','SN-LEN-A009',8500000,2022,'baik',NULL,2,1),
(1,1,'PC-A10','PC Student 10','Lenovo','ThinkCentre M70s','SN-LEN-A010',8500000,2022,'baik',NULL,3,1),
(1,1,'PC-A11','PC Student 11','Lenovo','ThinkCentre M70s','SN-LEN-A011',8500000,2022,'baik',NULL,4,1),
(1,1,'PC-A12','PC Student 12','Lenovo','ThinkCentre M70s','SN-LEN-A012',8500000,2022,'baik',NULL,5,1),
(1,1,'PC-A13','PC Student 13','Lenovo','ThinkCentre M70s','SN-LEN-A013',8500000,2022,'baik',NULL,0,2),
(1,1,'PC-A14','PC Student 14','Lenovo','ThinkCentre M70s','SN-LEN-A014',8500000,2022,'baik',NULL,1,2),
(1,1,'PC-A15','PC Student 15','Lenovo','ThinkCentre M70s','SN-LEN-A015',8500000,2022,'baik',NULL,2,2),
(1,1,'PC-A16','PC Student 16','Lenovo','ThinkCentre M70s','SN-LEN-A016',8500000,2022,'rusak','Monitor tidak menyala',3,2),
(1,1,'PC-A17','PC Student 17','Lenovo','ThinkCentre M70s','SN-LEN-A017',8500000,2022,'baik',NULL,4,2),
(1,1,'PC-A18','PC Student 18 (Guru)','Lenovo','ThinkCentre M80s','SN-LEN-A018',12000000,2023,'baik','PC Guru/Instruktur',5,2);

-- Mouse Lab A
INSERT INTO `assets` (`lab_id`,`asset_type_id`,`kode_aset`,`nama`,`merk`,`model`,`serial_number`,`harga`,`tahun_beli`,`kondisi`,`posisi_x`,`posisi_y`) VALUES
(1,2,'MSE-A01','Mouse 01','Logitech','B100','MSN-A001',85000,2022,'baik',0,0),
(1,2,'MSE-A02','Mouse 02','Logitech','B100','MSN-A002',85000,2022,'baik',1,0),
(1,2,'MSE-A03','Mouse 03','Logitech','B100','MSN-A003',85000,2022,'rusak',2,0),
(1,2,'MSE-A04','Mouse 04','Logitech','B100','MSN-A004',85000,2022,'baik',3,0),
(1,2,'MSE-A05','Mouse 05','Logitech','B100','MSN-A005',85000,2022,'baik',4,0),
(1,2,'MSE-A06','Mouse 06','Logitech','B100','MSN-A006',85000,2022,'baik',5,0),
(1,2,'MSE-A07','Mouse 07','Logitech','B100','MSN-A007',85000,2022,'baik',0,1),
(1,2,'MSE-A08','Mouse 08','Logitech','B100','MSN-A008',85000,2022,'baik',1,1),
(1,2,'MSE-A09','Mouse 09','Logitech','B100','MSN-A009',85000,2022,'baik',2,1),
(1,2,'MSE-A10','Mouse 10','Logitech','B100','MSN-A010',85000,2022,'baik',3,1),
(1,2,'MSE-A11','Mouse 11','Logitech','B100','MSN-A011',85000,2022,'baik',4,1),
(1,2,'MSE-A12','Mouse 12','Logitech','B100','MSN-A012',85000,2022,'baik',5,1),
(1,2,'MSE-A13','Mouse 13','Logitech','B100','MSN-A013',85000,2022,'baik',0,2),
(1,2,'MSE-A14','Mouse 14','Logitech','B100','MSN-A014',85000,2022,'baik',1,2),
(1,2,'MSE-A15','Mouse 15','Logitech','B100','MSN-A015',85000,2022,'baik',2,2),
(1,2,'MSE-A16','Mouse 16','Logitech','B100','MSN-A016',85000,2022,'baik',3,2),
(1,2,'MSE-A17','Mouse 17','Logitech','B100','MSN-A017',85000,2022,'baik',4,2),
(1,2,'MSE-A18','Mouse 18','Logitech','B100','MSN-A018',85000,2022,'baik',5,2);

-- Keyboard Lab A
INSERT INTO `assets` (`lab_id`,`asset_type_id`,`kode_aset`,`nama`,`merk`,`model`,`serial_number`,`harga`,`tahun_beli`,`kondisi`,`posisi_x`,`posisi_y`) VALUES
(1,3,'KB-A01','Keyboard 01','Logitech','K120','KBN-A001',120000,2022,'baik',0,0),
(1,3,'KB-A02','Keyboard 02','Logitech','K120','KBN-A002',120000,2022,'baik',1,0),
(1,3,'KB-A03','Keyboard 03','Logitech','K120','KBN-A003',120000,2022,'baik',2,0),
(1,3,'KB-A04','Keyboard 04','Logitech','K120','KBN-A004',120000,2022,'maintenance',3,0),
(1,3,'KB-A05','Keyboard 05','Logitech','K120','KBN-A005',120000,2022,'baik',4,0),
(1,3,'KB-A06','Keyboard 06','Logitech','K120','KBN-A006',120000,2022,'baik',5,0),
(1,3,'KB-A07','Keyboard 07','Logitech','K120','KBN-A007',120000,2022,'baik',0,1),
(1,3,'KB-A08','Keyboard 08','Logitech','K120','KBN-A008',120000,2022,'baik',1,1),
(1,3,'KB-A09','Keyboard 09','Logitech','K120','KBN-A009',120000,2022,'baik',2,1),
(1,3,'KB-A10','Keyboard 10','Logitech','K120','KBN-A010',120000,2022,'baik',3,1),
(1,3,'KB-A11','Keyboard 11','Logitech','K120','KBN-A011',120000,2022,'baik',4,1),
(1,3,'KB-A12','Keyboard 12','Logitech','K120','KBN-A012',120000,2022,'rusak',5,1),
(1,3,'KB-A13','Keyboard 13','Logitech','K120','KBN-A013',120000,2022,'baik',0,2),
(1,3,'KB-A14','Keyboard 14','Logitech','K120','KBN-A014',120000,2022,'baik',1,2),
(1,3,'KB-A15','Keyboard 15','Logitech','K120','KBN-A015',120000,2022,'baik',2,2),
(1,3,'KB-A16','Keyboard 16','Logitech','K120','KBN-A016',120000,2022,'baik',3,2),
(1,3,'KB-A17','Keyboard 17','Logitech','K120','KBN-A017',120000,2022,'baik',4,2),
(1,3,'KB-A18','Keyboard 18','Logitech','K120','KBN-A018',120000,2022,'baik',5,2);

-- TV, Proyektor, AC Lab A
INSERT INTO `assets` (`lab_id`,`asset_type_id`,`kode_aset`,`nama`,`merk`,`model`,`serial_number`,`harga`,`tahun_beli`,`kondisi`,`keterangan`,`posisi_x`,`posisi_y`) VALUES
(1,4,'TV-A01','TV Monitor Interaktif','Samsung','Flip 55"','SN-SAM-A001',18500000,2023,'baik','Touch screen, koneksi HDMI + WiFi',0,4),
(1,5,'PRJ-A01','Proyektor Utama','Epson','EB-X51','SN-EPS-A001',5500000,2021,'baik',NULL,3,4),
(1,6,'AC-A01','AC Split 1','Daikin','FTV25AXV14','SN-DAI-A001',4200000,2021,'baik',NULL,0,5),
(1,6,'AC-A02','AC Split 2','Daikin','FTV25AXV14','SN-DAI-A002',4200000,2021,'maintenance','Filter perlu dibersihkan',5,5);

-- ============================================================
-- ASSETS - LAB B  (15 PC, 15 Mouse, 15 KB, 1 TV, 1 PRJ, 2 AC)
-- ============================================================
INSERT INTO `assets` (`lab_id`,`asset_type_id`,`kode_aset`,`nama`,`merk`,`model`,`serial_number`,`harga`,`tahun_beli`,`kondisi`,`posisi_x`,`posisi_y`) VALUES
(2,1,'PC-B01','PC Student 01','HP','ProDesk 400 G7','SN-HP-B001',9200000,2021,'baik',0,0),
(2,1,'PC-B02','PC Student 02','HP','ProDesk 400 G7','SN-HP-B002',9200000,2021,'baik',1,0),
(2,1,'PC-B03','PC Student 03','HP','ProDesk 400 G7','SN-HP-B003',9200000,2021,'rusak',2,0),
(2,1,'PC-B04','PC Student 04','HP','ProDesk 400 G7','SN-HP-B004',9200000,2021,'baik',3,0),
(2,1,'PC-B05','PC Student 05','HP','ProDesk 400 G7','SN-HP-B005',9200000,2021,'baik',4,0),
(2,1,'PC-B06','PC Student 06','HP','ProDesk 400 G7','SN-HP-B006',9200000,2021,'baik',0,1),
(2,1,'PC-B07','PC Student 07','HP','ProDesk 400 G7','SN-HP-B007',9200000,2021,'baik',1,1),
(2,1,'PC-B08','PC Student 08','HP','ProDesk 400 G7','SN-HP-B008',9200000,2021,'maintenance',2,1),
(2,1,'PC-B09','PC Student 09','HP','ProDesk 400 G7','SN-HP-B009',9200000,2021,'baik',3,1),
(2,1,'PC-B10','PC Student 10','HP','ProDesk 400 G7','SN-HP-B010',9200000,2021,'baik',4,1),
(2,1,'PC-B11','PC Student 11','HP','ProDesk 400 G7','SN-HP-B011',9200000,2021,'baik',0,2),
(2,1,'PC-B12','PC Student 12','HP','ProDesk 400 G7','SN-HP-B012',9200000,2021,'baik',1,2),
(2,1,'PC-B13','PC Student 13','HP','ProDesk 400 G7','SN-HP-B013',9200000,2021,'baik',2,2),
(2,1,'PC-B14','PC Student 14','HP','ProDesk 400 G7','SN-HP-B014',9200000,2021,'baik',3,2),
(2,1,'PC-B15','PC Student 15 (Guru)','HP','ProDesk 600 G6','SN-HP-B015',11000000,2022,'baik',4,2),
(2,2,'MSE-B01','Mouse 01','Logitech','B100','MSN-B001',85000,2021,'baik',0,0),
(2,2,'MSE-B02','Mouse 02','Logitech','B100','MSN-B002',85000,2021,'baik',1,0),
(2,2,'MSE-B03','Mouse 03','Logitech','B100','MSN-B003',85000,2021,'rusak',2,0),
(2,2,'MSE-B04','Mouse 04','Logitech','B100','MSN-B004',85000,2021,'baik',3,0),
(2,2,'MSE-B05','Mouse 05','Logitech','B100','MSN-B005',85000,2021,'baik',4,0),
(2,2,'MSE-B06','Mouse 06','Logitech','B100','MSN-B006',85000,2021,'baik',0,1),
(2,2,'MSE-B07','Mouse 07','Logitech','B100','MSN-B007',85000,2021,'baik',1,1),
(2,2,'MSE-B08','Mouse 08','Logitech','B100','MSN-B008',85000,2021,'baik',2,1),
(2,2,'MSE-B09','Mouse 09','Logitech','B100','MSN-B009',85000,2021,'baik',3,1),
(2,2,'MSE-B10','Mouse 10','Logitech','B100','MSN-B010',85000,2021,'baik',4,1),
(2,2,'MSE-B11','Mouse 11','Logitech','B100','MSN-B011',85000,2021,'baik',0,2),
(2,2,'MSE-B12','Mouse 12','Logitech','B100','MSN-B012',85000,2021,'baik',1,2),
(2,2,'MSE-B13','Mouse 13','Logitech','B100','MSN-B013',85000,2021,'baik',2,2),
(2,2,'MSE-B14','Mouse 14','Logitech','B100','MSN-B014',85000,2021,'baik',3,2),
(2,2,'MSE-B15','Mouse 15','Logitech','B100','MSN-B015',85000,2021,'baik',4,2),
(2,3,'KB-B01','Keyboard 01','Logitech','K120','KBN-B001',120000,2021,'baik',0,0),
(2,3,'KB-B02','Keyboard 02','Logitech','K120','KBN-B002',120000,2021,'baik',1,0),
(2,3,'KB-B03','Keyboard 03','Logitech','K120','KBN-B003',120000,2021,'baik',2,0),
(2,3,'KB-B04','Keyboard 04','Logitech','K120','KBN-B004',120000,2021,'baik',3,0),
(2,3,'KB-B05','Keyboard 05','Logitech','K120','KBN-B005',120000,2021,'maintenance',4,0),
(2,3,'KB-B06','Keyboard 06','Logitech','K120','KBN-B006',120000,2021,'baik',0,1),
(2,3,'KB-B07','Keyboard 07','Logitech','K120','KBN-B007',120000,2021,'baik',1,1),
(2,3,'KB-B08','Keyboard 08','Logitech','K120','KBN-B008',120000,2021,'baik',2,1),
(2,3,'KB-B09','Keyboard 09','Logitech','K120','KBN-B009',120000,2021,'baik',3,1),
(2,3,'KB-B10','Keyboard 10','Logitech','K120','KBN-B010',120000,2021,'rusak',4,1),
(2,3,'KB-B11','Keyboard 11','Logitech','K120','KBN-B011',120000,2021,'baik',0,2),
(2,3,'KB-B12','Keyboard 12','Logitech','K120','KBN-B012',120000,2021,'baik',1,2),
(2,3,'KB-B13','Keyboard 13','Logitech','K120','KBN-B013',120000,2021,'baik',2,2),
(2,3,'KB-B14','Keyboard 14','Logitech','K120','KBN-B014',120000,2021,'baik',3,2),
(2,3,'KB-B15','Keyboard 15','Logitech','K120','KBN-B015',120000,2021,'baik',4,2),
(2,4,'TV-B01','TV Monitor Interaktif','LG','One:Quick Flex 65"','SN-LG-B001',22000000,2022,'baik',0,4),
(2,5,'PRJ-B01','Proyektor Multimedia','Epson','EB-W51','SN-EPS-B001',6500000,2022,'baik',3,4),
(2,6,'AC-B01','AC Split 1','Panasonic','CS-YN9WKJ','SN-PAN-B001',3800000,2020,'baik',0,5),
(2,6,'AC-B02','AC Split 2','Panasonic','CS-YN9WKJ','SN-PAN-B002',3800000,2020,'rusak',5,5);

-- ============================================================
-- ASSETS - LAB C  (12 PC, 12 Mouse, 12 KB, 1 TV, 2 AC)
-- ============================================================
INSERT INTO `assets` (`lab_id`,`asset_type_id`,`kode_aset`,`nama`,`merk`,`model`,`serial_number`,`harga`,`tahun_beli`,`kondisi`,`posisi_x`,`posisi_y`) VALUES
(3,1,'PC-C01','PC Student 01','Dell','OptiPlex 3080','SN-DEL-C001',10500000,2020,'baik',0,0),
(3,1,'PC-C02','PC Student 02','Dell','OptiPlex 3080','SN-DEL-C002',10500000,2020,'baik',1,0),
(3,1,'PC-C03','PC Student 03','Dell','OptiPlex 3080','SN-DEL-C003',10500000,2020,'rusak',2,0),
(3,1,'PC-C04','PC Student 04','Dell','OptiPlex 3080','SN-DEL-C004',10500000,2020,'baik',3,0),
(3,1,'PC-C05','PC Student 05','Dell','OptiPlex 3080','SN-DEL-C005',10500000,2020,'baik',0,1),
(3,1,'PC-C06','PC Student 06','Dell','OptiPlex 3080','SN-DEL-C006',10500000,2020,'baik',1,1),
(3,1,'PC-C07','PC Student 07','Dell','OptiPlex 3080','SN-DEL-C007',10500000,2020,'maintenance',2,1),
(3,1,'PC-C08','PC Student 08','Dell','OptiPlex 3080','SN-DEL-C008',10500000,2020,'baik',3,1),
(3,1,'PC-C09','PC Student 09','Dell','OptiPlex 3080','SN-DEL-C009',10500000,2020,'baik',0,2),
(3,1,'PC-C10','PC Student 10','Dell','OptiPlex 3080','SN-DEL-C010',10500000,2020,'baik',1,2),
(3,1,'PC-C11','PC Student 11','Dell','OptiPlex 3080','SN-DEL-C011',10500000,2020,'baik',2,2),
(3,1,'PC-C12','PC Student 12 (Guru)','Dell','OptiPlex 5080','SN-DEL-C012',13500000,2021,'baik',3,2),
(3,2,'MSE-C01','Mouse 01','Rexus','Daxa M52','MSN-C001',95000,2020,'baik',0,0),
(3,2,'MSE-C02','Mouse 02','Rexus','Daxa M52','MSN-C002',95000,2020,'baik',1,0),
(3,2,'MSE-C03','Mouse 03','Rexus','Daxa M52','MSN-C003',95000,2020,'baik',2,0),
(3,2,'MSE-C04','Mouse 04','Rexus','Daxa M52','MSN-C004',95000,2020,'rusak',3,0),
(3,2,'MSE-C05','Mouse 05','Rexus','Daxa M52','MSN-C005',95000,2020,'baik',0,1),
(3,2,'MSE-C06','Mouse 06','Rexus','Daxa M52','MSN-C006',95000,2020,'baik',1,1),
(3,2,'MSE-C07','Mouse 07','Rexus','Daxa M52','MSN-C007',95000,2020,'baik',2,1),
(3,2,'MSE-C08','Mouse 08','Rexus','Daxa M52','MSN-C008',95000,2020,'baik',3,1),
(3,2,'MSE-C09','Mouse 09','Rexus','Daxa M52','MSN-C009',95000,2020,'baik',0,2),
(3,2,'MSE-C10','Mouse 10','Rexus','Daxa M52','MSN-C010',95000,2020,'baik',1,2),
(3,2,'MSE-C11','Mouse 11','Rexus','Daxa M52','MSN-C011',95000,2020,'baik',2,2),
(3,2,'MSE-C12','Mouse 12','Rexus','Daxa M52','MSN-C012',95000,2020,'baik',3,2),
(3,3,'KB-C01','Keyboard 01','Rexus','Daxa H1','KBN-C001',150000,2020,'baik',0,0),
(3,3,'KB-C02','Keyboard 02','Rexus','Daxa H1','KBN-C002',150000,2020,'baik',1,0),
(3,3,'KB-C03','Keyboard 03','Rexus','Daxa H1','KBN-C003',150000,2020,'baik',2,0),
(3,3,'KB-C04','Keyboard 04','Rexus','Daxa H1','KBN-C004',150000,2020,'baik',3,0),
(3,3,'KB-C05','Keyboard 05','Rexus','Daxa H1','KBN-C005',150000,2020,'baik',0,1),
(3,3,'KB-C06','Keyboard 06','Rexus','Daxa H1','KBN-C006',150000,2020,'rusak',1,1),
(3,3,'KB-C07','Keyboard 07','Rexus','Daxa H1','KBN-C007',150000,2020,'baik',2,1),
(3,3,'KB-C08','Keyboard 08','Rexus','Daxa H1','KBN-C008',150000,2020,'baik',3,1),
(3,3,'KB-C09','Keyboard 09','Rexus','Daxa H1','KBN-C009',150000,2020,'baik',0,2),
(3,3,'KB-C10','Keyboard 10','Rexus','Daxa H1','KBN-C010',150000,2020,'baik',1,2),
(3,3,'KB-C11','Keyboard 11','Rexus','Daxa H1','KBN-C011',150000,2020,'baik',2,2),
(3,3,'KB-C12','Keyboard 12','Rexus','Daxa H1','KBN-C012',150000,2020,'baik',3,2),
(3,4,'TV-C01','TV Monitor Interaktif','Samsung','Flip 65"','SN-SAM-C001',24000000,2023,'baik',0,4),
(3,6,'AC-C01','AC Split 1','Daikin','FTV20AXV14','SN-DAI-C001',3900000,2020,'baik',0,5),
(3,6,'AC-C02','AC Split 2','Daikin','FTV20AXV14','SN-DAI-C002',3900000,2020,'baik',5,5);

-- ============================================================
-- ASSETS - LAB D  (20 PC, 20 Mouse, 20 KB, 1 TV, 1 PRJ, 2 AC)
-- ============================================================
INSERT INTO `assets` (`lab_id`,`asset_type_id`,`kode_aset`,`nama`,`merk`,`model`,`serial_number`,`harga`,`tahun_beli`,`kondisi`,`posisi_x`,`posisi_y`) VALUES
(4,1,'PC-D01','PC Student 01','Acer','Veriton M4680G','SN-ACR-D001',7800000,2021,'baik',0,0),
(4,1,'PC-D02','PC Student 02','Acer','Veriton M4680G','SN-ACR-D002',7800000,2021,'baik',1,0),
(4,1,'PC-D03','PC Student 03','Acer','Veriton M4680G','SN-ACR-D003',7800000,2021,'baik',2,0),
(4,1,'PC-D04','PC Student 04','Acer','Veriton M4680G','SN-ACR-D004',7800000,2021,'rusak',3,0),
(4,1,'PC-D05','PC Student 05','Acer','Veriton M4680G','SN-ACR-D005',7800000,2021,'baik',4,0),
(4,1,'PC-D06','PC Student 06','Acer','Veriton M4680G','SN-ACR-D006',7800000,2021,'baik',0,1),
(4,1,'PC-D07','PC Student 07','Acer','Veriton M4680G','SN-ACR-D007',7800000,2021,'baik',1,1),
(4,1,'PC-D08','PC Student 08','Acer','Veriton M4680G','SN-ACR-D008',7800000,2021,'baik',2,1),
(4,1,'PC-D09','PC Student 09','Acer','Veriton M4680G','SN-ACR-D009',7800000,2021,'maintenance',3,1),
(4,1,'PC-D10','PC Student 10','Acer','Veriton M4680G','SN-ACR-D010',7800000,2021,'baik',4,1),
(4,1,'PC-D11','PC Student 11','Acer','Veriton M4680G','SN-ACR-D011',7800000,2021,'baik',0,2),
(4,1,'PC-D12','PC Student 12','Acer','Veriton M4680G','SN-ACR-D012',7800000,2021,'baik',1,2),
(4,1,'PC-D13','PC Student 13','Acer','Veriton M4680G','SN-ACR-D013',7800000,2021,'baik',2,2),
(4,1,'PC-D14','PC Student 14','Acer','Veriton M4680G','SN-ACR-D014',7800000,2021,'rusak',3,2),
(4,1,'PC-D15','PC Student 15','Acer','Veriton M4680G','SN-ACR-D015',7800000,2021,'baik',4,2),
(4,1,'PC-D16','PC Student 16','Acer','Veriton M4680G','SN-ACR-D016',7800000,2021,'baik',0,3),
(4,1,'PC-D17','PC Student 17','Acer','Veriton M4680G','SN-ACR-D017',7800000,2021,'baik',1,3),
(4,1,'PC-D18','PC Student 18','Acer','Veriton M4680G','SN-ACR-D018',7800000,2021,'baik',2,3),
(4,1,'PC-D19','PC Student 19','Acer','Veriton M4680G','SN-ACR-D019',7800000,2021,'baik',3,3),
(4,1,'PC-D20','PC Student 20 (Guru)','Acer','Veriton M6680G','SN-ACR-D020',10500000,2022,'baik',4,3),
(4,2,'MSE-D01','Mouse 01','A4Tech','OP-620D','MSN-D001',75000,2021,'baik',0,0),
(4,2,'MSE-D02','Mouse 02','A4Tech','OP-620D','MSN-D002',75000,2021,'baik',1,0),
(4,2,'MSE-D03','Mouse 03','A4Tech','OP-620D','MSN-D003',75000,2021,'baik',2,0),
(4,2,'MSE-D04','Mouse 04','A4Tech','OP-620D','MSN-D004',75000,2021,'baik',3,0),
(4,2,'MSE-D05','Mouse 05','A4Tech','OP-620D','MSN-D005',75000,2021,'rusak',4,0),
(4,2,'MSE-D06','Mouse 06','A4Tech','OP-620D','MSN-D006',75000,2021,'baik',0,1),
(4,2,'MSE-D07','Mouse 07','A4Tech','OP-620D','MSN-D007',75000,2021,'baik',1,1),
(4,2,'MSE-D08','Mouse 08','A4Tech','OP-620D','MSN-D008',75000,2021,'baik',2,1),
(4,2,'MSE-D09','Mouse 09','A4Tech','OP-620D','MSN-D009',75000,2021,'baik',3,1),
(4,2,'MSE-D10','Mouse 10','A4Tech','OP-620D','MSN-D010',75000,2021,'baik',4,1),
(4,2,'MSE-D11','Mouse 11','A4Tech','OP-620D','MSN-D011',75000,2021,'baik',0,2),
(4,2,'MSE-D12','Mouse 12','A4Tech','OP-620D','MSN-D012',75000,2021,'baik',1,2),
(4,2,'MSE-D13','Mouse 13','A4Tech','OP-620D','MSN-D013',75000,2021,'baik',2,2),
(4,2,'MSE-D14','Mouse 14','A4Tech','OP-620D','MSN-D014',75000,2021,'baik',3,2),
(4,2,'MSE-D15','Mouse 15','A4Tech','OP-620D','MSN-D015',75000,2021,'baik',4,2),
(4,2,'MSE-D16','Mouse 16','A4Tech','OP-620D','MSN-D016',75000,2021,'baik',0,3),
(4,2,'MSE-D17','Mouse 17','A4Tech','OP-620D','MSN-D017',75000,2021,'baik',1,3),
(4,2,'MSE-D18','Mouse 18','A4Tech','OP-620D','MSN-D018',75000,2021,'baik',2,3),
(4,2,'MSE-D19','Mouse 19','A4Tech','OP-620D','MSN-D019',75000,2021,'baik',3,3),
(4,2,'MSE-D20','Mouse 20','A4Tech','OP-620D','MSN-D020',75000,2021,'baik',4,3),
(4,3,'KB-D01','Keyboard 01','A4Tech','KR-750','KBN-D001',110000,2021,'baik',0,0),
(4,3,'KB-D02','Keyboard 02','A4Tech','KR-750','KBN-D002',110000,2021,'baik',1,0),
(4,3,'KB-D03','Keyboard 03','A4Tech','KR-750','KBN-D003',110000,2021,'baik',2,0),
(4,3,'KB-D04','Keyboard 04','A4Tech','KR-750','KBN-D004',110000,2021,'baik',3,0),
(4,3,'KB-D05','Keyboard 05','A4Tech','KR-750','KBN-D005',110000,2021,'baik',4,0),
(4,3,'KB-D06','Keyboard 06','A4Tech','KR-750','KBN-D006',110000,2021,'rusak',0,1),
(4,3,'KB-D07','Keyboard 07','A4Tech','KR-750','KBN-D007',110000,2021,'baik',1,1),
(4,3,'KB-D08','Keyboard 08','A4Tech','KR-750','KBN-D008',110000,2021,'baik',2,1),
(4,3,'KB-D09','Keyboard 09','A4Tech','KR-750','KBN-D009',110000,2021,'baik',3,1),
(4,3,'KB-D10','Keyboard 10','A4Tech','KR-750','KBN-D010',110000,2021,'baik',4,1),
(4,3,'KB-D11','Keyboard 11','A4Tech','KR-750','KBN-D011',110000,2021,'baik',0,2),
(4,3,'KB-D12','Keyboard 12','A4Tech','KR-750','KBN-D012',110000,2021,'baik',1,2),
(4,3,'KB-D13','Keyboard 13','A4Tech','KR-750','KBN-D013',110000,2021,'baik',2,2),
(4,3,'KB-D14','Keyboard 14','A4Tech','KR-750','KBN-D014',110000,2021,'baik',3,2),
(4,3,'KB-D15','Keyboard 15','A4Tech','KR-750','KBN-D015',110000,2021,'maintenance',4,2),
(4,3,'KB-D16','Keyboard 16','A4Tech','KR-750','KBN-D016',110000,2021,'baik',0,3),
(4,3,'KB-D17','Keyboard 17','A4Tech','KR-750','KBN-D017',110000,2021,'baik',1,3),
(4,3,'KB-D18','Keyboard 18','A4Tech','KR-750','KBN-D018',110000,2021,'baik',2,3),
(4,3,'KB-D19','Keyboard 19','A4Tech','KR-750','KBN-D019',110000,2021,'baik',3,3),
(4,3,'KB-D20','Keyboard 20','A4Tech','KR-750','KBN-D020',110000,2021,'baik',4,3),
(4,4,'TV-D01','TV Monitor Interaktif','Hisense','INX 55"','SN-HIS-D001',15000000,2022,'baik',0,5),
(4,5,'PRJ-D01','Proyektor','BenQ','MH5005','SN-BNQ-D001',6200000,2022,'baik',3,5),
(4,6,'AC-D01','AC Split 1','Sharp','AH-A5UCY','SN-SHP-D001',3600000,2021,'baik',0,6),
(4,6,'AC-D02','AC Split 2','Sharp','AH-A5UCY','SN-SHP-D002',3600000,2021,'baik',5,6);

-- ============================================================
-- ASSETS - LAB E  (25 PC, 25 Mouse, 25 KB, 2 TV, 1 PRJ, 3 AC)
-- ============================================================
INSERT INTO `assets` (`lab_id`,`asset_type_id`,`kode_aset`,`nama`,`merk`,`model`,`serial_number`,`harga`,`tahun_beli`,`kondisi`,`posisi_x`,`posisi_y`) VALUES
(5,1,'PC-E01','PC CBT 01','Lenovo','ThinkCentre M90s','SN-LEN-E001',11000000,2023,'baik',0,0),
(5,1,'PC-E02','PC CBT 02','Lenovo','ThinkCentre M90s','SN-LEN-E002',11000000,2023,'baik',1,0),
(5,1,'PC-E03','PC CBT 03','Lenovo','ThinkCentre M90s','SN-LEN-E003',11000000,2023,'baik',2,0),
(5,1,'PC-E04','PC CBT 04','Lenovo','ThinkCentre M90s','SN-LEN-E004',11000000,2023,'baik',3,0),
(5,1,'PC-E05','PC CBT 05','Lenovo','ThinkCentre M90s','SN-LEN-E005',11000000,2023,'baik',4,0),
(5,1,'PC-E06','PC CBT 06','Lenovo','ThinkCentre M90s','SN-LEN-E006',11000000,2023,'baik',0,1),
(5,1,'PC-E07','PC CBT 07','Lenovo','ThinkCentre M90s','SN-LEN-E007',11000000,2023,'baik',1,1),
(5,1,'PC-E08','PC CBT 08','Lenovo','ThinkCentre M90s','SN-LEN-E008',11000000,2023,'baik',2,1),
(5,1,'PC-E09','PC CBT 09','Lenovo','ThinkCentre M90s','SN-LEN-E009',11000000,2023,'baik',3,1),
(5,1,'PC-E10','PC CBT 10','Lenovo','ThinkCentre M90s','SN-LEN-E010',11000000,2023,'rusak',4,1),
(5,1,'PC-E11','PC CBT 11','Lenovo','ThinkCentre M90s','SN-LEN-E011',11000000,2023,'baik',0,2),
(5,1,'PC-E12','PC CBT 12','Lenovo','ThinkCentre M90s','SN-LEN-E012',11000000,2023,'baik',1,2),
(5,1,'PC-E13','PC CBT 13','Lenovo','ThinkCentre M90s','SN-LEN-E013',11000000,2023,'baik',2,2),
(5,1,'PC-E14','PC CBT 14','Lenovo','ThinkCentre M90s','SN-LEN-E014',11000000,2023,'baik',3,2),
(5,1,'PC-E15','PC CBT 15','Lenovo','ThinkCentre M90s','SN-LEN-E015',11000000,2023,'baik',4,2),
(5,1,'PC-E16','PC CBT 16','Lenovo','ThinkCentre M90s','SN-LEN-E016',11000000,2023,'maintenance',0,3),
(5,1,'PC-E17','PC CBT 17','Lenovo','ThinkCentre M90s','SN-LEN-E017',11000000,2023,'baik',1,3),
(5,1,'PC-E18','PC CBT 18','Lenovo','ThinkCentre M90s','SN-LEN-E018',11000000,2023,'baik',2,3),
(5,1,'PC-E19','PC CBT 19','Lenovo','ThinkCentre M90s','SN-LEN-E019',11000000,2023,'baik',3,3),
(5,1,'PC-E20','PC CBT 20','Lenovo','ThinkCentre M90s','SN-LEN-E020',11000000,2023,'baik',4,3),
(5,1,'PC-E21','PC CBT 21','Lenovo','ThinkCentre M90s','SN-LEN-E021',11000000,2023,'baik',0,4),
(5,1,'PC-E22','PC CBT 22','Lenovo','ThinkCentre M90s','SN-LEN-E022',11000000,2023,'baik',1,4),
(5,1,'PC-E23','PC CBT 23','Lenovo','ThinkCentre M90s','SN-LEN-E023',11000000,2023,'baik',2,4),
(5,1,'PC-E24','PC CBT 24','Lenovo','ThinkCentre M90s','SN-LEN-E024',11000000,2023,'baik',3,4),
(5,1,'PC-E25','PC CBT 25 (Pengawas)','Lenovo','ThinkCentre M90t','SN-LEN-E025',14000000,2023,'baik',4,4),
(5,2,'MSE-E01','Mouse 01','Logitech','M100','MSN-E001',90000,2023,'baik',0,0),
(5,2,'MSE-E02','Mouse 02','Logitech','M100','MSN-E002',90000,2023,'baik',1,0),
(5,2,'MSE-E03','Mouse 03','Logitech','M100','MSN-E003',90000,2023,'baik',2,0),
(5,2,'MSE-E04','Mouse 04','Logitech','M100','MSN-E004',90000,2023,'baik',3,0),
(5,2,'MSE-E05','Mouse 05','Logitech','M100','MSN-E005',90000,2023,'baik',4,0),
(5,2,'MSE-E06','Mouse 06','Logitech','M100','MSN-E006',90000,2023,'baik',0,1),
(5,2,'MSE-E07','Mouse 07','Logitech','M100','MSN-E007',90000,2023,'baik',1,1),
(5,2,'MSE-E08','Mouse 08','Logitech','M100','MSN-E008',90000,2023,'baik',2,1),
(5,2,'MSE-E09','Mouse 09','Logitech','M100','MSN-E009',90000,2023,'baik',3,1),
(5,2,'MSE-E10','Mouse 10','Logitech','M100','MSN-E010',90000,2023,'baik',4,1),
(5,2,'MSE-E11','Mouse 11','Logitech','M100','MSN-E011',90000,2023,'baik',0,2),
(5,2,'MSE-E12','Mouse 12','Logitech','M100','MSN-E012',90000,2023,'baik',1,2),
(5,2,'MSE-E13','Mouse 13','Logitech','M100','MSN-E013',90000,2023,'baik',2,2),
(5,2,'MSE-E14','Mouse 14','Logitech','M100','MSN-E014',90000,2023,'rusak',3,2),
(5,2,'MSE-E15','Mouse 15','Logitech','M100','MSN-E015',90000,2023,'baik',4,2),
(5,2,'MSE-E16','Mouse 16','Logitech','M100','MSN-E016',90000,2023,'baik',0,3),
(5,2,'MSE-E17','Mouse 17','Logitech','M100','MSN-E017',90000,2023,'baik',1,3),
(5,2,'MSE-E18','Mouse 18','Logitech','M100','MSN-E018',90000,2023,'baik',2,3),
(5,2,'MSE-E19','Mouse 19','Logitech','M100','MSN-E019',90000,2023,'baik',3,3),
(5,2,'MSE-E20','Mouse 20','Logitech','M100','MSN-E020',90000,2023,'baik',4,3),
(5,2,'MSE-E21','Mouse 21','Logitech','M100','MSN-E021',90000,2023,'baik',0,4),
(5,2,'MSE-E22','Mouse 22','Logitech','M100','MSN-E022',90000,2023,'baik',1,4),
(5,2,'MSE-E23','Mouse 23','Logitech','M100','MSN-E023',90000,2023,'baik',2,4),
(5,2,'MSE-E24','Mouse 24','Logitech','M100','MSN-E024',90000,2023,'baik',3,4),
(5,2,'MSE-E25','Mouse 25','Logitech','M100','MSN-E025',90000,2023,'baik',4,4),
(5,3,'KB-E01','Keyboard 01','Logitech','K120','KBN-E001',120000,2023,'baik',0,0),
(5,3,'KB-E02','Keyboard 02','Logitech','K120','KBN-E002',120000,2023,'baik',1,0),
(5,3,'KB-E03','Keyboard 03','Logitech','K120','KBN-E003',120000,2023,'baik',2,0),
(5,3,'KB-E04','Keyboard 04','Logitech','K120','KBN-E004',120000,2023,'baik',3,0),
(5,3,'KB-E05','Keyboard 05','Logitech','K120','KBN-E005',120000,2023,'baik',4,0),
(5,3,'KB-E06','Keyboard 06','Logitech','K120','KBN-E006',120000,2023,'baik',0,1),
(5,3,'KB-E07','Keyboard 07','Logitech','K120','KBN-E007',120000,2023,'baik',1,1),
(5,3,'KB-E08','Keyboard 08','Logitech','K120','KBN-E008',120000,2023,'baik',2,1),
(5,3,'KB-E09','Keyboard 09','Logitech','K120','KBN-E009',120000,2023,'baik',3,1),
(5,3,'KB-E10','Keyboard 10','Logitech','K120','KBN-E010',120000,2023,'baik',4,1),
(5,3,'KB-E11','Keyboard 11','Logitech','K120','KBN-E011',120000,2023,'baik',0,2),
(5,3,'KB-E12','Keyboard 12','Logitech','K120','KBN-E012',120000,2023,'baik',1,2),
(5,3,'KB-E13','Keyboard 13','Logitech','K120','KBN-E013',120000,2023,'baik',2,2),
(5,3,'KB-E14','Keyboard 14','Logitech','K120','KBN-E014',120000,2023,'rusak',3,2),
(5,3,'KB-E15','Keyboard 15','Logitech','K120','KBN-E015',120000,2023,'baik',4,2),
(5,3,'KB-E16','Keyboard 16','Logitech','K120','KBN-E016',120000,2023,'baik',0,3),
(5,3,'KB-E17','Keyboard 17','Logitech','K120','KBN-E017',120000,2023,'baik',1,3),
(5,3,'KB-E18','Keyboard 18','Logitech','K120','KBN-E018',120000,2023,'baik',2,3),
(5,3,'KB-E19','Keyboard 19','Logitech','K120','KBN-E019',120000,2023,'maintenance',3,3),
(5,3,'KB-E20','Keyboard 20','Logitech','K120','KBN-E020',120000,2023,'baik',4,3),
(5,3,'KB-E21','Keyboard 21','Logitech','K120','KBN-E021',120000,2023,'baik',0,4),
(5,3,'KB-E22','Keyboard 22','Logitech','K120','KBN-E022',120000,2023,'baik',1,4),
(5,3,'KB-E23','Keyboard 23','Logitech','K120','KBN-E023',120000,2023,'baik',2,4),
(5,3,'KB-E24','Keyboard 24','Logitech','K120','KBN-E024',120000,2023,'baik',3,4),
(5,3,'KB-E25','Keyboard 25','Logitech','K120','KBN-E025',120000,2023,'baik',4,4),
(5,4,'TV-E01','TV Monitor 1','Samsung','Flip 75"','SN-SAM-E001',32000000,2023,'baik',0,6),
(5,4,'TV-E02','TV Monitor 2','Samsung','Flip 75"','SN-SAM-E002',32000000,2023,'baik',3,6),
(5,5,'PRJ-E01','Proyektor CBT','Epson','EB-L200F','SN-EPS-E001',18000000,2023,'baik',2,6),
(5,6,'AC-E01','AC Split 1','Daikin','FTV35AXV14','SN-DAI-E001',5200000,2023,'baik',0,7),
(5,6,'AC-E02','AC Split 2','Daikin','FTV35AXV14','SN-DAI-E002',5200000,2023,'baik',3,7),
(5,6,'AC-E03','AC Split 3','Daikin','FTV35AXV14','SN-DAI-E003',5200000,2023,'baik',5,7);

-- ============================================================
-- ASSET SPECS  (PC specs only, yang lain straightforward)
-- ============================================================

-- Specs PC Lab A (Lenovo ThinkCentre M70s)
INSERT INTO `asset_specs` (`asset_id`, `spec_key`, `spec_value`, `urutan`)
SELECT a.id, s.spec_key, s.spec_value, s.urutan
FROM assets a
JOIN (
  SELECT 'CPU'        AS spec_key, 'Intel Core i5-10400 @ 2.9GHz'  AS spec_value, 1 AS urutan UNION ALL
  SELECT 'RAM',             '8GB DDR4 2666MHz',   2 UNION ALL
  SELECT 'Storage',         '256GB SSD NVMe',      3 UNION ALL
  SELECT 'GPU',             'Intel UHD Graphics 630', 4 UNION ALL
  SELECT 'OS',              'Windows 10 Pro 64-bit', 5 UNION ALL
  SELECT 'Monitor',         '19.5" FHD 1920x1080', 6
) s
WHERE a.kode_aset IN ('PC-A01','PC-A02','PC-A03','PC-A04','PC-A05','PC-A06',
                       'PC-A07','PC-A08','PC-A09','PC-A10','PC-A11','PC-A12',
                       'PC-A13','PC-A14','PC-A15','PC-A16','PC-A17');

-- Specs PC-A18 (Guru - spec lebih tinggi)
INSERT INTO `asset_specs` (`asset_id`, `spec_key`, `spec_value`, `urutan`)
SELECT a.id, s.spec_key, s.spec_value, s.urutan
FROM assets a
JOIN (
  SELECT 'CPU'     AS spec_key, 'Intel Core i7-10700 @ 2.9GHz' AS spec_value, 1 AS urutan UNION ALL
  SELECT 'RAM',          '16GB DDR4 2933MHz', 2 UNION ALL
  SELECT 'Storage',      '512GB SSD NVMe',    3 UNION ALL
  SELECT 'GPU',          'Intel UHD Graphics 630', 4 UNION ALL
  SELECT 'OS',           'Windows 11 Pro 64-bit', 5 UNION ALL
  SELECT 'Monitor',      '23.8" FHD 1920x1080',   6
) s WHERE a.kode_aset = 'PC-A18';

-- Specs PC Lab B (HP ProDesk 400 G7)
INSERT INTO `asset_specs` (`asset_id`, `spec_key`, `spec_value`, `urutan`)
SELECT a.id, s.spec_key, s.spec_value, s.urutan
FROM assets a
JOIN (
  SELECT 'CPU'     AS spec_key, 'Intel Core i5-10500 @ 3.1GHz' AS spec_value, 1 AS urutan UNION ALL
  SELECT 'RAM',          '8GB DDR4 2666MHz',  2 UNION ALL
  SELECT 'Storage',      '512GB SSD NVMe',    3 UNION ALL
  SELECT 'GPU',          'Intel UHD Graphics 630', 4 UNION ALL
  SELECT 'OS',           'Windows 10 Pro 64-bit',  5 UNION ALL
  SELECT 'Monitor',      '21.5" FHD 1920x1080',    6
) s WHERE a.kode_aset IN ('PC-B01','PC-B02','PC-B03','PC-B04','PC-B05','PC-B06',
                            'PC-B07','PC-B08','PC-B09','PC-B10','PC-B11','PC-B12',
                            'PC-B13','PC-B14');

INSERT INTO `asset_specs` (`asset_id`, `spec_key`, `spec_value`, `urutan`)
SELECT a.id, s.spec_key, s.spec_value, s.urutan
FROM assets a
JOIN (
  SELECT 'CPU'     AS spec_key, 'Intel Core i7-10700 @ 2.9GHz' AS spec_value, 1 AS urutan UNION ALL
  SELECT 'RAM',          '16GB DDR4 2933MHz', 2 UNION ALL
  SELECT 'Storage',      '1TB SSD NVMe',      3 UNION ALL
  SELECT 'GPU',          'NVIDIA Quadro P400', 4 UNION ALL
  SELECT 'OS',           'Windows 11 Pro 64-bit', 5 UNION ALL
  SELECT 'Monitor',      '27" QHD 2560x1440',     6
) s WHERE a.kode_aset = 'PC-B15';

-- Specs PC Lab C (Dell OptiPlex 3080)
INSERT INTO `asset_specs` (`asset_id`, `spec_key`, `spec_value`, `urutan`)
SELECT a.id, s.spec_key, s.spec_value, s.urutan
FROM assets a
JOIN (
  SELECT 'CPU'     AS spec_key, 'Intel Core i5-10500T @ 2.3GHz' AS spec_value, 1 AS urutan UNION ALL
  SELECT 'RAM',          '8GB DDR4 2933MHz',  2 UNION ALL
  SELECT 'Storage',      '256GB SSD SATA',    3 UNION ALL
  SELECT 'GPU',          'Intel UHD Graphics 630', 4 UNION ALL
  SELECT 'OS',           'Ubuntu 22.04 LTS',  5 UNION ALL
  SELECT 'Monitor',      '21.5" FHD 1920x1080', 6
) s WHERE a.kode_aset IN ('PC-C01','PC-C02','PC-C03','PC-C04','PC-C05','PC-C06',
                            'PC-C07','PC-C08','PC-C09','PC-C10','PC-C11');

INSERT INTO `asset_specs` (`asset_id`, `spec_key`, `spec_value`, `urutan`)
SELECT a.id, s.spec_key, s.spec_value, s.urutan
FROM assets a
JOIN (
  SELECT 'CPU'     AS spec_key, 'Intel Core i7-10700T @ 2.0GHz' AS spec_value, 1 AS urutan UNION ALL
  SELECT 'RAM',          '32GB DDR4 2933MHz', 2 UNION ALL
  SELECT 'Storage',      '512GB SSD NVMe',    3 UNION ALL
  SELECT 'GPU',          'Intel UHD Graphics 630', 4 UNION ALL
  SELECT 'OS',           'Ubuntu 22.04 LTS',  5 UNION ALL
  SELECT 'Monitor',      '24" FHD 1920x1080', 6
) s WHERE a.kode_aset = 'PC-C12';

-- Specs PC Lab D (Acer Veriton M4680G)
INSERT INTO `asset_specs` (`asset_id`, `spec_key`, `spec_value`, `urutan`)
SELECT a.id, s.spec_key, s.spec_value, s.urutan
FROM assets a
JOIN (
  SELECT 'CPU'     AS spec_key, 'Intel Core i3-10100 @ 3.6GHz' AS spec_value, 1 AS urutan UNION ALL
  SELECT 'RAM',          '4GB DDR4 2666MHz',  2 UNION ALL
  SELECT 'Storage',      '1TB HDD SATA',      3 UNION ALL
  SELECT 'GPU',          'Intel UHD Graphics 630', 4 UNION ALL
  SELECT 'OS',           'Windows 10 Home 64-bit', 5 UNION ALL
  SELECT 'Monitor',      '19.5" HD 1366x768', 6
) s WHERE a.kode_aset IN ('PC-D01','PC-D02','PC-D03','PC-D04','PC-D05','PC-D06',
                            'PC-D07','PC-D08','PC-D09','PC-D10','PC-D11','PC-D12',
                            'PC-D13','PC-D14','PC-D15','PC-D16','PC-D17','PC-D18','PC-D19');

INSERT INTO `asset_specs` (`asset_id`, `spec_key`, `spec_value`, `urutan`)
SELECT a.id, s.spec_key, s.spec_value, s.urutan
FROM assets a
JOIN (
  SELECT 'CPU'     AS spec_key, 'Intel Core i5-10400 @ 2.9GHz' AS spec_value, 1 AS urutan UNION ALL
  SELECT 'RAM',          '8GB DDR4 2666MHz',  2 UNION ALL
  SELECT 'Storage',      '256GB SSD + 1TB HDD', 3 UNION ALL
  SELECT 'GPU',          'Intel UHD Graphics 630', 4 UNION ALL
  SELECT 'OS',           'Windows 10 Pro 64-bit',  5 UNION ALL
  SELECT 'Monitor',      '21.5" FHD 1920x1080',    6
) s WHERE a.kode_aset = 'PC-D20';

-- Specs PC Lab E (Lenovo ThinkCentre M90s - CBT terbaru)
INSERT INTO `asset_specs` (`asset_id`, `spec_key`, `spec_value`, `urutan`)
SELECT a.id, s.spec_key, s.spec_value, s.urutan
FROM assets a
JOIN (
  SELECT 'CPU'     AS spec_key, 'Intel Core i5-12400 @ 2.5GHz' AS spec_value, 1 AS urutan UNION ALL
  SELECT 'RAM',          '16GB DDR4 3200MHz', 2 UNION ALL
  SELECT 'Storage',      '512GB SSD NVMe',    3 UNION ALL
  SELECT 'GPU',          'Intel UHD Graphics 730', 4 UNION ALL
  SELECT 'OS',           'Windows 11 Pro 64-bit',  5 UNION ALL
  SELECT 'Monitor',      '23.8" FHD 1920x1080 IPS', 6
) s WHERE a.kode_aset IN ('PC-E01','PC-E02','PC-E03','PC-E04','PC-E05','PC-E06',
                            'PC-E07','PC-E08','PC-E09','PC-E10','PC-E11','PC-E12',
                            'PC-E13','PC-E14','PC-E15','PC-E16','PC-E17','PC-E18',
                            'PC-E19','PC-E20','PC-E21','PC-E22','PC-E23','PC-E24');

INSERT INTO `asset_specs` (`asset_id`, `spec_key`, `spec_value`, `urutan`)
SELECT a.id, s.spec_key, s.spec_value, s.urutan
FROM assets a
JOIN (
  SELECT 'CPU'     AS spec_key, 'Intel Core i7-12700 @ 2.1GHz' AS spec_value, 1 AS urutan UNION ALL
  SELECT 'RAM',          '32GB DDR4 3200MHz', 2 UNION ALL
  SELECT 'Storage',      '1TB SSD NVMe',      3 UNION ALL
  SELECT 'GPU',          'Intel UHD Graphics 770', 4 UNION ALL
  SELECT 'OS',           'Windows 11 Pro 64-bit',  5 UNION ALL
  SELECT 'Monitor',      '27" QHD 2560x1440 IPS',  6
) s WHERE a.kode_aset = 'PC-E25';

-- ============================================================
-- KONDISI LOGS - Sample history
-- ============================================================
INSERT INTO `kondisi_logs` (`asset_id`, `kondisi_lama`, `kondisi_baru`, `catatan`, `diubah_oleh`) VALUES
((SELECT id FROM assets WHERE kode_aset='PC-A03'), 'baik',        'rusak',       'RAM slot 2 error, perlu penggantian modul', 1),
((SELECT id FROM assets WHERE kode_aset='PC-A08'), 'baik',        'maintenance', 'Jadwal reinstall OS karena virus',          1),
((SELECT id FROM assets WHERE kode_aset='PC-A16'), 'maintenance', 'rusak',       'Monitor backlight putus setelah perbaikan', 2),
((SELECT id FROM assets WHERE kode_aset='AC-A02'), 'baik',        'maintenance', 'Filter kotor, perlu servis berkala',        1),
((SELECT id FROM assets WHERE kode_aset='PC-B03'), 'baik',        'rusak',       'Motherboard tidak terdeteksi',              1),
((SELECT id FROM assets WHERE kode_aset='AC-B02'), 'baik',        'rusak',       'Kompresor rusak, butuh penggantian part',   2),
((SELECT id FROM assets WHERE kode_aset='PC-C03'), 'baik',        'rusak',       'HDD bad sector, data tidak bisa diakses',  1),
((SELECT id FROM assets WHERE kode_aset='PC-D04'), 'baik',        'rusak',       'PSU mati total',                           1),
((SELECT id FROM assets WHERE kode_aset='PC-D09'), 'baik',        'maintenance', 'Update BIOS dan driver',                   2),
((SELECT id FROM assets WHERE kode_aset='PC-D14'), 'baik',        'rusak',       'Keyboard port PS/2 rusak',                 1),
((SELECT id FROM assets WHERE kode_aset='PC-E10'), 'baik',        'rusak',       'GPU integrated error, display artefak',    1),
((SELECT id FROM assets WHERE kode_aset='PC-E16'), 'baik',        'maintenance', 'Upgrade RAM dari 8GB ke 16GB',             2);

-- ============================================================
-- USEFUL VIEWS (optional, bantu query di PHP)
-- ============================================================

-- View: Ringkasan aset per lab
CREATE OR REPLACE VIEW `v_lab_summary` AS
SELECT
  l.id                                        AS lab_id,
  l.kode_lab,
  l.nama                                      AS nama_lab,
  COUNT(DISTINCT a.id)                        AS total_aset,
  SUM(a.harga)                                AS total_nilai,
  SUM(CASE WHEN at2.kode='PC'  THEN 1 ELSE 0 END) AS total_pc,
  SUM(CASE WHEN at2.kode='MSE' THEN 1 ELSE 0 END) AS total_mouse,
  SUM(CASE WHEN at2.kode='KB'  THEN 1 ELSE 0 END) AS total_keyboard,
  SUM(CASE WHEN at2.kode='TV'  THEN 1 ELSE 0 END) AS total_tv,
  SUM(CASE WHEN at2.kode='PRJ' THEN 1 ELSE 0 END) AS total_proyektor,
  SUM(CASE WHEN at2.kode='AC'  THEN 1 ELSE 0 END) AS total_ac,
  SUM(CASE WHEN a.kondisi='baik'        THEN 1 ELSE 0 END) AS kondisi_baik,
  SUM(CASE WHEN a.kondisi='rusak'       THEN 1 ELSE 0 END) AS kondisi_rusak,
  SUM(CASE WHEN a.kondisi='maintenance' THEN 1 ELSE 0 END) AS kondisi_maintenance
FROM labs l
LEFT JOIN assets a  ON a.lab_id = l.id AND a.is_active = 1
LEFT JOIN asset_types at2 ON at2.id = a.asset_type_id
WHERE l.is_active = 1
GROUP BY l.id, l.kode_lab, l.nama;

-- View: Detail aset + tipe + lab (include kolom grid & rotasi)
CREATE OR REPLACE VIEW `v_asset_detail` AS
SELECT
  a.id, a.kode_aset, a.nama, a.merk, a.model,
  a.serial_number, a.harga, a.tahun_beli,
  a.kondisi, a.keterangan,
  a.posisi_x, a.posisi_y,
  a.rotasi, a.nama_custom,
  a.is_active,
  at2.kode  AS tipe_kode,
  at2.nama  AS tipe_nama,
  at2.icon  AS tipe_icon,
  l.id      AS lab_id,
  l.kode_lab,
  l.nama    AS nama_lab,
  l.grid_cols,
  l.grid_rows,
  l.pintu_posisi
FROM assets a
JOIN asset_types at2 ON at2.id = a.asset_type_id
JOIN labs l          ON l.id   = a.lab_id;

-- ============================================================
-- END OF FILE
-- ============================================================