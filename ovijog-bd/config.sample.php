<?php
/**
 * ওভিজোগ বিডি — কনফিগারেশন
 * ------------------------------------------------------------
 * এই ফাইলটি `config.php` নামে কপি করে শেয়ার হোস্টিংয়ের ডাটাবেজ
 * তথ্য বসিয়ে নিন। এই ফাইলে কোনো গোপন তথ্য গিটে রাখা হবে না।
 */
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database_name');
define('DB_USER', 'your_database_user');
define('DB_PASS', 'your_database_password');

define('DB_CHARSET', 'utf8mb4');

/** সাইট রুট (ট্রেইলিং স্ল্যাশ ছাড়া) */
define('BASE_URL', '');

/** সাইটে ব্যবহার করা সুরক্ষিত সল্ট — changed এখানে নতুন স্ট্রিং দিন */
define('APP_KEY', 'change-this-random-salt-please');

/** optional: IP লোকেশনের জন্য ip-api-এর টোকেন/বিকল্প ব্যবহার করলে এখানে দিন */
define('GEO_API_KEY', '');
