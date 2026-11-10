<?php

function tables_all(bool $onlyActive = false): array
{
    return fetch_all('SELECT * FROM dining_tables' . ($onlyActive ? ' WHERE active = 1' : '') . ' ORDER BY code');
}

function tables_with_open_flag(): array
{
    return fetch_all('SELECT t.*, (SELECT COUNT(*) FROM table_sessions s WHERE s.table_id = t.id AND s.closed_at IS NULL) AS is_open
                      FROM dining_tables t WHERE t.active = 1 ORDER BY t.code');
}
function table_find(int $id): ?array
{
    return fetch_one('SELECT * FROM dining_tables WHERE id = ?', [$id]);
}
function table_by_code(string $code): ?array
{
    return fetch_one('SELECT * FROM dining_tables WHERE UPPER(code) = UPPER(?)', [$code]);
}
function table_save(?int $id, string $code, string $name, int $seats, bool $active): int
{
    if ($id) {
        q('UPDATE dining_tables SET code = ?, name = ?, seats = ?, active = ? WHERE id = ?', [$code, $name, $seats, $active ? 1 : 0, $id]);
        return $id;
    }
    q('INSERT INTO dining_tables (code, name, seats, active, qr_key, join_code) VALUES (?,?,?,?,?,?)', [$code, $name, $seats, $active ? 1 : 0, random_key(6), str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT)]);
    return insert_id();
}
function random_key(int $n): string
{
    return substr(bin2hex(random_bytes($n)), 0, $n);
}
function table_regenerate_qr(int $id): void
{
    q('UPDATE dining_tables SET qr_key = ? WHERE id = ?', [random_key(6), $id]);
}

function session_open_of(int $tableId): ?array
{
    return fetch_one('SELECT * FROM table_sessions WHERE table_id = ? AND closed_at IS NULL', [$tableId]);
}
function session_find(int $id): ?array
{
    return fetch_one('SELECT * FROM table_sessions WHERE id = ?', [$id]);
}
function sessions_open(): array
{
    return fetch_all('SELECT * FROM table_sessions WHERE closed_at IS NULL');
}

function session_open(int $tableId, int $userId): int
{
    $ban = table_find($tableId);
    $code = $ban['join_code']; // mã 4 số cố định của bàn (cột join_code)
    try {
        q('INSERT INTO table_sessions (table_id, join_code, opened_by, opened_at, open_flag) VALUES (?,?,?,?,1)', [$tableId, $code, $userId, now()]);
    } catch (PDOException $e) {
        throw new AppError('Bàn này đã có phiên đang mở.');
    }
    return insert_id();
}
function session_close(int $id): void
{
    q('UPDATE table_sessions SET closed_at = ?, open_flag = NULL WHERE id = ?', [now(), $id]);
}


function table_status(?array $session): string
{
    if (!$session) {
        return 'free';
    }
    $inv = invoice_of_session((int) $session['id']);
    if (!$inv) {
        return 'serving';
    }
    return $inv['status'] === 'paid' ? 'paid' : 'awaiting-payment';
}
const TABLE_STATUS_LABEL = ['free' => 'Trống', 'serving' => 'Đang phục vụ', 'awaiting-payment' => 'Chờ thanh toán', 'paid' => 'Đã thanh toán'];
const TABLE_STATUS_TONE = ['free' => 'neu', 'serving' => 'acc', 'awaiting-payment' => 'warn', 'paid' => 'pos'];
