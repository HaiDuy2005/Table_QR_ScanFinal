<?php
require __DIR__ . '/../../app/init.php';
require ROOT_DIR . '/views/customer_layout.php';

[$table, $session] = customer_context(get('table'));
$sid = (int) $session['id'];
$self = url('public/customer/orders.php?table=' . urlencode($table['code']));

if (is_post()) {
    handle_post(function () use ($table, $session) {
        return post('act') === 'transfer' ? customer_claim_transfer($table, $session) : customer_call_staff($table, $session);
    }, $self);
}

$orders = orders_of_session($sid);
$pipe = pipeline_of($orders);
$inv = invoice_of_session($sid);
$total = session_total($sid);
customer_open($table, $session, 'orders', 'Đơn của bàn', 4);
?>
<div class="pad" data-live>
  <div class="row between"><h2 class="display" style="font-size:26px">Đơn của bàn</h2>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="act" value="call"><button class="btn btn-sm" type="submit">🔔 Gọi nhân viên</button></form></div>

  <?php if ($pipe['total'] > 0): ?>
    <div class="pipe mt" aria-label="Tiến độ">
      <div><b><?= $pipe['wait'] ?></b><span class="tiny muted">Chờ bếp</span></div><div><b><?= $pipe['cook'] ?></b><span class="tiny muted">Đang làm</span></div>
      <div><b><?= $pipe['ready'] ?></b><span class="tiny muted">Sẵn sàng</span></div><div><b><?= $pipe['served'] ?></b><span class="tiny muted">Đã mang ra</span></div>
    </div>
  <?php endif; ?>

  <?php if (!$orders): ?>
    <div class="empty mt"><h3>Bàn chưa gọi món nào</h3><p class="muted">Các lần gọi món và tiến độ chế biến của bàn sẽ hiện ở đây.</p>
      <a class="btn btn-accent" style="margin-top:12px" href="<?= e(url('public/customer/menu.php?table=' . urlencode($table['code']))) ?>">Gọi món</a></div>
  <?php else: foreach ($orders as $i => $o): ?>
    <section class="card fade-in mt" style="--d:<?= $i * 0.05 ?>s" aria-label="Đơn <?= e($o['code']) ?>">
      <div class="row between"><b class="mono"><?= e($o['code']) ?></b><span class="tiny muted">Gửi lúc <?= e(fmt_time($o['created_at'])) ?></span></div>
      <?php foreach ($o['items'] as $it): $st = item_stage($it); $n = net_qty($it); ?>
        <div style="display:grid;gap:2px">
          <div class="row between" style="flex-wrap:nowrap;align-items:flex-start">
            <b style="<?= $st === 'cancelled' ? 'text-decoration:line-through;color:var(--muted)' : '' ?>"><?= e($it['name']) ?> <span class="muted" style="font-weight:400">× <?= (int) $it['qty'] ?></span></b>
            <?= pill(STAGE_LABEL[$st], STAGE_TONE[$st]) ?>
          </div>
          <?php if ($it['note'] !== ''): ?><span class="tiny muted">Ghi chú: <?= e($it['note']) ?></span><?php endif; ?>
          <?php if ((int) $it['cancelled'] > 0): ?><span class="tiny" style="color:var(--neg)">Đã hủy <?= (int) $it['cancelled'] ?> phần</span><?php endif; ?>
            <?php if ($n > 0): ?><span class="tiny muted">Đã làm <?= (int) $it['done'] ?>/<?= $n ?> · Đã mang ra <?= (int) $it['served'] ?>/<?= $n ?></span><?php endif; ?>
        </div>
      <?php endforeach; ?>
    </section>
  <?php endforeach; ?>

    <div class="total-row mt"><span>Tổng tạm tính</span><span><?= e(money($total)) ?></span></div>
    <?php if ($inv): ?>
      <section class="ticket-lite mt">
        <div class="row between"><b>Hóa đơn</b><?= pill($inv['status'] === 'paid' ? 'Đã thanh toán' : 'Chờ thanh toán', $inv['status'] === 'paid' ? 'pos' : 'warn') ?></div>
        <?php foreach (invoice_lines((int) $inv['id']) as $l): ?>
          <div class="row between" style="flex-wrap:nowrap;margin-top:6px"><span><?= e($l['name']) ?> × <?= (int) $l['qty'] ?></span><span><?= e(money($l['qty'] * $l['price'])) ?></span></div>
        <?php endforeach; ?>
        <div class="total-row" style="margin-top:10px"><span>Tổng cộng</span><span><?= e(money($inv['total'])) ?></span></div>
        <?php if ($inv['status'] === 'unpaid'): ?>
          <p class="tiny muted" style="margin-top:8px">Vui lòng thanh toán tại quầy, hoặc hỏi nhân viên thông tin chuyển khoản. Nếu đã chuyển khoản, bấm nút dưới để báo thu ngân đối chiếu.</p>
          <form method="post" class="mt-s"><?= csrf_field() ?><input type="hidden" name="act" value="transfer"><button class="btn btn-ink btn-block" type="submit">Tôi đã chuyển khoản</button></form>
        <?php else: ?>
          <p style="margin-top:8px;color:var(--pos);font-weight:600">Cảm ơn quý khách! Thu ngân đã xác nhận thanh toán.</p>
        <?php endif; ?>
      </section>
    <?php endif; ?>
  <?php endif; ?>
</div>
<?php customer_close($table, $session, 'orders'); ?>