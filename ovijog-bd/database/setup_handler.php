<?php
/**
 * সেটআপ হ্যান্ডলার — প্রথমবার database/tables তৈরি করার জন্য
 */

$setupResult = null;
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $installed = db_install(__DIR__ . '/schema.sql');
    if ($installed['ok']) {
        // নিশ্চিত ডিফল্ট পাসওয়ার্ড: Admin@123
        try {
            db_query('UPDATE users SET password_hash=? WHERE username=?', [password_hash('Admin@123', PASSWORD_DEFAULT), 'admin']);
        } catch (Throwable $e) {}
        $setupResult = [
            'ok' => true,
            'msg' => 'ডাটাবেজ টেবিল ও ডিফল্ট ডেটা সফলভাবে তৈরি হয়েছে। ডিফল্ট অ্যাডমিন: admin / Admin@123'
        ];
    } else {
        $setupResult = ['ok' => false, 'msg' => $installed['msg']];
    }
} else {
    $setupResult = ['ok' => true, 'msg' => 'সেটআপ ফর্ম প্রস্তুত।'];
    // form shows on GET; we want not show success yet
    $setupResult = null;
}
