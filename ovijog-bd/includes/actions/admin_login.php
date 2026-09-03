<?php
/**
 * অ্যাডমিন লগইন
 */

if (!function_exists('admin_login_action')) {
    function admin_login_action(): array
    {
        header('Content-Type: application/json; charset=utf-8');
        $csrf = require_csrf();
        if ($csrf) return $csrf;

        $username = trim($_POST['username'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        if ($username === '' || $password === '') {
            return ['ok' => false, 'message' => 'ইউজারনেম ও পাসওয়ার্ড দিন।'];
        }

        $rateKey = 'login_' . client_ip();
        $_SESSION[$rateKey] = ($_SESSION[$rateKey] ?? 0) + 1;
        if ($_SESSION[$rateKey] > 15) {
            return ['ok' => false, 'message' => 'অনেকবার চেষ্টা হয়েছে। একটু পরে চেষ্টা করুন।'];
        }

        if (!attempt_login($username, $password)) {
            return ['ok' => false, 'message' => 'ইউজারনেম অথবা পাসওয়ার্ড ভুল।'];
        }
        return ['ok' => true, 'message' => 'লগইন সফল', 'data' => ['redirect' => url('admin/dashboard')]];
    }
}
