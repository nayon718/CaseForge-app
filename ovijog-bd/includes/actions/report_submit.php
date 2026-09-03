<?php
/**
 * রিপোর্ট জমা — AJAX/POST
 */

if (!function_exists('report_submit_action')) {
    function report_submit_action(): array
    {
        header('Content-Type: application/json; charset=utf-8');

        $csrf = require_csrf();
        if ($csrf) return $csrf;

        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $crimeType = trim($_POST['crime_type'] ?? '');
        $districtName = trim($_POST['district_name'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $mobile = trim($_POST['mobile'] ?? '');
        $details = trim($_POST['details'] ?? '');
        $amount = trim($_POST['amount'] ?? '');
        $evidenceLink = trim($_POST['evidence_link'] ?? '');
        $declaration = (int) ($_POST['declaration'] ?? 0);
        $captchaToken = $_POST['captcha_token'] ?? '';

        if ($declaration !== 1) {
            return ['ok' => false, 'message' => 'অনুগ্রহ করে তথ্য সঠিক বলে ঘোষণা বক্সে টিক দিন।'];
        }
        if ($categoryId < 1 || $name === '' || $crimeType === '' || $details === '') {
            return ['ok' => false, 'message' => 'বিভাগ, অভিযুক্তের নাম, অপরাধের ধরন ও বিস্তারিত বিবরণ অবশ্যই দিতে হবে।'];
        }
        if (mb_strlen($name) > 150 || mb_strlen($address) > 500 || mb_strlen($details) > 6000) {
            return ['ok' => false, 'message' => 'তথ্য সীমা অতিক্রম করেছে।'];
        }
        if ($mobile !== '' && !preg_match('/^01[3-9][0-9]{8}$/', $mobile)) {
            return ['ok' => false, 'message' => 'মোবাইল নম্বর সঠিক নয়। (যেমন: 01712345678)'];
        }
        if ($amount !== '' && (!is_numeric($amount) || (float)$amount < 0)) {
            return ['ok' => false, 'message' => 'টাকার পরিমাণ সঠিক নয়।'];
        }
        if ($evidenceLink !== '' && !filter_var($evidenceLink, FILTER_VALIDATE_URL)) {
            return ['ok' => false, 'message' => 'প্রমাণ লিংক সঠিক নয়।'];
        }

        // সিম্পল anti-bot (রেট লিমিট + honeypot)
        if (!empty($_POST['website_field'])) {
            return ['ok' => true, 'message' => 'আপনার তথ্য সফলভাবে জমা হয়েছে।', 'data' => ['report_no' => fake_report_no()]];
        }
        $limitKey = 'report_limit_' . client_ip();
        $_SESSION[$limitKey] = ($_SESSION[$limitKey] ?? 0) + 1;
        if ($_SESSION[$limitKey] > 10) {
            return ['ok' => false, 'message' => 'একটু বিরতি দিয়ে আবার চেষ্টা করুন।'];
        }

        $images = [];
        $jsonImages = $_POST['report_images'] ?? '';
        if (is_string($jsonImages) && $jsonImages !== '') {
            $decoded = json_decode($jsonImages, true);
            if (is_array($decoded)) {
                $i = 0;
                foreach ($decoded as $item) {
                    if ($i >= 5) break;
                    $res = save_base64_image((string)$item, 'reports', 5242880);
                    if ($res['ok']) {
                        $images[] = $res['path'];
                        $i++;
                    }
                }
            }
        }
        if (count($images) === 0 && !empty($_FILES['report_photos'])) {
            $files = $_FILES['report_photos'];
            $count = is_array($files['name']) ? count($files['name']) : 0;
            for ($i = 0; $i < min($count, 5); $i++) {
                $f = [
                    'name' => $files['name'][$i],
                    'type' => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error' => $files['error'][$i],
                    'size' => $files['size'][$i],
                ];
                $res = save_uploaded_file($f, 'reports', 5242880);
                if ($res['ok']) $images[] = $res['path'];
            }
        }

        // রিপোর্ট নম্বর: RPT-YYYY-####
        $year = date('Y');
        $prefix = 'RPT-' . $year . '-';
        $last = db_fetch("SELECT report_no FROM reports WHERE report_no LIKE ? ORDER BY id DESC LIMIT 1", [$prefix . '%']);
        $next = 1;
        if ($last) {
            $num = (int) substr($last['report_no'], -4);
            $next = $num + 1;
        }
        $reportNo = $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
        $hash = hash_hmac('sha256', $reportNo . $name . microtime(), APP_KEY);

        $reportId = db_insert('reports', [
            'report_no' => $reportNo,
            'category_id' => $categoryId,
            'name' => $name,
            'crime_type' => $crimeType,
            'district' => $districtName,
            'district_name' => $districtName,
            'address' => $address,
            'mobile' => $mobile,
            'details' => $details,
            'amount' => $amount !== '' ? (float)$amount : 0,
            'evidence_link' => $evidenceLink,
            'status' => 'pending',
            'report_hash' => $hash,
            'ip_address' => client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        if (!$reportId) {
            return ['ok' => false, 'message' => 'রিপোর্ট জমা করা যায়নি।'];
        }

        foreach ($images as $i => $path) {
            db_insert('report_images', [
                'report_id' => $reportId,
                'file' => $path,
                'sort_order' => $i,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        return [
            'ok' => true,
            'message' => 'আপনার তথ্য সম্পূর্ণভাবে সফলভাবে জমা হয়েছে। আমাদের অ্যাডমিন বিষয়টি যাচাই করে approve করবেন।',
            'data' => ['report_no' => $reportNo],
        ];
    }
}

if (!function_exists('fake_report_no')) {
    function fake_report_no(): string
    {
        return 'RPT-' . date('Y') . '-' . str_pad((string) mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }
}
