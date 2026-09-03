<?php
/**
 * রিপোর্ট খোঁজ — AJAX
 */

if (!function_exists('search_action')) {
    function search_action(): array
    {
        $reportNo = trim($_POST['report_no'] ?? ($_GET['report_no'] ?? ''));
        $name = trim($_POST['name'] ?? ($_GET['name'] ?? ''));
        $district = trim($_POST['district_name'] ?? ($_GET['district_name'] ?? ''));
        $crime = trim($_POST['crime_type'] ?? ($_GET['crime_type'] ?? ''));
        $page = max(1, (int) ($_POST['page'] ?? ($_GET['page'] ?? 1)));

        $where = ['status = "approved"'];
        $params = [];
        if ($reportNo !== '') {
            $where[] = 'r.report_no LIKE ?';
            $params[] = '%' . $reportNo . '%';
        }
        if ($name !== '') {
            $where[] = 'r.name LIKE ?';
            $params[] = '%' . $name . '%';
        }
        if ($district !== '') {
            $where[] = '(r.district_name LIKE ? OR r.district LIKE ?)';
            $params[] = '%' . $district . '%';
            $params[] = '%' . $district . '%';
        }
        if ($crime !== '') {
            $where[] = '(r.crime_type LIKE ? OR c.name LIKE ?)';
            $params[] = '%' . $crime . '%';
            $params[] = '%' . $crime . '%';
        }
        if (count($where) === 1) {
            return ['ok' => false, 'message' => 'অনুগ্রহ করে রিপোর্ট আইডি, নাম, জেলা অথবা অপরাধের ধরনের যেকোনো একটি লিখুন।'];
        }

        $whereSql = implode(' AND ', $where);
        $total = (int) db_value("SELECT COUNT(*) c FROM reports r LEFT JOIN categories c ON c.id=r.category_id WHERE $whereSql", $params, 0);
        if ($total === 0) {
            return ['ok' => false, 'message' => 'রিপোর্ট খুঁজে পাওয়া যায় নাই।'];
        }

        $perPage = (int) setting('items_per_page', '20');
        $pg = paginate($total, $perPage, $page);
        $rows = db_all(
            "SELECT r.*, c.name AS category_name, c.color AS category_color, c.icon AS category_icon
             FROM reports r LEFT JOIN categories c ON c.id=r.category_id
             WHERE $whereSql
             ORDER BY r.created_at DESC, r.id DESC
             LIMIT " . (int)$perPage . " OFFSET " . (int)$pg['offset'],
            $params
        );
        $rows = array_map('package_report', $rows);
        $cards = '';
        foreach ($rows as $r) {
            $cards .= report_card_html($r, ['name'=>$r['category_name'] ?? '', 'color'=>$r['category_color'] ?? '', 'icon'=>$r['category_icon'] ?? '']);
        }
        $pagination = paginate_links($pg['pages'], $pg['current'], 'search');
        $html = '<div class="search-summary"><p><i class="fa-solid fa-circle-check"></i> ' . bn_number($total) . ' টি রিপোর্ট পাওয়া গেছে</p></div><div class="reports-list">' . $cards . '</div>' . $pagination;
        return ['ok' => true, 'message' => 'সার্চ শেষ।', 'data' => ['html' => $html, 'total' => $total]];
    }
}
