<?php
/**
 * ডাটাবেজ হেল্পার (PDO + MySQL)
 */

if (!function_exists('db')) {
    function db(): PDO
    {
        static $pdo = null;
        if ($pdo instanceof PDO) {
            return $pdo;
        }

        $host = defined('DB_HOST') ? DB_HOST : 'localhost';
        $name = defined('DB_NAME') ? DB_NAME : '';
        $user = defined('DB_USER') ? DB_USER : '';
        $pass = defined('DB_PASS') ? DB_PASS : '';

        $dsn = "mysql:host={$host};dbname={$name};charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        $pdo = new PDO($dsn, $user, $pass, $options);
        return $pdo;
    }
}

if (!function_exists('db_query')) {
    function db_query(string $sql, array $params = []): PDOStatement
    {
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
}

if (!function_exists('db_fetch')) {
    /** @return array|null */
    function db_fetch(string $sql, array $params = [])
    {
        $row = db_query($sql, $params)->fetch();
        return $row === false ? null : $row;
    }
}

if (!function_exists('db_all')) {
    function db_all(string $sql, array $params = []): array
    {
        return db_query($sql, $params)->fetchAll();
    }
}

if (!function_exists('db_value')) {
    function db_value(string $sql, array $params = [], $default = null)
    {
        $row = db_fetch($sql, $params);
        if (!$row) {
            return $default;
        }
        return reset($row) ?? $default;
    }
}

if (!function_exists('db_count')) {
    function db_count(string $table, string $where = '1=1', array $params = []): int
    {
        return (int) db_value("SELECT COUNT(*) AS c FROM `$table` WHERE $where", $params, 0);
    }
}

if (!function_exists('db_insert')) {
    function db_insert(string $table, array $data): int
    {
        if (!$data) return 0;
        $cols = array_keys($data);
        $place = [];
        $params = [];
        foreach ($cols as $i => $col) {
            $place[] = "`$col`";
            $params[] = $data[$col];
        }
        $sql = "INSERT INTO `$table` (" . implode(',', $place) . ") VALUES (" . implode(',', array_fill(0, count($cols), '?')) . ")";
        db_query($sql, $params);
        return (int) db()->lastInsertId();
    }
}

if (!function_exists('db_update')) {
    function db_update(string $table, array $data, string $where, array $whereParams = []): bool
    {
        if (!$data) return false;
        $set = [];
        $params = [];
        foreach ($data as $col => $val) {
            $set[] = "`$col` = ?";
            $params[] = $val;
        }
        $params = array_merge($params, $whereParams);
        $sql = "UPDATE `$table` SET " . implode(',', $set) . " WHERE $where";
        db_query($sql, $params);
        return true;
    }
}

if (!function_exists('db_table_exists')) {
    function db_table_exists(string $table): bool
    {
        try {
            $stmt = db()->prepare('SHOW TABLES LIKE ?');
            $stmt->execute([$table]);
            return (bool) $stmt->fetch();
        } catch (Throwable $e) {
            return false;
        }
    }
}

/**
 * ডাটাবেজ schema ইনস্টল/আপগ্রেড। install/upgrade পেজে ব্যবহৃত।
 */
if (!function_exists('db_install')) {
    function db_install(string $schemaPath): array
    {
        $sql = file_get_contents($schemaPath);
        if (!$sql) {
            return ['ok' => false, 'msg' => 'schema.sql খুঁজে পাওয়া যায়নি।'];
        }
        try {
            foreach (array_filter(array_map('trim', explode(";\n", $sql))) as $statement) {
                if (strpos($statement, '--') === 0) continue;
                db_query($statement);
            }
            // ডিফল্ট অ্যাডমিন ও সেটিংস seed ইতিমধ্যে schema-তে আছে
            return ['ok' => true];
        } catch (Throwable $e) {
            return ['ok' => false, 'msg' => $e->getMessage()];
        }
    }
}
