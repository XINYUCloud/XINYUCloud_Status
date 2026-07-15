CREATE DATABASE IF NOT EXISTS `xinyu_status` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `xinyu_status`;

CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) DEFAULT NULL,
    `role` ENUM('admin','viewer') NOT NULL DEFAULT 'admin',
    `last_login` DATETIME DEFAULT NULL,
    `last_ip` VARCHAR(45) DEFAULT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `sites` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `url` VARCHAR(500) NOT NULL,
    `check_method` ENUM('http','https','ping','tcp') NOT NULL DEFAULT 'https',
    `check_path` VARCHAR(255) DEFAULT '/',
    `expected_status` SMALLINT UNSIGNED NOT NULL DEFAULT 200,
    `expected_body` VARCHAR(500) DEFAULT NULL,
    `check_interval` INT UNSIGNED NOT NULL DEFAULT 300,
    `timeout` INT UNSIGNED NOT NULL DEFAULT 10,
    `check_ssl` TINYINT(1) NOT NULL DEFAULT 1,
    `check_dns` TINYINT(1) NOT NULL DEFAULT 1,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `display_order` INT UNSIGNED NOT NULL DEFAULT 0,
    `tags` VARCHAR(500) DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `checks` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `site_id` INT UNSIGNED NOT NULL,
    `status` ENUM('ok','error','warning','timeout') NOT NULL DEFAULT 'ok',
    `http_code` SMALLINT UNSIGNED DEFAULT NULL,
    `response_time` INT UNSIGNED DEFAULT NULL,
    `error_message` VARCHAR(1000) DEFAULT NULL,
    `ssl_valid` TINYINT(1) DEFAULT NULL,
    `ssl_days_remaining` INT DEFAULT NULL,
    `ssl_expires` DATE DEFAULT NULL,
    `dns_resolved` TINYINT(1) DEFAULT NULL,
    `dns_records` VARCHAR(1000) DEFAULT NULL,
    `response_body` TEXT DEFAULT NULL,
    `response_headers` TEXT DEFAULT NULL,
    `checked_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_site_status` (`site_id`,`status`),
    INDEX `idx_checked_at` (`checked_at`),
    FOREIGN KEY (`site_id`) REFERENCES `sites`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `incidents` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `site_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `severity` ENUM('critical','major','minor','info') NOT NULL DEFAULT 'minor',
    `status` ENUM('investigating','identified','monitoring','resolved') NOT NULL DEFAULT 'investigating',
    `started_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `resolved_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`site_id`) REFERENCES `sites`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `incident_updates` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `incident_id` INT UNSIGNED NOT NULL,
    `content` TEXT NOT NULL,
    `status` ENUM('investigating','identified','monitoring','resolved') NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`incident_id`) REFERENCES `incidents`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `site_id` INT UNSIGNED DEFAULT NULL,
    `type` ENUM('email','webhook','slack','dingtalk','wechat') NOT NULL,
    `config` JSON NOT NULL,
    `events` JSON NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`site_id`) REFERENCES `sites`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `settings` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(100) NOT NULL UNIQUE,
    `value` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `settings` (`key`,`value`) VALUES
('site_name','XINYU Status Monitor'),
('site_description','服务状态实时监控系统'),
('check_interval','300'),
('history_days','90'),
('timezone','Asia/Shanghai'),
('language','zh-CN'),
('public_page_enabled','1'),
('allow_public_api','1'),
('version','3.0.0');

INSERT IGNORE INTO `users` (`username`,`password_hash`,`email`,`role`) VALUES
('admin','$2y$12$LJ3m4ys3Gql.ZmCKvmpKveV0kXbBvF1pBxHnNBhRSGCqKqMqN7e','admin@example.com','admin');