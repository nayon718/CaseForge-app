<?php
/**
 * পাবলিক পেজ রেন্ডারার
 */

if (!function_exists('categories_list')) {
    function categories_list(): array
    {
        return db_all('SELECT * FROM categories WHERE status = 1 ORDER BY sort_order ASC, id ASC');
    }
}

if (!function_exists('districts_list')) {
    function districts_list(): array
    {
        return db_all('SELECT * FROM districts ORDER BY name_bn ASC');
    }
}

if (!function_exists('get_approved_reports')) {
    function get_approved_reports(int $limit, int $offset): array
    {
        return db_all(
            'SELECT r.*, c.name AS category_name, c.color AS category_color, c.icon AS category_icon
             FROM reports r
             LEFT JOIN categories c ON c.id = r.category_id
             WHERE r.status = "approved"
             ORDER BY r.created_at DESC, r.id DESC
             LIMIT ' . (int)$limit . ' OFFSET ' . (int)$offset
        );
    }
}

if (!function_exists('package_report')) {
    function package_report(array $r): array
    {
        $r['district_name'] = $r['district_name'] !== '' ? $r['district_name'] : ($r['district'] ?: 'বাংলাদেশ');
        return $r;
    }
}

if (!function_exists('page_home')) {
    function page_home(): string
    {
        $perPage = max(6, (int) setting('items_per_page', '20'));
        $current = max(1, (int) ($_GET['page'] ?? 1));
        $total = db_count('reports', 'status = "approved"');
        $pg = paginate($total, $perPage, $current);
        $reports = array_map('package_report', get_approved_reports($perPage, $pg['offset']));

        $totalMoney = (float) db_value('SELECT COALESCE(SUM(amount),0) FROM reports WHERE status = "approved"', [], 0);
        $topCategory = db_fetch(
            'SELECT c.name, COUNT(r.id) AS total, COALESCE(SUM(r.amount),0) AS money
             FROM reports r JOIN categories c ON c.id=r.category_id
             WHERE r.status="approved"
             GROUP BY c.id ORDER BY total DESC, money DESC LIMIT 1'
        );

        $html = '<section class="hero-page">
            <div class="hero-badge"><i class="fa-solid fa-shield-halved"></i> অনুমোদিত রিপোর্টসমূহ</div>
            <h1 class="hero-title">নাগরিকদের জন্য উন্মুক্ত ও যাচাইকৃত রিপোর্টসমূহ</h1>
            <p class="hero-sub">বাংলাদেশ নাগরিক প্ল্যাটফর্মে জমা হওয়া প্রতিষ্ঠিত রিপোর্ট। তথ্য যাচাই করা হয় এবং গোপনীয়তা বজায় রাখা হয়।</p>
            <div class="hero-stats">
                <div class="stat-box"><span class="stat-icon"><i class="fa-solid fa-file-lines"></i></span><div><div class="stat-value">' . bn_number($total) . '</div><div class="stat-label">মোট রিপোর্ট</div></div></div>
                <div class="stat-box"><span class="stat-icon money"><i class="fa-solid fa-coins"></i></span><div><div class="stat-value">' . e(format_money($totalMoney)) . '</div><div class="stat-label">মোট টাকা</div></div></div>
                <div class="stat-box"><span class="stat-icon category"><i class="fa-solid fa-chart-line"></i></span><div><div class="stat-value">' . e($topCategory['name'] ?? '—') . '</div><div class="stat-label">টপ বিভাগ রিপোর্ট</div></div></div>
            </div>
        </section>';

        $html .= '<section class="section-block" id="approved-reports">
            <div class="section-head"><div><h2 class="section-title"><i class="fa-solid fa-file-circle-check"></i> অনুমোদিত রিপোর্টসমূহ</h2><p class="section-desc">ধারাবাহিকভাবে প্রকাশিত যাচাইকৃত রিপোর্ট</p></div></div>';

        if (!$reports) {
            $html .= '<div class="empty-state"><i class="fa-regular fa-folder-open"></i><h3>এখনো কোনো রিপোর্ট যাচাই হয়নি</h3><p>প্রথম রিপোর্ট জমা দিন এবং অ্যাডমিন যাচাই করলে এখানে দেখা যাবে।</p><a href="' . e(url('report')) . '" data-link class="btn btn-primary"><i class="fa-solid fa-plus"></i> রিপোর্ট জমা দিন</a></div>';
        } else {
            $html .= '<div class="reports-list">' . implode('', array_map(function ($r) {
                return report_card_html($r, [
                    'name' => $r['category_name'] ?? '', 'color' => $r['category_color'] ?? '', 'icon' => $r['category_icon'] ?? '',
                ]);
            }, $reports)) . '</div>';
            $html .= paginate_links($pg['pages'], $pg['current'], '');
        }
        $html .= '</section>';

        return $html;
    }
}

if (!function_exists('page_reports')) {
    function page_reports(): string
    {
        return page_home();
    }
}

if (!function_exists('page_report')) {
    function page_report(): string
    {
        $cats = categories_list();
        $districts = districts_list();
        $catOptions = '<option value="">বিভাগ সিলেক্ট করুন</option>';
        foreach ($cats as $c) {
            $catOptions .= '<option value="' . $c['id'] . '">' . e($c['name']) . '</option>';
        }
        $distOptions = '<option value="">জেলা সিলেক্ট করুন</option>';
        foreach ($districts as $d) {
            $distOptions .= '<option value="' . e($d['name_bn']) . '">' . e($d['name_bn']) . '</option>';
        }
        $crimeOptions = '';
        foreach ($cats as $c) {
            $crimeOptions .= '<option value="' . e($c['name']) . '">' . e($c['name']) . '</option>';
        }

        $privacyText = setting('privacy_approved_text', 'আমি নিশ্চিত করছি যে দেওয়া তথ্য সঠিক এবং মিথ্যা তথ্য দেওয়ার জন্য আমি আইনগতভাবে দায়ী থাকব। বাংলাদেশ নাগরিক গোপনীয়তা নীতি পড়েছি এবং তা মেনে চলতে সম্মত আছি।');

        return '<section class="form-page">
            <div class="form-hero">
                <div class="form-badge"><i class="fa-solid fa-shield-halved"></i> ১০০% গোপনীয়তা</div>
                <h1 class="form-title">অভিযোগ জমা দিন</h1>
                <p class="form-sub">আপনার দেওয়া তথ্য সম্পূর্ণ গোপন রাখা হবে, নিশ্চিতভাবে রিপোর্ট করুন। প্রমাণ সহকারে তথ্য প্রদান করলে দ্রুত ব্যবস্থা নেওয়া সম্ভব।</p>
            </div>
            <form id="reportForm" class="report-form card" data-ajax-form data-url="' . e(url('api/report-submit')) . '" data-prevent-default>
                ' . csrf_field() . '
                <div class="form-hidden"><input type="text" name="website_field" value="" tabindex="-1" autocomplete="off"></div>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="category_id"><i class="fa-solid fa-layer-group"></i> বিভাগ সিলেক্ট করুন <span class="req">*</span></label>
                        <select id="category_id" name="category_id" required><option value="">বিভাগ সিলেক্ট করুন</option>' . $catOptions . '</select>
                    </div>
                    <div class="form-group">
                        <label for="name"><i class="fa-solid fa-user"></i> অভিযুক্তের নাম <span class="req">*</span></label>
                        <input id="name" name="name" type="text" maxlength="150" placeholder="অভিযুক্তের পুরো নাম লিখুন" required>
                    </div>
                    <div class="form-group">
                        <label for="crime_type"><i class="fa-solid fa-triangle-exclamation"></i> অপরাধের ধরন <span class="req">*</span></label>
                        <select id="crime_type" name="crime_type" required><option value="">অপরাধের ধরন সিলেক্ট করুন</option>' . $crimeOptions . '</select>
                    </div>
                    <div class="form-group">
                        <label for="district_name"><i class="fa-solid fa-location-dot"></i> জেলা <span class="req">*</span></label>
                        <select id="district_name" name="district_name" required><option value="">জেলা সিলেক্ট করুন</option>' . $distOptions . '</select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="address"><i class="fa-solid fa-map-location-dot"></i> ঠিকানা: <small>সম্পূর্ণ ঠিকানা — বাড়ি, রোড, গ্রাম, মহল্লা, থানা ইত্যাদি</small></label>
                    <textarea id="address" name="address" rows="2" maxlength="500" placeholder="সম্পূর্ণ ঠিকানা লিখুন..."></textarea>
                </div>
                <div class="form-group">
                    <label for="mobile"><i class="fa-solid fa-phone"></i> মোবাইল নম্বর (যদি থাকে): <small>উক্ত ব্যক্তির</small></label>
                    <input id="mobile" name="mobile" type="tel" maxlength="11" placeholder="01XXXXXXXXX">
                </div>
                <div class="form-group">
                    <label for="details"><i class="fa-solid fa-file-lines"></i> বিস্তারিত বিবরণ <span class="req">*</span></label>
                    <textarea id="details" name="details" rows="6" maxlength="6000" placeholder="যত বেশি বিস্তারিত লিখবেন, তত দ্রুত ব্যবস্থা নেওয়া সম্ভব হবে" required></textarea>
                    <small class="hint">* যত বেশি বিস্তারিত লিখবেন, তত দ্রুত ব্যবস্থা নেওয়া সম্ভব হবে</small>
                </div>
                <div class="form-grid two">
                    <div class="form-group">
                        <label for="amount"><i class="fa-solid fa-money-bill-wave"></i> টাকা নিয়ে থাকলে তার পরিমাণ <small>(ঐচ্ছিক)</small></label>
                        <input id="amount" name="amount" type="number" min="0" step="0.01" placeholder="00">
                    </div>
                    <div class="form-group">
                        <label for="evidence_link"><i class="fa-solid fa-link"></i> তথ্য বা প্রমাণ লিংক থাকলে <small>(ঐচ্ছিক)</small></label>
                        <input id="evidence_link" name="evidence_link" type="url" placeholder="https://example.com/evidence">
                    </div>
                </div>

                <div class="form-group">
                    <label><i class="fa-solid fa-images"></i> ছবি আপলোড <small>(ঐচ্ছিক)</small></label>
                    <div class="upload-zone" id="reportUploadZone">
                        <input type="file" id="reportPhotos" name="report_photos[]" accept="image/*" multiple>
                        <div class="upload-empty"><i class="fa-solid fa-cloud-arrow-up"></i><p>ছবি নির্বাচন করুন — ১ থেকে ৫টি ছবি</p><small>সর্বোচ্চ ৫MB, অটো-কম্প্রেস হয়ে যাবে</small></div>
                        <div class="upload-preview" id="reportPreview"></div>
                    </div>
                </div>

                <div class="declaration-box">
                    <label class="check-line">
                        <input type="checkbox" id="declaration" name="declaration" value="1" required>
                        <span class="checkmark"></span>
                        <span>আমি নিশ্চিত করছি যে দেওয়া তথ্য সঠিক এবং মিথ্যা তথ্য দেওয়ার জন্য আমি আইনগতভাবে দায়ী থাকব। <a href="' . e(url('privacy')) . '" data-link>বাংলাদেশ নাগরিক গোপনীয়তা নীতি</a> পড়েছি এবং তা মেনে চলতে সম্মত আছি।</span>
                    </label>
                </div>

                <div class="form-action-buttons">
                    <button type="submit" class="btn btn-primary btn-lg" id="submitReportBtn"><i class="fa-solid fa-paper-plane"></i> জমা দিন</button>
                    <button type="button" class="btn btn-ghost" data-idle-reset-form><i class="fa-solid fa-rotate-left"></i> রিসেট করুন</button>
                    <a href="' . e(url('')) . '" data-link class="btn btn-soft"><i class="fa-solid fa-house"></i> হোম এ ফিরে যান</a>
                </div>
            </form>
        </section>';
    }
}

if (!function_exists('page_search')) {
    function page_search(): string
    {
        $cats = categories_list();
        $districts = districts_list();
        $catOptions = '<option value="">সব বিভাগ</option>';
        foreach ($cats as $c) $catOptions .= '<option value="' . e($c['name']) . '">' . e($c['name']) . '</option>';
        $distOptions = '<option value="">সব জেলা</option>';
        foreach ($districts as $d) $distOptions .= '<option value="' . e($d['name_bn']) . '">' . e($d['name_bn']) . '</option>';

        return '<section class="search-page">
            <div class="search-hero">
                <h1 class="form-title"><i class="fa-solid fa-magnifying-glass"></i> রিপোর্ট খুঁজুন</h1>
                <p class="form-sub">কোনো ব্যক্তির নামে রিপোর্ট, রিপোর্ট আইডি বা জেলা/অপরাধের ধরন দিয়ে খোঁজ করুন। উপরের যেকোনো একটি ফিল্ড ব্যবহার করলেই ফলাফল পাবেন।</p>
                <div class="search-options"><span class="chip"><i class="fa-solid fa-id-card"></i> রিপোর্ট আইডি: RPT-2026-0123</span><span class="chip"><i class="fa-solid fa-user"></i> নাম অনুযায়ী</span><span class="chip"><i class="fa-solid fa-map"></i> জেলা</span><span class="chip"><i class="fa-solid fa-triangle-exclamation"></i> অপরাধের ধরন</span></div>
            </div>

            <div class="search-box card">
                <form id="searchForm" action="' . e(url('api/search')) . '" data-prevent-default>
                    ' . csrf_field() . '
                    <div class="form-grid two trow">
                        <div class="form-group">
                            <label for="searchId">রিপোর্ট আইডি দিয়ে খুঁজুন</label>
                            <input id="searchId" name="report_no" type="text" placeholder="RPT-2026-0123">
                        </div>
                        <div class="form-group">
                            <label for="searchName">নাম দ্বারা খুঁজুন</label>
                            <input id="searchName" name="name" type="text" placeholder="নাম লিখুন">
                        </div>
                    </div>
                    <div class="form-grid two trow">
                        <div class="form-group">
                            <label for="searchDistrict">জেলা সিলেক্ট করে খুঁজুন</label>
                            <select id="searchDistrict" name="district_name">' . $distOptions . '</select>
                        </div>
                        <div class="form-group">
                            <label for="searchCrime">অপরাধের ধরন</label>
                            <select id="searchCrime" name="crime_type">' . $catOptions . '</select>
                        </div>
                    </div>
                    <div class="search-actions">
                        <button type="submit" class="btn btn-primary btn-lg"><i class="fa-solid fa-magnifying-glass-location"></i> তথ্য খুঁজুন</button>
                        <button type="button" class="btn btn-ghost" data-search-reset><i class="fa-solid fa-broom"></i> রিসেট</button>
                    </div>
                </form>
            </div>
            <div id="searchResults" class="search-results"></div>
        </section>';
    }
}

if (!function_exists('page_leaderboard')) {
    function page_leaderboard(): string
    {
        $rows = db_all(
            'SELECT c.name AS category_name, c.color AS category_color, c.icon AS category_icon,
                    r.name, r.report_no, r.district_name, r.created_at, r.amount, r.id
             FROM reports r JOIN categories c ON c.id = r.category_id
             WHERE r.status = "approved"
             ORDER BY r.created_at DESC, r.id DESC
             LIMIT 50'
        );

        $html = '<section class="leaderboard-page">
            <div class="search-hero">
                <h1 class="form-title"><i class="fa-solid fa-trophy"></i> লিডার বোর্ড</h1>
                <p class="form-sub">টপ বিভাগ ও শীর্ষ ৫০ জনের নাম। যাদের নাম বিভাগের সাথে মিলে গেছে।</p>
            </div>
            <div class="leaderboard-top">
                <div class="lb-medal first"><i class="fa-solid fa-medal"></i><span class="rank">১</span><span class="lb-name">' . e($rows[0]['name'] ?? '—') . '</span><span class="lb-cat">' . e($rows[0]['category_name'] ?? '') . '</span></div>
                <div class="lb-medal second"><i class="fa-solid fa-medal"></i><span class="rank">২</span><span class="lb-name">' . e($rows[1]['name'] ?? '—') . '</span><span class="lb-cat">' . e($rows[1]['category_name'] ?? '') . '</span></div>
                <div class="lb-medal third"><i class="fa-solid fa-medal"></i><span class="rank">৩</span><span class="lb-name">' . e($rows[2]['name'] ?? '—') . '</span><span class="lb-cat">' . e($rows[2]['category_name'] ?? '') . '</span></div>
            </div>
            <div class="leaderboard-list card">';
        if (!$rows) {
            $html .= '<div class="empty-state"><i class="fa-regular fa-trophy"></i><h3>এখনো লিডার বোর্ড খালি</h3></div>';
        } else {
            $i = 0;
            foreach ($rows as $row) {
                $i++;
                $cls = $i <= 3 ? ' top-three' : '';
                $html .= '<div class="leaderboard-row' . $cls . '">
                    <span class="lb-index">' . bn_number($i) . '</span>
                    <span class="lb-icon" style="background:' . e($row['category_color']) . '"><i class="' . e($row['category_icon']) . '"></i></span>
                    <div class="lb-main"><div class="lb-user"><i class="fa-solid fa-user"></i> ' . e($row['name']) . ' <span class="lb-id">' . e($row['report_no']) . '</span></div><div class="lb-cat2">' . e($row['category_name']) . ' · ' . e($row['district_name'] ?: 'বাংলাদেশ') . '</div></div>
                    <span class="lb-date">' . e(time_ago($row['created_at'])) . '</span>
                    <a href="' . e(url('report/' . $row['report_no'])) . '" data-link class="lb-view"><i class="fa-solid fa-arrow-right"></i></a>
                </div>';
            }
        }
        $html .= '</div></section>';
        return $html;
    }
}

if (!function_exists('page_donation')) {
    function page_donation(): string
    {
        $methods = db_all('SELECT * FROM payment_methods WHERE status = 1 ORDER BY sort_order ASC, id ASC');
        $cards = '';
        foreach ($methods as $m) {
            $logo = $m['logo'] !== '' ? uploads_url($m['logo']) : '';
            $logoHtml = $logo !== '' ? '<img src="' . e($logo) . '" alt="' . e($m['name']) . '">' : '<i class="fa-solid fa-wallet"></i>';
            $safeAddress = e($m['address']);
            $cards .= '
            <div class="pay-card" data-method-id="' . $m['id'] . '" data-address="' . $safeAddress . '" data-name="' . e($m['name']) . '">
                <div class="pay-logo" style="--brand:' . e($m['color']) . '">' . $logoHtml . '</div>
                <div class="pay-info"><h3>' . e($m['name']) . '</h3><div class="pay-address">' . $safeAddress . '</div></div>
                <button type="button" class="btn btn-ghost copy-pay-btn" data-copy="' . $safeAddress . '"><i class="fa-solid fa-copy"></i> কপি</button>
            </div>';
        }
        $payOptions = '<option value="">পেমেন্ট মেথড সিলেক্ট করুন</option>';
        foreach ($methods as $m) $payOptions .= '<option value="' . $m['id'] . '">' . e($m['name']) . '</option>';

        return '<section class="donation-page">
            <div class="donation-header">
                <div class="donation-badge"><i class="fa-solid fa-heart"></i> অনুদান</div>
                <h1 class="form-title">আপনার সহযোগিতা আমাদের এগিয়ে নিতে সাহায্য করবে</h1>
                <p class="form-sub">আপনার ছোট্ট একটি অনুদান আমাদের উদ্যোগকে এগিয়ে নিতে সহায়তা করে। প্রতিটি টাকা সরাসরি দেশের উন্নয়নমূলক কাজে ব্যয় হয়।</p>
            </div>
            <div class="donation-grid">
                <div class="donation-reasons card">
                    <h2><i class="fa-solid fa-circle-question"></i> কেন অনুদান দিবেন</h2>
                    <div class="reason-item"><i class="fa-solid fa-chart-line"></i><div><strong>উন্নত সেবা</strong><p>আপনার সহযোগিতা আমাদেরকে আরও উন্নত সেবা প্রদান করতে, নতুন প্রযুক্তি যুক্ত করতে সহায়তা করে।</p></div></div>
                    <div class="reason-item"><i class="fa-solid fa-hand-holding-heart"></i><div><strong>দেশের উন্নয়ন</strong><p>প্রতিটি টাকা সরাসরি দেশের উন্নয়নমূলক কাজে ব্যয় হয়।</p></div></div>
                    <div class="reason-item"><i class="fa-solid fa-users"></i><div><strong>ছোট প্রচেষ্টা, বড় পরিবর্তন</strong><p>সকলের ছোট ছোট প্রচেষ্টাই বড় পরিবর্তন আনতে পারে।</p></div></div>
                </div>
                <div class="payment-section card">
                    <h2><i class="fa-solid fa-money-check-dollar"></i> অনুদান জমা দিন</h2>
                    <form id="donationForm" data-ajax-form data-url="' . e(url('api/payment-submit')) . '" data-prevent-default>
                        ' . csrf_field() . '
                        <div class="form-group"><label>পেমেন্ট মেথড</label><select name="payment_method_id" required>' . $payOptions . '</select></div>
                        <div class="selected-payment" id="selectedPayment"></div>
                        <div class="form-group"><label><i class="fa-solid fa-user"></i> আপনার নাম</label><input type="text" name="sender_name" placeholder="আপনার নাম"></div>
                        <div class="form-group"><label><i class="fa-solid fa-money-bill-wave"></i> টাকার পরিমাণ</label><input type="number" name="amount" min="1" step="0.01" required placeholder="00"></div>
                        <div class="form-group"><label><i class="fa-brands fa-hash"></i> Trx id <small>(ঐচ্ছিক)</small></label><input type="text" name="trx_id" placeholder="TRX ID"></div>
                        <div class="form-group">
                            <label><i class="fa-solid fa-camera"></i> পেমেন্ট স্ক্রিনশট <small>(সর্বোচ্চ 2MB, অটো কম্প্রেস)</small></label>
                            <div class="upload-zone single" id="paymentUploadZone">
                                <input type="file" id="paymentScreenshot" name="payment_screenshot" accept="image/*">
                                <div class="upload-empty"><i class="fa-solid fa-cloud-arrow-up"></i><p>পেমেন্ট স্ক্রিনশট নির্বাচন করুন</p></div>
                                <div class="upload-preview" id="paymentPreview"></div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg" style="width:100%"><i class="fa-solid fa-hand-holding-dollar"></i> তথ্য জমা দিন</button>
                    </form>
                </div>
            </div>
            <div class="payment-methods card">
                <h2><i class="fa-solid fa-building-columns"></i> পেমেন্ট মেথড</h2>
                <div class="pay-list">' . ($cards ?: '<div class="empty-inline">এখনো পেমেন্ট মেথড যোগ করা হয়নি।</div>') . '</div>
            </div>
        </section>';
    }
}

if (!function_exists('page_privacy')) {
    function page_privacy(): string
    {
        return '<section class="static-page">
            <div class="static-card">
                <h1><i class="fa-solid fa-shield-halved"></i> গোপনীয়তা নীতি</h1>
                <p>বাংলাদেশ নাগরিক প্ল্যাটফর্মে জমা দেওয়া প্রতিটি তথ্য সম্পূর্ণ গোপনীয়তার সাথে সংরক্ষণ করা হয়।</p>
                <ul>
                    <li>রিপোর্ট করার সময় আপনার ব্যক্তিগত তথ্য প্রকাশ করা হয় না।</li>
                    <li>শুধুমাত্র যাচাইকৃত অ্যাডমিন গোপন তথ্য দেখতে পারেন।</li>
                    <li>প্রমাণ ছাড়া ফেক রিপোর্ট দিলে আইনত দায়ী থাকতে হবে।</li>
                    <li>নিরাপত্তা বাড়াতে IP, Device, Browser তথ্য অ্যাডমিন যাচাইয়ে ব্যবহার হয়।</li>
                    <li>আলাদা ইমেইল/টেলিফোন সংগ্রহ করা হয় না।</li>
                </ul>
            </div>
        </section>';
    }
}

if (!function_exists('page_report_detail')) {
    function page_report_detail(string $reportNo): string
    {
        $report = db_fetch(
            'SELECT r.*, c.name AS category_name, c.color AS category_color, c.icon AS category_icon
             FROM reports r LEFT JOIN categories c ON c.id=r.category_id
             WHERE r.report_no = ? LIMIT 1', [$reportNo]
        );
        if (!$report || $report['status'] !== 'approved') {
            http_response_code(404);
            return page_404();
        }
        $report = package_report($report);
        $html = '<section class="detail-page"><div class="detail-topbar"><a href="' . e(url('')) . '" data-link class="btn btn-ghost"><i class="fa-solid fa-arrow-left"></i> হোম</a><span class="detail-title"><i class="fa-solid fa-file-invoice"></i> ' . e($report['report_no']) . '</span></div>';
        $html .= report_card_html($report, $report);
        $html .= '<a href="' . e(url('report')) . '" data-link class="btn btn-primary btn-lg detail-footer"><i class="fa-solid fa-plus"></i> নতুন রিপোর্ট জমা দিন</a></section>';
        return $html;
    }
}

if (!function_exists('report_detail_meta')) {
    function report_detail_meta(string $reportNo): array
    {
        $report = db_fetch('SELECT * FROM reports WHERE report_no=? LIMIT 1', [$reportNo]);
        if (!$report) return default_meta();
        $image = '';
        $img = db_fetch('SELECT file FROM report_images WHERE report_id=? ORDER BY id ASC LIMIT 1', [$report['id']]);
        if ($img) $image = uploads_url($img['file']);
        return [
            'title' => 'রিপোর্ট: ' . $report['name'] . ' | ' . setting('site_name'),
            'description' => mb_substr($report['details'], 0, 180),
            'keywords' => setting('site_keywords'),
            'image' => $image,
            'url' => url('report/' . $report['report_no']),
            'type' => 'article',
        ];
    }
}

if (!function_exists('page_404')) {
    function page_404(): string
    {
        return '<section class="notfound-page">
            <div class="nf-code">৪০৪</div>
            <div class="nf-icon"><i class="fa-solid fa-ghost"></i></div>
            <h1>পেজটি খুঁজে পাওয়া যায়নি</h1>
            <p>আপনি যে পেজটি খুঁজছেন সেটি সরানো হয়েছে বা ভুল URL ব্যবহার করেছেন।</p>
            <div class="nf-actions">
                <a href="' . e(url('')) . '" data-link class="btn btn-primary"><i class="fa-solid fa-house"></i> হোম এ ফিরে যান</a>
                <a href="' . e(url('report')) . '" data-link class="btn btn-soft"><i class="fa-solid fa-plus"></i> নতুন রিপোর্ট</a>
            </div>
        </section>';
    }
}
