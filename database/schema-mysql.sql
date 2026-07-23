-- database/schema-mysql.sql
-- MySQL schema untuk Katalog Store
-- Engine: InnoDB | Charset: utf8mb4 | Collation: utf8mb4_unicode_ci
-- Jalankan dengan: mysql -u root -p katalog_store < database/schema-mysql.sql

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET time_zone = '+07:00';

-- ─────────────────────────────────────────────────────────────
-- Tabel: admins
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `admins` (
  `id`         BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(150)     NOT NULL,
  `email`      VARCHAR(255)     NOT NULL,
  `password`   VARCHAR(255)     NOT NULL COMMENT 'bcrypt hash',
  `created_at` TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_admins_email` (`email`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────
-- Tabel: categories
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `categories` (
  `id`         BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(100)     NOT NULL,
  `slug`       VARCHAR(120)     NOT NULL,
  `is_active`  TINYINT(1)       NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_categories_slug` (`slug`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────
-- Tabel: products
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `products` (
  `id`          BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `category_id` BIGINT UNSIGNED  NOT NULL,
  `name`        VARCHAR(150)     NOT NULL,
  `brand`       VARCHAR(100)     NOT NULL,
  `price`       DECIMAL(15,2)    NOT NULL DEFAULT 0.00,
  `quantity`    INT UNSIGNED     NOT NULL DEFAULT 0,
  `image_path`  VARCHAR(500)     DEFAULT NULL,
  `is_active`   TINYINT(1)       NOT NULL DEFAULT 1,
  `created_at`  TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_products_category`    (`category_id`),
  KEY `idx_products_active`      (`is_active`),
  KEY `idx_products_search`      (`name`(50), `brand`(50)),
  CONSTRAINT `fk_products_category`
    FOREIGN KEY (`category_id`)
    REFERENCES `categories` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────
-- Tabel: settings
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `settings` (
  `setting_key`   VARCHAR(100) NOT NULL,
  `setting_value` TEXT         DEFAULT NULL,
  `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

