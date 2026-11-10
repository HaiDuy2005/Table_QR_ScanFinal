<?php
require __DIR__ . '/../app/init.php';
require ROOT_DIR . '/views/layout.php';

if (current_user()) {
    redirect('public/dashboard.php');
}
if (is_post()) {
    try {
        csrf_check();
        login_user(post('username'), (string) ($_POST['password'] ?? ''));
        redirect('public/dashboard.php');
    } catch (AppError $ex) {
        flash($ex->getMessage(), 'err');
        redirect('public/login.php');
    }
}
view_open('Đăng nhập nhân viên');
?>
<div class="login">
  <div class="card2">
    <div class="row" style="gap:8px"><?= mark_svg(26) ?><b>RestaurantQR</b></div>
    <h1 class="display">Đăng nhập nhân viên</h1>
    <div id="flash"><?= flash_render() ?></div>
    <form method="post" class="grid gap">
      <?= csrf_field() ?>
      <label class="f">Tên đăng nhập<input type="text" name="username" autocomplete="username" autofocus required></label>
      <label class="f">Mật khẩu<input type="password" name="password" autocomplete="current-password" required></label>
      <button class="btn btn-ink btn-lg" type="submit">Đăng nhập</button>
    </form>
    <a href="<?= e(url('public/index.php')) ?>" class="muted" style="text-align:center">← Về trang gọi món</a>
  </div>
</div>
<?php view_close(); ?>