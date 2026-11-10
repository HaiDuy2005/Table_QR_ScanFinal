<?php
require __DIR__ . '/../../app/init.php';
require ROOT_DIR . '/views/customer_layout.php';

[$table, $session] = customer_context(get('table'));
$sid = (int) $session['id'];

if (is_post()) { // tăng/giảm số phần trong giỏ
    handle_post(function () use ($sid) {
        cart_set_qty($sid, (int) post('menu_id'), (int) post('qty'));
        return '';
    });
}

$cat = get('cat', 'Tất cả');
$kw = get('q');
$cart = cart_get($sid);

$list = array_filter(menu_all(), function ($m) use ($cat, $kw) {
    return ($cat === 'Tất cả' || $m['category'] === $cat)
        && ($kw === '' || mb_stripos($m['name'], $kw) !== false || mb_stripos($m['description'], $kw) !== false);
});

$total = 0;
foreach ($cart as $mid => $c) {
    $m = menu_find((int) $mid);
    $total += $m ? $c['qty'] * (int) $m['price'] : 0;
}

$count = cart_count($sid);
$self = url('public/customer/menu.php');

customer_open($table, $session, 'menu', 'Thực đơn');
?>

<!-- NÚT QUAY LẠI -->
<div class="menu-back">
 <a href="<?= e(url('public/index.php')) ?>" class="btn-back">
    <span aria-hidden="true">←</span> Quay lại
</a>
</div>

<div class="searchbar">
    <form method="get" action="<?= e($self) ?>">
        <input type="hidden" name="table" value="<?= e($table['code']) ?>">
        <input type="hidden" name="cat" value="<?= e($cat) ?>">
        <input type="search" name="q" value="<?= e($kw) ?>"
               placeholder="Tìm món (ví dụ: phở, cà phê)"
               aria-label="Tìm món">
    </form>

    <div class="chips">
        <?php foreach (array_merge(['Tất cả'], CATEGORIES) as $c): ?>
            <a class="<?= $cat === $c ? 'on' : '' ?>"
               href="<?= e($self . '?table=' . urlencode($table['code']) . '&cat=' . urlencode($c) . ($kw !== '' ? '&q=' . urlencode($kw) : '')) ?>">
                <?= e($c) ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<div data-live>
    <ul class="dishes" aria-label="Danh sách món">

        <?php if (!$list): ?>
            <li class="pad" style="background:#fff">
                <div class="empty">
                    <h3>Không tìm thấy món phù hợp</h3>
                    <p class="muted">Thử từ khóa khác hoặc chọn mục “Tất cả”.</p>
                </div>
            </li>
        <?php endif; ?>

        <?php $i = 0; foreach ($list as $m): $qty = (int) ($cart[$m['id']]['qty'] ?? 0); ?>
            <li class="dish" style="--i:<?= min($i++, 8) ?>">
                <div class="pic">
                    <?= dish_art($m, $m['available'] ? '' : 'off') ?>
                </div>

                <div class="info">
                    <h2><?= e($m['name']) ?></h2>
                    <p class="desc"><?= e($m['description']) ?></p>

                    <div class="foot">
                        <div>
                            <b style="font-size:16px"><?= e(money($m['price'])) ?></b>
                            <div class="tiny muted">/<?= e($m['unit']) ?></div>
</div>

                        <?php if (!(int) $m['available']): ?>
                            <?= pill('Tạm hết') ?>

                        <?php elseif ($qty === 0): ?>
                            <form method="post" data-ajax>
                                <?= csrf_field() ?>
                                <input type="hidden" name="menu_id" value="<?= (int) $m['id'] ?>">
                                <input type="hidden" name="qty" value="1">
                                <button class="btn btn-accent" type="submit"
                                        aria-label="Thêm <?= e($m['name']) ?> vào giỏ">
                                    Thêm
                                </button>
                            </form>

                        <?php else: ?>
                            <div class="stepper">
                                <form method="post" data-ajax>
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="menu_id" value="<?= (int) $m['id'] ?>">
                                    <input type="hidden" name="qty" value="<?= $qty - 1 ?>">
                                    <button type="submit" aria-label="Bớt một phần">−</button>
                                </form>

                                <b><?= $qty ?></b>

                                <form method="post" data-ajax>
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="menu_id" value="<?= (int) $m['id'] ?>">
                                    <input type="hidden" name="qty" value="<?= $qty + 1 ?>">
                                    <button type="submit" aria-label="Thêm một phần">+</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </li>
        <?php endforeach; ?>

    </ul>

    <?php if ($count > 0): ?>
        <div class="cartbar">
            <a href="<?= e(url('public/customer/cart.php?table=' . urlencode($table['code']))) ?>">
                <span><?= $count ?> phần trong giỏ</span>
                <span>Xem giỏ · <?= e(money($total)) ?></span>
            </a>
        </div>
    <?php endif; ?>
</div>

<style>
    .off {
        opacity: .4;
        filter: grayscale(1);
    }

    .menu-back {
        padding: 8px 14px 4px;
    }

    .menu-back button {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 7px 13px;
        border: 1px solid #444;
        border-radius: 7px;
        background: #111;
        color: #fff;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: background .2s;
    }

    .menu-back button:hover {
        background: #333;
    }

    .menu-back button span {
        font-size: 17px;
    }
</style>
<?php customer_close($table, $session, 'menu'); ?>