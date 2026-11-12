-- ==========================================================
-- SISTEM INFORMASI MANAJEMEN INVENTARIS BARANG BERBASIS QR CODE
-- KELOMPOK 6 MPSI
-- Database: kel6mpsi_inventaris
-- ==========================================================

CREATE DATABASE IF NOT EXISTS `kel6mpsi_inventaris` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `kel6mpsi_inventaris`;

SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------
-- 1. Table structure for table `roles`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL UNIQUE,
  `display_name` VARCHAR(100) NOT NULL,
  `description` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 2. Table structure for table `users`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `role_id` INT NOT NULL,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `phone` VARCHAR(25) NULL,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 3. Table structure for table `categories`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(20) NOT NULL UNIQUE,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 4. Table structure for table `locations`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `locations`;
CREATE TABLE `locations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(20) NOT NULL UNIQUE,
  `name` VARCHAR(100) NOT NULL,
  `building` VARCHAR(100) NULL,
  `floor` VARCHAR(50) NULL,
  `description` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 5. Table structure for table `items`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `items`;
CREATE TABLE `items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `item_code` VARCHAR(50) NOT NULL UNIQUE,
  `name` VARCHAR(150) NOT NULL,
  `category_id` INT NOT NULL,
  `location_id` INT NOT NULL,
  `quantity` INT NOT NULL DEFAULT 0,
  `unit` VARCHAR(30) NOT NULL DEFAULT 'Unit',
  `item_condition` ENUM('Baik', 'Rusak Ringan', 'Rusak Berat') NOT NULL DEFAULT 'Baik',
  `status` ENUM('Tersedia', 'Dipinjam', 'Habis', 'Dalam Perbaikan', 'Afkir') NOT NULL DEFAULT 'Tersedia',
  `entry_date` DATE NOT NULL,
  `description` TEXT NULL,
  `qr_code_text` VARCHAR(255) NOT NULL,
  `created_by` INT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_items_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_items_location` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_items_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 6. Table structure for table `item_transactions`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `item_transactions`;
CREATE TABLE `item_transactions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `transaction_code` VARCHAR(50) NOT NULL UNIQUE,
  `item_id` INT NOT NULL,
  `transaction_type` ENUM('Masuk', 'Keluar') NOT NULL,
  `quantity` INT NOT NULL,
  `stock_before` INT NOT NULL,
  `stock_after` INT NOT NULL,
  `transaction_date` DATETIME NOT NULL,
  `user_id` INT NULL,
  `recipient_or_supplier` VARCHAR(150) NULL,
  `notes` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_trx_item` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_trx_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 7. Table structure for table `item_histories`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `item_histories`;
CREATE TABLE `item_histories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `action_type` VARCHAR(50) NOT NULL,
  `item_id` INT NULL,
  `item_code` VARCHAR(50) NOT NULL,
  `item_name` VARCHAR(150) NOT NULL,
  `user_id` INT NULL,
  `user_name` VARCHAR(100) NOT NULL,
  `user_role` VARCHAR(50) NOT NULL,
  `description` TEXT NOT NULL,
  `old_values` LONGTEXT NULL,
  `new_values` LONGTEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_history_item` (`item_id`),
  INDEX `idx_history_date` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 8. Table structure for table `settings`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `setting_key` VARCHAR(50) NOT NULL UNIQUE,
  `setting_value` TEXT NULL,
  `description` VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================================
-- DATA AWAL (SEED DATA)
-- ==========================================================

-- Roles
INSERT INTO `roles` (`id`, `name`, `display_name`, `description`) VALUES
(1, 'admin', 'Administrator', 'Akses penuh ke seluruh modul sistem inventaris, manajemen pengguna, dan pengaturan.'),
(2, 'petugas', 'Petugas Inventaris', 'Mengelola data barang, mutasi masuk/keluar, pemindaian QR code, serta laporan.'),
(3, 'peninjau', 'Peninjau (Viewer)', 'Hanya melihat data dashboard, inventaris barang, riwayat, dan laporan.');

-- Users
-- Password admin: admin123
-- Password petugas: petugas123
-- Password peninjau: peninjau123
INSERT INTO `users` (`id`, `role_id`, `username`, `password`, `full_name`, `email`, `phone`, `status`) VALUES
(1, 1, 'admin', '$2y$10$kRt5EvKgeolkOOj9DOTtbOQiQbc5xvp2GdgO2p9jUFY.tPEqnPRMK', 'Administrator Utama', 'admin@kel6mpsi.local', '081234567890', 'active'),
(2, 2, 'petugas', '$2y$10$9MZzjcFUzcjxV5KgpoQNgeOX/PA7.Kc54WB3rzjQv0h0CTlpV1ota', 'Siti Rahmawati (Petugas)', 'petugas@kel6mpsi.local', '082345678901', 'active'),
(3, 3, 'peninjau', '$2y$10$PF9yunfmsYDQ3YqWlN7JE.yfluQ8q4TTg2GVqsd2PPN6E20IB6xKC', 'Budi Santoso (Auditor/Peninjau)', 'peninjau@kel6mpsi.local', '083456789012', 'active');

-- Categories
INSERT INTO `categories` (`id`, `code`, `name`, `description`) VALUES
(1, 'CAT-ELK', 'Elektronik & Perangkat Digital', 'Perangkat elektronik, proyektor, monitor, dan aksesoris audio-visual.'),
(2, 'CAT-KOM', 'Komputer & Jaringan', 'PC Desktop, Laptop, Server, Router, Switch, dan perkabelan jaringan.'),
(3, 'CAT-FUR', 'Furnitur & Perabot Kantor', 'Meja kerja, kursi ergonomis, lemari berkas, dan partisi ruangan.'),
(4, 'CAT-ATK', 'Perlengkapan Kantor & Arsip', 'Printer, scanner, mesin penghancur kertas, dan perlengkapan inventaris tetap.'),
(5, 'CAT-LAB', 'Peralatan Laboratorium', 'Alat praktikum, mikroskop, multimeter, dan instrumen pengujian.');

-- Locations
INSERT INTO `locations` (`id`, `code`, `name`, `building`, `floor`, `description`) VALUES
(1, 'LOK-SRV', 'Ruang Server & IT Data Center', 'Gedung A', 'Lantai 2', 'Area penyimpanan server utama, rack networking, dan perangkat IT sensitif.'),
(2, 'LOK-KNT', 'Ruang Kantor Operasional', 'Gedung A', 'Lantai 1', 'Ruangan kerja staf administrasi, operasional, dan manajemen inventaris.'),
(3, 'LOK-GDA', 'Gudang Logistik Pusat A', 'Gedung B', 'Lantai Dasar', 'Penyimpanan utama stok cadangan, unit baru, dan suku cadang inventaris.'),
(4, 'LOK-LAB', 'Laboratorium Komputer & Riset', 'Gedung B', 'Lantai 3', 'Ruang praktikum komputer multimedia dan workstation penelitian.'),
(5, 'LOK-RPT', 'Ruang Pertemuan & Rapat Utama', 'Gedung A', 'Lantai 2', 'Ruang rapat kapasitas 30 orang dilengkapi proyektor dan display.');

-- Items
INSERT INTO `items` (`id`, `item_code`, `name`, `category_id`, `location_id`, `quantity`, `unit`, `item_condition`, `status`, `entry_date`, `description`, `qr_code_text`, `created_by`, `created_at`) VALUES
(1, 'BRG-2024-0001', 'Laptop Dell Latitude 5420 i7 16GB', 2, 2, 8, 'Unit', 'Baik', 'Tersedia', '2024-01-15', 'Laptop operasional staf manajemen dengan SSD 512GB dan Windows 11 Pro.', 'BRG-2024-0001', 1, '2024-01-15 08:30:00'),
(2, 'BRG-2024-0002', 'Proyektor Epson EB-E500 3300 Lumens', 1, 5, 2, 'Unit', 'Baik', 'Tersedia', '2024-02-10', 'Proyektor HDMI presentasi ruang rapat utama dilengkapi remote control.', 'BRG-2024-0002', 1, '2024-02-10 09:15:00'),
(3, 'BRG-2024-0003', 'Cisco Catalyst Switch 24-Port Gigabit', 2, 1, 3, 'Unit', 'Baik', 'Tersedia', '2024-02-20', 'Switch jaringan manageable layer 2 untuk distribusi LAN gedung A.', 'BRG-2024-0003', 1, '2024-02-20 11:00:00'),
(4, 'BRG-2024-0004', 'Kursi Kerja Ergonomis Mesh Hitam', 3, 2, 18, 'Unit', 'Baik', 'Tersedia', '2024-03-01', 'Kursi putar hidrolik dengan penopang lumbal dan sandaran tangan dapat diatur.', 'BRG-2024-0004', 2, '2024-03-01 13:45:00'),
(5, 'BRG-2024-0005', 'Printer HP LaserJet Pro M404dn', 4, 2, 2, 'Unit', 'Rusak Ringan', 'Dalam Perbaikan', '2024-03-12', 'Printer mono laser kecepatan tinggi. Roller kertas agak macet, sedang diservis.', 'BRG-2024-0005', 2, '2024-03-12 10:20:00'),
(6, 'BRG-2024-0006', 'Meja Kerja Modular 120x60 cm Kayu Jati Belanda', 3, 2, 12, 'Unit', 'Baik', 'Tersedia', '2024-03-15', 'Meja kerja staf dengan lubang kabel grommet dan rangka besi hollow.', 'BRG-2024-0006', 2, '2024-03-15 14:10:00'),
(7, 'BRG-2024-0007', 'Workstation PC Intel Core i9 Lab AI', 2, 4, 15, 'Unit', 'Baik', 'Tersedia', '2024-04-05', 'Komputer riset grafis dengan RTX 4070 12GB dan RAM 32GB.', 'BRG-2024-0007', 1, '2024-04-05 09:00:00'),
(8, 'BRG-2024-0008', 'UPS APC Smart-UPS RT 3000VA On-Line', 1, 1, 1, 'Unit', 'Baik', 'Tersedia', '2024-04-18', 'Sumber daya cadangan untuk server farm dan rak storage.', 'BRG-2024-0008', 1, '2024-04-18 15:30:00'),
(9, 'BRG-2024-0009', 'Digital Oscilloscope Rigol DS1054Z 50MHz', 5, 4, 4, 'Unit', 'Baik', 'Tersedia', '2024-05-02', 'Osiloskop 4 channel untuk pengujian sinyal laboratorium rekayasa.', 'BRG-2024-0009', 2, '2024-05-02 11:20:00'),
(10, 'BRG-2024-0010', 'Monitor LG 24 Inch IPS Full HD 75Hz', 1, 4, 15, 'Unit', 'Baik', 'Tersedia', '2024-05-15', 'Layar monitor IPS untuk laboratorium komputer dan desain visual.', 'BRG-2024-0010', 2, '2024-05-15 10:00:00'),
(11, 'BRG-2024-0011', 'Scanner Fujitsu ScanSnap iX1600 ADF', 4, 2, 1, 'Unit', 'Rusak Berat', 'Afkir', '2024-06-01', 'Scanner arsip otomatis. Motor scan utama terbakar akibat lonjakan arus.', 'BRG-2024-0011', 2, '2024-06-01 16:00:00'),
(12, 'BRG-2024-0012', 'Lemari Arsip Besi 4 Pintu Fireproof', 3, 3, 4, 'Unit', 'Baik', 'Tersedia', '2024-06-10', 'Lemari berkas tahan api untuk dokumen legalitas dan inventaris perusahaan.', 'BRG-2024-0012', 1, '2024-06-10 13:00:00');

-- Transactions
INSERT INTO `item_transactions` (`id`, `transaction_code`, `item_id`, `transaction_type`, `quantity`, `stock_before`, `stock_after`, `transaction_date`, `user_id`, `recipient_or_supplier`, `notes`) VALUES
(1, 'TRX-IN-202401-0001', 1, 'Masuk', 10, 0, 10, '2024-01-15 08:45:00', 1, 'PT Mitra Komputindo Utama', 'Pengadaan 10 unit laptop baru untuk staf'),
(2, 'TRX-OUT-202401-0001', 1, 'Keluar', 2, 10, 8, '2024-01-20 14:15:00', 2, 'Divisi Marketing & Sales', 'Pendistribusian laptop operasional staf baru'),
(3, 'TRX-IN-202402-0001', 3, 'Masuk', 3, 0, 3, '2024-02-20 11:30:00', 1, 'CV Jaringan Mandiri', 'Upgrade switch distribusi server rack'),
(4, 'TRX-IN-202403-0001', 4, 'Masuk', 20, 0, 20, '2024-03-01 14:00:00', 2, 'PT Karya Furnitur Sejahtera', 'Pengadaan kursi kerja ergonomis kantor'),
(5, 'TRX-OUT-202403-0001', 4, 'Keluar', 2, 20, 18, '2024-03-05 10:00:00', 2, 'Ruang Direksi Lantai 2', 'Penempatan kursi untuk meja rapat kecil'),
(6, 'TRX-IN-202404-0001', 7, 'Masuk', 15, 0, 15, '2024-04-05 09:30:00', 1, 'PT Teknologi Prima Nusantara', 'Pengadaan unit PC workstation Lab AI');

-- Histories
INSERT INTO `item_histories` (`id`, `action_type`, `item_id`, `item_code`, `item_name`, `user_id`, `user_name`, `user_role`, `description`, `old_values`, `new_values`, `created_at`) VALUES
(1, 'TAMBAH_BARANG', 1, 'BRG-2024-0001', 'Laptop Dell Latitude 5420 i7 16GB', 1, 'Administrator Utama', 'admin', 'Mendaftarkan item baru Laptop Dell Latitude 5420 i7 16GB ke dalam sistem.', NULL, '{"item_code":"BRG-2024-0001","name":"Laptop Dell Latitude 5420 i7 16GB","category_id":"2","location_id":"2","quantity":"10","item_condition":"Baik","status":"Tersedia"}', '2024-01-15 08:30:00'),
(2, 'BARANG_MASUK', 1, 'BRG-2024-0001', 'Laptop Dell Latitude 5420 i7 16GB', 1, 'Administrator Utama', 'admin', 'Transaksi barang masuk sejumlah 10 Unit dari PT Mitra Komputindo Utama.', '{"stock":0}', '{"stock":10}', '2024-01-15 08:45:00'),
(3, 'BARANG_KELUAR', 1, 'BRG-2024-0001', 'Laptop Dell Latitude 5420 i7 16GB', 2, 'Siti Rahmawati (Petugas)', 'petugas', 'Transaksi barang keluar sejumlah 2 Unit ke Divisi Marketing & Sales. Stok tersisa: 8 Unit.', '{"stock":10}', '{"stock":8}', '2024-01-20 14:15:00'),
(4, 'TAMBAH_BARANG', 5, 'BRG-2024-0005', 'Printer HP LaserJet Pro M404dn', 2, 'Siti Rahmawati (Petugas)', 'petugas', 'Mendaftarkan item baru Printer HP LaserJet Pro M404dn.', NULL, '{"item_code":"BRG-2024-0005","name":"Printer HP LaserJet Pro M404dn","category_id":"4","location_id":"2","quantity":"2","item_condition":"Baik","status":"Tersedia"}', '2024-03-12 10:20:00'),
(5, 'UBAH_KONDISI', 5, 'BRG-2024-0005', 'Printer HP LaserJet Pro M404dn', 2, 'Siti Rahmawati (Petugas)', 'petugas', 'Mengubah kondisi barang dari Baik menjadi Rusak Ringan dan status menjadi Dalam Perbaikan.', '{"condition":"Baik","status":"Tersedia"}', '{"condition":"Rusak Ringan","status":"Dalam Perbaikan"}', '2024-04-10 11:00:00'),
(6, 'TAMBAH_BARANG', 11, 'BRG-2024-0011', 'Scanner Fujitsu ScanSnap iX1600 ADF', 2, 'Siti Rahmawati (Petugas)', 'petugas', 'Mendaftarkan item baru Scanner Fujitsu ScanSnap iX1600 ADF.', NULL, '{"item_code":"BRG-2024-0011","name":"Scanner Fujitsu ScanSnap iX1600 ADF","category_id":"4","location_id":"2","quantity":"1","item_condition":"Baik","status":"Tersedia"}', '2024-06-01 16:00:00'),
(7, 'UBAH_KONDISI', 11, 'BRG-2024-0011', 'Scanner Fujitsu ScanSnap iX1600 ADF', 1, 'Administrator Utama', 'admin', 'Memperbarui status Scanner Fujitsu menjadi Afkir (Rusak Berat).', '{"condition":"Baik","status":"Tersedia"}', '{"condition":"Rusak Berat","status":"Afkir"}', '2024-07-02 09:15:00');

-- System Settings
INSERT INTO `settings` (`setting_key`, `setting_value`, `description`) VALUES
('app_name', 'SIM-INVENTARIS', 'Nama Singkat Aplikasi'),
('app_title', 'Sistem Informasi Manajemen Inventaris Barang QR Code', 'Judul Lengkap Aplikasi'),
('institution_name', 'Institut Teknologi & Bisnis Kelompok 6', 'Nama Lembaga / Instansi Pemilik Inventaris'),
('institution_address', 'Jl. Kampus Merdeka No. 6, Gedung MPSI Lantai 3', 'Alamat Lengkap Instansi'),
('institution_phone', '(021) 7890-6622 / 0812-3456-7890', 'Nomor Kontak / WhatsApp'),
('institution_email', 'inventaris@kel6mpsi.ac.id', 'Email Resmi Instansi'),
('item_code_prefix', 'BRG', 'Format Awalan Kode Barang Otomatis'),
('currency_symbol', 'Rp', 'Simbol Mata Uang');

SET FOREIGN_KEY_CHECKS = 1;

