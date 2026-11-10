<?php
require __DIR__ . '/../../app/init.php';
require ROOT_DIR . '/views/staff_layout.php';
require_role(['admin']);

if (is_post()) {
    handle_post(function () {
        return admin_toggle_menu((int) post('id'), post('on') === '1');
    });
}
$cat = get('cat', 'Tất cả');
$list = array_filter(menu_all(), function ($m) use ($cat) { return $cat === 'Tất cả' || $m['category'] === $cat; });
staff_open('Thực đơn', 'public/admin/menu.php');
?>
<div class="row between"><div><h1>Thực đơn</h1><p class="sub" style="margin-bottom:0">Giá và trạng thái món được lưu trong MySQL; khách luôn thấy dữ liệu mới nhất.</p></div>
  <a class="btn btn-ink" href="<?= e(url('public/admin/menu_form.php')) ?>">+ Thêm món</a></div>
<div class="tabbar mt">
  <?php foreach (array_merge(['Tất cả'], CATEGORIES) as $c): ?><a class="<?= $cat === $c ? 'on' : '' ?>" href="?cat=<?= urlencode($c) ?>"><?= e($c) ?></a><?php endforeach; ?>
</div>
<div class="table-wrap"><table class="t">
  <thead><tr><th></th><th>Món</th><th>Nhóm</th><th class="right">Giá</th><th>Trạng thái</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($list as $m): ?>
    <tr>
      <td style="width:64px"><div style="width:52px;height:52px;border-radius:6px;overflow:hidden"><?= dish_art($m, '') ?></div></td>
      <td><b><?= e($m['name']) ?></b><div class="tiny muted">/<?= e($m['unit']) ?></div></td>
      <td><?= e($m['category']) ?></td>
      <td class="right"><?= e(money($m['price'])) ?></td>
      <td><?= pill($m['available'] ? 'Đang bán' : 'Tạm hết', $m['available'] ? 'pos' : 'neg') ?></td>
      <td class="right"><div class="row" style="justify-content:flex-end">
        <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $m['id'] ?>"><input type="hidden" name="on" value="<?= $m['available'] ? 0 : 1 ?>"><button class="btn btn-sm" type="submit"><?= $m['available'] ? 'Đánh dấu hết' : 'Mở bán lại' ?></button></form>
        <a class="btn btn-sm btn-ink" href="<?= e(url('public/admin/menu_form.php?id=' . (int) $m['id'])) ?>">Sửa</a></div></td>
    </tr>
  <?php endforeach; ?>
  </tbody></table></div>
<?php staff_close(); ?>
