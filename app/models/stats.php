<?php

function stats_today(): array
{
    $from = date('Y-m-d 00:00:00');
    $rev = fetch_one('SELECT COALESCE(SUM(amount),0) AS s, COUNT(*) AS n FROM payments WHERE paid_at >= ?', [$from]);
    return [
        'revenue' => (int) $rev['s'],
        'paid_invoices' => (int) $rev['n'],
        'orders' => (int) fetch_one('SELECT COUNT(*) AS n FROM orders WHERE created_at >= ?', [$from])['n'],
        'menu_available' => (int) fetch_one('SELECT COUNT(*) AS n FROM menu_items WHERE available = 1')['n'],
        'menu_total' => (int) fetch_one('SELECT COUNT(*) AS n FROM menu_items')['n'],
        'users_active' => (int) fetch_one('SELECT COUNT(*) AS n FROM users WHERE locked = 0')['n'],
        'users_total' => (int) fetch_one('SELECT COUNT(*) AS n FROM users')['n'],
        'open_tables' => (int) fetch_one('SELECT COUNT(*) AS n FROM table_sessions WHERE closed_at IS NULL')['n'],
    ];
}
