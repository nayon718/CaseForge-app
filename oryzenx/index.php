<?php
/**
 * Oryzenx — front controller.
 * Every clean URL is routed through this file (see .htaccess).
 */
declare(strict_types=1);

/* Fail loudly and clearly on ancient PHP instead of showing a blank page */
if (PHP_VERSION_ID < 80000) {
    header('Content-Type: text/html; charset=utf-8');
    exit('<p style="font-family:sans-serif;padding:40px;line-height:1.7">This site needs <b>PHP 8.0 or newer</b>. '
       . 'Your server runs <b>' . PHP_VERSION . '</b>.<br>Fix: hPanel → Advanced → PHP Configuration → select PHP 8.1/8.2.</p>');
}

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/contact.php';

if (cfg('debug')) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

$path = current_path();

/* ---------- Diagnostics ---------- */
if ($path === 'health' && cfg('health', true) && is_file(__DIR__ . '/includes/health.php')) {
    require_once __DIR__ . '/includes/health.php';
    health_page();
}

/* ---------- API ---------- */
if ($path === 'api/contact') {
    handle_contact();
}

/* ---------- CV download ---------- */
if ($path === 'cv') {
    $file = ORY_ROOT . '/assets/AH-NAYON-CV.pdf';
    if (is_file($file)) {
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="AH-NAYON-CV.pdf"');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit;
    }
    http_response_code(404);
    echo '<p style="font-family:sans-serif;padding:40px">CV file not uploaded yet. Place <code>assets/AH-NAYON-CV.pdf</code>.</p>';
    exit;
}

/* ---------- Database setup ---------- */
if ($path === 'setup') {
    $result = ($_SERVER['REQUEST_METHOD'] === 'POST') ? db_install() : null;
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><meta charset="utf-8"><title>Setup</title>'
       . '<body style="font-family:system-ui;background:#f6f8fc;color:#0f1a2b;padding:40px">'
       . '<div style="max-width:620px;margin:auto;background:#fff;padding:32px;border-radius:18px;box-shadow:0 18px 50px rgba(15,40,90,.1)">'
       . '<h1>⚙️ Oryzenx Setup</h1>';
    if ($result) {
        echo '<p style="color:' . ($result['ok'] ? '#16a34a' : '#dc2626') . '"><b>' . e($result['msg']) . '</b></p>';
    } else {
        echo '<p>Create <code>config.php</code> with your MySQL credentials, then create the tables.</p>'
           . '<form method="post"><button style="background:#2563eb;color:#fff;border:0;padding:12px 20px;border-radius:12px;font-weight:600;cursor:pointer">Create database tables</button></form>';
    }
    echo '<p><a href="' . e(url()) . '">← Back to site</a></p></div>';
    exit;
}

/* ---------- robots.txt ---------- */
if ($path === 'robots.txt') {
    header('Content-Type: text/plain; charset=utf-8');
    echo "User-agent: *\nAllow: /\nDisallow: /setup\nDisallow: /api/\n";
    exit;
}

/* ---------- sitemap.xml ---------- */
if ($path === 'sitemap.xml') {
    $host = ($_SERVER['HTTPS'] ?? '') ? 'https' : 'http';
    $root = $host . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . base_path();
    header('Content-Type: application/xml; charset=utf-8');
    echo '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    foreach ([''] as $p) {
        echo '<url><loc>' . e($root . $p) . '</loc><changefreq>monthly</changefreq><priority>1.0</priority></url>';
    }
    echo '</urlset>';
    exit;
}

/* ---------- Pages (single-page site + deep-link aliases) ---------- */
$aliases = ['', 'home', 'about', 'skills', 'services', 'projects', 'contact', 'location'];
if (!in_array($path, $aliases, true)) {
    http_response_code(404);
}

render(['head', 'header', 'hero', 'about', 'skills', 'contact', 'footer']);

/* Deep-link support: /about scrolls to #about without a reload-looking jump */
if ($path !== '' && $path !== 'home' && in_array($path, $aliases, true)) {
    echo '<script>addEventListener("DOMContentLoaded",function(){var el=document.getElementById(' . json_encode($path) . ');if(el)el.scrollIntoView();});</script>';
}
