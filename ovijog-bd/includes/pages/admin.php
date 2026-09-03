<?php
/**
 * অ্যাডমিন প্যানেল পেজ
 */

if (!function_exists('status_label')) {
    function status_label(string $status): string
    {
        return ['pending' => 'পেন্ডিং', 'approved' => 'অনুমোদিত', 'rejected' => 'বাতিল'][$status] ?? $status;
    }
}

if (!function_exists('admin_stats')) {
    function admin_stats(): array
    {
        return [
            'reports_total' => db_count('reports'),
            'reports_pending' => db_count('reports', 'status="pending"'),
            'reports_approved' => db_count('reports', 'status="approved"'),
            'reports_rejected' => db_count('reports', 'status="rejected"'),
            'payments_total' => db_count('payments'),
            'payments_pending' => db_count('payments', 'status="pending"'),
            'payments_approved' => db_count('payments', 'status="approved"'),
            'payments_amount' => (float) db_value('SELECT COALESCE(SUM(amount),0) FROM payments', [], 0),
            'visitors_today' => db_count('analytics', 'visit_date = CURDATE()'),
            'visitors_total' => db_count('analytics'),
        ];
    }
}

if (!function_exists('admin_login_page')) {
    function admin_login_page(): string
    {
        return '<section class="admin-login-page">
            <div class="login-card">
                <div class="login-logo"><img src="' . e(setting('site_logo') ? uploads_url(setting('site_logo')) : asset_url('img/logo.png')) . '" alt="' . e(setting('site_name')) . '"></div>
                <h1><i class="fa-solid fa-lock"></i> অ্যাডমিন লগইন</h1>
                <p>ডিফল্ট: <code>admin</code> / <code>Admin@123</code> — লগইন করে পাসওয়ার্ড বদলান</p>
                <form id="adminLoginForm" data-ajax-form data-url="' . e(url('admin/login')) . '" data-prevent-default>
                    ' . csrf_field() . '
                    <div class="form-group"><label><i class="fa-solid fa-user"></i> ইউজারনেম</label><input type="text" name="username" required autocomplete="username"></div>
                    <div class="form-group"><label><i class="fa-solid fa-key"></i> পাসওয়ার্ড</label><input type="password" name="password" required autocomplete="current-password"></div>
                    <button type="submit" class="btn btn-primary btn-lg" style="width:100%"><i class="fa-solid fa-right-to-bracket"></i> লগইন করুন</button>
                </form>
                <a href="' . e(url('')) . '" data-link class="back-link"><i class="fa-solid fa-house"></i> সাইটে ফিরে যান</a>
            </div>
        </section>';
    }
}

if (!function_exists('admin_page')) {
    function admin_page(string $page, $id = null): string
    {
        if ($page === 'login') return admin_login_page();
        if (!is_admin_logged_in()) {
            header('Location: ' . url('admin/login'));
            exit;
        }

        switch ($page) {
            case 'dashboard': return admin_dashboard();
            case 'reports': return admin_reports();
            case 'report':
                if (!empty($_GET['del_img'])) {
                    $img = db_fetch('SELECT * FROM report_images WHERE id=? AND report_id=?', [(int) $_GET['del_img'], (int) $id]);
                    if ($img) {
                        $file = ROOT_PATH . '/' . $img['file'];
                        if (is_file($file)) @unlink($file);
                        db_query('DELETE FROM report_images WHERE id=?', [(int) $_GET['del_img']]);
                    }
                    header('Location: ' . url('admin/report/' . (int) $id));
                    exit;
                }
                return admin_report_edit((int) $id);
            case 'payments':
                if (!empty($_GET['approve'])) {
                    db_update('payments', ['status' => 'approved'], 'id = ?', [(int) $_GET['approve']]);
                    header('Location: ' . url('admin/payments'));
                    exit;
                }
                if (!empty($_GET['reject'])) {
                    db_update('payments', ['status' => 'rejected'], 'id = ?', [(int) $_GET['reject']]);
                    header('Location: ' . url('admin/payments'));
                    exit;
                }
                return admin_payments();
            case 'payment-methods': return admin_payment_methods();
            case 'settings': return admin_settings();
            case 'gallery':
                if (!empty($_GET['delete'])) {
                    $img = db_fetch('SELECT * FROM gallery WHERE id=?', [(int) $_GET['delete']]);
                    if ($img) {
                        $file = ROOT_PATH . '/' . $img['file'];
                        if (is_file($file)) @unlink($file);
                        db_query('DELETE FROM gallery WHERE id=?', [(int) $_GET['delete']]);
                    }
                    header('Location: ' . url('admin/gallery'));
                    exit;
                }
                return admin_gallery();
            case 'analytics': return admin_analytics();
            case 'change-password': return admin_change_password_page();
            case 'logout':
                admin_logout();
                header('Location: ' . url('admin/login'));
                exit;
            default: return admin_dashboard();
        }
    }
}

if (!function_exists('admin_dashboard')) {
    function admin_dashboard(): string
    {
        $s = admin_stats();
        $recent = db_all('SELECT * FROM reports ORDER BY id DESC LIMIT 6');
        $recentHtml = '';
        foreach ($recent as $r) {
            $recentHtml .= '<tr>
                <td>' . e($r['report_no']) . '</td>
                <td>' . e($r['name']) . '</td>
                <td><span class="badge badge-status">' . e(status_label($r['status'])) . '</span></td>
                <td>' . e(time_ago($r['created_at'])) . '</td>
                <td><a href="' . e(url('admin/report/' . $r['id'])) . '" data-link><i class="fa-solid fa-eye"></i></a></td>
            </tr>';
        }
        return '<div class="admin-content">
            <div class="admin-heading"><h1><i class="fa-solid fa-gauge-high"></i> ড্যাশবোর্ড</h1><p>সারসংক্ষেপ ও দ্রুত পর্যবেক্ষণ</p></div>
            <div class="stat-grid">
                <div class="stat-card"><i class="fa-solid fa-file-lines"></i><div><strong>' . bn_number($s['reports_total']) . '</strong><span>মোট রিপোর্ট</span></div></div>
                <div class="stat-card pending"><i class="fa-solid fa-hourglass-half"></i><div><strong>' . bn_number($s['reports_pending']) . '</strong><span>পেন্ডিং</span></div></div>
                <div class="stat-card approved"><i class="fa-solid fa-circle-check"></i><div><strong>' . bn_number($s['reports_approved']) . '</strong><span>অনুমোদিত</span></div></div>
                <div class="stat-card money"><i class="fa-solid fa-coins"></i><div><strong>' . e(format_money($s['payments_amount'])) . '</strong><span>মোট অনুদান</span></div></div>
                <div class="stat-card visitors"><i class="fa-solid fa-users"></i><div><strong>' . bn_number($s['visitors_today']) . '</strong><span>আজকের ভিজিটর</span></div></div>
            </div>
            <div class="admin-card">
                <div class="admin-card-head"><h2><i class="fa-solid fa-clock-rotate-left"></i> সাম্প্রতিক রিপোর্ট</h2><a href="' . e(url('admin/reports')) . '" data-link class="btn btn-soft btn-sm">সব দেখুন</a></div>
                <div class="table-wrap"><table><thead><tr><th>আইডি</th><th>নাম</th><th>স্ট্যাটাস</th><th>সময়</th><th></th></tr></thead><tbody>' . ($recentHtml ?: '<tr><td colspan="5" class="text-center">কোনো রিপোর্ট নেই</td></tr>') . '</tbody></table></div>
            </div>
        </div>';
    }
}

if (!function_exists('admin_reports')) {
    function admin_reports(): string
    {
        $status = $_GET['status'] ?? '';
        $q = trim($_GET['q'] ?? '');
        $where = '1=1';
        $params = [];
        if (in_array($status, ['pending','approved','rejected'], true)) {
            $where .= ' AND status=?'; $params[] = $status;
        }
        if ($q !== '') {
            $where .= ' AND (report_no LIKE ? OR name LIKE ?)';
            $params[] = '%' . $q . '%'; $params[] = '%' . $q . '%';
        }
        $perPage = 30;
        $current = max(1, (int) ($_GET['page'] ?? 1));
        $total = (int) db_value("SELECT COUNT(*) c FROM reports WHERE $where", $params, 0);
        $pg = paginate($total, $perPage, $current);
        $rows = db_all("SELECT * FROM reports WHERE $where ORDER BY id DESC LIMIT $perPage OFFSET {$pg['offset']}", $params);

        $statusOpts = '';
        foreach (['pending'=>'পেন্ডিং','approved'=>'অনুমোদিত','rejected'=>'বাতিল'] as $k=>$v) {
            $statusOpts .= '<option value="' . $k . '"' . ($status===$k ? ' selected':'') . '>' . $v . '</option>';
        }
        $rowsHtml = '';
        foreach ($rows as $r) {
            $rowsHtml .= '<tr>
                <td>' . e($r['report_no']) . '</td>
                <td>' . e($r['name']) . '</td>
                <td>' . e($r['district_name'] ?: '—') . '</td>
                <td>' . e($r['crime_type']) . '</td>
                <td><span class="badge badge-' . e($r['status']) . '">' . e(status_label($r['status'])) . '</span></td>
                <td><a href="' . e(url('admin/report/' . $r['id'])) . '" data-link class="btn btn-soft btn-sm"><i class="fa-solid fa-eye"></i></a></td>
            </tr>';
        }
        return '<div class="admin-content">
            <div class="admin-heading"><h1><i class="fa-solid fa-file-lines"></i> রিপোর্টসমূহ</h1><p>সমস্ত রিপোর্ট দেখুন, এডিট, অ্যাপ্রুভ বা বাতিল করুন</p></div>
            <form class="filter-bar" method="get" action="' . e(url('admin/reports')) . '">
                <input type="text" name="q" placeholder="আইডি বা নাম খুঁজুন" value="' . e($q) . '">
                <select name="status"><option value="">সব স্ট্যাটাস</option>' . $statusOpts . '</select>
                <button class="btn btn-primary btn-sm"><i class="fa-solid fa-filter"></i> ফিল্টার</button>
            </form>
            <div id="adminReportsTable" class="admin-card">
                <div class="table-wrap"><table><thead><tr><th>আইডি</th><th>নাম</th><th>জেলা</th><th>অপরাধ</th><th>স্ট্যাটাস</th><th></th></tr></thead><tbody>' . ($rowsHtml ?: '<tr><td colspan="6" class="text-center">কোনো রিপোর্ট পাওয়া যায়নি</td></tr>') . '</tbody></table></div>
                ' . paginate_links($pg['pages'], $pg['current'], 'admin/reports') . '
            </div>
        </div>';
    }
}

if (!function_exists('admin_report_edit')) {
    function admin_report_edit(int $id): string
    {
        $row = db_fetch('SELECT * FROM reports WHERE id=?', [$id]);
        if (!$row) return '<div class="admin-content"><div class="empty-state"><h3>রিপোর্ট পাওয়া যায়নি</h3></div></div>';
        $images = report_images($id);
        $imgHtml = '';
        foreach ($images as $img) {
            $imgHtml .= '<div class="gallery-thumb"><img src="' . e(uploads_url($img['file'])) . '" alt=""><a href="' . e(url('admin/report/' . $id . '?del_img=' . $img['id'])) . '" data-link class="del"><i class="fa-solid fa-trash"></i></a></div>';
        }
        $statusOptions = '';
        foreach (['pending'=>'পেন্ডিং','approved'=>'অনুমোদিত','rejected'=>'বাতিল'] as $k=>$v) {
            $statusOptions .= '<option value="' . $k . '"' . ($row['status']===$k ? ' selected':'') . '>' . $v . '</option>';
        }
        return '<div class="admin-content">
            <div class="admin-heading"><h1><i class="fa-solid fa-file-pen"></i> রিপোর্ট এডিট</h1><a href="' . e(url('admin/reports')) . '" data-link class="btn btn-ghost btn-sm"><i class="fa-solid fa-arrow-left"></i> ফিরে যান</a></div>
            <form data-ajax-form data-url="' . e(url('admin/report/' . $id)) . '" data-prevent-default class="admin-card report-edit-form">
                ' . csrf_field() . '
                <div class="form-grid two">
                    <div class="form-group"><label>রিপোর্ট নং</label><input value="' . e($row['report_no']) . '" disabled></div>
                    <div class="form-group"><label>স্ট্যাটাস</label><select name="status">' . $statusOptions . '</select></div>
                </div>
                <div class="form-grid two">
                    <div class="form-group"><label>অভিযুক্তের নাম</label><input name="name" value="' . e($row['name']) . '" required></div>
                    <div class="form-group"><label>অপরাধের ধরন</label><input name="crime_type" value="' . e($row['crime_type']) . '" required></div>
                </div>
                <div class="form-grid two">
                    <div class="form-group"><label>জেলা</label><input name="district_name" value="' . e($row['district_name']) . '"></div>
                    <div class="form-group"><label>মোবাইল</label><input name="mobile" value="' . e($row['mobile']) . '"></div>
                </div>
                <div class="form-group"><label>ঠিকানা</label><textarea name="address" rows="2">' . e($row['address']) . '</textarea></div>
                <div class="form-group"><label>বিস্তারিত বিবরণ</label><textarea name="details" rows="6">' . e($row['details']) . '</textarea></div>
                <div class="form-grid two">
                    <div class="form-group"><label>টাকার পরিমাণ</label><input type="number" name="amount" value="' . e($row['amount']) . '"></div>
                    <div class="form-group"><label>প্রমাণ লিংক</label><input name="evidence_link" value="' . e($row['evidence_link']) . '"></div>
                </div>
                <div class="attachments"><h3><i class="fa-solid fa-images"></i> ছবি</h3><div class="gallery-grid">' . ($imgHtml ?: '<div class="empty-inline">ছবি নেই</div>') . '</div></div>
                <button class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> সংরক্ষণ করুন</button>
            </form>
        </div>';
    }
}

if (!function_exists('admin_payments')) {
    function admin_payments(): string
    {
        $rows = db_all('SELECT p.*, m.name AS method_name FROM payments p LEFT JOIN payment_methods m ON m.id=p.payment_method_id ORDER BY p.id DESC LIMIT 200');
        $rowsHtml = '';
        foreach ($rows as $r) {
            $thumb = $r['screenshot'] !== '' ? '<a href="' . e(uploads_url($r['screenshot'])) . '" target="_blank"><img src="' . e(uploads_url($r['screenshot'])) . '" class="pay-thumb"></a>' : '—';
            $actions = '';
            if ($r['status'] === 'pending') {
                $actions = '<a href="' . e(url('admin/payments?approve=' . $r['id'])) . '" data-link class="btn btn-soft btn-sm">অনুমোদন</a> <a href="' . e(url('admin/payments?reject=' . $r['id'])) . '" data-link class="btn btn-danger btn-sm">বাতিল</a>';
            } else {
                $actions = '<span class="lb-date">সম্পন্ন</span>';
            }
            $rowsHtml .= '<tr>
                <td>#' . $r['id'] . '</td>
                <td>' . e($r['method_name'] ?: '—') . '</td>
                <td>' . e(format_money($r['amount'])) . '</td>
                <td>' . e($r['trx_id']) . '</td>
                <td>' . $thumb . '</td>
                <td><span class="badge badge-' . e($r['status']) . '">' . e(status_label($r['status'])) . '</span></td>
                <td>' . e(time_ago($r['created_at'])) . '</td>
                <td>' . $actions . '</td>
            </tr>';
        }
        return '<div class="admin-content">
            <div class="admin-heading"><h1><i class="fa-solid fa-money-bill-transfer"></i> পেমেন্টসমূহ</h1><p>অনুদানের যাবতীয় তথ্য</p></div>
            <div class="admin-card"><div class="table-wrap"><table><thead><tr><th>ID</th><th>মেথড</th><th>টাকা</th><th>TRX</th><th>স্ক্রিনশট</th><th>স্ট্যাটাস</th><th>সময়</th><th>অ্যাকশন</th></tr></thead><tbody>' . ($rowsHtml ?: '<tr><td colspan="8" class="text-center">কোনো পেমেন্ট নেই</td></tr>') . '</tbody></table></div></div>
        </div>';
    }
}

if (!function_exists('admin_payment_methods')) {
    function admin_payment_methods(): string
    {
        $rows = db_all('SELECT * FROM payment_methods ORDER BY sort_order ASC, id ASC');
        $rowsHtml = '';
        foreach ($rows as $r) {
            $logo = $r['logo'] !== '' ? '<img src="' . e(uploads_url($r['logo'])) . '">' : '<i class="fa-solid fa-wallet"></i>';
            $rowsHtml .= '<tr>
                <td>' . e($r['name']) . '</td>
                <td class="pm-show">' . $logo . '</td>
                <td>' . e($r['address']) . '</td>
                <td><span class="badge badge-' . ($r['status'] ? 'approved':'rejected') . '">' . ($r['status'] ? 'সক্রিয়':'নিষ্ক্রিয়') . '</span></td>
                <td><a href="' . e(url('admin/payment-methods?id=' . $r['id'])) . '" data-link class="btn btn-soft btn-sm">এডিট</a></td>
            </tr>';
        }
        $edit = isset($_GET['id']) && is_numeric($_GET['id']) ? db_fetch('SELECT * FROM payment_methods WHERE id=?', [(int)$_GET['id']]) : null;
        $editId = $edit ? $edit['id'] : 0;
        $action = $editId ? url('admin/payment-methods/' . $editId) : url('admin/payment-methods');
        $formName = $edit ? $edit['name'] : '';
        $formAddr = $edit ? $edit['address'] : '';
        $formInst = $edit ? $edit['instructions'] : '';
        $formColor = $edit ? $edit['color'] : '#10b981';
        $formType = $edit ? $edit['type'] : 'mobile';
        $formStatus = $edit ? $edit['status'] : 1;
        $typeOpts = '';
        foreach (['mobile'=>'মোবাইল পেমেন্ট','crypto'=>'ক্রিপ্টো','bank'=>'ব্যাংক','other'=>'অন্যান্য'] as $k=>$v) {
            $typeOpts .= '<option value="'.$k.'"'.($formType===$k?' selected':'').'>'.$v.'</option>';
        }
        return '<div class="admin-content">
            <div class="admin-heading"><h1><i class="fa-solid fa-wallet"></i> পেমেন্ট মেথড</h1><p>অ্যাডমিন ইচ্ছা মতো পেমেন্ট মেথড ও ঠিকানা যোগ করতে পারবে</p></div>
            <div class="admin-grid">
                <div class="admin-card"><div class="table-wrap"><table><thead><tr><th>মেথড</th><th>লোগো</th><th>ঠিকানা</th><th>স্ট্যাটাস</th><th></th></tr></thead><tbody>' . ($rowsHtml ?: '<tr><td colspan="5" class="text-center">কোনো মেথড নেই</td></tr>') . '</tbody></table></div></div>
                <div class="admin-card">
                    <h2>' . ($editId ? '<i class="fa-solid fa-pen"></i> মেথড এডিট' : '<i class="fa-solid fa-plus"></i> নতুন মেথড') . '</h2>
                    <form data-ajax-form data-url="' . e($action) . '" data-prevent-default>
                        ' . csrf_field() . '
                        <div class="form-group"><label>পেমেন্ট মেথডের নাম</label><input name="name" value="' . e($formName) . '" required placeholder="বিকাশ, রকেট, নগদ, USDT, Binance, Bank"></div>
                        <div class="form-group"><label>ধরন</label><select name="type">' . $typeOpts . '</select></div>
                        <div class="form-group"><label>Address / নম্বর</label><input name="address" value="' . e($formAddr) . '" required></div>
                        <div class="form-group"><label>নির্দেশনা</label><textarea name="instructions" rows="3">' . e($formInst) . '</textarea></div>
                        <div class="form-group"><label>লোগো <small>(ঐচ্ছিক)</small></label><input type="file" name="logo_file" accept="image/*"></div>
                        <div class="form-grid two">
                            <div class="form-group"><label>রং</label><input type="color" name="color" value="' . e($formColor) . '"></div>
                            <div class="form-group"><label>স্ট্যাটাস</label><select name="status"><option value="1"' . ($formStatus==1?' selected':'') . '>সক্রিয়</option><option value="0"' . ($formStatus==0?' selected':'') . '>নিষ্ক্রিয়</option></select></div>
                        </div>
                        <button class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> সংরক্ষণ</button>
                    </form>
                </div>
            </div>
        </div>';
    }
}

if (!function_exists('admin_settings')) {
    function admin_settings(): string
    {
        $fields = default_site_settings();
        $groups = [
            'সাধারণ সেটিং' => ['site_name','site_subtitle','site_description','site_keywords','site_slogan','items_per_page','show_pagination'],
            'যোগাযোগ ও সোশ্যাল' => ['site_email','site_location','contact_address','contact_email','contact_phone','facebook_url','twitter_url','instagram_url','youtube_url','tiktok_url','whatsapp_number'],
            'ফুটার' => ['site_footer'],
            'গোপনীয়তা টেক্সট' => ['privacy_approved_text'],
        ];
        $html = '<div class="admin-content"><div class="admin-heading"><h1><i class="fa-solid fa-gear"></i> সেটিংস</h1><p>সাইট নাম, ফুটার, ফেভিকন, লোগো, মেটা ট্যাগ, সাইট SEO সব পরিবর্তন করুন</p></div><form data-ajax-form data-url="' . e(url('admin/settings')) . '" data-prevent-default>';
        $html .= csrf_field();
        foreach ($groups as $title => $keys) {
            $html .= '<div class="admin-card"><h2><i class="fa-solid fa-sliders"></i> ' . e($title) . '</h2><div class="form-grid two">';
            foreach ($keys as $key) {
                $type = 'text';
                if (in_array($key, ['site_description','site_footer','privacy_approved_text'], true)) $type = 'textarea';
                $val = setting($key, $fields[$key] ?? '');
                if ($key === 'items_per_page' || $key === 'show_pagination') $type = 'number';
                $html .= '<div class="form-group"><label>' . e(str_replace('_',' ',$key)) . '</label>';
                if ($type === 'textarea') {
                    $html .= '<textarea name="' . e($key) . '" rows="3">' . e($val) . '</textarea>';
                } else {
                    $html .= '<input type="' . $type . '" name="' . e($key) . '" value="' . e($val) . '">';
                }
                $html .= '</div>';
            }
            $html .= '</div></div>';
        }
        $logo = setting('site_logo');
        $favicon = setting('site_favicon');
        $metaImage = setting('site_meta_image');
        $html .= '<div class="admin-card"><h2><i class="fa-solid fa-image"></i> লোগো, ফেবিকন ও মেটা ইমেজ</h2>
            <div class="upload-line"><span>লোগো</span><input type="file" name="site_logo_file" accept="image/*"><input type="hidden" name="site_logo_value" value="' . e($logo) . '">' . ($logo?'<img class="admin-thumb" src="'.e(uploads_url($logo)).'">':'') . '</div>
            <div class="upload-line"><span>ফেবিকন</span><input type="file" name="site_favicon_file" accept="image/*"><input type="hidden" name="site_favicon_value" value="' . e($favicon) . '">' . ($favicon?'<img class="admin-thumb" src="'.e(uploads_url($favicon)).'">':'') . '</div>
            <div class="upload-line"><span>মেটা ইমেজ</span><input type="file" name="site_meta_image_file" accept="image/*"><input type="hidden" name="site_meta_image_value" value="' . e($metaImage) . '">' . ($metaImage?'<img class="admin-thumb" src="'.e(uploads_url($metaImage)).'">':'') . '</div>
        </div>';
        $html .= '<button class="btn btn-primary btn-lg"><i class="fa-solid fa-floppy-disk"></i> সেটিং সংরক্ষণ করুন</button></form></div>';
        return $html;
    }
}

if (!function_exists('admin_gallery')) {
    function admin_gallery(): string
    {
        $rows = db_all('SELECT * FROM gallery ORDER BY id DESC LIMIT 200');
        $html = '<div class="admin-content"><div class="admin-heading"><h1><i class="fa-solid fa-images"></i> গ্যালারি</h1><p>পেমেন্ট মেথড লোগো ও সাইট ইমেজ সংরক্ষণ</p></div>
            <div class="admin-card"><form data-ajax-form data-url="' . e(url('admin/gallery')) . '" data-prevent-default class="inline-upload">
                ' . csrf_field() . '
                <input type="text" name="title" placeholder="ছবির নাম (ঐচ্ছিক)">
                <input type="file" name="gallery_file" accept="image/*" required>
                <button class="btn btn-primary"><i class="fa-solid fa-upload"></i> আপলোড</button>
            </form></div>
            <div class="gallery-grid">';
        foreach ($rows as $r) {
            $html .= '<div class="gallery-item"><img src="' . e(uploads_url($r['file'])) . '" alt="' . e($r['title']) . '" loading="lazy"><div class="g-item-actions"><button class="btn btn-soft btn-sm" data-copy="' . e(uploads_url($r['file'])) . '"><i class="fa-solid fa-link"></i></button><a href="' . e(url('admin/gallery?delete=' . $r['id'])) . '" data-link class="btn btn-danger btn-sm"><i class="fa-solid fa-trash"></i></a></div></div>';
        }
        $html .= '</div></div>';
        return $html;
    }
}

if (!function_exists('admin_analytics')) {
    function admin_analytics(): string
    {
        $s = admin_stats();
        $todayRows = db_all('SELECT * FROM analytics WHERE visit_date=CURDATE() ORDER BY id DESC LIMIT 30');
        $rows = db_all('SELECT * FROM analytics ORDER BY id DESC LIMIT 100');
        $byPage = db_all('SELECT page, COUNT(*) total FROM analytics GROUP BY page ORDER BY total DESC LIMIT 20');
        $byLocation = db_all('SELECT location, COUNT(*) total FROM analytics GROUP BY location ORDER BY total DESC LIMIT 20');
        $pageHtml = '';
        foreach ($byPage as $r) $pageHtml .= '<tr><td>' . e($r['page']) . '</td><td>' . bn_number($r['total']) . '</td></tr>';
        $locHtml = '';
        foreach ($byLocation as $r) $locHtml .= '<tr><td>' . e($r['location'] ?: 'অজানা') . '</td><td>' . bn_number($r['total']) . '</td></tr>';
        $rowHtml = '';
        foreach ($todayRows as $r) {
            $rowHtml .= '<tr><td>' . e($r['visitor_id']) . '</td><td>' . e($r['device']) . '</td><td>' . e($r['browser']) . '</td><td>' . e($r['os']) . '</td><td>' . e($r['location'] ?: '—') . '</td><td>' . e($r['page']) . '</td><td>' . e($r['created_at']) . '</td></tr>';
        }
        return '<div class="admin-content">
            <div class="admin-heading"><h1><i class="fa-solid fa-chart-pie"></i> অ্যানালিটিকস</h1><p>ভিজিটর, পেজ ভিউ, লোকেশন ও ডিভাইস ট্র্যাকিং</p></div>
            <div class="stat-grid">
                <div class="stat-card"><i class="fa-solid fa-eye"></i><div><strong>' . bn_number($s['visitors_total']) . '</strong><span>মোট ভিজিটর</span></div></div>
                <div class="stat-card approved"><i class="fa-solid fa-person"></i><div><strong>' . bn_number($s['visitors_today']) . '</strong><span>আজকের ভিজিটর</span></div></div>
                <div class="stat-card pending"><i class="fa-solid fa-device-mobile"></i><div><strong>' . bn_number($s['reports_pending']) . '</strong><span>পেন্ডিং রিপোর্ট</span></div></div>
            </div>
            <div class="admin-grid">
                <div class="admin-card"><h2><i class="fa-solid fa-chart-line"></i> পেজ ভিউ</h2><div class="table-wrap"><table><thead><tr><th>পেজ</th><th>ভিউ</th></tr></thead><tbody>' . $pageHtml . '</tbody></table></div></div>
                <div class="admin-card"><h2><i class="fa-solid fa-location-dot"></i> লোকেশন</h2><div class="table-wrap"><table><thead><tr><th>লোকেশন</th><th>ভিজিটর</th></tr></thead><tbody>' . $locHtml . '</tbody></table></div></div>
            </div>
            <div class="admin-card"><h2><i class="fa-solid fa-bolt"></i> আজকের ভিজিটর ডিটেইলস</h2><div class="table-wrap"><table><thead><tr><th>Visitor</th><th>ডিভাইস</th><th>ব্রাউজার</th><th>OS</th><th>লোকেশন</th><th>পেজ</th><th>সময়</th></tr></thead><tbody>' . $rowHtml . '</tbody></table></div></div>
        </div>';
    }
}

if (!function_exists('admin_change_password_page')) {
    function admin_change_password_page(): string
    {
        return '<div class="admin-content"><div class="admin-heading"><h1><i class="fa-solid fa-key"></i> পাসওয়ার্ড পরিবর্তন</h1><p>নিরাপত্তার জন্য নিয়মিত পাসওয়ার্ড বদলান</p></div>
            <div class="admin-card narrow"><form data-ajax-form data-url="' . e(url('admin/change-password')) . '" data-prevent-default>
                ' . csrf_field() . '
                <div class="form-group"><label>নতুন পাসওয়ার্ড</label><input type="password" name="password" required minlength="8"></div>
                <div class="form-group"><label>পুনরায় নতুন পাসওয়ার্ড</label><input type="password" name="password_confirm" required minlength="8"></div>
                <button class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> পরিবর্তন করুন</button>
            </form></div></div>';
    }
}
