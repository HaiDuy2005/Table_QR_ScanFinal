<?php

function staff_open_session(int $tableId): string
{
    $u = assert_role(['cashier']);
    $t = table_find($tableId);
    if (!$t || !(int) $t['active']) {
        throw new AppError('Bàn không tồn tại hoặc đã ngừng dùng.');
    }
    session_open($tableId, (int) $u['id']);
    audit($u['username'], 'Mở phiên bàn', $t['name']);
    return 'Đã mở ' . $t['name'] . '. Đưa mã 4 số hoặc mã QR cho khách.';
}

function staff_finalize_invoice(int $sessionId): string
{
    $u = assert_role(['cashier']);
    transaction(function () use ($sessionId, $u) {
        $s = fetch_one('SELECT * FROM table_sessions WHERE id = ?' . for_update(), [$sessionId]);
        if (!$s || $s['closed_at'] !== null) {
            throw new AppError('Phiên bàn không còn mở.');
        }
        if (invoice_of_session($sessionId)) {
            throw new AppError('Hóa đơn của bàn này đã được chốt.');
        }
        $lines = [];
        $total = 0;
        foreach (orders_of_session($sessionId) as $o) {
            foreach ($o['items'] as $it) {
                $n = net_qty($it);
                if ($n > 0) {
                    $lines[] = [$it['name'], $it['unit'], $n, (int) $it['price']];
                    $total += $n * (int) $it['price'];
                }
            }
        }
        if (!$lines) {
            throw new AppError('Bàn chưa có món nào để chốt hóa đơn.');
        }
        q('INSERT INTO invoices (session_id, total, status, finalized_at, finalized_by) VALUES (?,?,?,?,?)', [$sessionId, $total, 'unpaid', now(), $u['id']]);
        $iid = insert_id();
        foreach ($lines as [$name, $unit, $qty, $price]) {
            q('INSERT INTO invoice_lines (invoice_id, name, unit, qty, price) VALUES (?,?,?,?,?)', [$iid, $name, $unit, $qty, $price]);
        }
        $t = table_find((int) $s['table_id']);
        audit($u['username'], 'Chốt hóa đơn', $t['name'], money($total));
    });
    return 'Đã chốt hóa đơn. Bàn không nhận thêm món.';
}

function staff_confirm_payment(int $invoiceId, string $method, int $amount): string
{
    $u = assert_role(['cashier']);
    if (!in_array($method, ['cash', 'transfer'], true)) {
        throw new AppError('Hình thức thanh toán không hợp lệ.');
    }
    transaction(function () use ($invoiceId, $method, $amount, $u) {
        $inv = fetch_one('SELECT * FROM invoices WHERE id = ?' . for_update(), [$invoiceId]);
        if (!$inv) {
            throw new AppError('Không tìm thấy hóa đơn.');
        }
        if ($inv['status'] === 'paid' || payment_of_invoice($invoiceId)) {
            throw new AppError('Hóa đơn này đã được thanh toán, không ghi nhận lần hai.');
        }
        if ($amount !== (int) $inv['total']) {
            throw new AppError('Số tiền phải bằng đúng tổng hóa đơn (' . money($inv['total']) . ').');
        }
        q('INSERT INTO payments (invoice_id, method, amount, paid_at, confirmed_by) VALUES (?,?,?,?,?)', [$invoiceId, $method, $amount, now(), $u['id']]);
        q("UPDATE invoices SET status = 'paid' WHERE id = ?", [$invoiceId]);
        $s = session_find((int) $inv['session_id']);
        $t = table_find((int) $s['table_id']);
        audit($u['username'], 'Xác nhận thanh toán', $t['name'], ($method === 'cash' ? 'Tiền mặt ' : 'Chuyển khoản ') . money($amount));
    });
    return 'Đã ghi nhận thanh toán.';
}

function staff_close_session(int $sessionId): string
{
    $u = assert_role(['cashier']);
    transaction(function () use ($sessionId, $u) {
        $s = fetch_one('SELECT * FROM table_sessions WHERE id = ?' . for_update(), [$sessionId]);
        if (!$s || $s['closed_at'] !== null) {
            throw new AppError('Phiên bàn đã được đóng.');
        }
        $inv = invoice_of_session($sessionId);
        $hasItems = session_total($sessionId) > 0;
        if ($hasItems && (!$inv || $inv['status'] !== 'paid')) {
            throw new AppError('Bàn còn món chưa thanh toán. Hãy chốt hóa đơn và xác nhận thanh toán trước.');
        }
        session_close($sessionId);
        q('UPDATE notices SET handled = 1 WHERE session_id = ?', [$sessionId]);
        $t = table_find((int) $s['table_id']);
        audit($u['username'], 'Đóng phiên bàn', $t['name']);
    });
    return 'Đã đóng phiên, bàn trống trở lại.';
}

function staff_cancel_item(int $itemId, int $qty, string $reason): string
{
    $u = assert_role(['cashier']);
    $reason = trim($reason);
    if (mb_strlen($reason) < 3) {
        throw new AppError('Hãy nhập lý do hủy món (ít nhất 3 ký tự).');
    }
    transaction(function () use ($itemId, $qty, $reason, $u) {
        $it = order_item_find($itemId);
        if (!$it) {
            throw new AppError('Không tìm thấy món.');
        }
        if (invoice_of_session((int) $it['session_id'])) {
            throw new AppError('Hóa đơn đã chốt, không hủy món được nữa.');
        }
        
        $max = net_qty($it) - (int) $it['done'];
        if ($qty < 1 || $qty > $max) {
            throw new AppError($max > 0 ? "Chỉ hủy được tối đa $max phần (phần đã làm xong thì không hủy)." : 'Món này đã làm xong, không hủy được.');
        }
        q('UPDATE order_items SET cancelled = cancelled + ? WHERE id = ?', [$qty, $itemId]);
        audit($u['username'], 'Hủy món', $it['order_code'], $it['name'] . ' × ' . $qty, $reason);
    });
    return 'Đã hủy món và ghi vào nhật ký.';
}

function staff_handle_notice(int $noticeId): string
{
    $u = assert_role(['cashier']);
    $n = notice_find($noticeId);
    if (!$n) {
        throw new AppError('Không tìm thấy thông báo.');
    }
    notice_handle($noticeId);
    audit($u['username'], 'Xử lý thông báo', $n['text']);
    return 'Đã đánh dấu đã xử lý.';
}


function kitchen_start(int $itemId): string
{
    assert_role(['kitchen']);
    $it = order_item_find($itemId);
    if (!$it) {
        throw new AppError('Không tìm thấy món.');
    }
    q('UPDATE order_items SET started = 1 WHERE id = ?', [$itemId]);
    return 'Bếp bắt đầu làm món này.';
}
function kitchen_set_done(int $itemId, int $done): string
{
    assert_role(['kitchen']);
    transaction(function () use ($itemId, $done) {
        $it = order_item_find($itemId);
        if (!$it) {
            throw new AppError('Không tìm thấy món.');
        }
        if ($done < (int) $it['served']) {
            throw new AppError('Không giảm dưới số phần phục vụ đã mang ra (' . $it['served'] . ').');
        }
        if ($done > net_qty($it)) {
            throw new AppError('Số phần đã làm không vượt quá số phần gọi.');
        }
        q('UPDATE order_items SET done = ?, started = 1 WHERE id = ?', [$done, $itemId]);
    });
    return '';
}

function waiter_set_served(int $itemId, int $served): string
{
    assert_role(['waiter']);
    transaction(function () use ($itemId, $served) {
        $it = order_item_find($itemId);
        if (!$it) {
            throw new AppError('Không tìm thấy món.');
        }
        if ($served < 0 || $served > (int) $it['done']) {
            throw new AppError('Chỉ mang ra được tối đa số phần bếp đã làm xong (' . $it['done'] . ').');
        }
        q('UPDATE order_items SET served = ? WHERE id = ?', [$served, $itemId]);
    });
    return '';
}
