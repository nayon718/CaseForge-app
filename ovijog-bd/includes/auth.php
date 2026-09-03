<?php
/**
 * অ্যাডমিন অথেনটিকেশন
 */

if (!function_exists('admin_user')) {
    function admin_user(): ?array
    {
        return $_SESSION['admin_user'] ?? null;
    }
}

if (!function_exists('is_admin_logged_in')) {
    function is_admin_logged_in(): bool
    {
        return !empty($_SESSION['admin_user']['id']);
    }
}

if (!function_exists('require_admin')) {
    function require_admin(): void
    {
        if (!is_admin_logged_in()) {
            header('Location: ' . url('admin/login'));
            exit;
        }
    }
}

if (!function_exists('attempt_login')) {
    function attempt_login(string $username, string $password): bool
    {
        $user = db_fetch('SELECT * FROM users WHERE username = ? LIMIT 1', [$username]);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return false;
        }
        session_regenerate_id(true);
        $_SESSION['admin_user'] = [
            'id' => (int) $user['id'],
            'username' => $user['username'],
            'name' => $user['name'],
            'role' => $user['role'] ?? 'admin',
        ];
        return true;
    }
}

if (!function_exists('admin_logout')) {
    function admin_logout(): void
    {
        unset($_SESSION['admin_user']);
        session_regenerate_id(true);
    }
}

if (!function_exists('admin_change_password')) {
    function admin_change_password(string $newPassword): bool
    {
        if (!is_admin_logged_in()) return false;
        db_update('users', ['password_hash' => password_hash($newPassword, PASSWORD_DEFAULT)], 'id = ?', [admin_user()['id']]);
        return true;
    }
}
