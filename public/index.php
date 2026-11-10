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
        <div class="grid" style="gap:8px">
          <div class="nav">
            <button type="button" class="sq" data-step="-1" aria-label="Bàn trước">‹</button>
            <p class="num display">Bàn <span id="pk-num">01</span></p>
            <button type="button" class="sq" data-step="1" aria-label="Bàn sau">›</button>
          </div>
          <p id="pk-state" style="font-weight:600;font-size:14px"></p>
          <button type="button" id="pk-try" class="btn btn-ink btn-lg" style="height:44px;font-size:15px" data-open="join-dialog">Quét thử bàn này →</button>
        </div>
      </div>

      <ul class="menu-dots">
        <?php foreach (array_slice($menu, 0, 5) as $m): ?>
          <li><span><?= e($m['name']) ?></span><i></i><b><?= e(money($m['price'])) ?></b></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
</section>

<section id="chon-ban" class="section white">
  <div class="wrap">
    <div class="reveal"><h2 class="display">Bạn ngồi bàn nào?</h2>
      <p class="lead muted">Bấm vào bàn của bạn, quét mã QR hoặc nhập mã 4 số, rồi chọn món. Bàn nào đang mở phiên mới vào được.</p></div>
    <ul class="table-grid">
      <?php foreach ($jsTables as $i => $t): ?>
        <li class="reveal" style="--d:<?= (($i % 5) * 0.05 + floor($i / 5) * 0.06) ?>s">
          <button type="button" class="tcard <?= $t['open'] ? 'open' : '' ?>" data-open="join-dialog" data-table="<?= e($t['code']) ?>" aria-label="<?= e($t['name']) ?>, <?= $t['open'] ? 'đang mở' : 'chưa mở' ?>">
            <span class="n"><?= e($t['num']) ?></span>
            <span style="font-weight:500;font-size:14px"><?= e($t['name']) ?> · <?= (int) $t['seats'] ?> chỗ</span>
            <?= pill($t['open'] ? 'Đang mở' : 'Chưa mở', $t['open'] ? 'pos' : 'neu') ?>
          </button>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>

<section id="quy-trinh" class="section ink" style="padding-top:96px;padding-bottom:140px">
  <div class="slip-wrap">
    <div class="reveal"><h2 class="display" style="font-size:clamp(40px,4.4vw,68px);line-height:.98">Hương vị ngon, trọn niềm vui</h2>
      <p class="lead" style="max-width:30ch;color:rgba(255,255,255,.85)">Chúng tôi mang đến những món ăn thơm ngon, được chế biến từ nguyên liệu tươi ngon và phục vụ tận tâm. Mỗi món ăn là sự kết hợp giữa hương vị đặc trưng và tình yêu ẩm thực, giúp bạn tận hưởng những khoảnh khắc tuyệt vời bên gia đình và bạn bè.</p></div>
    <div class="slip reveal">
      <p style="font-weight:600;font-size:15px">Ví dụ một đơn của Bàn 07</p>
      <?php foreach ([
          ['Khách ngồi bàn', [['Quét mã QR, nhập mã tham gia', '18:40'], ['Gửi đơn DH0001', '18:42']]],
          ['Bếp', [['Nhận đơn, bắt đầu làm', '18:43'], ['Phở bò tái × 2 xong', '18:55']]],
          ['Phục vụ', [['Mang ra bàn từng phần', '18:57']]],
          ['Thu ngân', [['Chốt hóa đơn', '19:20'], ['Xác nhận đã nhận tiền', '19:22']]],
      ] as [$head, $rows]): ?>
        <h3><?= e($head) ?></h3>
        <ul><?php foreach ($rows as [$t, $time]): ?><li><span><?= e($t) ?></span><i></i><span><?= e($time) ?></span></li><?php endforeach; ?></ul>
      <?php endforeach; ?>
      <div class="total"><span>Đóng phiên, bàn trống lại</span><span>43 phút</span></div>
    </div>
  </div>
</section>

<section id="thuc-don" class="section orange" style="padding:0">
  <div class="dish-split">
    <div class="dish-pic">
      <?php foreach ($picks as $i => $m): ?>
        <div class="dish-sq" data-dish="<?= (int) $m['id'] ?>" <?= $i ? 'hidden' : '' ?>><?= dish_art($m) ?></div>
      <?php endforeach; ?>
    </div>
    <div class="dish-info">
      <div class="dtabs"><?php foreach ($picks as $i => $m): ?><button type="button" data-for="<?= (int) $m['id'] ?>" class="<?= $i ? '' : 'on' ?>"><?= e($m['name']) ?></button><?php endforeach; ?></div>
      <div>
        <h2 class="display" style="font-size:clamp(34px,3.6vw,52px);line-height:1">Món nào cũng có giá và ghi chú</h2>
        <?php foreach ($picks as $i => $m): ?>
          <div data-dish="<?= (int) $m['id'] ?>" <?= $i ? 'hidden' : '' ?> class="fade-in">
            <p style="margin-top:32px;font-size:22px;font-weight:600"><?= e($m['name']) ?> · <?= e(money($m['price'])) ?></p>
            <p style="margin-top:8px;font-size:19px;max-width:34ch"><?= e($m['description']) ?></p>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="cta2">
        <button type="button" class="btn-ink" style="background:var(--ink);color:var(--brand)" data-open="join-dialog">Gọi món thử</button>
        <a href="#so-lieu" style="background:var(--brand-soft);color:var(--ink)">Xem phạm vi</a>
      </div>
    </div>
  </div>
</section>

<section id="so-lieu" class="section white" style="padding-top:110px;padding-bottom:110px">
  <div class="two">
    <h2 class="display reveal" style="font-size:clamp(32px,3.2vw,52px);line-height:1.02"><span style="display:block;color:rgba(5,15,22,.7)">Trọn vị yêu thương, </span>đậm chất Việt</h2>
    <ul class="ledger">
      <?php foreach ([[count($tables), 'bàn trong quán, mỗi bàn một mã QR riêng'], [count($menu), 'món trong thực đơn'], [7 , 'giờ quán bắt đầu mở cửa'], [22, 'giờ quán sẽ đóng cửa']] as $i => [$n, $cap]): ?>
        <li class="reveal" style="--d:<?= $i * 0.09 ?>s"><b data-count="<?= (int) $n ?>"><?= (int) $n ?></b><p style="font-size:16px"><?= e($cap) ?></p></li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>

<section class="section ink" style="padding-bottom:0;overflow:hidden">
  <div class="two" style="align-items:end">
    <p style="font-size:22px;max-width:28ch;padding-bottom:64px"></p>
    <div class="cta-card reveal">
      <h2 class="display">Vào quầy thu ngân</h2>
      <p style="margin:12px auto 0;max-width:34ch;font-size:17px">Đăng nhập để mở bàn, theo dõi đơn và chốt hóa đơn.</p>
      <a class="btn btn-ink btn-block btn-lg" style="margin-top:28px;height:56px" href="<?= e(url('public/login.php')) ?>">Đăng nhập nhân viên →</a>
    </div>
  </div>
</section>

<footer class="site-foot">
  <div class="foot-card">
    <div><p class="display" style="font-size:32px;line-height:1">Chúc ngon miệng</p><p style="margin-top:14px;max-width:34ch;font-size:16px">Nhóm 02 – Ctrl Shift N.</p></div>
    <address class="grid" style="gap:12px;align-content:start;font-style:normal">
      <a href="<?= e(url('public/login.php')) ?>">Đăng nhập nhân viên</a><a href="#quy-trinh">Quy trình một đơn</a><a href="#thuc-don">Thực đơn</a>
    </address>
  </div>
  <p class="tiny" style="text-align:center;margin-top:16px">© RestaurantQR · Nhóm 02 · Ctrl Shift N</p>
</footer>

<!-- Hộp thoại: vào bàn bằng mã QR hoặc mã 4 số -->
<dialog id="join-dialog" aria-labelledby="dlg-title">
  <div class="dlg">
    <div class="row between"><h2 class="display" id="dlg-title">Bàn</h2><button type="button" class="btn btn-ghost btn-sm" data-close>Đóng ✕</button></div>
    <div id="dlg-state"></div>
    <div id="dlg-closed" hidden class="empty"><h3>Bàn này chưa mở phiên</h3><p class="muted">Hãy gọi nhân viên để mở bàn rồi quay lại.</p></div>
    <div id="dlg-form">
      <div class="tabs2" role="tablist"><button type="button" class="on" data-tab="code">Nhập mã 4 số</button><button type="button" data-tab="qr">Quét mã QR</button></div>
      <form method="post" action="<?= e(url('public/customer/join.php')) ?>" data-pane="code" class="grid gap" style="margin-top:12px">
        <?= csrf_field() ?><input type="hidden" name="table" id="dlg-table">
        <label class="f">Mã tham gia do nhân viên đưa<input class="codein" type="text" name="code" inputmode="numeric" maxlength="4" pattern="[0-9]{4}" placeholder="••••" autocomplete="off" required></label>
        <button class="btn btn-ink btn-lg" type="submit">Vào bàn và chọn món</button>
      </form>
      <div data-pane="qr" hidden class="grid gap" style="margin-top:12px;justify-items:center;text-align:center">
        <div class="qrbox"><div id="dlg-qr" data-qr="" data-size="170"></div></div>
        <p class="muted">Quét mã này bằng điện thoại để mở trang của bàn, rồi nhập mã 4 số nhân viên đưa. Trên máy này bấm nút dưới.</p>
        <a id="dlg-qrlink" class="btn btn-ink btn-lg" href="#">Mở trang của bàn</a>
      </div>
    </div>
  </div>
</dialog>
<?php endif; ?>
<?php view_close(); ?>
