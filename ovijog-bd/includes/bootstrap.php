<?php
/**
 * ওভিজোগ বিডি — মূল বুটস্ট্র্যাপ
 * সব পাতা এই ফাইল দিয়ে চালু হয়।
 */

declare(strict_types=1);

mb_internal_encoding('UTF-8');
date_default_timezone_set('Asia/Dhaka');

/* কনফিগ লোড */
if (file_exists(__DIR__ . '/../config.php')) {
    require_once __DIR__ . '/../config.php';
} elseif (file_exists(__DIR__ . '/../config.sample.php')) {
    require_once __DIR__ . '/../config.sample.php';
} else {
    require_once __DIR__ . '/../config.sample.php';
}

/* নিরাপত্তামূলক ডিফল্ট */
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');
if (!defined('BASE_URL'))    define('BASE_URL', '');
if (!defined('APP_KEY'))     define('APP_KEY', 'ovijog-bd');
if (!defined('GEO_API_KEY')) define('GEO_API_KEY', '');

/* পাথ */
define('ROOT_PATH', dirname(__DIR__));
define('UPLOAD_PATH', ROOT_PATH . '/uploads');
define('UPLOAD_URL', BASE_URL . '/uploads');

/* সেশন */
if (session_status() === PHP_SESSION_NONE) {
    session_name('ovijogsid');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

/* ক্লায়েন্ট আইপি */
if (!function_exists('client_ip')) {
    function client_ip(): string
    {
        $keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
        foreach ($keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = trim(explode(',', $_SERVER[$key])[0]);
                return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
            }
        }
        return '0.0.0.0';
    }
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';

/* ডাটাবেজ সংযোগ পরীক্ষা */
if (!defined('SKIP_DB_BOOT') && function_exists('db') && file_exists(__DIR__ . '/../config.php')) {
    try {
        $pdo = db();
        $pdo->query('SELECT 1');
    } catch (Throwable $e) {
        $message = 'ডাটাবেজ সংযোগ করা যায়নি। <code>config.php</code> ফাইলে সঠিক DB_USER, DB_PASS, DB_NAME বসিয়ে নিন।';
        if (!empty($_GET['debug'])) {
            $message .= '<br>' . htmlspecialchars($e->getMessage());
        }
        http_response_code(503);
        exit('<!DOCTYPE html><html lang="bn"><head><meta charset="utf-8"><title>ডাটাবেজ সংযোগ প্রয়োজন</title></head><body style="font-family:sans-serif;padding:40px;background:#f8fafc;color:#0f172a;"><h2>🔧 ডাটাবেজ সেটআপ প্রয়োজন</h2><p>' . $message . '</p><p>এরপর <a href="' . (BASE_URL ?: '') . '/setup">/setup</a> খুলে টেবিল তৈরি করুন।</p></body></html>');
    }
}

require_once __DIR__ . '/router.php';
