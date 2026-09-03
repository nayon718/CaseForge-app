-- ওভিজোগ বিডি — MySQL schema (utf8mb4)

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  username VARCHAR(100) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  name VARCHAR(255) NOT NULL DEFAULT '',
  role VARCHAR(50) NOT NULL DEFAULT 'admin',
  last_login_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS settings (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  setting_key VARCHAR(190) NOT NULL,
  setting_value TEXT NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_setting_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS categories (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  color VARCHAR(20) NOT NULL DEFAULT '#10b981',
  icon VARCHAR(100) NOT NULL DEFAULT 'fa-solid fa-folder',
  sort_order INT NOT NULL DEFAULT 0,
  status TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS districts (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name_bn VARCHAR(255) NOT NULL,
  name_en VARCHAR(255) NOT NULL,
  PRIMARY KEY (id),
  KEY idx_name (name_bn, name_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS reports (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  report_no VARCHAR(40) NOT NULL,
  category_id INT UNSIGNED NOT NULL DEFAULT 0,
  name VARCHAR(255) NOT NULL,
  crime_type VARCHAR(255) NOT NULL,
  district VARCHAR(255) NOT NULL DEFAULT '',
  district_name VARCHAR(255) NOT NULL DEFAULT '',
  address VARCHAR(500) NOT NULL DEFAULT '',
  mobile VARCHAR(30) NOT NULL DEFAULT '',
  details TEXT NOT NULL,
  amount DECIMAL(16,2) NOT NULL DEFAULT 0,
  evidence_link VARCHAR(500) NOT NULL DEFAULT '',
  status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  report_hash VARCHAR(128) NOT NULL DEFAULT '',
  ip_address VARCHAR(64) NOT NULL DEFAULT '',
  user_agent VARCHAR(500) NOT NULL DEFAULT '',
  created_at DATETIME NOT NULL,
  updated_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_report_no (report_no),
  KEY idx_status (status),
  KEY idx_category (category_id),
  KEY idx_name (name),
  KEY idx_district (district_name),
  KEY idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS report_images (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  report_id INT UNSIGNED NOT NULL,
  file VARCHAR(500) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_report (report_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payment_methods (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  type VARCHAR(50) NOT NULL DEFAULT 'mobile',
  address TEXT NOT NULL,
  instructions TEXT NOT NULL,
  logo VARCHAR(500) NOT NULL DEFAULT '',
  color VARCHAR(20) NOT NULL DEFAULT '#10b981',
  status TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payments (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  payment_method_id INT UNSIGNED NOT NULL DEFAULT 0,
  user_name VARCHAR(255) NOT NULL DEFAULT '',
  amount DECIMAL(16,2) NOT NULL DEFAULT 0,
  trx_id VARCHAR(255) NOT NULL DEFAULT '',
  note TEXT NOT NULL,
  screenshot VARCHAR(500) NOT NULL DEFAULT '',
  status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  ip_address VARCHAR(64) NOT NULL DEFAULT '',
  user_agent VARCHAR(500) NOT NULL DEFAULT '',
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_status (status),
  KEY idx_method (payment_method_id),
  KEY idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS gallery (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title VARCHAR(255) NOT NULL DEFAULT '',
  file VARCHAR(500) NOT NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS analytics (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  visitor_id VARCHAR(64) NOT NULL,
  ip_address VARCHAR(64) NOT NULL DEFAULT '',
  user_agent VARCHAR(500) NOT NULL DEFAULT '',
  browser VARCHAR(100) NOT NULL DEFAULT '',
  os VARCHAR(100) NOT NULL DEFAULT '',
  device VARCHAR(50) NOT NULL DEFAULT '',
  location VARCHAR(255) NOT NULL DEFAULT '',
  page VARCHAR(255) NOT NULL,
  page_title VARCHAR(255) NOT NULL DEFAULT '',
  visit_date DATE NOT NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_visitor (visitor_id),
  KEY idx_page (page),
  KEY idx_date (visit_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- District seed (ছোট সেট; সেটিং থেকে পূর্ণ তালিকা বাড়ানো যায়)
INSERT IGNORE INTO districts (id, name_bn, name_en) VALUES
(1,'ঢাকা','Dhaka'),(2,'চট্টগ্রাম','Chattogram'),(3,'সিলেট','Sylhet'),(4,'রাজশাহী','Rajshahi'),(5,'খুলনা','Khulna'),(6,'বরিশাল','Barishal'),(7,'রংপুর','Rangpur'),(8,'ময়মনসিংহ','Mymensingh'),
(9,'গাজীপুর','Gazipur'),(10,'নারায়ণগঞ্জ','Narayanganj'),(11,'কুমিল্লা','Cumilla'),(12,'যশোর','Jashore'),(13,'কক্সবাজার','Cox''s Bazar'),(14,'নোয়াখালী','Noakhali'),(15,'ফেনী','Feni'),(16,'টাঙ্গাইল','Tangail');

-- Category seed
INSERT IGNORE INTO categories (id, name, color, icon, sort_order, status, created_at) VALUES
(1,'হয়রানি','#ef4444','fa-solid fa-person-rays',1,1,NOW()),
(2,'চাদাবাজি','#f59e0b','fa-solid fa-hand-holding-dollar',2,1,NOW()),
(3,'খুন','#7f1d1d','fa-solid fa-skull-crossbones',3,1,NOW()),
(4,'সন্ত্রাস','#b91c1c','fa-solid fa-fire',4,1,NOW()),
(5,'দুর্নীতি','#0ea5e9','fa-solid fa-handcuffs',5,1,NOW()),
(6,'জালিয়াতি','#8b5cf6','fa-solid fa-user-secret',6,1,NOW()),
(7,'ঘুষ','#d97706','fa-solid fa-money-bill-wave',7,1,NOW()),
(8,'আইন লঙ্ঘন','#dc2626','fa-solid fa-scale-balanced',8,1,NOW()),
(9,'ক্ষমতার অপব্যবহার','#475569','fa-solid fa-crown',9,1,NOW()),
(10,'অন্যান্য','#64748b','fa-solid fa-circle-exclamation',10,1,NOW());

-- Default admin: admin / Admin@123 (লগইন করে অবশ্যই পরিবর্তন করুন)
INSERT IGNORE INTO users (username, password_hash, name, role, created_at)
VALUES ('admin', '$2y$10$V33F2iDdXZ5XRh85vM3bku9xTfeKxSOnZTjyXvVxhqV/eM4eZq6I6', 'অ্যাডমিন', 'admin', NOW());

-- Payment methods seed
INSERT IGNORE INTO payment_methods (name, type, address, instructions, logo, color, status, sort_order, created_at) VALUES
('বিকাশ','mobile','01700000000','বিকাশ পেমেন্ট ম্যানুয়ালি সম্পন্ন করে TRX ID দিন।','','#e2136e',1,1,NOW()),
('নগদ','mobile','01700000000','নগদ অ্যাপ দিয়ে পেমেন্ট করে TRX ID দিন।','','#f6921e',1,2,NOW()),
('রকেট','mobile','01700000000','রকেট পেমেন্ট করে TRX ID দিন।','','#8c3494',1,3,NOW()),
('বিন্যান্স','crypto','123456789','বিন্যান্স পেমেন্ট করে TXID দিন।','','#f0b90b',1,4,NOW()),
('USDT','crypto','TRC20-address','USDT (TRC20) পাঠিয়ে TXID দিন।','','#26a17b',1,5,NOW()),
('ব্যাংক','bank','Bank Name, Account No','ব্যাংক ট্রান্সফার করে পিআর নম্বর দিন।','','#2563eb',1,6,NOW());

-- Default settings (আপনার `config.sample.php` বা binary মূল্য রেখে গেছে? নিচে Seed করে দিচ্ছি)
INSERT IGNORE INTO settings (setting_key, setting_value, updated_at) VALUES
('site_name','বাংলাদেশ নাগরিক',NOW()),
('site_subtitle','Ovijog BD · স্বচ্ছতার প্ল্যাটফর্ম',NOW()),
('site_email','ovijogbd.support.com',NOW()),
('site_location','Bangladesh',NOW()),
('site_footer','নাগরিক স্বার্থ রক্ষা ও অনিয়ম প্রতিরোধে জড়িত ব্যক্তিদের শনাক্তকরণের জন্য এই সিস্টেমটি উন্নয়ন করা হয়েছে।',NOW()),
('site_slogan','"ডিজিটাল বাংলাদেশ, স্বচ্ছ বাংলাদেশ"',NOW()),
('site_description','বাংলাদেশ নাগরিক — অনুমোদিত ও যাচাইকৃত অসংগতি/অপরাধের রিপোর্ট, স্বচ্ছতার প্ল্যাটফর্ম।',NOW()),
('site_keywords','বাংলাদেশ নাগরিক, অভিযোগ, চাদাবাজি, দুর্নীতি, রিপোর্ট, ওভিজোগ বিডি',NOW()),
('site_meta_image','',NOW()),
('site_logo','',NOW()),
('site_favicon','',NOW()),
'items_per_page','30',NOW()),
('show_pagination','30',NOW()),
('facebook_url','',NOW()),
('twitter_url','',NOW()),
('instagram_url','',NOW()),
('youtube_url','',NOW()),
('tiktok_url','',NOW()),
('whatsapp_number','',NOW()),
('contact_address','Bangladesh',NOW()),
('contact_email','ovijogbd.support.com',NOW()),
('contact_phone','',NOW()),
('privacy_approved_text','আমি নিশ্চিত করছি যে দেওয়া তথ্য সঠিক এবং মিথ্যা তথ্য দেওয়ার জন্য আমি আইনগতভাবে দায়ী থাকব। বাংলাদেশ নাগরিক গোপনীয়তা নীতি পড়েছি এবং তা মেনে চলতে সম্মত আছি।',NOW());
