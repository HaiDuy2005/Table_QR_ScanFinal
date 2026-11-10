<?php
require __DIR__ . '/../../app/init.php';
require ROOT_DIR . '/views/staff_layout.php';
$user = require_role(['cashier']);

if (is_post()) {
    handle_post(function () {
        switch (post('act')) {
            case 'open':     return staff_open_session((int) post('table_id'));
            case 'finalize': return staff_finalize_invoice((int) post('session_id'));
            case 'pay':      return staff_confirm_payment((int) post('invoice_id'), post('method'), (int) post('amount'));
            case 'close':    return staff_close_session((int) post('session_id'));
            case 'cancel':   return staff_cancel_item((int) post('item_id'), (int) post('qty'), post('reason'));
            case 'notice':   return staff_handle_notice((int) post('notice_id'));
        }
        throw new AppError('Thao tác không hợp lệ.');
    });
}

$stats = stats_today();
$tables = tables_all(true);
$notices = notices_open();
$selId = (int) get('t');
$sel = $selId ? table_find($selId) : null;
$self = url('public/staff/cashier.php');

staff_open('Thu ngân', 'public/staff/cashier.php', 5);
?>
<h1>Thu ngân</h1>
<p class="sub">Mở bàn, theo dõi đơn, chốt hóa đơn và xác nhận thanh toán.</p>

<div data-live>
<div class="kpis">
  <div><small>Doanh thu hôm nay</small><b><?= e(money($stats['revenue'])) ?></b></div>
  <div><small>Hóa đơn đã thanh toán</small><b><?= $stats['paid_invoices'] ?></b></div>
  <div><small>Bàn đang mở</small><b><?= $stats['open_tables'] ?></b></div>
  <div><small>Thông báo chưa xử lý</small><b><?= count($notices) ?></b></div>
</div>

<?php if ($notices): ?>
  <div class="grid gap" style="margin-bottom:20px">
    <?php foreach ($notices as $n): ?>
      <div class="note-row">
        <div><b><?= e($n['table_name']) ?></b> · <?= e($n['text']) ?> <span class="tiny muted"><?= e(fmt_time($n['created_at'])) ?></span></div>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="act" value="notice"><input type="hidden" name="notice_id" value="<?= (int) $n['id'] ?>"><button class="btn btn-sm" type="submit">Đã xử lý</button></form>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<div class="cols">
  <section>
    <h2 style="font-size:16px;margin-bottom:10px">Sơ đồ bàn</h2>
    <div class="tgrid">
      <?php foreach ($tables as $i => $t):
          $s = session_open_of((int) $t['id']);
          $st = table_status($s); ?>
        <a class="tbtn st-<?= e($st) ?> fade-in <?= $sel && (int) $sel['id'] === (int) $t['id'] ? 'sel' : '' ?>" style="--d:<?= $i * 0.02 ?>s" href="<?= e($self . '?t=' . (int) $t['id']) ?>">
          <b><?= e($t['name']) ?></b>
          <?= pill(TABLE_STATUS_LABEL[$st], TABLE_STATUS_TONE[$st]) ?>
          <?php if ($s): ?><span class="tiny muted">Từ <?= e(fmt_time($s['opened_at'])) ?></span><?php else: ?><span class="tiny muted"><?= (int) $t['seats'] ?> chỗ</span><?php endif; ?>
        </a>
        <?php endforeach; ?>
    </div>
  </section>

  <section class="panel">
  <?php if (!$sel): ?>
    <div class="empty"><h3>Chọn một bàn ở bên trái</h3><p class="muted">Bàn trống thì mở phiên; bàn đang phục vụ thì xem đơn và thanh toán.</p></div>
  <?php else:
      $s = session_open_of((int) $sel['id']);
      $st = table_status($s); ?>
    <div class="row between"><h2><?= e($sel['name']) ?></h2><?= pill(TABLE_STATUS_LABEL[$st], TABLE_STATUS_TONE[$st]) ?></div>
    <?php if (!$s): ?>
      <p class="muted">Bàn đang trống. Mở phiên để khách có thể vào gọi món bằng mã 4 số hoặc mã QR.</p>
      <form method="post"><?= csrf_field() ?><input type="hidden" name="act" value="open"><input type="hidden" name="table_id" value="<?= (int) $sel['id'] ?>"><button class="btn btn-ink btn-lg" type="submit">Mở phiên bàn</button></form>
    <?php else:
        $sid = (int) $s['id'];
        $orders = orders_of_session($sid);
        $pipe = pipeline_of($orders);
        $inv = invoice_of_session($sid);
        $total = session_total($sid);
        $joinUrl = full_url('public/customer/join.php?table=' . $sel['code'] . '&k=' . $sel['qr_key'] . '&c=' . $s['join_code']); ?>
      <div class="row" style="gap:20px;align-items:flex-start">
        <div class="qrbox"><div data-qr="<?= e($joinUrl) ?>" data-size="116"></div></div>
        <div>
          <div class="tiny muted">Mã tham gia của bàn</div>
          <div class="codebig"><?= e($s['join_code']) ?></div>
          <div class="tiny muted" style="margin-top:4px">Mở lúc <?= e(fmt_datetime($s['opened_at'])) ?>. QR đã kèm sẵn mã này.</div>
        </div>
      </div>

      <?php if ($pipe['total'] > 0): ?>
        <div class="pipe"><div><b><?= $pipe['wait'] ?></b><span class="tiny muted">Chờ bếp</span></div><div><b><?= $pipe['cook'] ?></b><span class="tiny muted">Đang làm</span></div><div><b><?= $pipe['ready'] ?></b><span class="tiny muted">Sẵn sàng</span></div><div><b><?= $pipe['served'] ?></b><span class="tiny muted">Đã mang ra</span></div></div>
      <?php endif; ?>

      <?php if (!$orders): ?>
        <p class="muted">Bàn chưa gọi món nào.</p>
      <?php else: ?>
        <div class="table-wrap"><table class="t">
          <thead><tr><th>Đơn</th><th>Món</th><th>SL</th><th>Tiến độ</th><th class="right">Thành tiền</th></tr></thead>
          <tbody>
          <?php foreach ($orders as $o): foreach ($o['items'] as $it): $stg = item_stage($it); $n = net_qty($it); ?>
            <tr>
              <td class="mono"><?= e($o['code']) ?><div class="tiny muted"><?= e(fmt_time($o['created_at'])) ?></div></td>
              <td><b style="<?= $stg === 'cancelled' ? 'text-decoration:line-through' : '' ?>"><?= e($it['name']) ?></b>
                <?php if ($it['note'] !== ''): ?><div class="tiny muted">Ghi chú: <?= e($it['note']) ?></div><?php endif; ?>
                    <?php if (!$inv && $n - (int) $it['done'] > 0): ?>
                  <details class="inl"><summary>Hủy món</summary>
                    <form method="post"><?= csrf_field() ?><input type="hidden" name="act" value="cancel"><input type="hidden" name="item_id" value="<?= (int) $it['id'] ?>">
                      <label class="f">Số phần hủy (tối đa <?= $n - (int) $it['done'] ?>)<input type="number" name="qty" min="1" max="<?= $n - (int) $it['done'] ?>" value="1" required></label>
                      <label class="f">Lý do <span class="hint">(bắt buộc, được ghi vào nhật ký)</span><input type="text" name="reason" minlength="3" maxlength="200" required></label>
                      <button class="btn btn-danger btn-sm" type="submit">Xác nhận hủy</button></form></details>
                <?php endif; ?></td>
              <td><?= $n ?><?php if ((int) $it['cancelled'] > 0): ?><div class="tiny" style="color:var(--neg)">hủy <?= (int) $it['cancelled'] ?></div><?php endif; ?></td>
              <td><?= pill(STAGE_LABEL[$stg], STAGE_TONE[$stg]) ?></td>
              <td class="right"><?= e(money($n * $it['price'])) ?></td>
            </tr>
          <?php endforeach; endforeach; ?>
          </tbody></table></div>
      <?php endif; ?>

      <div class="total-row"><span>Tổng cộng</span><span><?= e(money($total)) ?></span></div>

      <?php if (!$inv): ?>
        <div class="row">
          <form method="post" data-confirm="Chốt hóa đơn? Sau khi chốt, bàn không gọi thêm món và không hủy món được nữa."><?= csrf_field() ?><input type="hidden" name="act" value="finalize"><input type="hidden" name="session_id" value="<?= $sid ?>">
            <button class="btn btn-ink" type="submit" <?= $total > 0 ? '' : 'disabled' ?>>Chốt hóa đơn</button></form>
          <?php if ($total === 0): ?>
            <form method="post" data-confirm="Đóng phiên bàn này?"><?= csrf_field() ?><input type="hidden" name="act" value="close"><input type="hidden" name="session_id" value="<?= $sid ?>"><button class="btn" type="submit">Đóng phiên (bàn chưa gọi món)</button></form>
          <?php endif; ?>
        </div>
      <?php elseif ($inv['status'] === 'unpaid'): ?>
        <div class="note-row" style="background:#fff3d6;display:grid">
          <b>Hóa đơn đã chốt lúc <?= e(fmt_time($inv['finalized_at'])) ?> · <?= e(money($inv['total'])) ?></b>
          <form method="post" class="row" data-confirm="Xác nhận đã nhận đủ <?= e(money($inv['total'])) ?>?"><?= csrf_field() ?><input type="hidden" name="act" value="pay"><input type="hidden" name="invoice_id" value="<?= (int) $inv['id'] ?>"><input type="hidden" name="amount" value="<?= (int) $inv['total'] ?>">
            <label class="row" style="gap:6px"><input type="radio" name="method" value="cash" checked> Tiền mặt</label>
            <label class="row" style="gap:6px"><input type="radio" name="method" value="transfer"> Chuyển khoản</label>
            <button class="btn btn-accent" type="submit">Xác nhận đã nhận tiền</button></form>
          <span class="tiny muted">Khách bấm “đã chuyển khoản” chỉ tạo thông báo; chỉ thu ngân mới xác nhận được thanh toán.</span>
        </div>
      <?php else:
          $pay = payment_of_invoice((int) $inv['id']); ?>
        <div class="note-row" style="background:#dcfae5">
          <span><b>Đã thanh toán</b> <?= e(money($inv['total'])) ?> · <?= $pay && $pay['method'] === 'cash' ? 'Tiền mặt' : 'Chuyển khoản' ?> lúc <?= e(fmt_time($pay['paid_at'] ?? '')) ?></span>
          <form method="post" data-confirm="Đóng phiên và trả bàn về trạng thái trống?"><?= csrf_field() ?><input type="hidden" name="act" value="close"><input type="hidden" name="session_id" value="<?= $sid ?>"><button class="btn btn-ink btn-sm" type="submit">Đóng phiên</button></form>
        </div>
        <button class="btn" type="button" onclick="window.print()">In hóa đơn</button>
      <?php endif; ?>
    <?php endif; ?>
  <?php endif; ?>
  </section>
</div>
</div>
<?php staff_close(); ?>