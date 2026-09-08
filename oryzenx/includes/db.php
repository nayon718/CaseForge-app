<?php
/** Oryzenx — MySQL connection (PDO) */
declare(strict_types=1);

function db(): ?PDO
{
    static $pdo = null;
    static $tried = false;
    if ($pdo instanceof PDO) return $pdo;
    if ($tried) return null;
    $tried = true;

    $c = cfg('db', []);
    if (empty($c['name'])) return null;

    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $c['host'] ?? 'localhost', $c['port'] ?? '3306', $c['name']);
    try {
        $pdo = new PDO($dsn, $c['user'] ?? 'root', $c['pass'] ?? '', [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        return $pdo;
    } catch (Throwable $ex) {
        error_log('[oryzenx] DB connection failed: ' . $ex->getMessage());
        return null;
    }
}

/** Create tables (used by /setup) */
function db_install(): array
{
    $pdo = db();
    if (!$pdo) return ['ok' => false, 'msg' => 'Database connection failed. Check config.php.'];
    $sql = (string)file_get_contents(ORY_ROOT . '/database/schema.sql');
    try {
        foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
            $pdo->exec($stmt);
        }
        return ['ok' => true, 'msg' => 'Tables created successfully.'];
    } catch (Throwable $ex) {
        return ['ok' => false, 'msg' => $ex->getMessage()];
    }
}
