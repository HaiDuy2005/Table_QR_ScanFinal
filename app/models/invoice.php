<?php
function invoice_of_session(int $sessionId): ?array
{
    static $cache = [];
    return fetch_one('SELECT * FROM invoices WHERE session_id = ?', [$sessionId]);
}
function invoice_find(int $id): ?array
{
    return fetch_one('SELECT * FROM invoices WHERE id = ?', [$id]);
}
function invoice_lines(int $invoiceId): array
{
    return fetch_all('SELECT * FROM invoice_lines WHERE invoice_id = ? ORDER BY id', [$invoiceId]);
}
function payment_of_invoice(int $invoiceId): ?array
{
    return fetch_one('SELECT * FROM payments WHERE invoice_id = ?', [$invoiceId]);
}
