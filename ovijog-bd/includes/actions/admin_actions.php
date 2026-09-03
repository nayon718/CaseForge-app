<?php
/**
 * অ্যাডমিন AJAX POST অ্যাকশন
 */

if (!function_exists('admin_post_action')) {
    function admin_post_action(string $path): ?array
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') return null;
        require_admin();
        $csrf = require_csrf();
        if ($csrf) return $csrf;

        // রিপোর্ট এডিট
        if (preg_match('#^/admin/report/([0-9]+)$#', $path, $m)) {
            return admin_save_report((int) $m[1]);
        }

        // পেমেন্ট মেথড সংরক্ষণ
        if ($path === '/admin/payment-methods') {
            return admin_save_payment_method(0);
        }
        if (preg_match('#^/admin/payment-methods/([0-9]+)$#', $path, $m)) {
            return admin_save_payment_method((int) $m[1]);
        }

        // সেটিংস
        if ($path === '/admin/settings') {
            return admin_save_settings();
        }

        // গ্যালারি আপলোড
        if ($path === '/admin/gallery') {
            return admin_upload_gallery();
        }

        // পাসওয়ার্ড
        if ($path === '/admin/change-password') {
            return admin_change_password_action();
        }

        return null;
    }
}

if (!function_exists('admin_save_report')) {
    function admin_save_report(int $id): array
    {
        $row = db_fetch('SELECT id FROM reports WHERE id=?', [$id]);
        if (!$row) return ['ok' => false, 'message' => 'রিপোর্ট পাওয়া যায়নি।'];
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'crime_type' => trim($_POST['crime_type'] ?? ''),
            'district_name' => trim($_POST['district_name'] ?? ''),
            'mobile' => trim($_POST['mobile'] ?? ''),
            'address' => trim($_POST['address'] ?? ''),
            'details' => trim($_POST['details'] ?? ''),
            'amount' => (float) ($_POST['amount'] ?? 0),
            'evidence_link' => trim($_POST['evidence_link'] ?? ''),
            'status' => in_array($_POST['status'] ?? '', ['pending','approved','rejected'], true) ? $_POST['status'] : 'pending',
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        db_update('reports', $data, 'id = ?', [$id]);
        return ['ok' => true, 'message' => 'রিপোর্ট সফলভাবে সংরক্ষণ হয়েছে।'];
    }
}

if (!function_exists('admin_save_payment_method')) {
    function admin_save_payment_method(int $id): array
    {
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'type' => trim($_POST['type'] ?? 'other'),
            'address' => trim($_POST['address'] ?? ''),
            'instructions' => trim($_POST['instructions'] ?? ''),
            'color' => trim($_POST['color'] ?? '#10b981'),
            'status' => (int) (($_POST['status'] ?? 1) ? 1 : 0),
        ];
        if ($data['name'] === '' || $data['address'] === '') {
            return ['ok' => false, 'message' => 'নাম ও ঠিকানা অবশ্যই দিতে হবে।'];
        }
        $logoPath = null;
        if (!empty($_FILES['logo_file'])) {
            $res = save_uploaded_file($_FILES['logo_file'], 'gallery', 1048576);
            if ($res['ok']) $logoPath = $res['path'];
        }
        if ($id) {
            db_update('payment_methods', $data, 'id = ?', [$id]);
            if ($logoPath) db_update('payment_methods', ['logo' => $logoPath], 'id = ?', [$id]);
            return ['ok' => true, 'message' => 'পেমেন্ট মেথড আপডেট হয়েছে।'];
        }
        $newId = db_insert('payment_methods', $data + ['logo' => $logoPath ?: '', 'sort_order' => (int) db_value('SELECT COALESCE(MAX(sort_order),0)+1 FROM payment_methods', [], 1), 'created_at' => date('Y-m-d H:i:s')]);
        return ['ok' => true, 'message' => 'নতুন মেথড যোগ হয়েছে।', 'data' => ['id' => $newId]];
    }
}

if (!function_exists('admin_save_settings')) {
    function admin_save_settings(): array
    {
        $fields = default_site_settings();
        foreach ($fields as $key => $default) {
            if (isset($_POST[$key])) {
                save_setting($key, trim((string) $_POST[$key]));
            }
        }
        // ফাইল আপলোড
        foreach (['site_logo_file'=>'site_logo','site_favicon_file'=>'site_favicon','site_meta_image_file'=>'site_meta_image'] as $fileKey=>$settingKey) {
            if (!empty($_FILES[$fileKey]) && is_uploaded_file($_FILES[$fileKey]['tmp_name'])) {
                $res = save_uploaded_file($_FILES[$fileKey], 'gallery', 2097152);
                if ($res['ok']) save_setting($settingKey, $res['path']);
            }
        }
        return ['ok' => true, 'message' => 'সেটিংস সফলভাবে সংরক্ষণ হয়েছে।'];
    }
}

if (!function_exists('admin_upload_gallery')) {
    function admin_upload_gallery(): array
    {
        if (empty($_FILES['gallery_file'])) return ['ok' => false, 'message' => 'ছবি নির্বাচন করুন।'];
        $res = save_uploaded_file($_FILES['gallery_file'], 'gallery', 2097152);
        if (!$res['ok']) return $res;
        db_insert('gallery', ['title' => trim($_POST['title'] ?? ''), 'file' => $res['path'], 'created_at' => date('Y-m-d H:i:s')]);
        return ['ok' => true, 'message' => 'গ্যালারিতে ছবি আপলোড হয়েছে।'];
    }
}

if (!function_exists('admin_change_password_action')) {
    function admin_change_password_action(): array
    {
        $password = (string) ($_POST['password'] ?? '');
        $confirm = (string) ($_POST['password_confirm'] ?? '');
        if (strlen($password) < 8) return ['ok' => false, 'message' => 'পাসওয়ার্ড কমপক্ষে ৮ অক্ষরের হতে হবে।'];
        if ($password !== $confirm) return ['ok' => false, 'message' => 'দুইটি পাসওয়ার্ড মিলছে না।'];
        if (admin_change_password($password)) {
            return ['ok' => true, 'message' => 'পাসওয়ার্ড পরিবর্তন হয়েছে।'];
        }
        return ['ok' => false, 'message' => 'পাসওয়ার্ড পরিবর্তন ব্যর্থ।'];
    }
}
