<?php
/**
 * Oryzenx — /health diagnostics.
 * Shows exactly what is broken (PHP version, files, config, DB, rewrite).
 * Delete this file, or set 'health' => false in config.php, once the site works.
 */
declare(strict_types=1);

function health_page(): void
{
    $rows = [];
    $add = function (string $label, bool $ok, string $info) use (&$rows) {
        $rows[] = [$label, $ok, $info];
    };

    /* PHP */
    $add('PHP version', PHP_VERSION_ID >= 80000, PHP_VERSION . (PHP_VERSION_ID >= 80000 ? '' : ' — needs PHP 8.0+. Change it in hPanel → PHP Configuration.'));

    /* Extensions */
    $add('PDO MySQL driver', extension_loaded('pdo_mysql'), extension_loaded('pdo_mysql') ? 'loaded' : 'missing — enable pdo_mysql in hPanel → PHP Configuration → Extensions');
    $add('mbstring', extension_loaded('mbstring'), extension_loaded('mbstring') ? 'loaded' : 'missing');
    $add('JSON', extension_loaded('json'), extension_loaded('json') ? 'loaded' : 'missing');

    /* Files */
    foreach ([
        'config.php'                => 'your real DB credentials',
        'site.json'                 => 'site content',
        'partials/head.html'        => 'template',
        'partials/hero.html'        => 'template',
        'assets/css/style.css'      => 'stylesheet',
        'assets/js/app.js'          => 'javascript',
        'database/schema.sql'       => 'database schema',
    ] as $f => $why) {
        $p = ORY_ROOT . '/' . $f;
        $add('File: ' . $f, is_file($p), is_file($p) ? 'found (' . number_format((float)filesize($p)) . ' bytes)' : 'MISSING — ' . $why);
    }

    /* Writable storage */
    $st = ORY_ROOT . '/storage';
    $add('storage/ writable', is_dir($st) && is_writable($st), is_dir($st) ? (is_writable($st) ? 'ok' : 'not writable — chmod 755') : 'missing folder (create it)');

    /* Config values */
    $c = cfg('db', []);
    $add('Config loaded', file_exists(ORY_ROOT . '/config.php'), file_exists(ORY_ROOT . '/config.php') ? 'config.php in use' : 'using config.sample.php defaults — create config.php');
    $add('DB name set', !empty($c['name']), !empty($c['name']) ? e((string)$c['name']) : 'empty — contact form will not save to MySQL');
    $add('DB user set', !empty($c['user']), !empty($c['user']) ? e((string)$c['user']) : 'empty');
    $add('DB password set', !empty($c['pass']), !empty($c['pass']) ? '•••••• (' . strlen((string)$c['pass']) . ' chars)' : 'empty');
    $add('DB host', true, e((string)($c['host'] ?? '')) . ':' . e((string)($c['port'] ?? '')) . ' — on Hostinger use localhost');

    /* Live DB connection */
    $dbOk = false; $dbMsg = 'not attempted';
    if (!empty($c['name'])) {
        try {
            $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $c['host'] ?? 'localhost', $c['port'] ?? '3306', $c['name']);
            $pdo = new PDO($dsn, (string)($c['user'] ?? ''), (string)($c['pass'] ?? ''), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5]);
            $dbOk  = true;
            $ver   = $pdo->query('SELECT VERSION()')->fetchColumn();
            $tbls  = $pdo->query("SHOW TABLES LIKE 'messages'")->fetchAll();
            $dbMsg = 'connected — MySQL ' . $ver . ' · messages table: ' . ($tbls ? 'exists ✓' : 'NOT created yet → open /setup');
        } catch (Throwable $ex) {
            $dbMsg = $ex->getMessage();
        }
    }
    $add('MySQL connection', $dbOk, $dbMsg);

    /* Rewrite / clean URLs */
    $rw = function_exists('apache_get_modules') ? in_array('mod_rewrite', apache_get_modules(), true) : null;
    $add('mod_rewrite', $rw !== false, $rw === null ? 'cannot detect (LiteSpeed/CGI — usually fine)' : ($rw ? 'enabled' : 'NOT enabled — clean URLs will 404'));
    $add('.htaccess present', is_file(ORY_ROOT . '/.htaccess'), is_file(ORY_ROOT . '/.htaccess') ? 'found' : 'MISSING — upload it (hidden file! enable "show hidden files")');
    $add('Base path detected', true, e(base_path()) . ' — site root should be public_html');

    $fails = array_filter($rows, fn($r) => !$r[1]);

    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Oryzenx Health Check</title>';
    echo '<style>body{font-family:system-ui,sans-serif;background:#f6f8fc;color:#0f1a2b;margin:0;padding:24px}
    .box{max-width:820px;margin:auto;background:#fff;border-radius:18px;padding:24px;box-shadow:0 18px 50px rgba(15,40,90,.1)}
    h1{font-size:22px;margin:0 0 4px}p.sub{color:#5a6a85;margin:0 0 18px;font-size:14px}
    table{width:100%;border-collapse:collapse;font-size:14px}td{padding:10px 8px;border-bottom:1px solid #eef1f6;vertical-align:top}
    td:first-child{font-weight:600;white-space:nowrap}.ok{color:#16a34a;font-weight:700}.bad{color:#dc2626;font-weight:700}
    .info{color:#5a6a85;word-break:break-word}.banner{padding:14px 16px;border-radius:12px;margin-bottom:18px;font-weight:600}
    .g{background:#dcfce7;color:#166534}.r{background:#fee2e2;color:#991b1b}a{color:#2563eb}</style>';
    echo '<div class="box"><h1>⚙️ Oryzenx Health Check</h1><p class="sub">Delete <code>includes/health.php</code> when everything is green.</p>';
    echo $fails
        ? '<div class="banner r">' . count($fails) . ' problem(s) found — see the red rows below.</div>'
        : '<div class="banner g">All checks passed ✓ Your site should load at <a href="' . e(url()) . '">' . e(url()) . '</a></div>';
    echo '<table>';
    foreach ($rows as [$label, $ok, $info]) {
        echo '<tr><td>' . e($label) . '</td><td class="' . ($ok ? 'ok' : 'bad') . '">' . ($ok ? '✓' : '✕') . '</td><td class="info">' . $info . '</td></tr>';
    }
    echo '</table><p style="margin-top:18px"><a href="' . e(url()) . '">← Home</a> · <a href="' . e(url('setup')) . '">Run /setup</a></p></div>';
    exit;
}
