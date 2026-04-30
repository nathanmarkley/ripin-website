-- ============================================================
-- database/setup.sql
-- Run ONCE in Bluehost phpMyAdmin
-- cPanel → phpMyAdmin → select ripinorg_ripin_dev26
-- Click SQL tab → paste everything → click Go
-- ============================================================

SET NAMES utf8mb4;
SET time_zone = 'America/New_York';

-- ── USERS ─────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `users` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`         VARCHAR(100)  NOT NULL,
  `email`        VARCHAR(150)  NOT NULL UNIQUE,
  `password`     VARCHAR(255)  NOT NULL,
  `role`         ENUM('admin','editor','contributor') NOT NULL DEFAULT 'contributor',
  `active`       TINYINT(1)    NOT NULL DEFAULT 1,
  `last_login`   DATETIME      DEFAULT NULL,
  `created_at`   DATETIME      NOT NULL DEFAULT NOW(),
  `updated_at`   DATETIME      NOT NULL DEFAULT NOW() ON UPDATE NOW()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── LOGIN LOG (brute force protection) ────────────────────────
CREATE TABLE IF NOT EXISTS `login_log` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `email`        VARCHAR(150)  NOT NULL,
  `success`      TINYINT(1)    NOT NULL DEFAULT 0,
  `ip_address`   VARCHAR(45)   DEFAULT NULL,
  `attempted_at` DATETIME      NOT NULL DEFAULT NOW(),
  INDEX `idx_email_time` (`email`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── CONTENT ZONES ─────────────────────────────────────────────
-- Editable text blocks on public pages (staff edit, you control layout via Git)
CREATE TABLE IF NOT EXISTS `content_zones` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `zone_key`   VARCHAR(100)  NOT NULL UNIQUE,
  `label`      VARCHAR(150)  NOT NULL,
  `value`      LONGTEXT      NOT NULL DEFAULT '',
  `type`       ENUM('text','textarea','richtext','image_url','url') NOT NULL DEFAULT 'textarea',
  `page`       VARCHAR(50)   NOT NULL DEFAULT 'global',
  `updated_by` INT UNSIGNED  DEFAULT NULL,
  `updated_at` DATETIME      NOT NULL DEFAULT NOW() ON UPDATE NOW()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── PAGES ─────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `pages` (
  `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `title`          VARCHAR(255)  NOT NULL,
  `slug`           VARCHAR(255)  NOT NULL UNIQUE,
  `content`        LONGTEXT      NOT NULL DEFAULT '',
  `excerpt`        TEXT          DEFAULT NULL,
  `featured_image` VARCHAR(500)  DEFAULT NULL,
  `status`         ENUM('draft','pending_review','published','archived') NOT NULL DEFAULT 'draft',
  `meta_title`     VARCHAR(255)  DEFAULT NULL,
  `meta_desc`      TEXT          DEFAULT NULL,
  `sort_order`     INT           NOT NULL DEFAULT 0,
  `review_note`    TEXT          DEFAULT NULL,
  `created_by`     INT UNSIGNED  NOT NULL,
  `updated_by`     INT UNSIGNED  DEFAULT NULL,
  `reviewed_by`    INT UNSIGNED  DEFAULT NULL,
  `published_at`   DATETIME      DEFAULT NULL,
  `created_at`     DATETIME      NOT NULL DEFAULT NOW(),
  `updated_at`     DATETIME      NOT NULL DEFAULT NOW() ON UPDATE NOW(),
  INDEX `idx_slug`   (`slug`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── PAGE VERSION HISTORY ───────────────────────────────────────
CREATE TABLE IF NOT EXISTS `page_versions` (
  `id`       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `page_id`  INT UNSIGNED  NOT NULL,
  `title`    VARCHAR(255)  NOT NULL,
  `content`  LONGTEXT      NOT NULL,
  `status`   VARCHAR(20)   NOT NULL,
  `saved_by` INT UNSIGNED  NOT NULL,
  `saved_at` DATETIME      NOT NULL DEFAULT NOW(),
  INDEX `idx_page_id` (`page_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── EVENTS ────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `events` (
  `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `title`            VARCHAR(255)  NOT NULL,
  `description`      TEXT          DEFAULT NULL,
  `content`          LONGTEXT      DEFAULT NULL,
  `start_date`       DATETIME      NOT NULL,
  `end_date`         DATETIME      DEFAULT NULL,
  `all_day`          TINYINT(1)    NOT NULL DEFAULT 0,
  `location`         VARCHAR(255)  DEFAULT NULL,
  `location_url`     VARCHAR(500)  DEFAULT NULL,
  `registration_url` VARCHAR(500)  DEFAULT NULL,
  `category`         VARCHAR(100)  DEFAULT NULL,
  `color`            VARCHAR(7)    NOT NULL DEFAULT '#1E5CA8',
  `featured`         TINYINT(1)    NOT NULL DEFAULT 0,
  `status`           ENUM('draft','pending_review','published','cancelled') NOT NULL DEFAULT 'published',
  `review_note`      TEXT          DEFAULT NULL,
  `created_by`       INT UNSIGNED  NOT NULL,
  `updated_by`       INT UNSIGNED  DEFAULT NULL,
  `reviewed_by`      INT UNSIGNED  DEFAULT NULL,
  `created_at`       DATETIME      NOT NULL DEFAULT NOW(),
  `updated_at`       DATETIME      NOT NULL DEFAULT NOW() ON UPDATE NOW(),
  INDEX `idx_start_date` (`start_date`),
  INDEX `idx_status`     (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── RESOURCE CATEGORIES ───────────────────────────────────────
CREATE TABLE IF NOT EXISTS `resource_categories` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`       VARCHAR(100) NOT NULL,
  `slug`       VARCHAR(100) NOT NULL UNIQUE,
  `sort_order` INT          NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── RESOURCES ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `resources` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `title`       VARCHAR(255)  NOT NULL,
  `description` TEXT          DEFAULT NULL,
  `type`        ENUM('pdf','video','link') NOT NULL,
  `url`         VARCHAR(500)  DEFAULT NULL,
  `file_path`   VARCHAR(500)  DEFAULT NULL,
  `file_name`   VARCHAR(255)  DEFAULT NULL,
  `file_size`   INT UNSIGNED  DEFAULT NULL,
  `language`    VARCHAR(10)   NOT NULL DEFAULT 'en',
  `featured`    TINYINT(1)    NOT NULL DEFAULT 0,
  `status`      ENUM('draft','pending_review','published','archived') NOT NULL DEFAULT 'published',
  `review_note` TEXT          DEFAULT NULL,
  `created_by`  INT UNSIGNED  NOT NULL,
  `updated_by`  INT UNSIGNED  DEFAULT NULL,
  `reviewed_by` INT UNSIGNED  DEFAULT NULL,
  `created_at`  DATETIME      NOT NULL DEFAULT NOW(),
  `updated_at`  DATETIME      NOT NULL DEFAULT NOW() ON UPDATE NOW(),
  INDEX `idx_type`     (`type`),
  INDEX `idx_status`   (`status`),
  INDEX `idx_language` (`language`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── RESOURCE ↔ CATEGORY ───────────────────────────────────────
CREATE TABLE IF NOT EXISTS `resource_category_map` (
  `resource_id` INT UNSIGNED NOT NULL,
  `category_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`resource_id`, `category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── ASSETS (file manager — images + PDFs only) ────────────────
CREATE TABLE IF NOT EXISTS `assets` (
  `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `file_name`     VARCHAR(255) NOT NULL,
  `original_name` VARCHAR(255) NOT NULL,
  `file_path`     VARCHAR(500) NOT NULL,
  `file_url`      VARCHAR(500) NOT NULL,
  `file_type`     ENUM('image','pdf') NOT NULL,
  `mime_type`     VARCHAR(100) NOT NULL,
  `file_size`     INT UNSIGNED NOT NULL DEFAULT 0,
  `alt_text`      VARCHAR(255) DEFAULT NULL,
  `uploaded_by`   INT UNSIGNED NOT NULL,
  `uploaded_at`   DATETIME     NOT NULL DEFAULT NOW(),
  INDEX `idx_file_type`   (`file_type`),
  INDEX `idx_uploaded_by` (`uploaded_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── BANNER ────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `banner` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `active`      TINYINT(1)   NOT NULL DEFAULT 0,
  `icon`        VARCHAR(10)  DEFAULT '📢',
  `message`     TEXT         NOT NULL DEFAULT '',
  `button_text` VARCHAR(100) DEFAULT NULL,
  `button_url`  VARCHAR(500) DEFAULT NULL,
  `style`       ENUM('blue','orange','red','teal','yellow') NOT NULL DEFAULT 'blue',
  `updated_by`  INT UNSIGNED DEFAULT NULL,
  `updated_at`  DATETIME     NOT NULL DEFAULT NOW() ON UPDATE NOW()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── POPUPS ────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `popups` (
  `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`           VARCHAR(150) NOT NULL,
  `active`         TINYINT(1)   NOT NULL DEFAULT 0,
  `page`           VARCHAR(50)  NOT NULL DEFAULT 'all',
  `delay_seconds`  INT          NOT NULL DEFAULT 3,
  `show_once`      TINYINT(1)   NOT NULL DEFAULT 1,
  `heading`        VARCHAR(255) DEFAULT NULL,
  `message`        TEXT         DEFAULT NULL,
  `image_url`      VARCHAR(500) DEFAULT NULL,
  `embed_type`     ENUM('button','code','contactForm','none') NOT NULL DEFAULT 'button',
  `embed_code`     TEXT         DEFAULT NULL,
  `button_text`    VARCHAR(100) DEFAULT NULL,
  `button_url`     VARCHAR(500) DEFAULT NULL,
  `button_new_tab` TINYINT(1)   NOT NULL DEFAULT 0,
  `size`           ENUM('small','medium','large') NOT NULL DEFAULT 'medium',
  `header_color`   VARCHAR(7)   NOT NULL DEFAULT '#1E5CA8',
  `created_by`     INT UNSIGNED NOT NULL,
  `updated_by`     INT UNSIGNED DEFAULT NULL,
  `created_at`     DATETIME     NOT NULL DEFAULT NOW(),
  `updated_at`     DATETIME     NOT NULL DEFAULT NOW() ON UPDATE NOW()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── REVIEW NOTIFICATIONS ───────────────────────────────────────
CREATE TABLE IF NOT EXISTS `review_queue` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `content_type` ENUM('page','event','resource') NOT NULL,
  `content_id`   INT UNSIGNED NOT NULL,
  `content_title`VARCHAR(255) NOT NULL,
  `submitted_by` INT UNSIGNED NOT NULL,
  `submitted_at` DATETIME     NOT NULL DEFAULT NOW(),
  `reviewed_by`  INT UNSIGNED DEFAULT NULL,
  `reviewed_at`  DATETIME     DEFAULT NULL,
  `action`       ENUM('pending','approved','changes_requested','declined') NOT NULL DEFAULT 'pending',
  `review_note`  TEXT         DEFAULT NULL,
  INDEX `idx_action` (`action`),
  INDEX `idx_content` (`content_type`, `content_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── CONTENT ZONE HISTORY ───────────────────────────────────────
CREATE TABLE IF NOT EXISTS `content_zone_history` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `zone_key`   VARCHAR(100) NOT NULL,
  `old_value`  LONGTEXT     NOT NULL,
  `new_value`  LONGTEXT     NOT NULL,
  `changed_by` INT UNSIGNED NOT NULL,
  `changed_at` DATETIME     NOT NULL DEFAULT NOW(),
  INDEX `idx_zone_key` (`zone_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── BLOG POSTS ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `posts` (
  `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `title`          VARCHAR(255) NOT NULL,
  `slug`           VARCHAR(255) NOT NULL UNIQUE,
  `content`        LONGTEXT     NOT NULL DEFAULT '',
  `excerpt`        TEXT         DEFAULT NULL,
  `featured_image` VARCHAR(500) DEFAULT NULL,
  `status`         ENUM('draft','pending_review','published','archived') NOT NULL DEFAULT 'draft',
  `review_note`    TEXT         DEFAULT NULL,
  `meta_title`     VARCHAR(255) DEFAULT NULL,
  `meta_desc`      TEXT         DEFAULT NULL,
  `created_by`     INT UNSIGNED NOT NULL,
  `updated_by`     INT UNSIGNED DEFAULT NULL,
  `reviewed_by`    INT UNSIGNED DEFAULT NULL,
  `published_at`   DATETIME     DEFAULT NULL,
  `created_at`     DATETIME     NOT NULL DEFAULT NOW(),
  `updated_at`     DATETIME     NOT NULL DEFAULT NOW() ON UPDATE NOW(),
  INDEX `idx_slug`   (`slug`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- SEED DATA
-- ============================================================

-- Default banner (inactive)
INSERT INTO `banner` (`active`, `message`, `style`) VALUES
(0, 'Welcome to RIPIN. Our services are free, multilingual, and confidential.', 'blue');

-- Default admin user
-- Temporary password: Change_Me_Now!99
-- YOU MUST CHANGE THIS ON FIRST LOGIN
INSERT INTO `users` (`name`, `email`, `password`, `role`) VALUES
('RIPIN Admin', 'admin@ripin.org',
 '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uHxXHsK2',
 'admin');

-- Default content zones
INSERT INTO `content_zones` (`zone_key`, `label`, `value`, `type`, `page`) VALUES
('home_hero_heading',  'Hero Heading',         'Personal Support Built on Personal Experience',         'text',     'home'),
('home_hero_subtext',  'Hero Subtext',          'We help Rhode Islanders navigate health care, special education, and healthy aging — with peer professionals who truly understand your journey.', 'textarea', 'home'),
('home_hero_btn_text', 'Hero Button Text',      'Explore Our Services',                                 'text',     'home'),
('home_hero_btn_url',  'Hero Button Link',      'services.php',                                         'url',      'home'),
('home_trust_heading', 'Trust Banner Heading',  'You Don\'t Have to Figure It Out Alone',               'text',     'home'),
('home_trust_text',    'Trust Banner Text',     'Since 1991, RIPIN has been a steady hand for Rhode Island families. Every person on our team has walked a path similar to yours.', 'textarea', 'home'),
('about_intro',        'About Introduction',    'RIPIN (Rhode Island Parent Information Network) is a nonprofit organization dedicated to helping Rhode Islanders navigate health care, special education, and healthy aging.', 'richtext', 'about'),
('about_mission',      'Mission Statement',     'Our peer professionals have lived experience caring for a loved one with special needs — allowing us to connect with families on a deeply personal level.', 'richtext', 'about'),
('contact_intro',      'Contact Page Intro',    'We\'re here to help. Reach out any way that works for you.', 'textarea', 'contact'),
('contact_hours',      'Office Hours',          'Monday – Friday, 8:30am – 4:30pm',                    'text',     'contact'),
('footer_tagline',     'Footer Tagline',        'Helping Rhode Islanders navigate health care, special education, and healthy aging since 1991.', 'textarea', 'global'),
('phone',              'Main Phone Number',     '401-270-0101',                                         'text',     'global'),
('fax',                'Fax Number',            '401-270-7049',                                         'text',     'global'),
('email',              'Main Email',            'info@ripin.org',                                       'text',     'global'),
('address',            'Office Address',        '300 Jefferson Blvd, Suite 300, Warwick, RI 02888',     'textarea', 'global'),
('facebook_url',       'Facebook URL',          'https://www.facebook.com/RIPIN.ORG',                   'url',      'global'),
('instagram_url',      'Instagram URL',         'https://www.instagram.com/ripin_ri/',                  'url',      'global'),
('twitter_url',        'Twitter/X URL',         'https://twitter.com/RIPIN_RI',                         'url',      'global'),
('youtube_url',        'YouTube URL',           'https://www.youtube.com/@ripin_ri',                    'url',      'global');

-- Default resource categories
INSERT INTO `resource_categories` (`name`, `slug`, `sort_order`) VALUES
('Special Education',  'special-education',  1),
('Healthcare',         'healthcare',          2),
('Healthy Aging',      'healthy-aging',       3),
('Early Intervention', 'early-intervention',  4),
('Family Voices',      'family-voices',       5),
('Recursos en Español','recursos-en-espanol', 6);
