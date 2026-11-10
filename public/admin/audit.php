<?php
require __DIR__ . '/../../app/init.php';
require ROOT_DIR . '/views/staff_layout.php';
require_role(['admin']);
$kw = get('q');
$logs = audit_search($kw);
staff_open('Nhật ký', 'public/admin/audit.php', 10);
?>
<h1>Nhật ký thao tác</h1>
<p class="sub">Mọi thao tác quan trọng đều được ghi lại trong MySQL (tối đa 300 dòng mới nhất).</p>
<form method="get" class="row" style="margin-bottom:14px"><input type="search" name="q" value="<?= e($kw) ?>" placeholder="Tìm theo người làm, thao tác, lý do…" style="max-width:360px"><button class="btn btn-ink" type="submit">Tìm</button>
  <?php if ($kw !== ''): ?><a class="btn btn-ghost" href="audit.php">Xóa lọc</a><?php endif; ?></form>
<div data-live class="table-wrap"><table class="t">
  <thead><tr><th>Thời gian</th><th>Người làm</th><th>Thao tác</th><th>Đối tượng</th><th>Chi tiết</th><th>Lý do</th></tr></thead>
  <tbody>
  <?php if (!$logs): ?><tr><td colspan="6" class="muted">Chưa có dòng nhật ký nào.</td></tr><?php endif; ?>
  <?php foreach ($logs as $l): ?>
    <tr><td class="tiny"><?= e(fmt_datetime($l['at'])) ?></td><td><?= e($l['actor']) ?></td><td><b><?= e($l['action']) ?></b></td><td><?= e($l['target']) ?></td><td><?= e($l['detail']) ?></td><td><?= e($l['reason']) ?></td></tr>
  <?php endforeach; ?>
  </tbody></table></div>
<?php staff_close(); ?>