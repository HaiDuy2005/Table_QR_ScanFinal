<?php
require __DIR__ . '/../../app/init.php';
require ROOT_DIR . '/views/staff_layout.php';
require_role(['waiter']);

if (is_post()) {
    handle_post(function () {
        return waiter_set_served((int) post('item_id'), (int) post('served'));
    });
}

$all = kitchen_items();
$ready = array_values(array_filter($all, function ($i) { return (int) $i['done'] > (int) $i['served']; }));
$cooking = array_filter($all, function ($i) { return (int) $i['done'] < net_qty($i); });
staff_open('Phục vụ', 'public/staff/waiter.php', 4);
?>
<h1>Phục vụ</h1>
<p class="sub">Món bếp đã làm xong, cần mang ra bàn. Bấm “+1 mang ra” sau khi đặt món lên bàn.</p>
<div data-live>
  <div class="row" style="margin-bottom:14px"><?= pill(count($ready) . ' món cần mang ra', 'acc') ?> <?= pill(count($cooking) . ' món bếp còn đang làm', 'warn') ?></div>
  <?php if (!$ready): ?>
    <div class="empty"><h3>Chưa có món nào cần mang ra</h3><p class="muted">Khi bếp làm xong, món sẽ hiện ở đây.</p></div>
  <?php else: ?>
    <div class="kgrid">
    <?php foreach ($ready as $i => $it): $wait = (int) $it['done'] - (int) $it['served']; ?>
      <article class="kcard" style="--d:<?= min($i, 8) * 0.04 ?>s">
        <div class="row between" style="flex-wrap:nowrap;align-items:flex-start">
          <div><b style="font-size:16px"><?= e($it['name']) ?></b><div class="tiny muted"><b style="color:var(--ink)"><?= e($it['table_name']) ?></b> · <span class="mono"><?= e($it['order_code']) ?></span></div></div>
          <?= pill($wait . ' phần chờ mang', 'acc') ?>
        </div>
        <?php if ($it['note'] !== ''): ?><div class="note-row" style="padding:6px 10px">Ghi chú: <b><?= e($it['note']) ?></b></div><?php endif; ?>
        <div class="muted">Đã mang ra <?= (int) $it['served'] ?>/<?= net_qty($it) ?> phần</div>
        <div class="row">
          <form method="post" data-ajax><?= csrf_field() ?><input type="hidden" name="item_id" value="<?= (int) $it['id'] ?>"><input type="hidden" name="served" value="<?= (int) $it['served'] + 1 ?>"><button class="btn btn-accent" type="submit">+1 mang ra</button></form>
          <?php if ($wait > 1): ?>
            <form method="post" data-ajax><?= csrf_field() ?><input type="hidden" name="item_id" value="<?= (int) $it['id'] ?>"><input type="hidden" name="served" value="<?= (int) $it['done'] ?>"><button class="btn btn-ink" type="submit">Mang hết</button></form>
          <?php endif; ?>
          <?php if ((int) $it['served'] > 0): ?>
            <form method="post" data-ajax><?= csrf_field() ?><input type="hidden" name="item_id" value="<?= (int) $it['id'] ?>"><input type="hidden" name="served" value="<?= (int) $it['served'] - 1 ?>"><button class="btn btn-ghost" type="submit">−1</button></form>
          <?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
<?php staff_close(); ?>
