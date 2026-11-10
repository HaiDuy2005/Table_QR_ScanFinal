<?php
require __DIR__ . '/../../app/init.php';
require ROOT_DIR . '/views/staff_layout.php';
$me = require_role(['admin']);

if (is_post()) {
    handle_post(function () {
        return admin_save_user();
    });
}
$users = user_all();
staff_open('Tài khoản', 'public/admin/users.php');
?>
<h1>Tài khoản và quyền</h1>
<p class="sub">Mật khẩu được băm bcrypt trong MySQL, không lưu dạng rõ. Quyền được kiểm tra ở PHP cho từng thao tác.</p>

<div class="panel" style="margin-bottom:20px">
  <h2>Thêm tài khoản</h2>
  <form method="post" class="row" style="align-items:flex-end"><?= csrf_field() ?><input type="hidden" name="id" value="0">
    <label class="f" style="width:150px">Tên đăng nhập<input type="text" name="username" pattern="[a-z0-9_]{3,30}" required></label>
    <label class="f" style="width:200px">Họ tên<input type="text" name="full_name" required></label>
    <label class="f" style="width:150px">Vai trò<select name="role"><?php foreach (ROLE_LABEL as $k => $l): ?><option value="<?= e($k) ?>"><?= e($l) ?></option><?php endforeach; ?></select></label>
    <label class="f" style="width:170px">Mật khẩu<input type="password" name="password" minlength="6" required autocomplete="new-password"></label>
    <button class="btn btn-ink" type="submit">Thêm</button></form>
</div>

<div class="table-wrap"><table class="t">
  <thead><tr><th>Tên đăng nhập</th><th>Họ tên và vai trò</th><th>Trạng thái</th></tr></thead>
  <tbody>
  <?php foreach ($users as $u): ?>
    <tr>
      <td><b><?= e($u['username']) ?></b><?= (int) $u['id'] === (int) $me['id'] ? ' ' . pill('Bạn', 'acc') : '' ?></td>
      <td>
        <form method="post" class="row" style="align-items:flex-end"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $u['id'] ?>"><input type="hidden" name="username" value="<?= e($u['username']) ?>">
          <label class="f" style="width:190px">Họ tên<input type="text" name="full_name" value="<?= e($u['full_name']) ?>" required></label>
          <label class="f" style="width:140px">Vai trò<select name="role"><?php foreach (ROLE_LABEL as $k => $l): ?><option value="<?= e($k) ?>" <?= $u['role'] === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></label>
          <label class="f" style="width:170px">Mật khẩu mới <span class="hint">(bỏ trống nếu giữ)</span><input type="password" name="password" autocomplete="new-password"></label>
          <label class="row" style="gap:6px;height:40px"><input type="checkbox" name="locked" <?= $u['locked'] ? 'checked' : '' ?>> Khóa</label>
          <button class="btn btn-sm" type="submit">Lưu</button></form></td>
      <td><?= pill($u['locked'] ? 'Đã khóa' : 'Hoạt động', $u['locked'] ? 'neg' : 'pos') ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody></table></div>
<?php staff_close(); ?>