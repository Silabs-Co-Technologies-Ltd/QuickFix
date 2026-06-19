-- QuickFix Nearby Version 2.0 Database Schema
-- MySQL 8.0+ / MariaDB 10.5+
-- Import with: mysql -u <user> -p if0_42163414_quickfix < db.sql

SET NAMES utf8mb4;
SET time_zone = '+01:00';

CREATE DATABASE IF NOT EXISTS `if0_42163414_quickfix`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `quickfix_db`;

-- Unified accounts table for customers, professionals, and admins.
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `password` VARCHAR(255) DEFAULT NULL,
  `role` ENUM('customer', 'professional', 'admin') NOT NULL DEFAULT 'customer',
  `google_id` VARCHAR(255) DEFAULT NULL,
  `profile_picture` VARCHAR(500) DEFAULT NULL,
  `email_verified_at` TIMESTAMP NULL DEFAULT NULL,
  `last_login_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`),
  UNIQUE KEY `uq_users_google_id` (`google_id`),
  KEY `idx_users_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `customers` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(30) DEFAULT NULL,
  `location` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_customers_email` (`email`),
  KEY `idx_customers_user_id` (`user_id`),
  CONSTRAINT `fk_customers_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `service_categories` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(120) NOT NULL,
  `icon` VARCHAR(20) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_service_categories_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `professionals` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(30) NOT NULL,
  `service_category_id` INT UNSIGNED DEFAULT NULL,
  `service_type` VARCHAR(100) NOT NULL,
  `bio` TEXT DEFAULT NULL,
  `location` VARCHAR(255) DEFAULT NULL,
  `coverage_area` VARCHAR(255) DEFAULT NULL,
  `verified` TINYINT(1) NOT NULL DEFAULT 0,
  `availability_status` ENUM('available', 'busy', 'offline') NOT NULL DEFAULT 'offline',
  `rating` DECIMAL(3,2) NOT NULL DEFAULT 0.00,
  `total_jobs` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_professionals_email` (`email`),
  KEY `idx_professionals_user_id` (`user_id`),
  KEY `idx_professionals_category` (`service_category_id`),
  KEY `idx_professionals_verified_available` (`verified`, `availability_status`),
  CONSTRAINT `fk_professionals_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_professionals_category` FOREIGN KEY (`service_category_id`) REFERENCES `service_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `service_requests` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `customer_id` INT UNSIGNED DEFAULT NULL,
  `professional_id` INT UNSIGNED DEFAULT NULL,
  `service_category_id` INT UNSIGNED DEFAULT NULL,
  `service_type` VARCHAR(100) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `location` VARCHAR(255) DEFAULT NULL,
  `urgency` ENUM('low', 'medium', 'high', 'emergency') NOT NULL DEFAULT 'medium',
  `scheduled_for` DATETIME DEFAULT NULL,
  `status` ENUM('pending', 'assigned', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_service_requests_customer` (`customer_id`),
  KEY `idx_service_requests_professional` (`professional_id`),
  KEY `idx_service_requests_category` (`service_category_id`),
  KEY `idx_service_requests_status_created` (`status`, `created_at`),
  CONSTRAINT `fk_service_requests_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_service_requests_professional` FOREIGN KEY (`professional_id`) REFERENCES `professionals` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_service_requests_category` FOREIGN KEY (`service_category_id`) REFERENCES `service_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `contact_submissions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `interest_type` VARCHAR(100) NOT NULL,
  `message` TEXT NOT NULL,
  `source` VARCHAR(80) NOT NULL DEFAULT 'landing_page',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_contact_submissions_email` (`email`),
  KEY `idx_contact_submissions_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `remember_tokens` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `token_hash` CHAR(64) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_remember_tokens_hash` (`token_hash`),
  KEY `idx_remember_tokens_user` (`user_id`),
  CONSTRAINT `fk_remember_tokens_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `service_categories` (`name`, `slug`, `icon`, `description`) VALUES
  ('Electrician', 'electrician', '⚡', 'Electrical installation, troubleshooting, and emergency repairs.'),
  ('Plumber', 'plumber', '🚰', 'Leak repairs, pipe installation, fixtures, and drainage support.'),
  ('Mechanic', 'mechanic', '🚗', 'Vehicle diagnostics, mobile mechanic support, and routine maintenance.'),
  ('Builder', 'builder', '🧱', 'Construction, renovation, masonry, and building maintenance.'),
  ('Barber', 'barber', '💈', 'Mobile grooming, barbering, and personal-care appointments.')
ON DUPLICATE KEY UPDATE
  `name` = VALUES(`name`),
  `icon` = VALUES(`icon`),
  `description` = VALUES(`description`),
  `is_active` = 1;
