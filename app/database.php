<?php

function db(): PDO
{
    static $pdo = null;
    if ($pdo) {
        return $pdo;
    }
    $cfg = require __DIR__ . '/config/config.php';
    try {
        $dsn = getenv('RQR_DSN') ?: "mysql:host={$cfg['db_host']};port={$cfg['db_port']};dbname={$cfg['db_name']};charset=utf8mb4";
        $pdo = new PDO($dsn, $cfg['db_user'], $cfg['db_pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (PDOException $ex) {
        http_response_code(500);
        echo '<meta charset="utf-8"><body style="font-family:sans-serif;padding:32px;max-width:640px">'
            . '<h2>Chưa kết nối được cơ sở dữ liệu</h2>'
            . '<p>Hãy kiểm tra: (1) đã bấm Start cho <b>MySQL</b> trong XAMPP; (2) đã nhập file <code>database/restaurantqr.sql</code> trong phpMyAdmin; '
            . '(3) tên CSDL, user, mật khẩu trong <code>app/config/config.php</code> đúng.</p>'
            . '<p style="color:#888">Chi tiết: ' . htmlspecialchars($ex->getMessage()) . '</p></body>';
        exit;
    }
    return $pdo;
}


function q(string $sql, array $params = []): PDOStatement
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st;
}
function fetch_all(string $sql, array $params = []): array
{
    return q($sql, $params)->fetchAll();
}
function fetch_one(string $sql, array $params = []): ?array
{
    $r = q($sql, $params)->fetch();
    return $r === false ? null : $r;
}
function insert_id(): int
{
    return (int) db()->lastInsertId();
}

function transaction(callable $fn)
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $r = $fn();
        $pdo->commit();
        return $r;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}


function for_update(): string
{
    return db()->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql' ? ' FOR UPDATE' : '';
}
