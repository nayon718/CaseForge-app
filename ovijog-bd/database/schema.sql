-- চাকরি সার্কুলার — CakriCircular.com — Core PHP Schema (utf8mb4)
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;

-- Users
CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  username VARCHAR(100) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  name VARCHAR(255) NOT NULL DEFAULT '',
  role VARCHAR(50) NOT NULL DEFAULT 'admin',
  permissions TEXT NOT NULL DEFAULT '',
  last_login_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Settings
CREATE TABLE IF NOT EXISTS settings (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  setting_key VARCHAR(190) NOT NULL,
  setting_value TEXT NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_setting_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Categories
CREATE TABLE IF NOT EXISTS categories (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  slug VARCHAR(190) NOT NULL,
  color VARCHAR(20) NOT NULL DEFAULT '#0f766e',
  icon VARCHAR(120) NOT NULL DEFAULT 'fa-solid fa-briefcase',
  sort_order INT NOT NULL DEFAULT 0,
  status TINYINT(1) NOT NULL DEFAULT 1,
  seo_title VARCHAR(500) NOT NULL DEFAULT '',
  seo_description TEXT NOT NULL DEFAULT '',
  seo_keywords TEXT NOT NULL DEFAULT '',
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_slug (slug),
  KEY idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Districts / Divisions (location)
CREATE TABLE IF NOT EXISTS districts (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name_bn VARCHAR(255) NOT NULL,
  name_en VARCHAR(255) NOT NULL,
  type VARCHAR(20) NOT NULL DEFAULT 'district',
  PRIMARY KEY (id),
  KEY idx_name (name_bn, name_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Jobs
CREATE TABLE IF NOT EXISTS jobs (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title VARCHAR(500) NOT NULL,
  slug VARCHAR(255) NOT NULL,
  category_id INT UNSIGNED NOT NULL DEFAULT 0,
  short_description VARCHAR(500) NOT NULL DEFAULT '',
  description MEDIUMTEXT NOT NULL,
  image VARCHAR(500) NOT NULL DEFAULT '',
  images TEXT NOT NULL DEFAULT '',
  pdf_file VARCHAR(500) NOT NULL DEFAULT '',
  district VARCHAR(255) NOT NULL DEFAULT '',
  location_text VARCHAR(500) NOT NULL DEFAULT '',
  deadline DATETIME NULL,
  application_link VARCHAR(1000) NOT NULL DEFAULT '',
  apply_button_text VARCHAR(255) NOT NULL DEFAULT 'আবেদন করুন',
  extra_links TEXT NOT NULL DEFAULT '',
  status ENUM('draft','published','expired','archived') NOT NULL DEFAULT 'published',
  views INT UNSIGNED NOT NULL DEFAULT 0,
  impressions BIGINT UNSIGNED NOT NULL DEFAULT 0,
  is_trending TINYINT(1) NOT NULL DEFAULT 0,
  seo_title VARCHAR(500) NOT NULL DEFAULT '',
  seo_description TEXT NOT NULL DEFAULT '',
  seo_keywords TEXT NOT NULL DEFAULT '',
  meta_image VARCHAR(500) NOT NULL DEFAULT '',
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_slug (slug),
  KEY idx_category (category_id),
  KEY idx_status (status),
  KEY idx_deadline (deadline),
  KEY idx_created (created_at),
  KEY idx_trending (is_trending),
  FULLTEXT KEY ft_title_desc (title, description)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Banners
CREATE TABLE IF NOT EXISTS banners (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title VARCHAR(255) NOT NULL DEFAULT '',
  image VARCHAR(500) NOT NULL,
  link VARCHAR(1000) NOT NULL DEFAULT '',
  sort_order INT NOT NULL DEFAULT 0,
  status TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notices
CREATE TABLE IF NOT EXISTS notices (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title VARCHAR(500) NOT NULL,
  content TEXT NOT NULL DEFAULT '',
  type VARCHAR(50) NOT NULL DEFAULT 'general',
  is_important TINYINT(1) NOT NULL DEFAULT 0,
  link VARCHAR(1000) NOT NULL DEFAULT '',
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Wrong reports (তথ্যের ভুল রিপোর্ট)
CREATE TABLE IF NOT EXISTS wrong_reports (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  job_id INT UNSIGNED NOT NULL DEFAULT 0,
  name VARCHAR(255) NOT NULL DEFAULT '',
  email VARCHAR(255) NOT NULL DEFAULT '',
  title VARCHAR(500) NOT NULL DEFAULT '',
  details TEXT NOT NULL,
  status ENUM('pending','resolved') NOT NULL DEFAULT 'pending',
  ip_address VARCHAR(64) NOT NULL DEFAULT '',
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_job (job_id),
  KEY idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Analytics
CREATE TABLE IF NOT EXISTS analytics (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  visitor_id VARCHAR(64) NOT NULL,
  ip_address VARCHAR(64) NOT NULL DEFAULT '',
  user_agent VARCHAR(500) NOT NULL DEFAULT '',
  browser VARCHAR(100) NOT NULL DEFAULT '',
  os VARCHAR(100) NOT NULL DEFAULT '',
  device VARCHAR(50) NOT NULL DEFAULT '',
  location VARCHAR(255) NOT NULL DEFAULT '',
  division VARCHAR(100) NOT NULL DEFAULT '',
  country VARCHAR(100) NOT NULL DEFAULT 'Bangladesh',
  page VARCHAR(500) NOT NULL,
  page_title VARCHAR(500) NOT NULL DEFAULT '',
  visit_date DATE NOT NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_visitor (visitor_id),
  KEY idx_page (page),
  KEY idx_date (visit_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Search logs
CREATE TABLE IF NOT EXISTS search_logs (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  keyword VARCHAR(255) NOT NULL,
  count INT UNSIGNED NOT NULL DEFAULT 1,
  last_searched_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_keyword (keyword)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Job views for trending
CREATE TABLE IF NOT EXISTS job_views (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  job_id INT UNSIGNED NOT NULL,
  visitor_id VARCHAR(64) NOT NULL DEFAULT '',
  ip_address VARCHAR(64) NOT NULL DEFAULT '',
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_job (job_id),
  KEY idx_visitor (visitor_id),
  KEY idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Gallery (general uploads)
CREATE TABLE IF NOT EXISTS gallery (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title VARCHAR(255) NOT NULL DEFAULT '',
  file VARCHAR(500) NOT NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- District seed (বিভাগ + জেলা)
INSERT IGNORE INTO districts (id, name_bn, name_en, type) VALUES
(1,'ঢাকা','Dhaka','division'),(2,'চট্টগ্রাম','Chattogram','division'),(3,'সিলেট','Sylhet','division'),(4,'রাজশাহী','Rajshahi','division'),(5,'খুলনা','Khulna','division'),(6,'বরিশাল','Barishal','division'),(7,'রংপুর','Rangpur','division'),(8,'ময়মনসিংহ','Mymensingh','division'),
(9,'ঢাকা','Dhaka','district'),(10,'গাজীপুর','Gazipur','district'),(11,'নারায়ণগঞ্জ','Narayanganj','district'),(12,'চট্টগ্রাম','Chattogram','district'),(13,'কুমিল্লা','Cumilla','district'),(14,'সিলেট','Sylhet','district'),(15,'রাজশাহী','Rajshahi','district'),(16,'খুলনা','Khulna','district'),(17,'বরিশাল','Barishal','district'),(18,'রংপুর','Rangpur','district'),(19,'ময়মনসিংহ','Mymensingh','district'),(20,'কক্সবাজার','Cox''s Bazar','district'),(21,'যশোর','Jashore','district'),(22,'টাঙ্গাইল','Tangail','district'),(23,'নোয়াখালী','Noakhali','district'),(24,'ফেনী','Feni','district'),(25,'বগুড়া','Bogura','district'),(26,'পাবনা','Pabna','district'),(27,'দিনাজপুর','Dinajpur','district'),(28,'কুষ্টিয়া','Kushtia','district'),(29,'ফরিদপুর','Faridpur','district'),(30,'মাদারীপুর','Madaripur','district');

-- Category seed (চাকরি সার্কুলার categories)
INSERT IGNORE INTO categories (id, name, slug, color, icon, sort_order, status, seo_title, seo_description, created_at) VALUES
(1,'চাকরি','chakri','#0f766e','fa-solid fa-briefcase',1,1,'চাকরি সার্কুলার | আজকের চাকরির খবর','বাংলাদেশের সকল সরকারি ও বেসরকারি চাকরির সার্কুলার প্রতিদিন হালনাগাদ',NOW()),
(2,'ভর্তি','vorti','#2563eb','fa-solid fa-graduation-cap',2,1,'ভর্তি বিজ্ঞপ্তি ২০২৬ | বিশ্ববিদ্যালয় ভর্তি','সকল বিশ্ববিদ্যালয়, কলেজ ও স্কুল ভর্তি বিজ্ঞপ্তি',NOW()),
(3,'রেজাল্ট','result','#7c3aed','fa-solid fa-square-poll-vertical',3,1,'পরীক্ষার রেজাল্ট | চাকরির ফলাফল','সকল চাকরি ও ভর্তি পরীক্ষার ফলাফল',NOW()),
(4,'প্রতিষ্ঠান','protisthan','#db2777','fa-solid fa-building-columns',4,1,'প্রতিষ্ঠান পরিচিতি','বিভিন্ন সরকারি ও বেসরকারি প্রতিষ্ঠানের তথ্য',NOW()),
(5,'নোটিশ','notice','#ea580c','fa-solid fa-bell',5,1,'নোটিশ বোর্ড | জরুরি বিজ্ঞপ্তি','গুরুত্বপূর্ণ নোটিশ ও বিজ্ঞপ্তি',NOW()),
(6,'স্কলারশিপ','scholarship','#16a34a','fa-solid fa-award',6,1,'স্কলারশিপ ২০২৬ | বৃত্তি বিজ্ঞপ্তি','দেশি ও বিদেশি স্কলারশিপের খবর',NOW()),
(7,'সরকারি চাকরি','sarkari-chakri','#0e7490','fa-solid fa-landmark',7,1,'সরকারি চাকরি ২০২৬ | Government Jobs BD','বাংলাদেশের সকল সরকারি চাকরির নিয়োগ বিজ্ঞপ্তি',NOW()),
(8,'বেসরকারি চাকরি','besarkari-chakri','#9333ea','fa-solid fa-building',8,1,'বেসরকারি চাকরি | Private Jobs','বেসরকারি প্রতিষ্ঠানের চাকরির খবর',NOW()),
(9,'ব্যাংক চাকরি','bank-chakri','#15803d','fa-solid fa-building-columns',9,1,'ব্যাংক চাকরি ২০২৬ | Bank Jobs BD','সকল ব্যাংকের নিয়োগ বিজ্ঞপ্তি',NOW()),
(10,'শিক্ষক নিয়োগ','shikkhok-niyog','#c2410c','fa-solid fa-chalkboard-teacher',10,1,'শিক্ষক নিয়োগ বিজ্ঞপ্তি ২০২৬','স্কুল কলেজ মাদ্রাসা শিক্ষক নিয়োগ',NOW());

-- Default admin: admin / Admin@123 (লগইন করে অবশ্যই পরিবর্তন করুন)
INSERT IGNORE INTO users (id, username, password_hash, name, role, created_at)
VALUES (1, 'admin', '$2y$10$V33F2iDdXZ5XRh85vM3bku9xTfeKxSOnZTjyXvVxhqV/eM4eZq6I6', 'প্রধান অ্যাডমিন', 'admin', NOW());

-- Default settings for CakriCircular
INSERT IGNORE INTO settings (setting_key, setting_value, updated_at) VALUES
('site_name','চাকরি সার্কুলার',NOW()),
('site_subtitle','CakriCircular.com',NOW()),
('site_slogan','বাংলাদেশের সরকারি-বেসরকারি চাকরির সার্কুলার, ভর্তি বিজ্ঞপ্তি ও পরীক্ষার ফলাফল — প্রতিদিন হালনাগাদ, এক জায়গায়।',NOW()),
('site_description','চাকরি সার্কুলার | আজকের চাকরির খবর | সরকারি ও বেসরকারি চাকরি, ব্যাংক চাকরি, শিক্ষক নিয়োগ, ভর্তি বিজ্ঞপ্তি ও ফলাফল প্রতিদিন হালনাগাদ।',NOW()),
('site_keywords','চাকরি সার্কুলার, আজকের চাকরির খবর, সরকারি চাকরি, বেসরকারি চাকরি, ব্যাংক চাকরি, শিক্ষক নিয়োগ, নিয়োগ বিজ্ঞপ্তি, CakriCircular, চাকরির খবর',NOW()),
('site_email','cakricircular.support@gmail.com',NOW()),
('site_location','Bangladesh',NOW()),
('site_footer','বাংলাদেশের সরকারি-বেসরকারি চাকরির সার্কুলার, ভর্তি বিজ্ঞপ্তি ও পরীক্ষার ফলাফল — প্রতিদিন হালনাগাদ, এক জায়গায়।',NOW()),
('site_copyright','© ২০২৬ চাকরি সার্কুলার। সর্বস্বত্ব সংরক্ষিত।',NOW()),
('site_logo','',NOW()),
('site_favicon','',NOW()),
('site_meta_image','',NOW()),
('og_default_image','',NOW()),
('items_per_page','20',NOW()),
('google_verification','',NOW()),
('facebook_url','',NOW()),
('twitter_url','',NOW()),
('telegram_url','',NOW()),
('youtube_url','',NOW()),
('contact_email','cakricircular.support@gmail.com',NOW()),
('contact_phone','',NOW()),
('contact_address','Bangladesh',NOW()),
('maintenance_mode','0',NOW()),
('maintenance_message','সাইটটি রক্ষণাবেক্ষণের জন্য সাময়িক বন্ধ আছে।',NOW()),
('home_seo_title','চাকরি সার্কুলার | আজকের চাকরির খবর | সরকারি ও বেসরকারি চাকরি',NOW()),
('home_seo_h1','চাকরি সার্কুলার ও আজকের চাকরির খবর',NOW()),
('home_meta_description','চাকরি সার্কুলার - বাংলাদেশের সবচেয়ে বিশ্বস্ত চাকরির পোর্টাল। সরকারি চাকরি, বেসরকারি চাকরি, ব্যাংক চাকরি, শিক্ষক নিয়োগ, ভর্তি বিজ্ঞপ্তি ও পরীক্ষার ফলাফল প্রতিদিন হালনাগাদ।',NOW()),
('home_meta_keywords','চাকরি সার্কুলার, আজকের চাকরির খবর, সরকারি চাকরি, বেসরকারি চাকরি, CakriCircular, চাকরির সার্কুলার',NOW()),
('enable_trending','1',NOW()),
('enable_notices','1',NOW());

SET FOREIGN_KEY_CHECKS=1;
