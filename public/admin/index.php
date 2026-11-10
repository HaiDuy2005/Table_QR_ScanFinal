<?php
require __DIR__ . '/../../app/init.php';
require ROOT_DIR . '/views/staff_layout.php';
require_role(['admin']);
$s = stats_today();
staff_open('Tổng quan', 'public/admin/index.php', 10);
?>
<h1>Quản trị · Tổng quan</h1>
<p class="sub">Số liệu được truy vấn trực tiếp từ MySQL, cập nhật mỗi 10 giây.</p>
<div data-live>
  <div class="kpis">
    <div><small>Doanh thu hôm nay</small><b><?= e(money($s['revenue'])) ?></b><small><?= $s['paid_invoices'] ?> hóa đơn đã thanh toán</small></div>
    <div><small>Đơn gọi món hôm nay</small><b><?= $s['orders'] ?></b></div>
    <div><small>Món đang bán</small><b><?= $s['menu_available'] ?></b><small>trên <?= $s['menu_total'] ?> món trong thực đơn</small></div>
    <div><small>Tài khoản hoạt động</small><b><?= $s['users_active'] ?></b><small>trên <?= $s['users_total'] ?> tài khoản</small></div>
  </div>
  <div class="grid gap" style="grid-template-columns:repeat(auto-fill,minmax(220px,1fr))">
    <a class="panel" style="text-decoration:none" href="<?= e(url('public/admin/menu.php')) ?>"><h2>Thực đơn →</h2><span class="muted">Thêm, sửa món, đổi giá, đánh dấu tạm hết.</span></a>
    <a class="panel" style="text-decoration:none" href="<?= e(url('public/admin/tables.php')) ?>"><h2>Bàn và mã QR →</h2><span class="muted">Thêm bàn, xem và tạo lại mã QR.</span></a>
    <a class="panel" style="text-decoration:none" href="<?= e(url('public/admin/users.php')) ?>"><h2>Tài khoản →</h2><span class="muted">Tạo nhân viên, phân vai trò, đổi mật khẩu, khóa.</span></a>
    <a class="panel" style="text-decoration:none" href="<?= e(url('public/admin/audit.php')) ?>"><h2>Nhật ký →</h2><span class="muted">Ai đã làm gì, lúc nào, lý do hủy món.</span></a>
  </div>
</div>
<?php staff_close(); ?>