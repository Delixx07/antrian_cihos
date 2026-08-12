-- ═══════════════════════════════════════════════════════════════════
--  SETUP DATABASE ANTRIAN — server produksi 172.20.0.39
--
--  Membuat database `antrian_cihos` dari NOL beserta seluruh tabel
--  dan data awal (hak akses user, banner, video, foto dokter).
--
--  CARA PAKAI (di server produksi):
--    mysql.exe -u root -p < setup-database.sql
--
--  Setelah itu, dari folder aplikasi jalankan:
--    php artisan migrate --force      (memastikan migrasi terbaru ikut)
--    php artisan config:clear
--
--  CATATAN: berkas ini AMAN dijalankan di database kosong.
--  Bila `antrian_cihos` SUDAH ADA & berisi data, JANGAN dijalankan —
--  baris DROP di bawah akan menghapus isinya.
-- ═══════════════════════════════════════════════════════════════════

CREATE DATABASE IF NOT EXISTS `antrian_cihos`
  DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `antrian_cihos`;

SET FOREIGN_KEY_CHECKS = 0;

-- ── STRUKTUR TABEL ──

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `antrian`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `antrian` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tanggal` date NOT NULL,
  `no_antrian` varchar(30) DEFAULT NULL,
  `prefix` varchar(20) DEFAULT NULL,
  `queue_no` int(10) unsigned NOT NULL DEFAULT 0,
  `pasien_nama` varchar(255) DEFAULT NULL,
  `pasien_nomrn` varchar(50) DEFAULT NULL,
  `pasien_jk` varchar(5) DEFAULT NULL,
  `poli_kode` varchar(50) DEFAULT NULL,
  `poli_nama` varchar(255) DEFAULT NULL,
  `paramedic_id` bigint(20) unsigned DEFAULT NULL,
  `poli_dokter_nama` varchar(255) DEFAULT NULL,
  `room_code` varchar(50) DEFAULT NULL,
  `room_name` varchar(255) DEFAULT NULL,
  `appointment_no` varchar(60) DEFAULT NULL,
  `registration_no` varchar(60) DEFAULT NULL,
  `tahap` varchar(20) NOT NULL DEFAULT 'klinik',
  `is_booking` tinyint(1) NOT NULL DEFAULT 0,
  `status_resep` varchar(20) DEFAULT NULL,
  `resep_clear` tinyint(1) NOT NULL DEFAULT 0,
  `farmasi_jenis` varchar(20) DEFAULT NULL,
  `counter` varchar(255) DEFAULT NULL,
  `klinik_tunggu_at` timestamp NULL DEFAULT NULL,
  `klinik_panggil_at` timestamp NULL DEFAULT NULL,
  `klinik_selesai_at` timestamp NULL DEFAULT NULL,
  `transfer_at` timestamp NULL DEFAULT NULL,
  `kasir_tunggu_at` timestamp NULL DEFAULT NULL,
  `kasir_panggil_at` timestamp NULL DEFAULT NULL,
  `kasir_selesai_at` timestamp NULL DEFAULT NULL,
  `farmasi_tunggu_at` timestamp NULL DEFAULT NULL,
  `farmasi_panggil_at` timestamp NULL DEFAULT NULL,
  `farmasi_selesai_at` timestamp NULL DEFAULT NULL,
  `panggil_count` smallint(5) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_antrian_appt` (`tanggal`,`appointment_no`),
  KEY `antrian_tanggal_tahap_index` (`tanggal`,`tahap`),
  KEY `antrian_tanggal_index` (`tanggal`),
  KEY `antrian_paramedic_id_index` (`paramedic_id`),
  KEY `antrian_tahap_index` (`tahap`),
  KEY `antrian_status_resep_index` (`status_resep`),
  KEY `antrian_tanggal_is_booking_index` (`tanggal`,`is_booking`)
) ENGINE=InnoDB AUTO_INCREMENT=156 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `antrian_access`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `antrian_access` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` varchar(40) NOT NULL,
  `paramedic_id` bigint(20) unsigned DEFAULT NULL,
  `paramedic_name` varchar(255) DEFAULT NULL,
  `counter` varchar(255) DEFAULT NULL,
  `room_code` varchar(255) DEFAULT NULL,
  `room_occupied_at` timestamp NULL DEFAULT NULL,
  `zona` varchar(255) DEFAULT NULL,
  `is_blocked` tinyint(1) NOT NULL DEFAULT 0,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `antrian_access_username_unique` (`username`),
  KEY `antrian_access_role_index` (`role`)
) ENGINE=InnoDB AUTO_INCREMENT=279 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `banner_clinics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `banner_clinics` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `banner_id` bigint(20) unsigned NOT NULL,
  `service_unit_code` varchar(40) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `banner_clinics_banner_id_service_unit_code_unique` (`banner_id`,`service_unit_code`),
  KEY `banner_clinics_service_unit_code_index` (`service_unit_code`),
  CONSTRAINT `banner_clinics_banner_id_foreign` FOREIGN KEY (`banner_id`) REFERENCES `banners` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `banners`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `banners` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nama` varchar(255) NOT NULL,
  `image` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `clinic_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `clinic_settings` (
  `service_unit_code` varchar(255) NOT NULL,
  `zone_code` varchar(255) DEFAULT NULL,
  `zone_name` varchar(255) DEFAULT NULL,
  `room_code_1` varchar(255) DEFAULT NULL,
  `room_code_2` varchar(255) DEFAULT NULL,
  `room_code_3` varchar(255) DEFAULT NULL,
  `room_code_4` varchar(255) DEFAULT NULL,
  `room_code_5` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`service_unit_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `doctor_photos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `doctor_photos` (
  `paramedic_id` bigint(20) unsigned NOT NULL,
  `nik` varchar(255) DEFAULT NULL,
  `filename` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`paramedic_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `video_clinics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `video_clinics` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `video_id` bigint(20) unsigned NOT NULL,
  `service_unit_code` varchar(40) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `video_clinics_video_id_service_unit_code_unique` (`video_id`,`service_unit_code`),
  KEY `video_clinics_service_unit_code_index` (`service_unit_code`),
  CONSTRAINT `video_clinics_video_id_foreign` FOREIGN KEY (`video_id`) REFERENCES `videos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `videos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `videos` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `judul` varchar(255) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `sort` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;


-- ── DATA AWAL ──

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

LOCK TABLES `antrian_access` WRITE;
/*!40000 ALTER TABLE `antrian_access` DISABLE KEYS */;
INSERT INTO `antrian_access` (`id`, `username`, `name`, `password`, `role`, `paramedic_id`, `paramedic_name`, `counter`, `room_code`, `room_occupied_at`, `zona`, `is_blocked`, `last_login_at`, `created_at`, `updated_at`) VALUES (1,'dustinfelix','Dustin Felix',NULL,'administrator',NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-08-03 01:10:30','2026-07-28 03:00:44','2026-08-03 01:10:30'),(2,'admin','Super Admin','$2y$12$T/6KnggURrlbcVCDgEQ8beHv4074tdgEZaeDyDNRD35s8MbJ4Kqe.','administrator',NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-08-06 08:58:33','2026-07-28 18:40:32','2026-08-06 08:58:33'),(12,'farmasi','Farmasi',NULL,'farmasi',NULL,NULL,'Farmasi Racik',NULL,NULL,NULL,0,'2026-08-06 07:54:11','2026-07-28 19:51:09','2026-08-06 07:54:11'),(13,'kasir','Kasir Administrasi',NULL,'kasir_administrasi',NULL,NULL,'Counter 1',NULL,NULL,NULL,0,'2026-08-06 08:14:20','2026-07-28 19:51:09','2026-08-06 08:14:20'),(14,'kasirfarmasi','Kasir Farmasi',NULL,'kasir_farmasi',NULL,NULL,'Kasir Farmasi 1',NULL,NULL,NULL,0,'2026-07-28 19:51:52','2026-07-28 19:51:09','2026-07-28 19:51:52'),(15,'admisirl','Admisi Rajal & LAB',NULL,'admisi_rajal_lab',NULL,NULL,'Counter 1',NULL,NULL,NULL,0,'2026-07-28 19:51:52','2026-07-28 19:51:09','2026-07-28 19:51:52'),(16,'admisiigd','Admisi IGD',NULL,'admisi_igd',NULL,NULL,'IGD 1',NULL,NULL,NULL,0,'2026-07-28 19:51:52','2026-07-28 19:51:09','2026-07-28 19:51:52'),(17,'admisirad','Admisi Radiologi',NULL,'admisi_radiologi',NULL,NULL,'Radiologi 1',NULL,NULL,NULL,0,'2026-07-28 19:51:52','2026-07-28 19:51:09','2026-07-28 19:51:52'),(18,'spv','Supervisor',NULL,'spv',NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-07-28 19:51:52','2026-07-28 19:51:09','2026-07-28 19:51:52'),(19,'klinik','Klinik (Dokter Tes)',NULL,'klinik',124,'dr. I Gusti Made Aswin Rahmadi Ranuh, Sp.BS',NULL,NULL,NULL,NULL,0,'2026-07-29 00:04:11','2026-07-28 19:51:09','2026-07-29 00:04:11'),(20,'20240618','dr. Christy Thong','$2y$12$VBlieEb1dExxkH/pbIbxqeem9JsbU6MLknIDOJsJyug96nMx8PaGC','klinik',357,'dr. Christy Thong',NULL,'1859','2026-08-07 04:23:42',NULL,0,'2026-08-07 04:23:42','2026-07-29 00:08:10','2026-08-07 04:23:42'),(22,'D240069','Yahya Haryo Nugroho, dr., Sp.PD','$2y$12$KrKGuXpMuNaN3m/yNkQRve2bNVKTEjfr1skz.RU9cmR2vCC4mBOr6','klinik',70,'dr. Yahya Haryo Nugroho, Sp.PD',NULL,'1738',NULL,NULL,0,'2026-08-06 08:58:52','2026-08-06 08:57:36','2026-08-07 03:36:41'),(23,'20240029','Kholis Maria Ulfa',NULL,'spv',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(24,'D240001',' Prof. Dr. Yudi Her Oktaviono, dr.,SpJP.,Subsp.K.I. (K), MM',NULL,'klinik',1,' Prof. Dr. Yudi Her Oktaviono, dr.,SpJP.,Subsp.K.I. (K), MM',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(25,'D240002','Joko Hermawan, dr., Sp.JP (K), FIHA',NULL,'klinik',2,'Joko Hermawan, dr., Sp.JP (K), FIHA',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(26,'D240003','Christian Pramudita Budianto, dr., Sp.JP, FIHA',NULL,'klinik',3,'Christian Pramudita Budianto, dr., Sp.JP, FIHA',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(27,'D240004','Dara Ninggar Ghassani, dr., Sp.JP, FIHA',NULL,'klinik',5,'Dara Ninggar Ghassani, dr., Sp.JP, FIHA',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(28,'D240005','Ruth Irena Gunadi, dr., Sp.JP, FIHA',NULL,'klinik',6,'Ruth Irena Gunadi, dr., Sp.JP, FIHA',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(29,'D240006','Prof Dr. dr. Muhtarum Yusuf, Sp.T.H.T.B.K.L, Subsp.Onk. (K).FICS',NULL,'klinik',7,'Prof Dr. dr. Muhtarum Yusuf, Sp.T.H.T.B.K.L, Subsp.Onk. (K).FICS',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(30,'D240007','Dr. Wiyono Hadi, dr. Sp. THTBKL Sub.Sp Rino (K)',NULL,'klinik',8,'Dr. Wiyono Hadi, dr. Sp. THTBKL Sub.Sp Rino (K)',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(31,'D240008','dr. Olivia Tantana Mbiomed, SpTHT-KL',NULL,'klinik',9,'dr. Olivia Tantana Mbiomed, SpTHT-KL',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(32,'D240009','dr. Dian Ratna Chamora, SpTHT-KL',NULL,'klinik',10,'dr. Dian Ratna Chamora, SpTHT-KL',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(33,'D240010','dr. Audi Wahyu Utomo, Sp. THT-BKL',NULL,'klinik',11,'dr. Audi Wahyu Utomo, Sp. THT-BKL',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(34,'D240011','Prof. Dr. Dian Agustin Wahyuningrum, drg., Sp.KG, Subsp.KE(K)',NULL,'klinik',12,'Prof. Dr. Dian Agustin Wahyuningrum, drg., Sp.KG, Subsp.KE(K)',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(35,'D240012','drg. Mirza Bahar Firnanda., Sp.KG',NULL,'klinik',13,'drg. Mirza Bahar Firnanda., Sp.KG',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(36,'D240013','Setyabudi, drg., M.Kes., Sp.KG.(K).',NULL,'klinik',14,'Setyabudi, drg., M.Kes., Sp.KG.(K).',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(37,'D240014','drg. Dyshafilia Charindra, Sp.Ort.',NULL,'klinik',15,'drg. Dyshafilia Charindra, Sp.Ort.',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(38,'D240015','Dr. Eric Priyo Prasetyo, drg., M.Kes., Sp.KG, Subsp.KE(K)',NULL,'klinik',16,'Dr. Eric Priyo Prasetyo, drg., M.Kes., Sp.KG, Subsp.KE(K)',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(39,'D240016','drg. Indra Mulyawan, Sp.BMM, FICS.',NULL,'klinik',17,'drg. Indra Mulyawan, Sp.BMM, FICS.',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(40,'D240017','drg. Ardianti Maartrina Dewi, M.Kes., Sp. KGA(K)',NULL,'klinik',18,'drg. Ardianti Maartrina Dewi, M.Kes., Sp. KGA(K)',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(41,'D240018','drg. Ratri Maya, M.Kes., Ph.D., Sp.Pros (K)',NULL,'klinik',19,'drg. Ratri Maya, M.Kes., Ph.D., Sp.Pros (K)',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(42,'D240019','Dr. drg. Devi Eka Juniati., SpKG(K)',NULL,'klinik',20,'Dr. drg. Devi Eka Juniati., SpKG(K)',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(43,'D240020','drg. Chan Iwan Chandoko',NULL,'klinik',21,'drg. Chan Iwan Chandoko',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(44,'D240021','drg. Nadhifa Salma',NULL,'klinik',22,'drg. Nadhifa Salma',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(45,'D240022','drg. Lavinia Devin Irawan, Sp.Perio',NULL,'klinik',23,'drg. Lavinia Devin Irawan, Sp.Perio',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(46,'D240023','Dr. dr. Yulia Primitasari Sp.M(K)',NULL,'klinik',24,'Dr. dr. Yulia Primitasari Sp.M(K)',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(47,'D240024','Intifada, dr., Sp.M, M.Ked.Klin',NULL,'klinik',25,'Intifada, dr., Sp.M, M.Ked.Klin',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(48,'D240025','dr. Astry Ayunda, SP.M, M.Ked.Klin',NULL,'klinik',26,'dr. Astry Ayunda, SP.M, M.Ked.Klin',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(49,'D240026','Prof. Dr. Anang Endaryanto., dr. Sp.A(K)',NULL,'klinik',27,'Prof. Dr. Anang Endaryanto., dr. Sp.A(K)',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(50,'D240027','Dr. dr. Dina Djojo Husodo, Sp.A (K)',NULL,'klinik',28,'Dr. dr. Dina Djojo Husodo, Sp.A (K)',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(51,'D240028','dr. Areta Idarto, SpA., MARS., MM',NULL,'klinik',29,'dr. Areta Idarto, SpA., MARS., MM',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(52,'D240029','dr. Harry Febryanto, Sp.A',NULL,'klinik',30,'dr. Harry Febryanto, Sp.A',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(53,'D240031','dr. Lianto Kurniawan Nyoto, Sp.A',NULL,'klinik',32,'dr. Lianto Kurniawan Nyoto, Sp.A',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(54,'D240032','dr. Vanessa Lini Gunawan, Sp.A',NULL,'klinik',33,'dr. Vanessa Lini Gunawan, Sp.A',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(55,'D240033','dr.Glabela Christiana Pandango Sp.A',NULL,'klinik',34,'dr.Glabela Christiana Pandango Sp.A',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(56,'D240034','dr. Audrey, Sp.A',NULL,'klinik',35,'dr. Audrey, Sp.A',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(57,'D240035','Dr. Kohar Hari Santoso, dr., SpAn-TI, Subsp. An.Ped (K), Subsp. TI (K)',NULL,'klinik',36,'Dr. Kohar Hari Santoso, dr., SpAn-TI, Subsp. An.Ped (K), Subsp. TI (K)',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(58,'D240036','Dr. dr. Hamzah, Sp.An., KNA., K',NULL,'klinik',37,'Dr. dr. Hamzah, Sp.An., KNA., K',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(59,'D240037','Khildan Miftahul Firdaus, dr., SpAn-TI.M.Ked.Klin',NULL,'klinik',38,'Khildan Miftahul Firdaus, dr., SpAn-TI.M.Ked.Klin',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(60,'D240038','Nicolaas P Simamora, dr., SpAn-TI., Subsp.TI(K)',NULL,'klinik',39,'Nicolaas P Simamora, dr., SpAn-TI., Subsp.TI(K)',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(61,'D240039','Akhyar Nur Uhud, dr., SpAn-TI.M.Ked.,Klin',NULL,'klinik',40,'Akhyar Nur Uhud, dr., SpAn-TI.M.Ked.,Klin',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(62,'D240040','Dr. dr. Anna Surgean Veterini, Sp.An.KIC',NULL,'klinik',41,'Dr. dr. Anna Surgean Veterini, Sp.An.KIC',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(63,'D240041','Fajar Perdhana, dr., SpAn-TI., Subsp. AKV(K)., Subsp. TI(K)',NULL,'klinik',42,'Fajar Perdhana, dr., SpAn-TI., Subsp. AKV(K)., Subsp. TI(K)',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(64,'D240042','Erik Jaya Gunawan, dr., M.Ked.Klin., Sp.An-TI.',NULL,'klinik',43,'Erik Jaya Gunawan, dr., M.Ked.Klin., Sp.An-TI.',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(65,'D240043','dr. Herdiani Sulistyo, Sp.An-TI., FIP',NULL,'klinik',44,'dr. Herdiani Sulistyo, Sp.An-TI., FIP',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(66,'D240044','Dr. Philia Setiawan, dr., SpAn-TI., Subsp. TI(K)., Subsp. AnKv(K)',NULL,'klinik',45,'Dr. Philia Setiawan, dr., SpAn-TI., Subsp. TI(K)., Subsp. AnKv(K)',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(67,'D240045','dr. Belindo Wirabuana, Sp.An-TI., FIP., Subsp.MN(K)',NULL,'klinik',46,'dr. Belindo Wirabuana, Sp.An-TI., FIP., Subsp.MN(K)',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(68,'D240046','Prof. Dr. Laksmi Wulandari, dr., Sp.P(K), FCCP, FISCM, FISR',NULL,'klinik',47,'Prof. Dr. Laksmi Wulandari, dr., Sp.P(K), FCCP, FISCM, FISR',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(69,'D240047','Dr. Isnin Anang Marhana, dr., Sp.P(K), FCCP, FISR, FAPSR',NULL,'klinik',48,'Dr. Isnin Anang Marhana, dr., Sp.P(K), FCCP, FISR, FAPSR',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(70,'D240048','Anna Febriani, dr, Sp.P(K) Onk',NULL,'klinik',49,'Anna Febriani, dr, Sp.P(K) Onk',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(71,'D240049','Adhitri Anggoro, dr., Sp.P',NULL,'klinik',50,'Adhitri Anggoro, dr., Sp.P',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(72,'D240050','dr. Paul Agus Dwiyanu Sp.P (K) FISR, FAPSR',NULL,'klinik',51,'dr. Paul Agus Dwiyanu Sp.P (K) FISR, FAPSR',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(73,'D240051','Prof. Dr. Cita RS Prakoeswa, dr., Sp. DVE., Subsp. DAI., FINSDV, FAADV., MARS ',NULL,'klinik',52,'Prof. Dr. Cita RS Prakoeswa, dr., Sp. DVE., Subsp. DAI., FINSDV, FAADV., MARS ',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(74,'D240052','dr. Stefani Nurhadi, M.Biomed, Sp.KK',NULL,'klinik',53,'dr. Stefani Nurhadi, M.Biomed, Sp.KK',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(75,'D240053','dr. Nanny Herwanto, Sp.DVE, FINSDV',NULL,'klinik',54,'dr. Nanny Herwanto, Sp.DVE, FINSDV',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(76,'D240054','dr. Catherina Jessica Sutantoyo, Sp.DVE',NULL,'klinik',55,'dr. Catherina Jessica Sutantoyo, Sp.DVE',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(77,'D240055','dr. Mochammad Ayyub Arachman, M.Ked.Klin., Sp.DVE',NULL,'klinik',56,'dr. Mochammad Ayyub Arachman, M.Ked.Klin., Sp.DVE',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(78,'D240056','dr. Wisnu Triadi Nugroho, M.Ked.Klin., Sp.DVE',NULL,'klinik',57,'dr. Wisnu Triadi Nugroho, M.Ked.Klin., Sp.DVE',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(79,'D240057','Dr. dr. Edi Mustamsir, Sp.OT (K)',NULL,'klinik',58,'Dr. dr. Edi Mustamsir, Sp.OT (K)',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(80,'D240058','dr. Muhammad Shoifi, SpOT(K)',NULL,'klinik',59,'dr. Muhammad Shoifi, SpOT(K)',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(81,'D240059','dr. Taufin Warindra, Sp.OT(K)',NULL,'klinik',60,'dr. Taufin Warindra, Sp.OT(K)',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(82,'D240060','dr. Bernard Satrya Surya P, Sp.OT',NULL,'klinik',61,'dr. Bernard Satrya Surya P, Sp.OT',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(83,'D240061','Udria Satya Pratama, dr.Sp.OT(K)',NULL,'klinik',62,'Udria Satya Pratama, dr.Sp.OT(K)',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(84,'D240062','Ferdiansyah Danang P, dr., Sp.OT., M.Ked.Klin',NULL,'klinik',63,'Ferdiansyah Danang P, dr., Sp.OT., M.Ked.Klin',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(85,'D240063','dr. Theri Effendi, Sp.OT(K) Upper Limb and Microsugery',NULL,'klinik',64,'dr. Theri Effendi, Sp.OT(K) Upper Limb and Microsugery',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(86,'D240064','dr. Gede Chandra Purnama Yudha, Sp.OT (K) Sport Injury',NULL,'klinik',65,'dr. Gede Chandra Purnama Yudha, Sp.OT (K) Sport Injury',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(87,'D240065','dr. Inggra Vivayuna, SpOT',NULL,'klinik',66,'dr. Inggra Vivayuna, SpOT',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(88,'D240066','Dr.Primadenny Ariesa Airlangga, M.Si., Sp.OT (K-Spine)',NULL,'klinik',67,'Dr.Primadenny Ariesa Airlangga, M.Si., Sp.OT (K-Spine)',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(89,'D240067','Nunuk Mardiana, dr., Sp.PD-KGH',NULL,'klinik',68,'Nunuk Mardiana, dr., Sp.PD-KGH',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(90,'D240068','dr. Purwakaning Purnomo Agung, M.Kes, Sp.PD',NULL,'klinik',69,'dr. Purwakaning Purnomo Agung, M.Kes, Sp.PD',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(91,'D240070','Brinna Anindita, dr., Sp.PD',NULL,'klinik',71,'Brinna Anindita, dr., Sp.PD',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(92,'D240071','dr. Dilly Niza Paramita, Sp.PD',NULL,'klinik',72,'dr. Dilly Niza Paramita, Sp.PD',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(93,'D240072','dr. Andy Purnomo, Sp.PD, KHOM, FINASIM',NULL,'klinik',73,'dr. Andy Purnomo, Sp.PD, KHOM, FINASIM',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(94,'D240073','dr. Rio Wironegoro, SpPD, KEMD, FINASIM',NULL,'klinik',74,'dr. Rio Wironegoro, SpPD, KEMD, FINASIM',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(95,'D240074','dr. Dana Pramudya, Sp.PD, KGH',NULL,'klinik',75,'dr. Dana Pramudya, Sp.PD, KGH',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(96,'D240075','dr. Ummi Maimmunah, Sp.PD., KGEH',NULL,'klinik',76,'dr. Ummi Maimmunah, Sp.PD., KGEH',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(97,'D240076','dr. Rusdiyana Ekawati, Sp.PD',NULL,'klinik',77,'dr. Rusdiyana Ekawati, Sp.PD',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(98,'D240077','dr. Wiwiek Indriyani Maskoep, Sp.PD, FINASIM',NULL,'klinik',78,'dr. Wiwiek Indriyani Maskoep, Sp.PD, FINASIM',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(99,'D240078','Dr. RA. Meisy Andriana, dr., Sp.KFR.N.M (K)',NULL,'klinik',79,'Dr. RA. Meisy Andriana, dr., Sp.KFR.N.M (K)',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(100,'D240079','dr. Priscilla Anastasia Fitriany, Sp.K.F.R., M.Ked.Klin',NULL,'klinik',80,'dr. Priscilla Anastasia Fitriany, Sp.K.F.R., M.Ked.Klin',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(101,'D240080','dr. Swan Ien (Inez), Sp.K.F.R., M.S.(K), FEMG, AIFO-K',NULL,'klinik',81,'dr. Swan Ien (Inez), Sp.K.F.R., M.S.(K), FEMG, AIFO-K',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(102,'D240081','dr.Jonathan Wibisono',NULL,'klinik',82,'dr.Jonathan Wibisono',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(103,'D240082','Prof. Dr. dr. Hendy Hedarto, Sp.OG(K)',NULL,'klinik',83,'Prof. Dr. dr. Hendy Hedarto, Sp.OG(K)',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(104,'D240083','Dr. dr. Amang Surya Priyanto, Sp.OG, F-MAS',NULL,'klinik',84,'Dr. dr. Amang Surya Priyanto, Sp.OG, F-MAS',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(105,'D240084','dr. Imam Djoko Mulyawan, Sp.OG(K)',NULL,'klinik',85,'dr. Imam Djoko Mulyawan, Sp.OG(K)',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(106,'D240085','dr. Robert Hunan Purwaka, Sp.OG., D.MAS., F.MAS',NULL,'klinik',86,'dr. Robert Hunan Purwaka, Sp.OG., D.MAS., F.MAS',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(107,'D240086','Dr. dr., Salmon Charles, Sp.OG',NULL,'klinik',87,'Dr. dr., Salmon Charles, Sp.OG',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(108,'D240087','dr. Andianto Indrawan T, Sp.OG',NULL,'klinik',88,'dr. Andianto Indrawan T, Sp.OG',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(109,'D240088','dr. Yoan Alexandria Angelina, M.Ked.Klin, Sp.OG, Subsp.Onk',NULL,'klinik',89,'dr. Yoan Alexandria Angelina, M.Ked.Klin, Sp.OG, Subsp.Onk',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(110,'D240089','dr. Hanny Aditanzil Sugianto, Sp.OG',NULL,'klinik',90,'dr. Hanny Aditanzil Sugianto, Sp.OG',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(111,'D240090','dr. Dharma Putra P. Banjarnahor, Sp.OG., Subsp. KFm.',NULL,'klinik',91,'dr. Dharma Putra P. Banjarnahor, Sp.OG., Subsp. KFm.',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(112,'D240091','dr. Budi Setiawan Harjoto, Sp.OG',NULL,'klinik',92,'dr. Budi Setiawan Harjoto, Sp.OG',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(113,'D240092','Oky Revianto Sediono Pribadi, dr., Sp.BTKV, Subsp JD (K), FIATCVS',NULL,'klinik',93,'Oky Revianto Sediono Pribadi, dr., Sp.BTKV, Subsp JD (K), FIATCVS',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(114,'D240093','Chikita Nur Rachmi, dr., Sp.BTKV, FIATCVS',NULL,'klinik',94,'Chikita Nur Rachmi, dr., Sp.BTKV, FIATCVS',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(115,'D240094','Adhitya Ginting, dr., Sp.BTKV, FIATCVS',NULL,'klinik',96,'Adhitya Ginting, dr., Sp.BTKV, FIATCVS',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(116,'D240095','Agus Santoso Budi, dr., Sp.BP, REK (K)',NULL,'klinik',97,'Agus Santoso Budi, dr., Sp.BP, REK (K)',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(117,'D240096','dr. Radias Dwi Padmani, Sp.BP-RE(KKF)',NULL,'klinik',98,'dr. Radias Dwi Padmani, Sp.BP-RE(KKF)',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(118,'D240097','dr. Melia Bogari, Sp.B.P.R.E',NULL,'klinik',99,'dr. Melia Bogari, Sp.B.P.R.E',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(119,'D240098','dr. Satrya Husada, Sp.U',NULL,'klinik',100,'dr. Satrya Husada, Sp.U',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(120,'BOBBYH','dr. Bobby Hery Yudhanto, Sp.U',NULL,'klinik',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(121,'D240100','dr. Andre Kurniawan, Sp.B,Subsp. Onk(K) FINACS',NULL,'klinik',102,'dr. Andre Kurniawan, Sp.B,Subsp. Onk(K) FINACS',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(122,'D240101','dr.Arga Patrianagara , SpB, Subsp. Onk(K) ',NULL,'klinik',103,'dr.Arga Patrianagara , SpB, Subsp. Onk(K) ',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(123,'D240102','dr. F. Siusanto Hadi, SpB-KBD',NULL,'klinik',104,'dr. F. Siusanto Hadi, SpB-KBD',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(124,'D240103','dr. Marthen Imanuel Benu, SpB, subsp.BD(K)',NULL,'klinik',105,'dr. Marthen Imanuel Benu, SpB, subsp.BD(K)',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(125,'D240104','dr. Fariza Hakim Rio Brank, SpB-KBD',NULL,'klinik',106,'dr. Fariza Hakim Rio Brank, SpB-KBD',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(126,'D240105','dr. Adhitya Angga Wardhana, Sp.B., Subsp.BD(K)',NULL,'klinik',107,'dr. Adhitya Angga Wardhana, Sp.B., Subsp.BD(K)',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(127,'D240106','Dr. dr. IGB Adria Hariastawa. SpB. SpBA (K) ',NULL,'klinik',108,'Dr. dr. IGB Adria Hariastawa. SpB. SpBA (K) ',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(128,'D240107','dr. Astri Taufi Ramadhani, Sp.BA, M.Ked.Klin',NULL,'klinik',109,'dr. Astri Taufi Ramadhani, Sp.BA, M.Ked.Klin',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(129,'D240108','Prof. Dr. Anggraini dwi S, dr., Sp.Rad, Subsp. NKL(K)',NULL,'klinik',110,'Prof. Dr. Anggraini dwi S, dr., Sp.Rad, Subsp. NKL(K)',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(130,'D240109','dr. Juanda Hanjaya, Sp.Rad',NULL,'klinik',111,'dr. Juanda Hanjaya, Sp.Rad',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(131,'D240110','dr. Francisca Notopuro, Sp.Rad',NULL,'klinik',112,'dr. Francisca Notopuro, Sp.Rad',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(132,'D240112','Lysa Veterini, dr., Sp.PA',NULL,'klinik',114,'Lysa Veterini, dr., Sp.PA',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(133,'D240113','dr. Lidya Handayani, M.KedKlin, PhD, Sp.MK',NULL,'klinik',115,'dr. Lidya Handayani, M.KedKlin, PhD, Sp.MK',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(134,'D240114','dr. Denys Putra Alim, Sp.FM',NULL,'klinik',116,'dr. Denys Putra Alim, Sp.FM',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(135,'D240115','dr. Ferdy Royland Marpaung, Sp.PK.,Subsp.E.M(K)',NULL,'klinik',117,'dr. Ferdy Royland Marpaung, Sp.PK.,Subsp.E.M(K)',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(136,'D240116','dr. Jihan Sasmita, Sp.PK',NULL,'klinik',118,'dr. Jihan Sasmita, Sp.PK',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(137,'D240117','dr. Stephanus Massora, Sp.KN-TM(K)',NULL,'klinik',119,'dr. Stephanus Massora, Sp.KN-TM(K)',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(138,'D240118','Prof. Dr. Joni Wahyuhadi, dr., SpBS (K)., MARS',NULL,'klinik',120,'Prof. Dr. Joni Wahyuhadi, dr., SpBS (K)., MARS',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(139,'D240119','dr. Agus Chairul Anab, Sp.BS(K)',NULL,'klinik',121,'dr. Agus Chairul Anab, Sp.BS(K)',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(140,'D240120','dr. M. Sofyanto, Sp.BS',NULL,'klinik',122,'dr. M. Sofyanto, Sp.BS',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(141,'D240121','dr. Gigih Pramono, Sp.BS',NULL,'klinik',123,'dr. Gigih Pramono, Sp.BS',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(142,'D240122','dr. I Gusti Made Aswin Rahmadi Ranuh, Sp.BS',NULL,'klinik',124,'dr. I Gusti Made Aswin Rahmadi Ranuh, Sp.BS',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(143,'D240123','dr. Fajar Herbowo Niantiarno, Sp.BS',NULL,'klinik',125,'dr. Fajar Herbowo Niantiarno, Sp.BS',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(144,'D240124','dr. Pieter David Adriaan Ferdinandus, Sp.B, FINACS,FICS',NULL,'klinik',126,'dr. Pieter David Adriaan Ferdinandus, Sp.B, FINACS,FICS',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(145,'D240125','dr. Maria Melita, Sp.B',NULL,'klinik',127,'dr. Maria Melita, Sp.B',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(146,'D240126','dr. Reza Halim, M. Biomed, Sp.B-FInaCs',NULL,'klinik',128,'dr. Reza Halim, M. Biomed, Sp.B-FInaCs',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(147,'D240127','dr. Endy Wahyudi Sp.B',NULL,'klinik',129,'dr. Endy Wahyudi Sp.B',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(148,'D240128','dr.Kathleen Valentina Kawilarang, Sp.N',NULL,'klinik',130,'dr.Kathleen Valentina Kawilarang, Sp.N',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(149,'D240129','dr. Andre Dharmawan Wijono, Sp.N',NULL,'klinik',131,'dr. Andre Dharmawan Wijono, Sp.N',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(150,'D240130','dr. Chiquita Putri Vania Rau, Sp.N',NULL,'klinik',132,'dr. Chiquita Putri Vania Rau, Sp.N',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(151,'D240131','Andreas Soejitno, dr., Sp.N, EDPM, CIPS',NULL,'klinik',133,'Andreas Soejitno, dr., Sp.N, EDPM, CIPS',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(152,'D240132','dr. Evander Aloysius Raymond Desun, Sp.N',NULL,'klinik',134,'dr. Evander Aloysius Raymond Desun, Sp.N',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(153,'D240133','Dr. Yunias Setiawati, dr., SpKJ (K) FISCM',NULL,'klinik',135,'Dr. Yunias Setiawati, dr., SpKJ (K) FISCM',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(154,'D240134','dr. Efendi Rimba SpKJ',NULL,'klinik',136,'dr. Efendi Rimba SpKJ',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(155,'D240135','dr. Yoke Surpri Marlina, SpOnk.Rad(K)',NULL,'klinik',137,'dr. Yoke Surpri Marlina, SpOnk.Rad(K)',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(156,'D240136','Tjahjo Djojo Tanojo, dr., MS., Sp.And(K)',NULL,'klinik',138,'Tjahjo Djojo Tanojo, dr., MS., Sp.And(K)',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(157,'D240138','dr. Albert Christianto, M.Biomed',NULL,'klinik',140,'dr. Albert Christianto, M.Biomed',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(158,'D240139','dr. Corinne Prawira',NULL,'klinik',141,'dr. Corinne Prawira',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(159,'D240140','dr. Hartandyo Anang Ashari Hadju',NULL,'klinik',142,'dr. Hartandyo Anang Ashari Hadju',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(160,'D240143','dr. Meyland Citra',NULL,'klinik',145,'dr. Meyland Citra',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(161,'D240144','dr Fidela olivia W,Sp.M',NULL,'klinik',192,'dr Fidela olivia W,Sp.M',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(162,'D240145','Gunawan Yoga, dr., Sp.JP',NULL,'klinik',193,'Gunawan Yoga, dr., Sp.JP',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(163,'20240079','Vicky Bagus P',NULL,'administrator',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(164,'20240137','Apt.Hendri Wahyu Ningrum',NULL,'farmasi',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(165,'20240138','Apt.Yessica Dini A',NULL,'farmasi',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(166,'20240172','Apt.Steven Guitomo',NULL,'farmasi',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(167,'20240107','Apt.Daniel Oktavianus',NULL,'farmasi',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(168,'20240146','Triyuni Ingemaulina',NULL,'kasir_administrasi',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(169,'20240168','Senika Okta Gigih Erganisak',NULL,'kasir_administrasi',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(170,'20240186','Vionita Rizki Yuhandari',NULL,'kasir_administrasi',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(171,'20240190','Dessy Mayang Sari',NULL,'kasir_administrasi',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(172,'20240241','Sholeh Adhim Febriyanshah',NULL,'kasir_administrasi',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(173,'20240258','Galuh Pradipto',NULL,'kasir_administrasi',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(174,'20240432','Apt. Mochammad Taqwim, S.farm',NULL,'farmasi',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(175,'20240429','Liya Megawanti',NULL,'kasir_administrasi',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(176,'20240436','Gamal',NULL,'administrator',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(177,'20240482','Apt.Dinda Putri Cahyani',NULL,'farmasi',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(178,'20240483','Sandy Grasia Yovitania',NULL,'farmasi',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(179,'D20240001','dr. Farahdila Adline, Sp.M, M.KedKlin',NULL,'klinik',258,'dr. Farahdila Adline, Sp.M, M.KedKlin',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(180,'D20240002','drg. Stephanie Naranathadewi',NULL,'klinik',259,'drg. Stephanie Naranathadewi',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(181,'D20240003','drg. Ermin Budiyanti Sukisno',NULL,'klinik',262,'drg. Ermin Budiyanti Sukisno',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(182,'D20240004','Dr. Deasy Fetarayani, Sp.PD, K-AI, FINASIM',NULL,'klinik',263,'Dr. Deasy Fetarayani, Sp.PD, K-AI, FINASIM',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(183,'20240480','Fernandus Ardian',NULL,'administrator',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(184,'D20240007','dr. Dwiki Novendrianto, Sp.PD',NULL,'klinik',274,'dr. Dwiki Novendrianto, Sp.PD',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(185,'D20240110','Dian Sp. THT BKL',NULL,'klinik',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(186,'D20240112','Andi Wahyu Sp.THT.BHC',NULL,'klinik',276,'Andi Wahyu Sp.THT.BHC',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(187,'D20240115','dr. Karel Ramli, Sp.PD',NULL,'klinik',277,'dr. Karel Ramli, Sp.PD',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(188,'D20240116','dr. Amie Vidyani, Sp.PD, KGEH',NULL,'klinik',278,'dr. Amie Vidyani, Sp.PD, KGEH',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(189,'D20240123','dr. Anna Febriani, Sp.P(K)',NULL,'klinik',280,'dr. Anna Febriani, Sp.P(K)',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(190,'D20240124','dr. Edwin Hadinata, Sp.PD',NULL,'klinik',281,'dr. Edwin Hadinata, Sp.PD',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(191,'20240495','Indri Isna Kartika Sari',NULL,'farmasi',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(192,'20240423','dr. Steven Hartanto',NULL,'klinik',253,'dr. Steven Hartanto',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(193,'D240146','Dr. Liem Audi Natalino, SpJP(K), FIHA, FAPSC',NULL,'klinik',292,'Dr. Liem Audi Natalino, SpJP(K), FIHA, FAPSC',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(194,'D2400100','Dr. Camoya Gersom, Sp.PD',NULL,'klinik',266,'Dr. Camoya Gersom, Sp.PD',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(195,'D20240147','dr. Jonathan Wibisono Tumali, Sp.KFR., M.Ked.Klin., M.H',NULL,'klinik',293,'dr. Jonathan Wibisono Tumali, Sp.KFR., M.Ked.Klin., M.H',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(196,'D20240148','dr. Zaky Bajamal, Sp.BS',NULL,'klinik',294,'dr. Zaky Bajamal, Sp.BS',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(197,'20240492','Adhyat Rachman S',NULL,'kasir_administrasi',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(198,'D240030','dr. Monica Sampurna, Sp.A., M.Bio Med.',NULL,'klinik',31,'dr. Monica Sampurna, Sp.A., M.Bio Med.',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(199,'20240516','Meiska Anggita',NULL,'kasir_administrasi',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(200,'20240518','Aprilia Dwi Purwanti',NULL,'kasir_administrasi',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(201,'20240517','Firmansyach Maulana Hariyono',NULL,'kasir_administrasi',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(202,'20240500','apt. Karunia Sekar',NULL,'farmasi',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(203,'20240509','apt. Angela Jane',NULL,'farmasi',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(204,'20240127','Christine Abadiana',NULL,'spv',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(205,'20240411','Albert Christianto',NULL,'klinik',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:31','2026-08-06 09:43:31'),(206,'20240487','dr. Faiz Afano',NULL,'klinik',313,'dr. Faiz Afano',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:32','2026-08-06 09:43:32'),(207,'20240488','dr. Muhammad Satryo Aji Pamungkas',NULL,'klinik',314,'dr. Muhammad Satryo Aji Pamungkas',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:32','2026-08-06 09:43:32'),(208,'20240493','dr. Jeany Thalia',NULL,'klinik',315,'dr. Jeany Thalia',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:32','2026-08-06 09:43:32'),(209,'D240147','dr. Sidharta Suwanto, Sp.Rad',NULL,'klinik',316,'dr. Sidharta Suwanto, Sp.Rad',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:32','2026-08-06 09:43:32'),(210,'20240524','apt. Firsty Ananda Ayu Berliana',NULL,'farmasi',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:32','2026-08-06 09:43:32'),(211,'D240148','dr. Rosi Amrilla Fagi, Sp.JP (K) FIHA, FAsCC',NULL,'klinik',318,'dr. Rosi Amrilla Fagi, Sp.JP (K) FIHA, FAsCC',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:32','2026-08-06 09:43:32'),(212,'20240527','Mochamad Bintoro Marsudi Putra',NULL,'kasir_administrasi',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:32','2026-08-06 09:43:32'),(213,'20240530','Ilham Surya Permana Putra',NULL,'kasir_administrasi',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:32','2026-08-06 09:43:32'),(214,'20240538','Yonas Bianityo Ardani',NULL,'farmasi',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:32','2026-08-06 09:43:32'),(215,'20240546','Silla Purwati',NULL,'farmasi',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:32','2026-08-06 09:43:32'),(216,'20240543','apt. Sahrul Riadi',NULL,'farmasi',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:32','2026-08-06 09:43:32'),(217,'20240553','apt. Steven Hendry',NULL,'farmasi',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:32','2026-08-06 09:43:32'),(218,'20240555','dr. Denny Efendi',NULL,'klinik',331,'dr. Denny Efendi',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:32','2026-08-06 09:43:32'),(219,'20240570','Tiara Dewanti Putri',NULL,'kasir_administrasi',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:32','2026-08-06 09:43:32'),(220,'20240571','I Putu Erwin Adinata',NULL,'kasir_administrasi',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:32','2026-08-06 09:43:32'),(221,'20240579','Yermia Miyarni',NULL,'kasir_administrasi',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:32','2026-08-06 09:43:32'),(222,'D240149','drg. Eveline Yulia Darmadi, Sp.KG',NULL,'klinik',348,'drg. Eveline Yulia Darmadi, Sp.KG',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:32','2026-08-06 09:43:32'),(223,'D240154','dr. Maria Natalia Indawati, Sp.A',NULL,'klinik',353,'dr. Maria Natalia Indawati, Sp.A',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:32','2026-08-06 09:43:32'),(224,'20240610','dr. Johanes Tanzil, MARS',NULL,'klinik',356,'dr. Johanes Tanzil, MARS',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:32','2026-08-06 09:43:32'),(225,'D240153','dr. Freddy, Sp.B., Subsp.BD (K)., S.H., M.H',NULL,'klinik',352,'dr. Freddy, Sp.B., Subsp.BD (K)., S.H., M.H',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:32','2026-08-06 09:43:32'),(226,'20240630','Fiyan Dwi Rinaldiyanto',NULL,'farmasi',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:32','2026-08-06 09:43:32'),(227,'20240631','Rachman Awali',NULL,'kasir_administrasi',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:32','2026-08-06 09:43:32'),(228,'D240155','dr. Primasitha Maharany Harsoyo Putri, Sp.JP, FIHA',NULL,'klinik',354,'dr. Primasitha Maharany Harsoyo Putri, Sp.JP, FIHA',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:32','2026-08-06 09:43:32'),(229,'20240655','Apt. Erin Agnes Elysia',NULL,'farmasi',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:32','2026-08-06 09:43:32'),(230,'20240658','Setya Suhartanti',NULL,'farmasi',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:32','2026-08-06 09:43:32'),(231,'20240667','Apt. Andri Utomo',NULL,'farmasi',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:32','2026-08-06 09:43:32'),(232,'20250005','Dyah Ika Wulan',NULL,'farmasi',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:32','2026-08-06 09:43:32'),(233,'D240158','dr. Ivana Sajogo, Sp.KJ, Subsp.A.R (K)',NULL,'klinik',407,'dr. Ivana Sajogo, Sp.KJ, Subsp.A.R (K)',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:32','2026-08-06 09:43:32'),(234,'20250036','Yunita Ika Widyawati',NULL,'farmasi',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:32','2026-08-06 09:43:32'),(235,'20250059','Theresia Endah Lestari',NULL,'farmasi',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:32','2026-08-06 09:43:32'),(236,'20250064','Midfa\'ul Haawan Fitayaatin Mawaddah',NULL,'farmasi',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:32','2026-08-06 09:43:32'),(237,'20250071','Rahma Arila Permatasari',NULL,'kasir_administrasi',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:32','2026-08-06 09:43:32'),(238,'20250076','Nando Widyas Utomo',NULL,'kasir_administrasi',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:32','2026-08-06 09:43:32'),(239,'20250074','dr. Jovita Liman',NULL,'klinik',422,'dr. Jovita Liman',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:32','2026-08-06 09:43:32'),(240,'20250073','dr Alvionita Muntholib',NULL,'klinik',423,'dr Alvionita Muntholib',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:32','2026-08-06 09:43:32'),(241,'20250070','dr. Valerie Rosalind Angkawidjaja',NULL,'klinik',424,'dr. Valerie Rosalind Angkawidjaja',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:32','2026-08-06 09:43:32'),(242,'20250085','Vania Angel',NULL,'farmasi',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:32','2026-08-06 09:43:32'),(243,'20250083','Chrisfani Yesica',NULL,'farmasi',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:32','2026-08-06 09:43:32'),(244,'D240152','drg. Pradipto Natryo Nugroho, Sp.KG',NULL,'klinik',351,'drg. Pradipto Natryo Nugroho, Sp.KG',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:32','2026-08-06 09:43:32'),(245,'D240159','Prof. Dr. dr. Ami Ashariati, SpPD-KHOM, FINASIM',NULL,'klinik',432,'Prof. Dr. dr. Ami Ashariati, SpPD-KHOM, FINASIM',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:32','2026-08-06 09:43:32'),(246,'20250031','apt. Rahmi Three Wahyuni',NULL,'farmasi',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:32','2026-08-06 09:43:32'),(247,'D240160','dr. Benediktus Arifin, Sp.OG (K), MD, MPH, FICS',NULL,'klinik',438,'dr. Benediktus Arifin, Sp.OG (K), MD, MPH, FICS',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:32','2026-08-06 09:43:32'),(248,'20250117','Sulton Satrio Sholehuddin',NULL,'farmasi',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:32','2026-08-06 09:43:32'),(249,'D240161','dr. Satria Audi Hutama, Sp.OG',NULL,'klinik',440,'dr. Satria Audi Hutama, Sp.OG',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:32','2026-08-06 09:43:32'),(250,'20250115','Maria Tasha Wijayanti',NULL,'kasir_administrasi',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:32','2026-08-06 09:43:32'),(251,'D240162','Prof. dr. Bambang Wirjatmadi, MS, MCN, PhD, SpGK (K)',NULL,'klinik',445,'Prof. dr. Bambang Wirjatmadi, MS, MCN, PhD, SpGK (K)',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:32','2026-08-06 09:43:32'),(252,'20250183','Muchammad Miftachul Huda',NULL,'kasir_administrasi',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:32','2026-08-06 09:43:32'),(253,'D240165','dr. Susanto MSi. Med, Sp.A',NULL,'klinik',458,'dr. Susanto MSi. Med, Sp.A',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:32','2026-08-06 09:43:32'),(254,'20250468','Billy Anthony Bingtoyo',NULL,'administrator',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:32','2026-08-06 09:43:32'),(255,'20250220','Grace Silviana',NULL,'kasir_administrasi',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:32','2026-08-06 09:43:32'),(256,'D240170','dr. Nanda Aulya Ramadhan, M.Kes., M.Ked.Klin., Sp.KFR, AIFO-K',NULL,'klinik',477,'dr. Nanda Aulya Ramadhan, M.Kes., M.Ked.Klin., Sp.KFR, AIFO-K',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:32','2026-08-06 09:43:32'),(257,'20250448','Engga Aditya',NULL,'kasir_administrasi',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:32','2026-08-06 09:43:32'),(258,'20250536','dr. Delvin Data Santoso',NULL,'klinik',508,'dr. Delvin Data Santoso',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:32','2026-08-06 09:43:32'),(259,'20240588','Elsya Elleriana Lisdy',NULL,'kasir_administrasi',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:32','2026-08-06 09:43:32'),(260,'D240176','dr. Harris Kristanto Gunawan, Sp.M., M.Ked.Klin.',NULL,'klinik',509,'dr. Harris Kristanto Gunawan, Sp.M., M.Ked.Klin.',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:32','2026-08-06 09:43:32'),(261,'20250542','dr. Wijayadi Prawiro Suyono',NULL,'klinik',512,'dr. Wijayadi Prawiro Suyono',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:32','2026-08-06 09:43:32'),(262,'D240177','dr. Putri Anya Universade, Sp.M, M.Ked.Klin',NULL,'klinik',513,'dr. Putri Anya Universade, Sp.M, M.Ked.Klin',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:32','2026-08-06 09:43:32'),(263,'20260074','dr. Elizabeth Suryani Winarno',NULL,'klinik',544,'dr. Elizabeth Suryani Winarno',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:32','2026-08-06 09:43:32'),(264,'20260079','dr. Jody Erlangga',NULL,'klinik',546,'dr. Jody Erlangga',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:32','2026-08-06 09:43:32'),(265,'D260002','dr. Carlos Gracia Suprianto Binti, Sp.OT(K)',NULL,'klinik',545,'dr. Carlos Gracia Suprianto Binti, Sp.OT(K)',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:32','2026-08-06 09:43:32'),(266,'20260078','Siska Firly Azizah',NULL,'administrator',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:32','2026-08-06 09:43:32'),(267,'820260058','Michelle',NULL,'administrator',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:32','2026-08-06 09:43:32'),(268,'D240166','dr. Ignatius Hanny Handoko Tanuwijaya, Sp.P',NULL,'klinik',463,'dr. Ignatius Hanny Handoko Tanuwijaya, Sp.P',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:32','2026-08-06 09:43:32'),(269,'20260119','Ester Yulianti',NULL,'kasir_administrasi',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:32','2026-08-06 09:43:32'),(270,'D250007','Dr. dr. Muhammad Faris, Sp.BS, K-Spine',NULL,'klinik',499,'Dr. dr. Muhammad Faris, Sp.BS, K-Spine',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:32','2026-08-06 09:43:32'),(271,'D20250001','dr. Mutiara Rizki Haryati, Sp.PD, K-GH',NULL,'klinik',448,'dr. Mutiara Rizki Haryati, Sp.PD, K-GH',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:32','2026-08-06 09:43:32'),(272,'20260144','Cindy Puspita Sari',NULL,'kasir_administrasi',NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:32','2026-08-06 09:43:32'),(273,'D240174','drg. Shirley Gautama, Sp.Ort',NULL,'klinik',502,'drg. Shirley Gautama, Sp.Ort',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:32','2026-08-06 09:43:32'),(274,'D260005','dr. Kadhafi, Sp.KN-TM, FANMB',NULL,'klinik',554,'dr. Kadhafi, Sp.KN-TM, FANMB',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:32','2026-08-06 09:43:32'),(275,'D240168','dr. Agoes Willyono, Sp.N',NULL,'klinik',468,'dr. Agoes Willyono, Sp.N',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:32','2026-08-06 09:43:32'),(276,'D260014','dr. Dian Pratamastuti, Sp.A',NULL,'klinik',588,'dr. Dian Pratamastuti, Sp.A',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:32','2026-08-06 09:43:32'),(277,'D260015','dr. Kalyana Vati, Sp.JP',NULL,'klinik',597,'dr. Kalyana Vati, Sp.JP',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:32','2026-08-06 09:43:32'),(278,'D260018','dr. Lewis Lie, Sp.B, FinaCS',NULL,'klinik',603,'dr. Lewis Lie, Sp.B, FinaCS',NULL,NULL,NULL,NULL,0,NULL,'2026-08-06 09:43:32','2026-08-06 09:43:32');
/*!40000 ALTER TABLE `antrian_access` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `banners` WRITE;
/*!40000 ALTER TABLE `banners` DISABLE KEYS */;
INSERT INTO `banners` (`id`, `nama`, `image`, `is_active`, `sort`, `created_at`, `updated_at`) VALUES (4,'bone mineral','banner_20260805101547_7025.jpeg',0,4,'2026-08-05 03:15:47','2026-08-05 04:37:40'),(5,'brain screening','banner_20260805101558_5445.jpeg',1,5,'2026-08-05 03:15:58','2026-08-05 04:37:44'),(6,'endoskopi kapsul','banner_20260805101611_1758.jpeg',0,6,'2026-08-05 03:16:11','2026-08-05 03:38:59'),(7,'family wellness','banner_20260805101620_4613.jpeg',0,7,'2026-08-05 03:16:20','2026-08-05 03:39:03'),(9,'health spine','banner_20260805101630_8282.jpeg',0,9,'2026-08-05 03:16:30','2026-08-05 03:39:05'),(10,'maternity package','banner_20260805101640_4621.jpeg',1,10,'2026-08-05 03:16:40','2026-08-05 03:16:40'),(11,'medical check blabla 2','banner_20260805101656_1030.jpeg',1,11,'2026-08-05 03:16:56','2026-08-05 03:16:56'),(12,'medical check blabla 1','banner_20260805101706_6819.jpeg',1,12,'2026-08-05 03:17:06','2026-08-05 03:17:06'),(13,'pet scan','banner_20260805101716_5868.jpeg',1,13,'2026-08-05 03:17:16','2026-08-05 03:17:16'),(14,'women health','banner_20260805101734_4815.jpeg',1,14,'2026-08-05 03:17:34','2026-08-05 03:17:34');
/*!40000 ALTER TABLE `banners` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `videos` WRITE;
/*!40000 ALTER TABLE `videos` DISABLE KEYS */;
INSERT INTO `videos` (`id`, `judul`, `filename`, `is_active`, `sort`, `created_at`, `updated_at`) VALUES (1,'Promo day 1 ni bos','video_20260731061658_1975.mp4',0,1,'2026-07-30 23:16:58','2026-08-05 03:24:05');
/*!40000 ALTER TABLE `videos` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `video_clinics` WRITE;
/*!40000 ALTER TABLE `video_clinics` DISABLE KEYS */;
INSERT INTO `video_clinics` (`id`, `video_id`, `service_unit_code`, `created_at`, `updated_at`) VALUES (1,1,'SU-023','2026-07-30 23:16:58','2026-07-30 23:16:58'),(2,1,'SU-029','2026-07-30 23:16:58','2026-07-30 23:16:58');
/*!40000 ALTER TABLE `video_clinics` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `banner_clinics` WRITE;
/*!40000 ALTER TABLE `banner_clinics` DISABLE KEYS */;
/*!40000 ALTER TABLE `banner_clinics` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `doctor_photos` WRITE;
/*!40000 ALTER TABLE `doctor_photos` DISABLE KEYS */;
INSERT INTO `doctor_photos` (`paramedic_id`, `nik`, `filename`, `created_at`, `updated_at`) VALUES (331,'3578090203920001','3578090203920001.jpeg','2026-07-28 23:42:14','2026-07-28 23:42:14'),(357,'6202084302990002','6202084302990002.jpeg','2026-07-27 19:56:48','2026-07-27 19:56:48');
/*!40000 ALTER TABLE `doctor_photos` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `clinic_settings` WRITE;
/*!40000 ALTER TABLE `clinic_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `clinic_settings` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2026_07_27_100000_create_clinic_settings_table',2),(5,'2026_07_27_110000_create_doctor_photos_table',3),(6,'2026_07_28_150000_create_antrian_access_table',4),(7,'2026_07_28_160000_add_password_to_antrian_access',5),(8,'2026_07_29_100000_create_banners_table',6),(9,'2026_07_29_100100_create_videos_table',6),(10,'2026_07_29_110000_create_antrian_table',7),(11,'2026_07_29_120000_add_resep_to_antrian',8),(12,'2026_07_31_120000_create_media_clinic_pivots',9),(13,'2026_08_04_100000_add_room_occupied_at_to_antrian_access',10),(14,'2026_08_05_100000_add_is_active_to_banners',11),(15,'2026_08_11_120000_add_is_booking_to_antrian',12);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;


SET FOREIGN_KEY_CHECKS = 1;

-- Selesai. Verifikasi:
--   SELECT COUNT(*) FROM antrian_access;   -- harus 268
--   SELECT COUNT(*) FROM banners;          -- harus 10
