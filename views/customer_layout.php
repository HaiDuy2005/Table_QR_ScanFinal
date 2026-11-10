<?php
require_once __DIR__ . '/layout.php';


function customer_open(array $table, array $session, string $active, string $title, int $refresh = 0): void
{
    view_open($title . ' · ' . $table['name'], '', $refresh);
    ?>
<div class="cust">
  <header>
    <div class="row between">
      <div>
        <h1 class="display"><?= e($table['name']) ?></h1>
        <p class="tiny" style="font-weight:500;margin-top:6px">Phiên mở lúc <?= e(fmt_time($session['opened_at'])) ?> · RestaurantQR</p>
      </div>
      <span class="live"><span class="dot"></span>Đang phục vụ</span>
    </div>
  </header>
  <main>
    <div id="flash" class="pad" style="padding-bottom:0"><?= flash_render() ?></div>
<?php
}
function customer_close(array $table, array $session, string $active): void
{
    $code = urlencode($table['code']);
    $count = cart_count((int) $session['id']);
    $tabs = [
        ['menu', 'Thực đơn', '☰'],
        ['cart', 'Giỏ hàng', '🛒'],
        ['orders', 'Đơn của bàn', '🧾'],
    ];
    ?>
  </main>
  <nav class="botnav" aria-label="Điều hướng của khách">
    <?php foreach ($tabs as [$k, $label, $icon]): ?>
      <a href="<?= e(url("public/customer/$k.php?table=$code")) ?>" class="<?= $active === $k ? 'on' : '' ?>">
        <span><?= $icon ?></span><?= e($label) ?>
        <?php if ($k === 'cart' && $count > 0): ?><span class="badge"><?= (int) $count ?></span><?php endif; ?>
      </a>
    <?php endforeach; ?>
  </nav>
</div>
<?php
    view_close();
}
