<?php
/**
 * অনুদান পেমেন্ট তথ্য জমা
 */

if (!function_exists('payment_submit_action')) {
    function payment_submit_action(): array
    {
        header('Content-Type: application/json; charset=utf-8');

        $csrf = require_csrf();
        if ($csrf) return $csrf;

        $methodId = (int) ($_POST['payment_method_id'] ?? 0);
        $trxId = trim($_POST['trx_id'] ?? '');
        $amount = trim($_POST['amount'] ?? '');
        $senderName = trim($_POST['sender_name'] ?? '');
        $screenshot = $_POST['payment_screenshot'] ?? '';

        if ($methodId < 1 || $amount === '' || !is_numeric($amount) || (float)$amount <= 0) {
            return ['ok' => false, 'message' => 'পেমেন্ট মেথড ও টাকার পরিমাণ সঠিকভাবে দিন।'];
        }

        $images = [];
        if (is_string($screenshot) && $screenshot !== '') {
            $res = save_base64_image($screenshot, 'payments', 2097152);
            if ($res['ok']) $images[] = $res['path'];
        }

        $paymentId = db_insert('payments', [
            'payment_method_id' => $methodId,
            'user_name' => $senderName,
            'amount' => (float)$amount,
            'trx_id' => $trxId,
            'note' => '',
            'screenshot' => $images[0] ?? '',
            'status' => 'pending',
            'ip_address' => client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return [
            'ok' => true,
            'message' => 'ধন্যবাদ! আপনার পেমেন্ট তথ্য জমা হয়েছে। অ্যাডমিন যাচাই করে শীঘ্রই নিশ্চিত করবেন।',
            'data' => ['payment_id' => $paymentId],
        ];
    }
}
