<?php
require __DIR__ . '/../../app/init.php';
require ROOT_DIR . '/views/layout.php';

$code = $_POST['table'] ?? get('table');
$key = get('k');
$table = table_by_code((string) $code);

if (is_post()) { // khách nhập mã 4 số
    try {
        csrf_check();
        customer_join((string) $code, $key, post('code'));
        redirect('public/customer/menu.php?table=' . urlencode($table['code']));
    } catch (AppError $ex) {
        flash($ex->getMessage(), 'err');
        redirect('public/customer/join.php?table=' . urlencode((string) $code) . ($key ? '&k=' . urlencode($key) : ''));
    }
}
if (!$table || !(int) $table['active']) {
    flash('Không tìm thấy bàn này.', 'err');
    redirect('public/index.php');
}
$session = session_open_of((int) $table['id']);
// Đã tham gia từ trước (phiên PHP còn hiệu lực) thì vào thẳng thực đơn
if ($session && ($_SESSION['grants'][$table['id']] ?? null) === (int) $session['id']) {
    redirect('public/customer/menu.php?table=' . urlencode($table['code']));
}
// QR in sẵn mã (do thu ngân in/hiển thị) thì vào tự động
$prefill = get('c');
if ($session && preg_match('/^\d{4}$/', $prefill)) {
    try {
        customer_join($table['code'], $key, $prefill);
        redirect('public/customer/menu.php?table=' . urlencode($table['code']));
    } catch (AppError $ex) {
        flash($ex->getMessage(), 'err');
    }
}
view_open('Vào bàn ' . $table['name']);
?>
<div class="login">
  <div class="card2">
    <div class="row" style="gap:8px"><?= mark_svg(26) ?><b>RestaurantQR</b></div>
    <h1 class="display"><?= e($table['name']) ?></h1>
    <div id="flash"><?= flash_render() ?></div>
    <?php if ($session): ?>
      <form method="post" class="grid gap">
        <?= csrf_field() ?><input type="hidden" name="table" value="<?= e($table['code']) ?>">
        <label class="f">Nhập mã 4 số do nhân viên đưa
          <input class="codein" type="text" name="code" inputmode="numeric" maxlength="4" pattern="[0-9]{4}" placeholder="••••" autocomplete="off" autofocus required></label>
        <button class="btn btn-ink btn-lg" type="submit">Vào bàn và chọn món</button>
      </form>
    <?php else: ?>
      <div class="empty"><h3>Bàn này chưa được mở</h3><p class="muted">Hãy gọi nhân viên để mở bàn, rồi tải lại trang.</p></div>
    <?php endif; ?>
    <a href="<?= e(url('public/index.php')) ?>" class="muted" style="text-align:center">← Chọn bàn khác</a>
  </div>
</div>
<?php view_close(); ?>
