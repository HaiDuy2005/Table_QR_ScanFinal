<?php
require __DIR__ . '/../../app/init.php';
require ROOT_DIR . '/views/staff_layout.php';
require_role(['admin']);

if (is_post()) {
    handle_post(function () {
        if (post('act') === 'regen') {
            return admin_regenerate_qr((int) post('id'));
        }
        return admin_save_table();
    });
}
$tables = tables_all();
staff_open('Bàn và QR', 'public/admin/tables.php');
?>
<h1>Bàn và mã QR</h1>
<p class="sub">Mỗi bàn có một mã QR riêng. Mã QR và mã 4 số của mỗi bàn cố định, in một lần dùng mãi.</p>

<div class="panel" style="margin-bottom:20px">
  <h2>Thêm bàn mới</h2>
  <form method="post" class="row" style="align-items:flex-end"><?= csrf_field() ?><input type="hidden" name="act" value="save"><input type="hidden" name="id" value="0">
    <label class="f" style="width:110px">Mã bàn<input type="text" name="code" placeholder="B16" maxlength="10" required></label>
    <label class="f" style="width:200px">Tên bàn<input type="text" name="name" placeholder="Bàn 16" required></label>
    <label class="f" style="width:100px">Số chỗ<input type="number" name="seats" value="4" min="1" max="50" required></label>
    <label class="row" style="gap:6px;height:40px"><input type="checkbox" name="active" checked> Đang dùng</label>
    <button class="btn btn-ink" type="submit">Thêm bàn</button></form>
</div>

<div class="table-wrap"><table class="t">
  <thead><tr><th>Mã</th><th>Tên</th><th>Số chỗ</th><th>Trạng thái</th><th>Mã QR</th></tr></thead>
  <tbody>
  <?php foreach ($tables as $t):
      $s = session_open_of((int) $t['id']);
      $qr = full_url('public/customer/join.php?table=' . $t['code'] . '&k=' . $t['qr_key']); ?>
    <tr>
      <td colspan="4">
        <form method="post" class="row" style="align-items:flex-end"><?= csrf_field() ?><input type="hidden" name="act" value="save"><input type="hidden" name="id" value="<?= (int) $t['id'] ?>">
          <label class="f" style="width:90px">Mã<input type="text" name="code" value="<?= e($t['code']) ?>" maxlength="10" required></label>
          <label class="f" style="width:170px">Tên<input type="text" name="name" value="<?= e($t['name']) ?>" required></label>
          <label class="f" style="width:80px">Chỗ<input type="number" name="seats" value="<?= (int) $t['seats'] ?>" min="1" max="50"></label>
          <label class="row" style="gap:6px;height:40px"><input type="checkbox" name="active" <?= $t['active'] ? 'checked' : '' ?>> Đang dùng</label>
          <button class="btn btn-sm" type="submit">Lưu</button>
          <?= $s ? pill('Đang có phiên', 'acc') : '' ?></form></td>
      <td>
        <details class="inl"><summary>Xem mã QR</summary>
          <div class="grid gap" style="margin-top:8px">
            <div>Mã 4 số cố định: <b class="mono"><?= e($t['join_code']) ?></b></div>
            <div class="qrbox"><div data-qr="<?= e($qr) ?>" data-size="150"></div></div>
<a class="tiny" href="<?= e($qr) ?>" target="_blank"><?= e($qr) ?></a>
            <button class="btn btn-sm" type="button" onclick="window.print()">In mã QR</button>
          </div></details></td>
    </tr>
  <?php endforeach; ?>
  </tbody></table></div>
<?php staff_close(); ?>