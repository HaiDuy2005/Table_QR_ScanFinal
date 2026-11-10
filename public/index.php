<?php
require __DIR__ . '/../app/init.php';
require ROOT_DIR . '/views/layout.php';

$tables = tables_with_open_flag();
$menu = menu_all();
$jsTables = [];
foreach ($tables as $t) {
    $jsTables[] = [
        'code' => $t['code'], 'name' => $t['name'], 'seats' => (int) $t['seats'], 'open' => (int) $t['is_open'] > 0,
        'num' => str_pad((string) ((int) preg_replace('/\D/', '', $t['code'])), 2, '0', STR_PAD_LEFT),
        'url' => full_url('public/customer/join.php?table=' . $t['code'] . '&k=' . $t['qr_key']),
        'join' => url('public/customer/join.php?table=' . $t['code'] . '&k=' . $t['qr_key']),
    ];
}
$picks = array_slice(array_merge(array_filter([$menu[4] ?? null, $menu[8] ?? null, $menu[14] ?? null, $menu[17] ?? null]), $menu), 0, 4);
$picks = array_values(array_unique($picks, SORT_REGULAR));
$flash = flash_render();
view_open('Gọi món bằng mã QR', 'home');
?>
<div class="scroll-bar"></div>
<script>window.TABLES = <?= json_encode($jsTables, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;</script>

<?php if (!$tables || !$menu): ?>
  <div class="hero" style="display:grid;place-items:center;text-align:center;padding:24px;color:var(--ink)">
    <div><h1 class="display" style="font-size:44px">RestaurantQR</h1>
      
      <p style="margin-top:16px"><a href="<?= e(url('public/login.php')) ?>">Đăng nhập nhân viên</a></p></div>
  </div>
<?php else: ?>

<section class="hero">
  <header class="topbar">
    <a class="brand" href="#"><?= mark_svg(34) ?> RestaurantQR</a>
    <nav aria-label="Mục trên trang"><a href="#chon-ban">Chọn bàn</a><a href="#quy-trinh">Quy Trình</a><a href="#thuc-don">Thực Đơn</a><a href="#so-lieu">Thông Tin</a></nav>
    <a class="btn btn-ink btn-lg" style="height:44px;font-size:15px" href="<?= e(url('public/login.php')) ?>">Đăng Nhập Nhân Viên</a>
  </header>
  <?php if ($flash): ?><div style="max-width:460px;margin:0 auto;padding:0 12px"><?= $flash ?></div><?php endif; ?>

  <div class="ticket">
    <div class="ticket-in">
      <div class="row" style="justify-content:center;font-weight:600;font-size:15px;gap:8px"><?= mark_svg(22) ?> RestaurantQR</div>
      <h1 class="display" aria-label="Gọi món bằng mã QR">
        <span><?php foreach (preg_split('//u', 'Gọi món', -1, PREG_SPLIT_NO_EMPTY) as $i => $c): ?><i style="--i:<?= $i ?>"><?= $c === ' ' ? '&nbsp;' : e($c) ?></i><?php endforeach; ?></span>
        <span><?php foreach (preg_split('//u', 'bằng mã QR', -1, PREG_SPLIT_NO_EMPTY) as $i => $c): ?><i style="--i:<?= $i ?>;--o:.5s"><?= $c === ' ' ? '&nbsp;' : e($c) ?></i><?php endforeach; ?></span>
      </h1>
      <p class="tags">Không cần tài khoản <span class="diamond"></span> Bếp nhận đơn ngay <span class="diamond"></span> Theo dõi từng phần</p>

      <div class="picker">
        <div class="qr"><div id="pk-qr" data-qr="" data-size="104"></div></div>