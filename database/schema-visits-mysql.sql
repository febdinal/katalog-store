-- database/schema-visits-mysql.sql
-- Tabel statistik kunjungan website untuk Katalog Store
-- Engine: InnoDB | Charset: utf8mb4 | Collation: utf8mb4_unicode_ci

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `website_visits` (
  `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `visitor_hash`     CHAR(64)        NOT NULL,
  `visit_date`       DATE            NOT NULL,
  `page_views`       INT UNSIGNED    NOT NULL DEFAULT 1,
  `first_visited_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_visited_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_visitor_date` (`visitor_hash`, `visit_date`),
  KEY `idx_visit_date` (`visit_date`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
