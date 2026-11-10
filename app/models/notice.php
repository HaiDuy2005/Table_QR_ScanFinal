<?php
function notices_open(): array
{
    return fetch_all('SELECT n.*, t.name AS table_name FROM notices n JOIN dining_tables t ON t.id = n.table_id
                      JOIN table_sessions s ON s.id = n.session_id AND s.closed_at IS NULL
                      WHERE n.handled = 0 ORDER BY n.id');
}
function notice_add(int $sessionId, int $tableId, string $kind, string $text): void
{
    if (fetch_one('SELECT id FROM notices WHERE session_id = ? AND kind = ? AND handled = 0', [$sessionId, $kind])) {
        return; 
    }
    q('INSERT INTO notices (session_id, table_id, kind, text, handled, created_at) VALUES (?,?,?,?,0,?)', [$sessionId, $tableId, $kind, $text, now()]);
}
function notice_find(int $id): ?array
{
    return fetch_one('SELECT * FROM notices WHERE id = ?', [$id]);
}
function notice_handle(int $id): void
{
    q('UPDATE notices SET handled = 1 WHERE id = ?', [$id]);
}
