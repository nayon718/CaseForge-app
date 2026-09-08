<?php
/**
 * Oryzenx — bootstrap
 * Loads config, site data and the tiny partial renderer.
 */
declare(strict_types=1);

define('ORY_ROOT', dirname(__DIR__));

date_default_timezone_set('Asia/Dhaka');
mb_internal_encoding('UTF-8');

/* Config: config.php overrides config.sample.php defaults */
$config = require ORY_ROOT . '/config.sample.php';
if (file_exists(ORY_ROOT . '/config.php')) {
    $config = array_replace_recursive($config, require ORY_ROOT . '/config.php');
}
$GLOBALS['ORY_CONFIG'] = $config;

/* Site content data */
$siteFile = ORY_ROOT . '/site.json';
if (!is_file($siteFile)) {
    header('Content-Type: text/html; charset=utf-8');
    exit('<p style="font-family:sans-serif;padding:40px">Missing <code>site.json</code>. Upload it next to <code>index.php</code>.</p>');
}
$GLOBALS['ORY_SITE'] = json_decode((string)file_get_contents($siteFile), true);
if (!is_array($GLOBALS['ORY_SITE'])) {
    header('Content-Type: text/html; charset=utf-8');
    exit('<p style="font-family:sans-serif;padding:40px">Could not read <code>site.json</code>: ' . htmlspecialchars(json_last_error_msg()) . '</p>');
}

function cfg(string $key, $default = null) {
    return $GLOBALS['ORY_CONFIG'][$key] ?? $default;
}
function site(string $key, $default = '') {
    return $GLOBALS['ORY_SITE'][$key] ?? $default;
}
function e($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Base path the app is installed under, e.g. "/" or "/oryzenx/" */
function base_path(): string {
    static $base = null;
    if ($base !== null) return $base;
    $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
    $base = rtrim($dir, '/') . '/';
    return $base;
}

/** Current clean path without base or query, e.g. "" or "cv" */
function current_path(): string {
    $uri  = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $path = substr($uri, strlen(base_path()));
    return trim((string)$path, '/');
}

function url(string $path = ''): string {
    return base_path() . ltrim($path, '/');
}

function is_ajax(): bool {
    return (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== '')
        || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
}

function json_out(array $data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/** WhatsApp deep link with pre-filled message */
function whatsapp_link(): string {
    return 'https://wa.me/' . site('whatsapp') . '?text=' . rawurlencode((string)site('whatsappText'));
}

/** Placeholder values shared by every partial */
function view_vars(): array {
    $s = $GLOBALS['ORY_SITE'];
    return [
        'base'         => base_path(),
        'brand'        => $s['brand'],
        'name'         => $s['name'],
        'nameFull'     => $s['nameFull'],
        'email'        => $s['email'],
        'phone'        => $s['phone'],
        'phoneIntl'    => $s['phoneIntl'],
        'location'     => $s['location'],
        'whatsappLink' => whatsapp_link(),
        'facebook'     => $s['social']['facebook'],
        'x'            => $s['social']['x'],
        'instagram'    => $s['social']['instagram'],
        'github'       => $s['social']['github'],
    ];
}

/** Render an HTML partial, replacing {{placeholders}} */
function partial(string $name): string {
    $file = ORY_ROOT . '/partials/' . basename($name) . '.html';
    if (!is_file($file)) return '';
    $html = (string)file_get_contents($file);
    foreach (view_vars() as $k => $v) {
        $html = str_replace('{{' . $k . '}}', (string)$v, $html);
    }
    return $html;
}

function render(array $partials): void {
    header('Content-Type: text/html; charset=utf-8');
    foreach ($partials as $p) echo partial($p);
}
