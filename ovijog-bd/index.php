<?php
/**
 * ওভিজোগ বিডি — ফ্রন্ট কন্ট্রোলার
 * সব পাবলিক ও অ্যাডমিন URL এই ফাইল দিয়ে চলে।
 */

require_once __DIR__ . '/includes/bootstrap.php';

/** প্রথমবার কনফিগ না থাকলে সেটআপে নিয়ে যাই */
if (!file_exists(__DIR__ . '/config.php') && current_path() !== '/setup' && !isset($_GET['debug'])) {
    header('Location: ' . url('setup'));
    exit;
}

/** সেটআপ (DB টেবিল তৈরি) */
if (current_path() === '/setup') {
    if (!file_exists(__DIR__ . '/config.php')) {
        $msg = 'একটি <code>config.php</code> ফাইল তৈরি করুন (উদাহরণ: <code>config.sample.php</code> কপি করে DB তথ্য দিন)।';
    } else {
        require_once __DIR__ . '/database/setup_handler.php';
    }
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="bn"><head><meta charset="utf-8"><title>সেটআপ</title></head><body style="font-family:sans-serif;background:#f8fafc;padding:30px;color:#0f172a"><div style="max-width:640px;margin:30px auto;background:#fff;border-radius:16px;padding:30px;box-shadow:0 10px 30px rgba(0,0,0,.08)">';
    echo '<h1 style="font-size:24px">⚙️ ওভিজোগ বিডি সেটআপ</h1>';
    if (isset($setupResult)) {
        echo '<h2 style="color:' . ($setupResult['ok'] ? '#16a34a' : '#dc2626') . '">' . ($setupResult['ok'] ? 'সফল' : 'ব্যর্থ') . '</h2><pre style="white-space:pre-wrap">' . e($setupResult['msg']) . '</pre>';
        if ($setupResult['ok']) echo '<p><a href="' . e(url('admin/login')) . '">অ্যাডমিন লগইন করুন</a></p>';
    } else {
        echo '<p>' . $msg . '</p>';
        echo '<form method="post"><button type="submit" style="background:#0f766e;color:#fff;border:0;border-radius:10px;padding:12px 18px;font-weight:700;cursor:pointer">ডাটাবেজ টেবিল তৈরি করুন</button></form>';
    }
    echo '</div></body></html>';
    exit;
}

/** স্থির SEO বন্ধু */
if (current_path() === '/robots.txt') {
    header('Content-Type: text/plain; charset=utf-8');
    echo "User-agent: *\nAllow: /\nDisallow: /admin/\nDisallow: /api/\nDisallow: /uploads/\nSitemap: " . site_url() . "/sitemap.xml\n";
    exit;
}
if (current_path() === '/sitemap.xml') {
    header('Content-Type: application/xml; charset=utf-8');
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    foreach (['', '/report', '/search', '/leaderboard', '/donation', '/privacy'] as $p) {
        echo '<url><loc>' . e(url($p)) . '</loc><lastmod>' . date('c') . '</lastmod><priority>' . ($p=== ''?'1.0':'0.8') . '</priority></url>' . "\n";
    }
    try {
        foreach (db_all('SELECT report_no FROM reports WHERE status="approved" ORDER BY id DESC LIMIT 1000') as $r) {
            echo '<url><loc>' . e(url('report/' . $r['report_no'])) . '</loc><lastmod>' . date('c') . '</lastmod><priority>0.7</priority></url>' . "\n";
        }
    } catch (Throwable $e) {}
    echo '</urlset>';
    exit;
}

/** ট্র্যাকিং শুধুমাত্র AJAX call */
if (is_ajax() && !empty($_GET['track'])) {
    track_visit((string) ($_GET['page'] ?? '/'));
    header('Content-Type: application/json; charset=utf-8');
    echo '{"ok":true}';
    exit;
}

try {
    $route = resolve_route();

    /* POST/AJAX action */
    if (($route['type'] ?? '') === 'public') {
        $action = handle_post_actions();
        if (is_array($action)) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($action, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
    }
    if (($route['type'] ?? '') === 'admin') {
        if ($route['page'] === 'login') {
            $action = handle_post_actions();
        } else {
            require_once __DIR__ . '/includes/actions/admin_actions.php';
            $action = admin_post_action($route['path']);
        }
        if (is_array($action)) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($action, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
    }

    /* রেন্ডার */
    if (($route['type'] ?? '') === 'admin') {
        if ($route['page'] === 'login') {
            if (is_admin_logged_in()) {
                header('Location: ' . url('admin/dashboard'));
                exit;
            }
            $render = ['type'=>'admin', 'page'=>'login', 'meta'=>default_meta(), 'content'=>admin_login_page(), 'admin_page'=>'login'];
        } else {
            $render = render_admin_page($route);
        }
        // অ্যাডমিন স্পা নয় — সম্পূর্ণ HTML
        require_once __DIR__ . '/includes/layout.php';
        echo render_html_document($render['meta'], $render['content'], true);
        exit;
    }

    $render = render_public_page($route);

    /* AJAX: শুধু main content ফেরত */
    if (is_ajax() && !isset($_GET['track'])) {
        track_visit($route['path'] ?? '/');
        header('Content-Type: text/html; charset=utf-8');
        echo $render['content'];
        exit;
    }

    track_visit($route['path'] ?? '/');
    require_once __DIR__ . '/includes/layout.php';
    echo render_html_document($render['meta'], $render['content'], false);
    exit;

} catch (Throwable $e) {
    if (is_ajax()) {
        header('Content-Type: text/html; charset=utf-8');
        $msg = 'সার্ভারে একটি সমস্যা হয়েছে।';
        if (!empty($_GET['debug'])) $msg .= ' <small>' . e($e->getMessage()) . '</small>';
        echo '<div class="results-empty"><i class="fa-solid fa-triangle-exclamation"></i><h3>' . $msg . '</h3></div>';
        exit;
    }
    http_response_code(500);
    require_once __DIR__ . '/includes/layout.php';
    echo render_html_document(['title' => 'সার্ভার ত্রুটি', 'description' => ''], '<section class="notfound-page"><div class="nf-icon"><i class="fa-solid fa-triangle-exclamation"></i></div><h1>সার্ভারে সমস্যা হয়েছে</h1><p>' . (empty($_GET['debug']) ? 'একটু পরে আবার চেষ্টা করুন।' : e($e->getMessage())) . '</p><a href="' . e(url('')) . '" data-link class="btn btn-primary">হোম</a></section>', false);
    exit;
}
