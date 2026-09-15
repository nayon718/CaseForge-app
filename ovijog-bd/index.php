<?php
/**
 * চাকরি সার্কুলার — ফ্রন্ট কন্ট্রোলার (Clean URL, SEO, SPA)
 */
require_once __DIR__ . '/includes/bootstrap.php';

if (!file_exists(__DIR__ . '/config.php') && current_path() !== '/setup' && !isset($_GET['debug'])) {
    header('Location: ' . url('setup'));
    exit;
}

if (current_path() === '/setup') {
    if (!file_exists(__DIR__ . '/config.php')) {
        $msg = 'একটি <code>config.php</code> ফাইল তৈরি করুন (উদাহরণ: <code>config.sample.php</code> কপি করে DB তথ্য দিন)।';
    } else {
        require_once __DIR__ . '/database/setup_handler.php';
    }
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="bn"><head><meta charset="utf-8"><title>সেটআপ</title></head><body style="font-family:sans-serif;background:#f8fafc;padding:30px;color:#0f172a"><div style="max-width:640px;margin:30px auto;background:#fff;border-radius:16px;padding:30px;box-shadow:0 10px 30px rgba(0,0,0,.08)">';
    echo '<h1 style="font-size:24px">⚙️ চাকরি সার্কুলার সেটআপ</h1>';
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

// robots.txt
if (current_path() === '/robots.txt') {
    header('Content-Type: text/plain; charset=utf-8');
    echo "User-agent: *\nAllow: /\nDisallow: /admin/\nDisallow: /v2admin/\nDisallow: /api/\nDisallow: /uploads/\n";
    echo "Sitemap: " . site_url() . "/sitemap.xml\n";
    exit;
}

// sitemap.xml
if (current_path() === '/sitemap.xml') {
    header('Content-Type: application/xml; charset=utf-8');
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    $pages = [
        '' => '1.0',
        '/jobs' => '0.9',
        '/trending' => '0.8',
        '/search' => '0.7',
        '/notices' => '0.7',
        '/about' => '0.5',
        '/privacy' => '0.5',
        '/contact' => '0.5',
    ];
    foreach ($pages as $p => $pri) {
        echo '<url><loc>' . e(site_url() . $p) . '</loc><lastmod>' . date('c') . '</lastmod><priority>' . $pri . '</priority></url>' . "\n";
    }
    try {
        foreach (db_all('SELECT slug FROM categories WHERE status=1 ORDER BY sort_order ASC') as $c) {
            echo '<url><loc>' . e(site_url() . '/category/' . $c['slug']) . '</loc><lastmod>' . date('c') . '</lastmod><priority>0.8</priority></url>' . "\n";
        }
        foreach (db_all('SELECT slug, updated_at FROM jobs WHERE status="published" ORDER BY id DESC LIMIT 5000') as $j) {
            $last = !empty($j['updated_at']) ? date('c', strtotime($j['updated_at'])) : date('c');
            echo '<url><loc>' . e(site_url() . '/job/' . $j['slug']) . '</loc><lastmod>' . $last . '</lastmod><priority>0.7</priority></url>' . "\n";
        }
        foreach (db_all('SELECT id FROM notices ORDER BY id DESC LIMIT 500') as $n) {
            echo '<url><loc>' . e(site_url() . '/notice/' . $n['id']) . '</loc><lastmod>' . date('c') . '</lastmod><priority>0.6</priority></url>' . "\n";
        }
    } catch (Throwable $e) {}
    echo '</urlset>';
    exit;
}

// Tracking AJAX
if (is_ajax() && !empty($_GET['track'])) {
    track_visit((string) ($_GET['page'] ?? '/'));
    header('Content-Type: application/json; charset=utf-8');
    echo '{"ok":true}';
    exit;
}

try {
    $route = resolve_route();

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
            if (is_array($action)) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode($action, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }
        } else {
            // Check GET actions for delete
            if (!empty($_GET['delete']) && is_numeric($_GET['delete'])) {
                require_admin();
                $id = (int) $_GET['delete'];
                if (strpos($route['path'], '/admin/banners') === 0) {
                    $b = db_fetch('SELECT * FROM banners WHERE id=?', [$id]);
                    if ($b) {
                        $f = ROOT_PATH . '/' . $b['image'];
                        if (is_file($f)) @unlink($f);
                        db_query('DELETE FROM banners WHERE id=?', [$id]);
                    }
                    header('Location: ' . url('admin/banners'));
                    exit;
                }
                if (strpos($route['path'], '/admin/notices') === 0) {
                    db_query('DELETE FROM notices WHERE id=?', [$id]);
                    header('Location: ' . url('admin/notices'));
                    exit;
                }
                if (strpos($route['path'], '/admin/gallery') === 0) {
                    $g = db_fetch('SELECT * FROM gallery WHERE id=?', [$id]);
                    if ($g) {
                        $f = ROOT_PATH . '/' . $g['file'];
                        if (is_file($f)) @unlink($f);
                        db_query('DELETE FROM gallery WHERE id=?', [$id]);
                    }
                    header('Location: ' . url('admin/gallery'));
                    exit;
                }
            }
            if (!empty($_GET['resolve']) && is_numeric($_GET['resolve'])) {
                require_admin();
                db_update('wrong_reports', ['status' => 'resolved'], 'id=?', [(int) $_GET['resolve']]);
                header('Location: ' . url('admin/reports'));
                exit;
            }
            require_once __DIR__ . '/includes/actions/admin_actions.php';
            $action = admin_post_action($route['path']);
            if (is_array($action)) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode($action, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }
        }
    }

    if (($route['type'] ?? '') === 'admin') {
        if ($route['page'] === 'login') {
            if (is_admin_logged_in()) {
                header('Location: ' . url('admin/dashboard'));
                exit;
            }
            $render = ['type' => 'admin', 'page' => 'login', 'meta' => default_meta(), 'content' => admin_login_page(), 'admin_page' => 'login'];
        } else {
            $render = render_admin_page($route);
        }
        require_once __DIR__ . '/includes/layout.php';
        echo render_html_document($render['meta'], $render['content'], true);
        exit;
    }

    $render = render_public_page($route);

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
        echo '<div class="empty-state"><i class="fa-solid fa-triangle-exclamation"></i><h3>' . $msg . '</h3></div>';
        exit;
    }
    http_response_code(500);
    require_once __DIR__ . '/includes/layout.php';
    echo render_html_document(['title' => 'সার্ভার ত্রুটি'], '<section class="notfound-page"><div class="nf-icon"><i class="fa-solid fa-triangle-exclamation"></i></div><h1>সার্ভারে সমস্যা হয়েছে</h1><p>' . (empty($_GET['debug']) ? 'একটু পরে আবার চেষ্টা করুন।' : e($e->getMessage())) . '</p><a href="' . e(url('')) . '" data-link class="btn btn-primary">হোম</a></section>', false);
    exit;
}
