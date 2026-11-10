<?php
/* Nhật ký thao tác: ai làm gì, lúc nào (bảng audit_logs). */
function audit(string $actor, string $action, string $target = '', ?string $detail = null, ?string $reason = null): void
{
    q('INSERT INTO audit_logs (at, actor, action, target, detail, reason) VALUES (?,?,?,?,?,?)', [now(), $actor, $action, $target, $detail, $reason]);
}
function audit_search(string $kw = '', int $limit = 300): array
{
    if ($kw === '') {
        return fetch_all('SELECT * FROM audit_logs ORDER BY id DESC LIMIT ' . (int) $limit);
    }
    $like = '%' . $kw . '%';
    return fetch_all(
        'SELECT * FROM audit_logs WHERE actor LIKE ? OR action LIKE ? OR target LIKE ? OR detail LIKE ? OR reason LIKE ? ORDER BY id DESC LIMIT ' . (int) $limit,
        [$like, $like, $like, $like, $like]
    );
}
