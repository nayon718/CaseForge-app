<?php
/**
 * সাধারণ হেল্পার ফাংশন
 */

if (!function_exists('e')) {
    function e($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('setting')) {
    function setting(string $key, $default = ''): string
    {
        static $cache = null;
        if ($cache === null) {
            $cache = [];
            try {
                foreach (db_all('SELECT setting_key, setting_value FROM settings') as $row) {
                    $cache[$row['setting_key']] = $row['setting_value'];
                }
            } catch (Throwable $e) {
                $cache = [];
            }
        }
        return isset($cache[$key]) && $cache[$key] !== '' ? $cache[$key] : (string) $default;
    }
}

if (!function_exists('save_setting')) {
    function save_setting(string $key, $value): void
    {
        if (db_count('settings', 'setting_key = ?', [$key]) > 0) {
            db_update('settings', ['setting_value' => (string) $value], 'setting_key = ?', [$key]);
        } else {
            db_insert('settings', ['setting_key' => $key, 'setting_value' => (string) $value]);
        }
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
    }
}

if (!function_exists('verify_csrf')) {
    function verify_csrf(): bool
    {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        return hash_equals(csrf_token(), (string) $token);
    }
}

if (!function_exists('require_csrf')) {
    function require_csrf(): ?array
    {
        if (!verify_csrf()) {
            http_response_code(419);
            return ['ok' => false, 'message' => 'সেশন মেয়াদ শেষ হয়েছে। পেজ রিফ্রেশ করে আবার চেষ্টা করুন।'];
        }
        return null;
    }
}

if (!function_exists('asset_url')) {
    function asset_url(string $path): string
    {
        return rtrim(BASE_URL, '/') . '/assets/' . ltrim($path, '/');
    }
}

if (!function_exists('uploads_url')) {
    function uploads_url(string $path): string
    {
        return rtrim(BASE_URL, '/') . '/uploads/' . ltrim($path, '/');
    }
}

if (!function_exists('url')) {
    function url(string $path = '/'): string
    {
        $base = rtrim(BASE_URL, '/');
        return $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('site_url')) {
    function site_url(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $scheme . '://' . $host . rtrim(BASE_URL, '/');
    }
}

if (!function_exists('current_path')) {
    function current_path(): string
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
        $base = parse_url(BASE_URL ?: '', PHP_URL_PATH) ?: '';
        if ($base && strpos($uri, $base) === 0) {
            $uri = substr($uri, strlen($base));
        }
        return '/' . ltrim($uri, '/');
    }
}

if (!function_exists('is_ajax')) {
    function is_ajax(): bool
    {
        $xreq = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? ($_SERVER['HTTP_X_REQUESTER'] ?? '');
        return !empty($_GET['_ajax']) || strtolower($xreq) === 'xmlhttprequest' || strtolower($xreq) === 'ajax';
    }
}

if (!function_exists('client_info')) {
    function client_info(): array
    {
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $ip = client_ip();

        $browser = 'Unknown';
        $os = 'Unknown';
        $deviceStr = 'Desktop';
        foreach ([
            'Edg/' => 'Microsoft Edge',
            'OPR/' => 'Opera',
            'Chrome/' => 'Google Chrome',
            'Firefox/' => 'Mozilla Firefox',
            'Safari' => 'Safari',
        ] as $key => $name) {
            if (stripos($ua, $key) !== false) { $browser = $name; break; }
        }
        foreach ([
            'Windows NT' => 'Windows',
            'Android' => 'Android',
            'iPhone' => 'iOS',
            'iPad' => 'iOS',
            'Mac OS X' => 'macOS',
            'Linux' => 'Linux',
        ] as $key => $name) {
            if (stripos($ua, $key) !== false) { $os = $name; break; }
        }
        if (preg_match('/Mobile|Android|iPhone|iPad/', $ua)) {
            $deviceStr = 'Mobile';
        }
        return [
            'ip' => $ip,
            'user_agent' => $ua,
            'browser' => $browser,
            'os' => $os,
            'device' => $deviceStr,
            'network' => class_exists('GeoIp') ? 'Localhost' : (preg_match('/Wifi|Wi-Fi/', $ua) ? 'WiFi' : 'Internet'),
        ];
    }
}

if (!function_exists('track_visit')) {
    function track_visit(string $page): void
    {
        try {
            $info = client_info();
            if (empty($_COOKIE['ovj_visitor'])) {
                $vid = bin2hex(random_bytes(8));
                setcookie('ovj_visitor', $vid, [
                    'expires' => time() + 86400 * 365,
                    'path' => '/',
                    'secure' => !empty($_SERVER['HTTPS']),
                    'httponly' => true,
                    'samesite' => 'Lax',
                ]);
                $_COOKIE['ovj_visitor'] = $vid;
            }
            $vid = $_COOKIE['ovj_visitor'] ?? 'anon';
            $date = date('Y-m-d');
            $exists = db_fetch(
                'SELECT id FROM analytics WHERE visitor_id=? AND page=? AND visit_date=? LIMIT 1',
                [$vid, $page, $date]
            );
            if (!$exists) {
                db_insert('analytics', [
                    'visitor_id' => $vid,
                    'ip_address' => $info['ip'],
                    'user_agent' => $info['user_agent'],
                    'browser' => $info['browser'],
                    'os' => $info['os'],
                    'device' => $info['device'],
                    'location' => '',
                    'page' => $page,
                    'page_title' => setting('site_name', 'ওভিজোগ বিডি'),
                    'visit_date' => $date,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }
        } catch (Throwable $e) {
            // বিশ্লেষণ ট্র্যাকিং ব্যর্থ হলে সাইট চালু থাকবে
        }
    }
}

if (!function_exists('time_ago')) {
    function time_ago(string $datetime): string
    {
        $time = strtotime($datetime);
        if (!$time) return $datetime;
        $diff = time() - $time;
        if ($diff < 60) return 'এখনই';
        if ($diff < 3600) return floor($diff / 60) . ' মিনিট আগে';
        if ($diff < 86400) return floor($diff / 3600) . ' ঘণ্টা আগে';
        if ($diff < 604800) return floor($diff / 86400) . ' দিন আগে';
        if ($diff < 2592000) return floor($diff / 604800) . ' সপ্তাহ আগে';
        return date('d M Y', $time);
    }
}

if (!function_exists('bn_number')) {
    function bn_number($num): string
    {
        $map = ['0'=>'০','1'=>'১','2'=>'২','3'=>'৩','4'=>'৪','5'=>'৫','6'=>'৬','7'=>'৭','8'=>'৮','9'=>'৯'];
        return strtr((string) $num, $map);
    }
}

if (!function_exists('format_money')) {
    function format_money($amount): string
    {
        $amount = (float) $amount;
        if ($amount <= 0) return '';
        $number = number_format($amount, 2, '.', ',');
        $number = rtrim(rtrim($number, '0'), '.');
        return bh_number_text($number) . ' টাকা';
    }
}

if (!function_exists('bh_number_text')) {
    function bh_number_text($num): string
    {
        return bn_number($num);
    }
}

if (!function_exists('paginate')) {
    function paginate(int $total, int $perPage, int $current): array
    {
        $pages = max(1, (int) ceil($total / $perPage));
        $current = max(1, min($current, $pages));
        $offset = ($current - 1) * $perPage;
        return ['pages' => $pages, 'current' => $current, 'offset' => $offset];
    }
}

if (!function_exists('paginate_links')) {
    function paginate_links(int $pages, int $current, string $basePath = ''): string
    {
        if ($pages <= 1) return '';
        $html = '<nav class="pagination">';
        $start = max(1, $current - 2);
        $end = min($pages, $start + 4);
        $start = max(1, $end - 4);
        if ($current > 1) {
            $html .= '<a href="' . e(url($basePath . '?page=' . ($current - 1))) . '" data-link data-page-param="' . ($current - 1) . '"><i class="fa-solid fa-chevron-left"></i></a>';
        }
        for ($i = $start; $i <= $end; $i++) {
            $cls = $i === $current ? ' class="active"' : '';
            $html .= '<a' . $cls . ' href="' . e(url($basePath . '?page=' . $i)) . '" data-link>' . bn_number($i) . '</a>';
        }
        if ($current < $pages) {
            $html .= '<a href="' . e(url($basePath . '?page=' . ($current + 1))) . '" data-link><i class="fa-solid fa-chevron-right"></i></a>';
        }
        $html .= '</nav>';
        return $html;
    }
}

if (!function_exists('is_image_file')) {
    function is_image_file(string $name): bool
    {
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'], true);
    }
}

if (!function_exists('random_filename')) {
    function random_filename(string $ext): string
    {
        return date('YmdHis') . '_' . bin2hex(random_bytes(8)) . '.' . strtolower($ext);
    }
}

if (!function_exists('save_uploaded_file')) {
    /**
     * নিরাপদ আপলোড। maxSize bytes। $dir uploads-এর সাবফোল্ডার।
     * @return array{ok:bool,path?:string,file?:string,message?:string}
     */
    function save_uploaded_file(array $file, string $dir, int $maxSize, array $allowed = ['jpg','jpeg','png','gif','webp','bmp']): array
    {
        if (empty($file['error']) && $file['error'] !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'message' => 'ফাইল আপলোড ব্যর্থ হয়েছে।'];
        }
        if (!is_uploaded_file($file['tmp_name'])) {
            return ['ok' => false, 'message' => 'অবৈধ আপলোড।'];
        }
        if ((int) $file['size'] > $maxSize) {
            return ['ok' => false, 'message' => 'ফাইল সাইজ বেশি। সর্বোচ্চ ' . round($maxSize / 1048576) . 'MB।'];
        }
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed, true)) {
            return ['ok' => false, 'message' => 'এই ফরম্যাট অনুমোদিত নয়।'];
        }
        $fullDir = UPLOAD_PATH . '/' . $dir;
        if (!is_dir($fullDir)) {
            mkdir($fullDir, 0755, true);
        }
        $name = random_filename($ext);
        $dest = $fullDir . '/' . $name;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            return ['ok' => false, 'message' => 'ফাইল সংরক্ষণ করা যায়নি।'];
        }
        return ['ok' => true, 'path' => 'uploads/' . $dir . '/' . $name, 'file' => $name];
    }
}

if (!function_exists('load_client_image')) {
    /**
     * ক্লায়েন্ট-সাইড কম্প্রেস হওয়া base64 ছবি সংরক্ষণ।
     */
    function save_base64_image(string $data, string $dir, int $maxSize = 5242880): array
    {
        $data = trim(strip_tags($data));
        if (preg_match('/^data:image\/([a-zA-Z0-9+\-.]+);base64,(.+)$/', $data, $m)) {
            $ext = strtolower($m[1]);
            $map = ['jpeg'=>'jpg','pjpeg'=>'jpg','x-png'=>'png','png'=>'png','gif'=>'gif','webp'=>'webp','bmp'=>'bmp'];
            $ext = $map[$ext] ?? 'jpg';
            if (!in_array($ext, ['jpg','png','gif','webp','bmp'], true)) {
                return ['ok' => false, 'message' => 'ছবির ফরম্যাট সাপোর্টেড নয়।'];
            }
            $binary = base64_decode($m[2], true);
            if ($binary === false || strlen($binary) > $maxSize) {
                return ['ok' => false, 'message' => 'ছবি বড় হয়ে গেছে।'];
            }
            $fullDir = UPLOAD_PATH . '/' . $dir;
            if (!is_dir($fullDir)) mkdir($fullDir, 0755, true);
            $name = random_filename($ext);
            file_put_contents($fullDir . '/' . $name, $binary);
            return ['ok' => true, 'path' => 'uploads/' . $dir . '/' . $name, 'file' => $name];
        }
        return ['ok' => false, 'message' => 'ছবির ডেটা সঠিক নয়।'];
    }
}

if (!function_exists('report_images')) {
    function report_images(int $reportId): array
    {
        return db_all('SELECT * FROM report_images WHERE report_id=? ORDER BY sort_order ASC, id ASC', [$reportId]);
    }
}

if (!function_exists('report_card_html')) {
    function report_card_html(array $report, array $category, int $imageIndex = 1): string
    {
        $images = report_images((int) $report['id']);
        $hasImg = count($images) > 0;
        $firstImg = $hasImg ? $images[0]['file'] : '';
        $more = count($images) > 1;
        $gallery = '';
        if ($hasImg) {
            $gallery = '<div class="report-gallery" data-gallery>
                <button class="gallery-main" data-lightbox="0" aria-label="ছবি বড় করুন">
                    <i class="fa-solid fa-expand gallery-expand"></i>
                    <img src="' . e(uploads_url($firstImg)) . '" alt="' . e($report['name']) . ' — রিপোর্ট ছবি" loading="lazy">
                </button>
                ' . ($more ? '<div class="gallery-thumbs">' : '') . '
                ' . implode('', array_map(function ($img, $i) {
                    return '<button class="gallery-thumb' . ($i === 0 ? ' active' : '') . '" data-lightbox="' . $i . '"><img src="' . e(uploads_url($img['file'])) . '" alt="ছবি ' . bn_number($i + 1) . '" loading="lazy"></button>';
                }, array_slice($images, 1), array_keys(array_slice($images, 0, max(count($images)-1, 1)))) ) . '
                ' . ($more ? '</div>' : '') . '
            </div>';
        } else {
            $gallery = '<div class="report-avatar no-image"><i class="fa-solid fa-user-secret"></i></div>';
        }

        $amount = (float) $report['amount'];
        $amountHtml = $amount > 0 ? '<span class="badge badge-money">' . e(format_money($amount)) . '</span>' : '';
        $mobileHtml = $report['mobile'] !== '' ? '<div class="detail-row"><span>মোবাইল:</span> ' . e($report['mobile']) . '</div>' : '';
        $addressHtml = $report['address'] !== '' ? '<div class="detail-row"><span>ঠিকানা:</span> ' . e($report['address']) . '</div>' : '';
        if ($amount > 0) {
            $addressHtml .= '<div class="detail-row"><span>টাকার পরিমাণ:</span> ' . e(format_money($amount)) . '</div>';
        }

        $detail = "
            {$mobileHtml}{$addressHtml}
            <div class=\"detail-row\"><span>অপরাধ:</span> " . e($category['name'] ?? $report['crime_type']) . "</div>
            <div class=\"detail-row\"><span>বিস্তারিত:</span> " . e($report['details']) . "</div>
        ";

        $shareLink = url('report/' . e($report['report_no']));
        $shareTitle = 'রিপোর্ট: ' . e($report['name']);
        $shareText = 'ওভিজোগ বিডি — ' . e($report['name']) . ' এর বিরুদ্ধে যাচাইকৃত রিপোর্ট দেখুন';
        $share = [
            'facebook' => 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode($shareLink),
            'messenger' => 'fb-messenger://share/?link=' . rawurlencode($shareLink),
            'whatsapp' => 'https://wa.me/?text=' . rawurlencode($shareText . ' ' . $shareLink),
            'twitter' => 'https://twitter.com/intent/tweet?url=' . rawurlencode($shareLink) . '&text=' . rawurlencode($shareText),
            'tiktok' => 'https://www.tiktok.com/upload?lang=en',
            'copy' => '',
        ];

        return '<article class="report-card" data-report="' . e($report['report_no']) . '">
            <div class="report-head">
                ' . $gallery . '
                <div class="report-meta">
                    <h3 class="report-name"><i class="fa-solid fa-user-shield"></i> ' . e($report['name']) . '</h3>
                    <div class="report-id"><i class="fa-regular fa-id-card"></i> ' . e($report['report_no']) . '</div>
                    <div class="report-tags">
                        <span class="badge badge-district"><i class="fa-solid fa-location-dot"></i> ' . e($report['district_name']) . '</span>
                        ' . $amountHtml . '
                        <span class="badge badge-status"><i class="fa-solid fa-circle-check"></i> অনুমোদিত</span>
                    </div>
                </div>
            </div>
            <div class="report-details ' . (mb_strlen($report['details']) > 240 ? 'collapsed' : '') . '">
                ' . $detail . '
            </div>
            ' . ($detail && mb_strlen($report['details']) > 500 ? '<button class="read-more-btn" data-readmore><span>আরও পড়ুন</span> <i class="fa-solid fa-angle-down"></i></button>' : '') . '
            <div class="report-foot">
                <span class="report-date"><i class="fa-regular fa-clock"></i> ' . e(time_ago($report['created_at'])) . '</span>
                <div class="report-actions">
                    <button class="share-btn" data-share><i class="fa-solid fa-share-nodes"></i> শেয়ার</button>
                </div>
            </div>
            ' . render_share_popup($shareLink, $shareTitle, $shareText, $share) . '
        </article>';
    }
}

if (!function_exists('render_share_popup')) {
    function render_share_popup(string $link, string $title, string $text, array $shares): string
    {
        $btns = '';
        foreach ($shares as $key => $href) {
            if ($key === 'copy') continue;
            $label = ucfirst($key);
            $btns .= '<a href="' . e($href) . '" target="_blank" rel="noopener" class="share-' . e($key) . '" data-share-url="' . e($href) . '" data-share-name="' . $label . '" data-title="' . e($title) . '" data-text="' . e($text) . '"><i class="fa-brands fa-' . ($key === 'twitter' ? 'x-twitter' : $key) . '"></i><span>' . e($label) . '</span></a>';
        }
        $btns .= '<button type="button" class="share-copy" data-copy-link="' . e($link) . '"><i class="fa-solid fa-link"></i><span>Copy Link</span></button>';
        return '<div class="share-popup" hidden><div class="share-grid">' . $btns . '</div></div>';
    }
}

if (!function_exists('default_site_settings')) {
    function default_site_settings(): array
    {
        return [
            'site_name' => 'বাংলাদেশ নাগরিক',
            'site_subtitle' => 'Ovijog BD · স্বচ্ছতার প্ল্যাটফর্ম',
            'site_email' => 'ovijogbd.support.com',
            'site_location' => 'Bangladesh',
            'site_footer' => 'নাগরিক স্বার্থ রক্ষা ও অনিয়ম প্রতিরোধে জড়িত ব্যক্তিদের শনাক্তকরণের জন্য এই সিস্টেমটি উন্নয়ন করা হয়েছে।',
            'site_slogan' => 'ডিজিটাল বাংলাদেশ, স্বচ্ছ বাংলাদেশ',
            'site_description' => 'বাংলাদেশ নাগরিক — অনুমোদিত ও যাচাইকৃত অসংগতি/অপরাধের রিপোর্ট, স্বচ্ছতার প্ল্যাটফর্ম।',
            'site_keywords' => 'বাংলাদেশ নাগরিক, অভিযোগ, চাদাবাজি, দুর্নীতি, রিপোর্ট, ওভিজোগ বিডি',
            'site_meta_image' => '',
            'site_logo' => '',
            'site_favicon' => '',
            'items_per_page' => '30',
            'show_pagination' => '30',
            'facebook_url' => '',
            'twitter_url' => '',
            'instagram_url' => '',
            'youtube_url' => '',
            'tiktok_url' => '',
            'whatsapp_number' => '',
            'contact_address' => 'Bangladesh',
            'contact_email' => 'ovijogbd.support.com',
            'contact_phone' => '',
            'privacy_approved_text' => 'আমি নিশ্চিত করছি যে দেওয়া তথ্য সঠিক এবং মিথ্যা তথ্য দেওয়ার জন্য আমি আইনগতভাবে দায়ী থাকব। বাংলাদেশ নাগরিক গোপনীয়তা নীতি পড়েছি এবং তা মেনে চলতে সম্মত আছি।',
        ];
    }
}

if (!function_exists('seo_meta')) {
    function seo_meta(array $args): string
    {
        $title = $args['title'] ?? setting('site_name');
        $description = $args['description'] ?? setting('site_description');
        $keywords = $args['keywords'] ?? setting('site_keywords');
        $image = $args['image'] ?? setting('site_meta_image');
        $url = $args['url'] ?? site_url() . current_path();
        $type = $args['type'] ?? 'website';
        $html = [];
        $html[] = '<title>' . e($title) . '</title>';
        $html[] = '<meta name="description" content="' . e($description) . '">';
        $html[] = '<meta name="keywords" content="' . e($keywords) . '">';
        $html[] = '<meta property="og:site_name" content="' . e(setting('site_name')) . '">';
        $html[] = '<meta property="og:type" content="' . e($type) . '">';
        $html[] = '<meta property="og:title" content="' . e($title) . '">';
        $html[] = '<meta property="og:description" content="' . e($description) . '">';
        $html[] = '<meta property="og:url" content="' . e($url) . '">';
        $html[] = '<meta property="og:image" content="' . e($image ?: asset_url('img/logo.png')) . '">';
        $html[] = '<meta name="twitter:card" content="summary_large_image">';
        $html[] = '<meta name="twitter:title" content="' . e($title) . '">';
        $html[] = '<meta name="twitter:description" content="' . e($description) . '">';
        $html[] = '<meta name="twitter:image" content="' . e($image ?: asset_url('img/logo.png')) . '">';
        return implode("\n", $html);
    }
}
