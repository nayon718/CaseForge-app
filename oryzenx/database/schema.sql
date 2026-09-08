CREATE TABLE IF NOT EXISTS `messages` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(120)  NOT NULL,
  `email`      VARCHAR(160)  NOT NULL,
  `phone`      VARCHAR(40)   DEFAULT NULL,
  `subject`    VARCHAR(180)  DEFAULT NULL,
  `message`    TEXT          NOT NULL,
  `ip`         VARCHAR(45)   DEFAULT NULL,
  `user_agent` VARCHAR(255)  DEFAULT NULL,
  `is_read`    TINYINT(1)    NOT NULL DEFAULT 0,
  `created_at` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_created` (`created_at`),
  KEY `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `visits` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `path`       VARCHAR(190)  NOT NULL,
  `referer`    VARCHAR(255)  DEFAULT NULL,
  `ip`         VARCHAR(45)   DEFAULT NULL,
  `created_at` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_path` (`path`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
