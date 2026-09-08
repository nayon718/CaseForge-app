<?php
/**
 * Oryzenx — STANDALONE checker.
 * Open it directly:  https://yourdomain.com/check.php
 * It does NOT use .htaccess, bootstrap.php or any other file,
 * so it still works when the main site returns 500.
 * DELETE THIS FILE once the site works.
 */
ini_set('display_errors', '1');
error_reporting(E_ALL);
header('Content-Type: text/html; charset=utf-8');

$rows = [];
function row($label, $ok, $info) { $GLOBALS['rows'][] = [$label, $ok, $info]; }

/* PHP version */
row('PHP version', PHP_VERSION_ID >= 80000,
    PHP_VERSION . (PHP_VERSION_ID >= 80000 ? ' ✓' : ' — TOO OLD. hPanel → Advanced → PHP Configuration → PHP 8.1 or 8.2'));

/* Extensions */
foreach (['pdo_mysql', 'mbstring', 'json'] as $ext) {
    row('Extension: ' . $ext, extension_loaded($ext),
        extension_loaded($ext) ? 'loaded' : 'MISSING — enable it in hPanel → PHP Configuration → Extensions');
}

/* Where am I? */
row('This file is at', true, __FILE__);
row('Document root', true, $_SERVER['DOCUMENT_ROOT'] ?? '(unknown)');
$inRoot = realpath(__DIR__) === realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
row('Installed in web root', $inRoot,
    $inRoot ? 'yes — correct' : 'files are in a SUBFOLDER. Move everything directly into public_html, or use the subfolder URL.');

/* Required files */
$need = ['index.php', 'site.json', 'config.php', '.htaccess',
         'includes/bootstrap.php', 'includes/db.php', 'includes/contact.php',
         'partials/head.html', 'partials/header.html', 'partials/hero.html',
         'partials/about.html', 'partials/skills.html', 'partials/contact.html', 'partials/footer.html',
         'assets/css/style.css', 'assets/js/app.js', 'database/schema.sql'];
foreach ($need as $f) {
    $p = __DIR__ . '/' . $f;
    $hint = ($f === '.htaccess') ? 'MISSING — it is a HIDDEN file. Turn on "show hidden files" in File Manager and upload it.' : 'MISSING — upload this file';
    row('File: ' . $f, is_file($p), is_file($p) ? number_format((float)filesize($p)) . ' bytes' : $hint);
}

/* storage */
$st = __DIR__ . '/storage';
row('storage/ folder', is_dir($st), is_dir($st) ? (is_writable($st) ? 'writable ✓' : 'exists but NOT writable — chmod 755') : 'missing — create a folder named storage');

/* site.json valid? */
if (is_file(__DIR__ . '/site.json')) {
    $j = json_decode((string)file_get_contents(__DIR__ . '/site.json'), true);
    row('site.json valid JSON', is_array($j), is_array($j) ? 'ok' : 'BROKEN: ' . json_last_error_msg());
}

/* config + database */
if (is_file(__DIR__ . '/config.php')) {
    $cfg = @include __DIR__ . '/config.php';
    $isArr = is_array($cfg);
    row('config.php returns array', $isArr, $isArr ? 'ok' : 'BROKEN — the file must start with <?php and use return [ ... ];');
    if ($isArr) {
        $d = $cfg['db'] ?? [];
        row('DB host', true, htmlspecialchars((string)($d['host'] ?? '')) . ' — on Hostinger use localhost');
        row('DB name', !empty($d['name']), htmlspecialchars((string)($d['name'] ?? '(empty)')));
        row('DB user', !empty($d['user']), htmlspecialchars((string)($d['user'] ?? '(empty)')));
        row('DB pass', !empty($d['pass']), !empty($d['pass']) ? '•••••• (' . strlen((string)$d['pass']) . ' chars)' : '(empty)');

        if (!empty($d['name']) && extension_loaded('pdo_mysql')) {
            try {
                $pdo = new PDO(
                    sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $d['host'] ?? 'localhost', $d['port'] ?? '3306', $d['name']),
                    (string)($d['user'] ?? ''), (string)($d['pass'] ?? ''),
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5]
                );
                $tbl = $pdo->query("SHOW TABLES LIKE 'messages'")->fetchAll();
                row('MySQL connection', true, 'CONNECTED ✓ MySQL ' . $pdo->query('SELECT VERSION()')->fetchColumn()
                    . ' · messages table: ' . ($tbl ? 'exists ✓' : 'not created → open /setup'));
            } catch (Throwable $ex) {
                row('MySQL connection', false, 'FAILED: ' . htmlspecialchars($ex->getMessage()));
            }
        }
    }
} else {
    row('config.php', false, 'MISSING — copy config.sample.php to config.php and add your DB details');
}

/* Rewrite */
$mods = function_exists('apache_get_modules') ? apache_get_modules() : null;
row('mod_rewrite', $mods === null ? true : in_array('mod_rewrite', $mods, true),
    $mods === null ? 'cannot detect (LiteSpeed/CGI — normally fine)' : (in_array('mod_rewrite', $mods, true) ? 'enabled ✓' : 'NOT enabled'));
row('Server software', true, htmlspecialchars($_SERVER['SERVER_SOFTWARE'] ?? 'unknown'));

/* Try booting the real app */
$bootMsg = 'not attempted';
$bootOk = false;
if (is_file(__DIR__ . '/index.php')) {
    try {
        ob_start();
        $_SERVER['REQUEST_URI'] = '/';
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        include __DIR__ . '/includes/bootstrap.php';
        ob_end_clean();
        $bootOk = true;
        $bootMsg = 'bootstrap.php loaded without errors ✓';
    } catch (Throwable $ex) {
        if (ob_get_level()) ob_end_clean();
        $bootMsg = 'FATAL: ' . htmlspecialchars($ex->getMessage()) . ' (' . htmlspecialchars(basename($ex->getFile())) . ':' . $ex->getLine() . ')';
    }
}
row('App bootstrap', $bootOk, $bootMsg);

$fails = array_filter($rows, function ($r) { return !$r[1]; });
?>
<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Oryzenx Check</title>
<style>
body{font-family:system-ui,-apple-system,sans-serif;background:#f6f8fc;color:#0f1a2b;margin:0;padding:20px;line-height:1.6}
.box{max-width:860px;margin:auto;background:#fff;border-radius:16px;padding:22px;box-shadow:0 18px 50px rgba(15,40,90,.1)}
h1{font-size:20px;margin:0 0 4px}p.sub{color:#5a6a85;font-size:13px;margin:0 0 16px}
table{width:100%;border-collapse:collapse;font-size:13px}
td{padding:9px 6px;border-bottom:1px solid #eef1f6;vertical-align:top;word-break:break-word}
td:first-child{font-weight:600;white-space:nowrap}
.ok{color:#16a34a;font-weight:700}.bad{color:#dc2626;font-weight:700}.info{color:#5a6a85}
.banner{padding:13px 15px;border-radius:11px;margin-bottom:16px;font-weight:600;font-size:14px}
.g{background:#dcfce7;color:#166534}.r{background:#fee2e2;color:#991b1b}
</style>
<div class="box">
<h1>⚙️ Oryzenx Standalone Check</h1>
<p class="sub">Works even when the site shows 500. <b>Delete check.php when done.</b></p>
<?php if ($fails): ?>
  <div class="banner r"><?= count($fails) ?> problem(s) found — see the red ✕ rows.</div>
<?php else: ?>
  <div class="banner g">All checks passed ✓ — if you still get 500, rename .htaccess to htaccess.txt and reload.</div>
<?php endif; ?>
<table>
<?php foreach ($rows as $r): ?>
  <tr><td><?= htmlspecialchars($r[0]) ?></td>
      <td class="<?= $r[1] ? 'ok' : 'bad' ?>"><?= $r[1] ? '✓' : '✕' ?></td>
      <td class="info"><?= $r[2] ?></td></tr>
<?php endforeach; ?>
</table>
</div>
