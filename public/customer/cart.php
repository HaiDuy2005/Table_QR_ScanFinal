<?php
require __DIR__ . '/../../app/init.php';
require ROOT_DIR . '/views/customer_layout.php';

[$table, $session] = customer_context(get('table'));
$sid = (int) $session['id'];
$self = url('public/customer/cart.php?table=' . urlencode($table['code']));

if (is_post()) {
    $act = post('act');
    handle_post(function () use ($act, $table, $session, $sid) {
        if ($act === 'qty') {
            cart_set_qty($sid, (int) post('menu_id'), (int) post('qty'));
            return '';
        }
        if ($act === 'note') {
            cart_set_note($sid, (int) post('menu_id'), post('note'));
            return 'Đã lưu ghi chú.';
        }
        if ($act === 'clear') {
            unset($_SESSION['cart'][$sid]);
            return 'Đã xóa giỏ hàng.';
        }
        if ($act === 'submit') {
            $msg = customer_submit_order($table, $session, post('token'));
            flash($msg);
            redirect('public/customer/orders.php?table=' . urlencode($table['code']));
        }
        return '';
    }, $self);
}

// Mã chống gửi trùng: mỗi lần mở giỏ có một mã; bấm gửi hai lần vẫn chỉ ra một đơn
if (empty($_SESSION['order_token'])) {
    $_SESSION['order_token'] = bin2hex(random_bytes(8));
}
$rows = [];
$total = 0;
foreach (cart_get($sid) as $mid => $c) {
    $m = menu_find((int) $mid);
    if ($m) {
        $rows[] = ['m' => $m, 'c' => $c];
        $total += $c['qty'] * (int) $m['price'];
    }
}
$inv = invoice_of_session($sid);
customer_open($table, $session, 'cart', 'Giỏ hàng');
?>
<div class="pad" data-live>
  <h2 class="display" style="font-size:26px">Giỏ hàng</h2>
  <?php if ($inv): ?><div class="flash flash-err" style="margin-top:12px">Hóa đơn đã được chốt, bàn không nhận thêm món.</div><?php endif; ?>
  <?php if (!$rows): ?>
    <div class="empty" style="margin-top:16px"><h3>Giỏ hàng đang trống</h3><p class="muted">Chọn món ở thực đơn rồi quay lại đây để gửi cho bếp.</p>
      <a class="btn btn-accent" style="margin-top:12px" href="<?= e(url('public/customer/menu.php?table=' . urlencode($table['code']))) ?>">Xem thực đơn</a></div>
  <?php else: foreach ($rows as $r): $m = $r['m']; $c = $r['c']; ?>
    <div class="line">
      <div class="row between" style="flex-wrap:nowrap">
        <div><b><?= e($m['name']) ?></b><div class="tiny muted"><?= e(money($m['price'])) ?> / <?= e($m['unit']) ?></div></div>
        <b><?= e(money($c['qty'] * $m['price'])) ?></b>
      </div>
      <div class="row between">
        <div class="stepper">
          <form method="post" data-ajax><?= csrf_field() ?><input type="hidden" name="act" value="qty"><input type="hidden" name="menu_id" value="<?= (int) $m['id'] ?>"><input type="hidden" name="qty" value="<?= $c['qty'] - 1 ?>"><button type="submit" aria-label="Bớt">−</button></form>
          <b><?= (int) $c['qty'] ?></b>
          <form method="post" data-ajax><?= csrf_field() ?><input type="hidden" name="act" value="qty"><input type="hidden" name="menu_id" value="<?= (int) $m['id'] ?>"><input type="hidden" name="qty" value="<?= $c['qty'] + 1 ?>"><button type="submit" aria-label="Thêm">+</button></form>
        </div>
        <form method="post" data-ajax><?= csrf_field() ?><input type="hidden" name="act" value="qty"><input type="hidden" name="menu_id" value="<?= (int) $m['id'] ?>"><input type="hidden" name="qty" value="0"><button class="btn btn-ghost btn-sm" type="submit">Xóa món</button></form>
      </div>
      <form method="post" class="row" style="flex-wrap:nowrap"><?= csrf_field() ?><input type="hidden" name="act" value="note"><input type="hidden" name="menu_id" value="<?= (int) $m['id'] ?>">
        <input type="text" name="note" maxlength="200" value="<?= e($c['note']) ?>" placeholder="Ghi chú cho bếp (ít cay, không hành…)" aria-label="Ghi chú cho <?= e($m['name']) ?>">
        <button class="btn btn-sm" type="submit">Lưu</button></form>
    </div>
  <?php endforeach; ?>
    <div class="total-row" style="margin-top:16px"><span>Tạm tính</span><span><?= e(money($total)) ?></span></div>
    <p class="tiny muted" style="margin-top:4px">Giá do máy chủ tính lại khi gửi đơn. Sau khi gửi, đơn không sửa được; muốn hủy món hãy nhờ thu ngân.</p>
    <form method="post" class="grid gap mt"><?= csrf_field() ?><input type="hidden" name="act" value="submit"><input type="hidden" name="token" value="<?= e($_SESSION['order_token']) ?>">
      <button class="btn btn-ink btn-lg btn-block" type="submit" <?= $inv ? 'disabled' : '' ?>>Gửi đơn cho nhà bếp</button></form>
    <form method="post" data-confirm="Xóa toàn bộ giỏ hàng?"><?= csrf_field() ?><input type="hidden" name="act" value="clear"><button class="btn btn-ghost btn-block mt-s" type="submit">Xóa giỏ hàng</button></form>
  <?php endif; ?>
</div>
<?php customer_close($table, $session, 'cart'); ?>
