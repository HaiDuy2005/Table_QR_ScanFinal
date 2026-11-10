<?php
/*
 * Tạo hoặc đặt lại mật khẩu tài khoản quản trị (dùng khi quên mật khẩu admin).
 * Mở http://localhost/RestaurantQR/create_admin.php, nhập thông tin rồi bấm nút.
 * SAU KHI DÙNG XONG hãy xóa file này khỏi thư mục dự án.
 */
require __DIR__ . '/app/init.php';
require ROOT_DIR . '/views/layout.php';

$done = '';
if (is_post()) {
    try {
        csrf_check();
        $username = post('username');
        $pass = (string) ($_POST['password'] ?? '');
        if (!preg_match('/^[a-z0-9_]{3,30}$/', $username)) {
            throw new AppError('Tên đăng nhập gồm chữ thường, số, gạch dưới (3–30 ký tự).');
        }
        if (strlen($pass) < 6) {
            throw new AppError('Mật khẩu cần ít nhất 6 ký tự.');
        }
        $old = user_by_username($username);
        if ($old) {
            user_update((int) $old['id'], $old['full_name'], 'admin', false, $pass);
            $done = "Đã đặt lại mật khẩu và mở khóa tài khoản quản trị “$username”.";
        } else {
            user_insert($username, 'Quản trị viên', $pass, 'admin');
            $done = "Đã tạo tài khoản quản trị “$username”.";
        }
        audit($username, 'Tạo/đặt lại quản trị viên', $username);
    } catch (AppError $ex) {
        flash($ex->getMessage(), 'err');
    }
}
view_open('Tạo tài khoản quản trị');
?>
<div class="login">
  <div class="card2">
    <h1 class="display" style="font-size:32px">Tạo quản trị viên</h1>
    <div id="flash"><?= flash_render() ?></div>
    <?php if ($done): ?><div class="flash flash-ok"><?= e($done) ?></div>
      <a class="btn btn-ink btn-lg" href="<?= e(url('public/login.php')) ?>">Đi tới đăng nhập</a>
      <p class="tiny muted">Nhớ xóa file create_admin.php sau khi dùng xong.</p>
    <?php else: ?>
    <form method="post" class="grid gap">
      <?= csrf_field() ?>
      <label class="f">Tên đăng nhập<input type="text" name="username" value="admin" required></label>
      <label class="f">Mật khẩu mới<input type="password" name="password" minlength="6" required></label>
      <button class="btn btn-ink btn-lg" type="submit">Tạo / đặt lại</button>
    </form>
    <p class="tiny muted">Nếu tên đăng nhập đã có, mật khẩu sẽ được đặt lại và tài khoản được mở khóa.</p>
    <?php endif; ?>
  </div>
</div>
<?php view_close(); ?>