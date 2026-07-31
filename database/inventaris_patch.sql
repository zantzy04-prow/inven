-- ============================================================
--  INVENTARIS SEKOLAH — PATCH (Compatible MySQL 5.7+)
--  Jalankan SETELAH inventaris.sql
-- ============================================================

-- ------------------------------------------------------------
-- 1. Tambah kolom ke tabel labs (tanpa IF NOT EXISTS)
-- ------------------------------------------------------------
ALTER TABLE `labs`
  ADD COLUMN `grid_cols`    TINYINT UNSIGNED NOT NULL DEFAULT 8           AFTER `luas_m2`,
  ADD COLUMN `grid_rows`    TINYINT UNSIGNED NOT NULL DEFAULT 6           AFTER `grid_cols`,
  ADD COLUMN `pintu_posisi` VARCHAR(20)      NOT NULL DEFAULT 'kiri-bawah' AFTER `grid_rows`;

-- Update nilai grid per lab
UPDATE `labs` SET `grid_cols`=8,  `grid_rows`=6, `pintu_posisi`='kiri-bawah'   WHERE `kode_lab`='LAB-A';
UPDATE `labs` SET `grid_cols`=8,  `grid_rows`=6, `pintu_posisi`='kanan-bawah'  WHERE `kode_lab`='LAB-B';
UPDATE `labs` SET `grid_cols`=6,  `grid_rows`=5, `pintu_posisi`='kiri-bawah'   WHERE `kode_lab`='LAB-C';
UPDATE `labs` SET `grid_cols`=8,  `grid_rows`=7, `pintu_posisi`='tengah-bawah' WHERE `kode_lab`='LAB-D';
UPDATE `labs` SET `grid_cols`=10, `grid_rows`=8, `pintu_posisi`='kiri-atas'    WHERE `kode_lab`='LAB-E';

-- ------------------------------------------------------------
-- 2. Tambah kolom ke tabel assets (tanpa IF NOT EXISTS)
-- ------------------------------------------------------------
ALTER TABLE `assets`
  ADD COLUMN `rotasi`      SMALLINT     NOT NULL DEFAULT 0    AFTER `posisi_y`,
  ADD COLUMN `nama_custom` VARCHAR(100) NULL     DEFAULT NULL AFTER `rotasi`;

-- ------------------------------------------------------------
-- 3. Tambah tipe aset baru (IGNORE jika sudah ada)
-- ------------------------------------------------------------
INSERT IGNORE INTO `asset_types` (`kode`, `nama`, `icon`) VALUES
  ('DESK', 'Meja Siswa',     '🪑'),
  ('CHR',  'Kursi',          '💺'),
  ('TDK',  'Meja Guru',      '🏫'),
  ('WBD',  'Papan Tulis',    '📋'),
  ('FAN',  'Kipas Angin',    '🌀'),
  ('CAB',  'Lemari/Kabinet', '🗄️');

-- ------------------------------------------------------------
-- 4. Update VIEW v_asset_detail
-- ------------------------------------------------------------
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
--  END OF PATCH
-- ============================================================
