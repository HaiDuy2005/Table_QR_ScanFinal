<?php



function customer_context(string $code): array
{
    $table = table_by_code($code);
    if (!$table || !(int) $table['active']) {
        flash('Không tìm thấy bàn này.', 'err');
        redirect('public/index.php');
    }
    $session = session_open_of((int) $table['id']);
    $granted = $_SESSION['grants'][$table['id']] ?? null;
    if (!$session || $granted !== (int) $session['id']) {
        if ($granted && !$session) {
            flash('Phiên của bàn đã kết thúc. Hãy nhờ nhân viên mở lại.', 'err');
        }
        redirect('public/customer/join.php?table=' . urlencode($table['code']));
    }
    return [$table, $session];
}

function customer_join(string $code, string $qrKey, string $joinCode): void
{
    $table = table_by_code($code);
    if (!$table || !(int) $table['active']) {
        throw new AppError('Không tìm thấy bàn này.');
    }
    if ($qrKey !== '' && !hash_equals($table['qr_key'], $qrKey)) {
        throw new AppError('Mã QR này đã hết hiệu lực. Hãy quét mã QR mới trên bàn.');
    }
    $tid = $table['id'];
    if (($_SESSION['join_lock'][$tid] ?? 0) > time()) {
        throw new AppError('Nhập sai nhiều lần. Hãy thử lại sau ' . ($_SESSION['join_lock'][$tid] - time()) . ' giây.');
    }
    $session = session_open_of((int) $tid);
    if (!$session) {
        throw new AppError('Bàn này chưa được mở. Hãy gọi nhân viên để mở bàn.');
    }
    if (!hash_equals($session['join_code'], $joinCode)) {
        $_SESSION['join_fail'][$tid] = ($_SESSION['join_fail'][$tid] ?? 0) + 1;
        if ($_SESSION['join_fail'][$tid] >= 5) {
            $_SESSION['join_lock'][$tid] = time() + 60;
            $_SESSION['join_fail'][$tid] = 0;
        }
        throw new AppError('Mã 4 số chưa đúng. Hãy xem lại mã nhân viên đưa cho bạn.');
    }
    $_SESSION['grants'][$tid] = (int) $session['id'];
    $_SESSION['join_fail'][$tid] = 0;
}


function cart_get(int $sessionId): array
{
    return $_SESSION['cart'][$sessionId] ?? [];
}
function cart_count(int $sessionId): int
{
    return array_sum(array_column(cart_get($sessionId), 'qty'));
}
function cart_set_qty(int $sessionId, int $menuId, int $qty): void
{
    $m = menu_find($menuId);
    if (!$m) {
        throw new AppError('Món không còn trong thực đơn.');
    }
    if ($qty > 0 && !(int) $m['available']) {
        throw new AppError('“' . $m['name'] . '” hiện đã tạm hết.');
    }
    $qty = max(0, min(99, $qty));
    if ($qty === 0) {
        unset($_SESSION['cart'][$sessionId][$menuId]);
    } else {
        $note = $_SESSION['cart'][$sessionId][$menuId]['note'] ?? '';
        $_SESSION['cart'][$sessionId][$menuId] = ['qty' => $qty, 'note' => $note];
    }
}
function cart_set_note(int $sessionId, int $menuId, string $note): void
{
    if (isset($_SESSION['cart'][$sessionId][$menuId])) {
        $_SESSION['cart'][$sessionId][$menuId]['note'] = mb_substr($note, 0, 200);
    }
}

function customer_submit_order(array $table, array $session, string $token): string
{
    $cart = cart_get((int) $session['id']);
    if (!$cart) {
        throw new AppError('Giỏ hàng đang trống.');
    }
    if ($token === '' || strlen($token) > 40) {
        throw new AppError('Yêu cầu không hợp lệ.');
    }
    $order = transaction(function () use ($session, $cart, $token) {
        $s = fetch_one('SELECT * FROM table_sessions WHERE id = ?' . for_update(), [$session['id']]);
        if (!$s || $s['closed_at'] !== null) {
            throw new AppError('Phiên bàn đã kết thúc.');
        }
        if (invoice_of_session((int) $s['id'])) {
            throw new AppError('Hóa đơn đã được chốt, bàn không nhận thêm món.');
        }
        $lines = [];
        foreach ($cart as $mid => $c) {
            $m = menu_find((int) $mid);
            if (!$m) {
                throw new AppError('Có món không còn trong thực đơn.');
            }
            if (!(int) $m['available']) {
                throw new AppError('“' . $m['name'] . '” hiện đã tạm hết, hãy bỏ món này khỏi giỏ.');
            }
            $lines[] = ['menu' => $m, 'qty' => (int) $c['qty'], 'note' => $c['note']];
        }
        return order_create((int) $s['id'], $token, $lines);
    });
    unset($_SESSION['cart'][$session['id']], $_SESSION['order_token']);
    return 'Đã gửi đơn ' . $order['code'] . ' cho nhà bếp.';
}

function customer_claim_transfer(array $table, array $session): string
{
    $inv = invoice_of_session((int) $session['id']);
    if (!$inv) {
        throw new AppError('Hóa đơn chưa được chốt.');
    }
    if ($inv['status'] === 'paid') {
        throw new AppError('Hóa đơn đã được thanh toán.');
    }
    
    notice_add((int) $session['id'], (int) $table['id'], 'transfer-claim', 'Khách báo đã chuyển khoản – cần đối chiếu.');
    return 'Đã báo cho thu ngân. Nhân viên sẽ đối chiếu và xác nhận.';
}
function customer_call_staff(array $table, array $session): string
{
    notice_add((int) $session['id'], (int) $table['id'], 'call-staff', 'Khách gọi nhân viên.');
    return 'Đã gọi nhân viên, vui lòng chờ một chút.';
}
