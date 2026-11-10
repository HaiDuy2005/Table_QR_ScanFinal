<?php

function net_qty(array $it): int
{
    return (int) $it['qty'] - (int) $it['cancelled'];
}
function item_stage(array $it): string
{
    $n = net_qty($it);
    if ($n <= 0 && (int) $it['cancelled'] > 0) {
        return 'cancelled';
    }
    if ((int) $it['served'] >= $n) {
        return 'served';
    }
    if ((int) $it['done'] >= $n) {
        return 'ready';
    }
    if ((int) $it['done'] > 0 || (int) $it['started'] === 1) {
        return 'cooking';
    }
    return 'new';
}
const STAGE_LABEL = ['new' => 'Mới', 'cooking' => 'Đang chế biến', 'ready' => 'Đã chế biến', 'served' => 'Đã phục vụ', 'cancelled' => 'Đã hủy'];
const STAGE_TONE = ['new' => 'neu', 'cooking' => 'warn', 'ready' => 'acc', 'served' => 'pos', 'cancelled' => 'neg'];


function orders_of_session(int $sessionId): array
{
    $orders = fetch_all('SELECT * FROM orders WHERE session_id = ? ORDER BY id', [$sessionId]);
    foreach ($orders as &$o) {
        $o['items'] = fetch_all('SELECT * FROM order_items WHERE order_id = ? ORDER BY id', [$o['id']]);
    }
    return $orders;
}
function order_item_find(int $id): ?array
{
    return fetch_one('SELECT i.*, o.session_id, o.code AS order_code FROM order_items i JOIN orders o ON o.id = i.order_id WHERE i.id = ?', [$id]);
}
function session_total(int $sessionId): int
{
    $t = 0;
    foreach (orders_of_session($sessionId) as $o) {
        foreach ($o['items'] as $it) {
            $t += net_qty($it) * (int) $it['price'];
        }
    }
    return $t;
}

function pipeline_of(array $orders): array
{
    $p = ['wait' => 0, 'cook' => 0, 'ready' => 0, 'served' => 0];
    foreach ($orders as $o) {
        foreach ($o['items'] as $it) {
            $n = net_qty($it);
            if ($n <= 0) {
                continue;
            }
            $p['served'] += (int) $it['served'];
            $p['ready'] += (int) $it['done'] - (int) $it['served'];
            $left = $n - (int) $it['done'];
            if ((int) $it['started'] === 1 || (int) $it['done'] > 0) {
                $p['cook'] += $left;
            } else {
                $p['wait'] += $left;
            }
        }
    }
    $p['total'] = array_sum($p);
    return $p;
}

function order_create(int $sessionId, string $token, array $lines): array
{
    $dup = fetch_one('SELECT * FROM orders WHERE session_id = ? AND request_token = ?', [$sessionId, $token]);
    if ($dup) {
        return $dup; 
    }
    q('INSERT INTO orders (code, session_id, request_token, created_at) VALUES (?,?,?,?)', ['tmp' . random_key(8), $sessionId, $token, now()]);
    $oid = insert_id();
    $code = 'DH' . str_pad((string) $oid, 4, '0', STR_PAD_LEFT);
    q('UPDATE orders SET code = ? WHERE id = ?', [$code, $oid]);
    foreach ($lines as $l) {
        $m = $l['menu'];
        q('INSERT INTO order_items (order_id, menu_item_id, name, unit, price, qty, note) VALUES (?,?,?,?,?,?,?)', [
            $oid, $m['id'], $m['name'], $m['unit'], $m['price'], $l['qty'], $l['note'],
        ]);
    }
    return ['id' => $oid, 'code' => $code];
}

function kitchen_items(): array
{
    return fetch_all(
        "SELECT i.*, o.code AS order_code, o.created_at AS ordered_at, t.name AS table_name
         FROM order_items i JOIN orders o ON o.id = i.order_id
         JOIN table_sessions s ON s.id = o.session_id AND s.closed_at IS NULL
         JOIN dining_tables t ON t.id = s.table_id
         WHERE i.qty - i.cancelled > 0 ORDER BY o.id, i.id"
    );
}
