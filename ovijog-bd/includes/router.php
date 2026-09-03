<?php
/**
 * রাউটিং ও কন্টেন্ট ডিসপ্যাচার
 */

if (!function_exists('resolve_route')) {
    function resolve_route(): array
    {
        $path = current_path();
        $segments = array_values(array_filter(explode('/', $path)));
        $seg0 = $segments[0] ?? '';

        // Admin routes
        if ($seg0 === 'admin' || $seg0 === 'elola') {
            $sub = $segments[1] ?? 'dashboard';
            $id = $segments[2] ?? null;
            $adminRoutes = ['login', 'logout', 'dashboard', 'reports', 'report', 'payments', 'payment-methods', 'settings', 'gallery', 'analytics', 'change-password'];
            if (in_array($sub, $adminRoutes, true)) {
                return ['type' => 'admin', 'page' => $sub, 'id' => $id, 'path' => $path];
            }
            return ['type' => '404', 'path' => $path];
        }

        switch ($seg0) {
            case '':
            case 'home':
                return ['type' => 'public', 'page' => 'home', 'path' => $path];
            case 'report':
                return empty($segments[1]) ? ['type' => 'public', 'page' => 'report', 'path' => $path]
                    : ['type' => 'public', 'page' => 'report-detail', 'report_no' => $segments[1], 'path' => $path];
            case 'reports':
                return ['type' => 'public', 'page' => 'reports', 'path' => $path];
            case 'search':
            case 'khujun':
                return ['type' => 'public', 'page' => 'search', 'path' => $path];
            case 'leaderboard':
            case 'leader-board':
                return ['type' => 'public', 'page' => 'leaderboard', 'path' => $path];
            case 'donation':
            case 'donate':
                return ['type' => 'public', 'page' => 'donation', 'path' => $path];
            case 'policy':
            case 'privacy':
            case 'privacy-policy':
                return ['type' => 'public', 'page' => 'privacy', 'path' => $path];
            case 'api':
                if (($segments[1] ?? '') === 'report-submit') return ['type' => 'public', 'page' => 'report', 'path' => $path];
                if (($segments[1] ?? '') === 'payment-submit') return ['type' => 'public', 'page' => 'donation', 'path' => $path];
                if (($segments[1] ?? '') === 'search') return ['type' => 'public', 'page' => 'search', 'path' => $path];
                return ['type' => '404', 'path' => $path];
            case 'sitemap.xml':
                return ['type' => 'sitemap', 'page' => 'sitemap', 'path' => $path];
            case 'robots.txt':
                return ['type' => 'robots', 'page' => 'robots', 'path' => $path];
            default:
                return ['type' => '404', 'path' => $path];
        }
    }
}

if (!function_exists('handle_post_actions')) {
    /**
     * AJAX POST endpoint গুলো এখানে হ্যান্ডেল হয়।
     * @return array{ok:bool,message?:string,data?:array}|null  null মানে সাধারণ পেজ রেন্ডার।
     */
    function handle_post_actions(): ?array
    {
        $path = current_path();
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            return null;
        }

        // রিপোর্ট জমা
        if (in_array($path, ['/report', '/api/report-submit', '/report-submit'], true)) {
            require_once __DIR__ . '/actions/report_submit.php';
            return report_submit_action();
        }

        // সিরিয়ালি রিপোর্ট খুঁজুন
        if (in_array($path, ['/search', '/khujun', '/api/search'], true)) {
            require_once __DIR__ . '/actions/search.php';
            return search_action();
        }

        // অনুদান তথ্য জমা
        if (in_array($path, ['/donation', '/api/payment-submit', '/payment-submit'], true)) {
            require_once __DIR__ . '/actions/payment_submit.php';
            return payment_submit_action();
        }

        // অ্যাডমিন লগইন
        if ($path === '/admin/login' || $path === '/elola/login') {
            require_once __DIR__ . '/actions/admin_login.php';
            return admin_login_action();
        }

        return null;
    }
}

if (!function_exists('render_public_page')) {
    function render_public_page(array $route): array
    {
        require_once __DIR__ . '/pages/public.php';

        $page = $route['page'] ?? 'home';
        $meta = default_meta();
        $content = '';

        switch ($page) {
            case 'home':
                $content = page_home();
                $meta = ['title' => setting('site_name') . ' — ' . setting('site_subtitle'), 'description' => setting('site_description'), 'keywords' => setting('site_keywords')];
                break;
            case 'report':
                $content = page_report();
                $meta = ['title' => 'রিপোর্ট জমা দিন — ' . setting('site_name'), 'description' => 'অভিযোগ জমা দিন; আপনার তথ্য সম্পূর্ণ গোপন রাখা হবে।'];
                break;
            case 'reports':
                $content = page_reports();
                $meta = ['title' => 'অনুমোদিত রিপোর্টসমূহ — ' . setting('site_name'), 'description' => 'সকল যাচাইকৃত রিপোর্ট।'];
                break;
            case 'search':
            case 'khujun':
                $content = page_search();
                $meta = ['title' => 'রিপোর্ট খুঁজুন — ' . setting('site_name'), 'description' => 'নাম, আইডি, জেলা বা অপরাধের ধরন দিয়ে রিপোর্ট খুঁজুন।'];
                break;
            case 'leaderboard':
                $content = page_leaderboard();
                $meta = ['title' => 'লিডার বোর্ড — ' . setting('site_name'), 'description' => 'টপ ক্যাটাগরি ও শীর্ষ রিপোর্ট লিডার বোর্ড।'];
                break;
            case 'donation':
                $content = page_donation();
                $meta = ['title' => 'অনুদান — ' . setting('site_name'), 'description' => 'আপনার সহযোগিতা আমাদের এগিয়ে নিতে সহায়তা করে।'];
                break;
            case 'privacy':
                $content = page_privacy();
                $meta = ['title' => 'গোপনীয়তা নীতি — ' . setting('site_name'), 'description' => 'বাংলাদেশ নাগরিক গোপনীয়তা নীতি।'];
                break;
            case 'report-detail':
                $content = page_report_detail((string) ($route['report_no'] ?? ''));
                $meta = report_detail_meta((string) ($route['report_no'] ?? ''));
                break;
            default:
                $content = page_404();
                $meta = ['title' => '৪০৪ — পেজ পাওয়া যায়নি'];
        }

        if ($page === '404') http_response_code(404);
        return ['type' => 'public', 'page' => $page, 'meta' => $meta, 'content' => $content];
    }
}

if (!function_exists('render_admin_page')) {
    function render_admin_page(array $route): array
    {
        require_once __DIR__ . '/pages/admin.php';
        $page = $route['page'] ?? 'dashboard';
        $id = $route['id'] ?? null;
        $content = admin_page($page, $id);
        $meta = ['title' => 'অ্যাডমিন | ' . setting('site_name'), 'description' => 'অ্যাডমিন প্যানেল'];
        $pageName = $page;
        return ['type' => 'admin', 'page' => $page, 'meta' => $meta, 'content' => $content, 'admin_page' => $pageName];
    }
}

if (!function_exists('default_meta')) {
    function default_meta(): array
    {
        return [
            'title' => setting('site_name') . ' — ' . setting('site_subtitle'),
            'description' => setting('site_description'),
            'keywords' => setting('site_keywords'),
        ];
    }
}
