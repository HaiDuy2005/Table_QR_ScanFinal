<?php
require_once __DIR__ . '/layout.php';


function staff_open(string $title, string $active, int $refresh = 0): void
{
    $u = current_user();
    $nav = [
        ['public/staff/cashier.php', 'Thu ngân', '₫', ['cashier']],
        ['public/staff/kitchen.php', 'Bếp', '♨', ['kitchen']],
        ['public/staff/waiter.php', 'Phục vụ', '➜', ['waiter']],
        ['public/admin/index.php', 'Tổng quan', '▦', ['admin']],
        ['public/admin/menu.php', 'Thực đơn', '☰', ['admin']],
        ['public/admin/tables.php', 'Bàn và QR', '▣', ['admin']],
        ['public/admin/users.php', 'Tài khoản', '☺', ['admin']],
        ['public/admin/audit.php', 'Nhật ký', '✎', ['admin']],
    ];
    view_open($title, '', $refresh);
    ?>
<div class="shell">
  <aside class="side no-print">
    <div class="logo"><?= mark_svg(30) ?><div><b>RestaurantQR</b><div class="muted tiny" style="display:none">Quán · MySQL XAMPP</div></div></div>
    <nav aria-label="Điều hướng nhân viên">
      <?php foreach ($nav as [$href, $label, $icon, $roles]):
          if ($u['role'] !== 'admin' && !in_array($u['role'], $roles, true)) continue;
          ?>
        <a href="<?= e(url($href)) ?>" class="<?= $active === $href ? 'on' : '' ?>"><i><?= $icon ?></i><?= e($label) ?></a>
      <?php endforeach; ?>
    </nav>
  </aside>
  <header class="sbar no-print">
    <p class="muted"><?= e($title) ?> · Giờ Việt Nam <span id="clock"><?= date('H:i') ?></span></p>
    <div class="row">
      <span class="muted" style="font-weight:500"><?= e($u['full_name']) ?></span>
      <?= pill(ROLE_LABEL[$u['role']], 'acc') ?>
      <a class="btn btn-ghost btn-sm" href="<?= e(url('public/logout.php')) ?>">Đăng xuất</a>
    </div>
  </header>
  <main class="sheet">
    <div id="flash"><?= flash_render() ?></div>
<?php
}
function staff_close(): void
{
    ?>
  </main>
</div>
<script>setInterval(function(){var d=new Date();document.getElementById('clock').textContent=('0'+d.getHours()).slice(-2)+':'+('0'+d.getMinutes()).slice(-2)},20000)</script>
<?php
    view_close();
}