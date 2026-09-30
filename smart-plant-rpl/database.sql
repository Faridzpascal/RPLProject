-- ===================================================================
-- Skrip Database: Smart Plant Dashboard (RPL IoT Project)
-- DBMS: MySQL / MariaDB (XAMPP / Laragon Compatible)
-- Dibuat untuk: Monitoring & Kontrol Penyiraman Tanaman Berbasis IoT
-- ===================================================================

-- 1. Buat Database
CREATE DATABASE IF NOT EXISTS `smart_plant_db`
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE `smart_plant_db`;

-- 2. Hapus Tabel Lama jika ada (Urutan foreign key aman)
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `sensor_logs`;
DROP TABLE IF EXISTS `devices`;
SET FOREIGN_KEY_CHECKS = 1;

-- 3. Tabel `devices`
-- Menyimpan informasi perangkat ESP32, mode operasi, dan status pompa saat ini
CREATE TABLE `devices` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `mac_address` VARCHAR(50) NOT NULL UNIQUE COMMENT 'Alamat MAC unik ESP32',
    `name` VARCHAR(100) NOT NULL COMMENT 'Nama label tanaman/perangkat',
    `operating_mode` ENUM('AUTO', 'MANUAL') NOT NULL DEFAULT 'AUTO' COMMENT 'Mode kerja: AUTO (otomatis) / MANUAL',
    `pump_status` ENUM('ON', 'OFF') NOT NULL DEFAULT 'OFF' COMMENT 'Status pompa relay: ON / OFF',
    `last_seen` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Waktu terakhir ESP32 terhubung',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Tabel `sensor_logs`
-- Menyimpan riwayat pembacaan sensor kelembapan tanah dari ESP32
CREATE TABLE `sensor_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `device_id` INT NOT NULL COMMENT 'Relasi ke tabel devices.id',
    `moisture_level` FLOAT NOT NULL COMMENT 'Nilai kelembapan tanah dalam persen (0 - 100%)',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Waktu pembacaan log disimpan',
    INDEX `idx_device_id` (`device_id`),
    INDEX `idx_created_at` (`created_at`),
    CONSTRAINT `fk_device_sensor_logs`
        FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Data Awal (Dummy Data) untuk Pengujian Dashboard
-- Masukkan 1 perangkat ESP32 default
INSERT INTO `devices` (`id`, `mac_address`, `name`, `operating_mode`, `pump_status`, `last_seen`)
VALUES (1, '24:6F:28:7A:B1:C0', 'ESP32 - Tanaman Monstera', 'AUTO', 'OFF', NOW());

-- Masukkan 10 data log sensor contoh (terbaru ke terlama)
INSERT INTO `sensor_logs` (`device_id`, `moisture_level`, `created_at`) VALUES
(1, 48.5, DATE_SUB(NOW(), INTERVAL 45 MINUTE)),
(1, 46.2, DATE_SUB(NOW(), INTERVAL 40 MINUTE)),
(1, 44.0, DATE_SUB(NOW(), INTERVAL 35 MINUTE)),
(1, 41.8, DATE_SUB(NOW(), INTERVAL 30 MINUTE)),
(1, 38.5, DATE_SUB(NOW(), INTERVAL 25 MINUTE)),
(1, 35.1, DATE_SUB(NOW(), INTERVAL 20 MINUTE)),
(1, 31.4, DATE_SUB(NOW(), INTERVAL 15 MINUTE)),
(1, 28.0, DATE_SUB(NOW(), INTERVAL 10 MINUTE)),
(1, 55.4, DATE_SUB(NOW(), INTERVAL 5 MINUTE)),
(1, 52.8, NOW());
