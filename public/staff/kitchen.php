<?php
require __DIR__ . '/../../app/init.php';
require ROOT_DIR . '/views/staff_layout.php';
require_role(['kitchen']);

if (is_post()) {
    handle_post(function () {
        $id = (int) post('item_id');
        if (post('act') === 'start') {
            return kitchen_start($id);
        }
        return kitchen_set_done($id, (int) post('done'));
    });
}

$all = kitchen_items();
$queue = array_values(array_filter($all, function ($i) { return (int) $i['done'] < net_qty($i); }));
$finished = count($all) - count($queue);
staff_open('Bếp', 'public/staff/kitchen.php', 4);
?>
<h1>Bếp</h1>
<p class="sub">Các món đang chờ làm, theo thứ tự đơn. Bấm “+1 xong” mỗi khi làm xong một phần.</p>
<div data-live>
  <div class="row" style="margin-bottom:14px"><?= pill(count($queue) . ' món đang chờ', 'warn') ?> <?= pill($finished . ' món đã làm xong', 'pos') ?></div>
  <?php if (!$queue): ?>
    <div class="empty"><h3>Không có món nào chờ làm</h3><p class="muted">Đơn mới của khách sẽ hiện ở đây trong vài giây.</p></div>
  <?php else: ?>
    <div class="kgrid">
    <?php foreach ($queue as $i => $it): $n = net_qty($it); $stg = item_stage($it); ?>
      <article class="kcard" style="--d:<?= min($i, 8) * 0.04 ?>s">
        <div class="row between" style="flex-wrap:nowrap;align-items:flex-start">
          <div><b style="font-size:16px"><?= e($it['name']) ?></b><div class="tiny muted"><?= e($it['table_name']) ?> · <span class="mono"><?= e($it['order_code']) ?></span> · <?= e(fmt_time($it['ordered_at'])) ?></div></div>
          <?= pill(STAGE_LABEL[$stg], STAGE_TONE[$stg]) ?>
        </div>
        <?php if ($it['note'] !== ''): ?><div class="note-row" style="padding:6px 10px">Ghi chú: <b><?= e($it['note']) ?></b></div><?php endif; ?>
        <div class="row between"><span>Đã làm <b><?= (int) $it['done'] ?></b>/<?= $n ?> phần</span><span class="muted tiny">Đã mang ra <?= (int) $it['served'] ?></span></div>
        <div class="row">
          <?php if ((int) $it['started'] === 0 && (int) $it['done'] === 0): ?>
            <form method="post" data-ajax><?= csrf_field() ?><input type="hidden" name="act" value="start"><input type="hidden" name="item_id" value="<?= (int) $it['id'] ?>"><button class="btn" type="submit">Bắt đầu làm</button></form>
          <?php endif; ?>
          <form method="post" data-ajax><?= csrf_field() ?><input type="hidden" name="act" value="done"><input type="hidden" name="item_id" value="<?= (int) $it['id'] ?>"><input type="hidden" name="done" value="<?= (int) $it['done'] + 1 ?>"><button class="btn btn-accent" type="submit">+1 xong</button></form>
          <?php if ($n - (int) $it['done'] > 1): ?>
            <form method="post" data-ajax><?= csrf_field() ?><input type="hidden" name="act" value="done"><input type="hidden" name="item_id" value="<?= (int) $it['id'] ?>"><input type="hidden" name="done" value="<?= $n ?>"><button class="btn btn-ink" type="submit">Xong hết</button></form>
          <?php endif; ?>
          <?php if ((int) $it['done'] > (int) $it['served']): ?>
            <form method="post" data-ajax><?= csrf_field() ?><input type="hidden" name="act" value="done"><input type="hidden" name="item_id" value="<?= (int) $it['id'] ?>"><input type="hidden" name="done" value="<?= (int) $it['done'] - 1 ?>"><button class="btn btn-ghost" type="submit">−1</button></form>
          <?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
<?php staff_close(); ?>