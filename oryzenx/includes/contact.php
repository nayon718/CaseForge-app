<?php
/** Oryzenx — contact form handler (returns JSON) */
declare(strict_types=1);

function handle_contact(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        json_out(['ok' => false, 'message' => 'Method not allowed.'], 405);
    }

    // Honeypot: bots fill hidden fields
    if (trim((string)($_POST['website'] ?? '')) !== '') {
        json_out(['ok' => true, 'message' => 'Thank you!']);
    }

    $name    = trim((string)($_POST['name'] ?? ''));
    $email   = trim((string)($_POST['email'] ?? ''));
    $phone   = trim((string)($_POST['phone'] ?? ''));
    $subject = trim((string)($_POST['subject'] ?? 'Website inquiry'));
    $message = trim((string)($_POST['message'] ?? ''));

    if ($name === '' || $email === '' || $message === '') {
        json_out(['ok' => false, 'message' => 'Name, email and message are required.'], 422);
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_out(['ok' => false, 'message' => 'Please enter a valid email address.'], 422);
    }
    if (mb_strlen($message) > 5000) {
        json_out(['ok' => false, 'message' => 'Message is too long.'], 422);
    }

    $stored = false;
    if ($pdo = db()) {
        try {
            $pdo->prepare(
                'INSERT INTO messages (name,email,phone,subject,message,ip,user_agent)
                 VALUES (?,?,?,?,?,?,?)'
            )->execute([
                $name, $email, $phone ?: null, $subject ?: null, $message,
                $_SERVER['REMOTE_ADDR'] ?? null,
                mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
            ]);
            $stored = true;
        } catch (Throwable $ex) {
            error_log('[oryzenx] message insert failed: ' . $ex->getMessage());
        }
    }

    if (!$stored) { // fallback so no lead is ever lost
        @file_put_contents(
            ORY_ROOT . '/storage/messages.log',
            json_encode(compact('name', 'email', 'phone', 'subject', 'message') + ['at' => date('c')], JSON_UNESCAPED_UNICODE) . PHP_EOL,
            FILE_APPEND
        );
    }

    if (cfg('send_mail')) {
        $to      = (string)cfg('mail_to');
        $body    = "Name: $name\nEmail: $email\nPhone: $phone\nSubject: $subject\n\n$message\n";
        $headers = 'From: ' . cfg('mail_from') . "\r\nReply-To: $email\r\nContent-Type: text/plain; charset=utf-8";
        @mail($to, 'Portfolio message: ' . $subject, $body, $headers);
    }

    json_out(['ok' => true, 'message' => 'ধন্যবাদ! Your message has been sent — I will reply soon.']);
}
