<?php
/* ===== Hàm dùng chung: hiển thị, địa chỉ, thông báo, CSRF ===== */

const CATEGORIES = ['Khai vị', 'Phở & bún', 'Cơm', 'Món chính', 'Bánh', 'Đồ uống & tráng miệng'];
const ROLE_LABEL = ['cashier' => 'Thu ngân', 'kitchen' => 'Bếp', 'waiter' => 'Phục vụ', 'admin' => 'Quản trị viên'];
const ART_KINDS = ['roll', 'salad', 'skewer', 'bowl', 'plate', 'pot', 'pancake', 'sandwich', 'glass', 'dessert'];

/** Một lỗi nghiệp vụ mà người dùng đọc được (hiện ra thành thông báo đỏ). */
class AppError extends Exception {}

function e($s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}
function money($n): string
{
    return number_format((int) $n, 0, ',', '.') . ' ₫';
}
function fmt_time($dt): string
{
    return $dt ? date('H:i', strtotime($dt)) : '';
}
function fmt_datetime($dt): string
{
    return $dt ? date('d/m/Y H:i', strtotime($dt)) : '';
}
function now(): string
{
    return date('Y-m-d H:i:s');
}

/** Đường dẫn gốc của dự án trên web, ví dụ /RestaurantQR (tự nhận theo thư mục đặt trong htdocs). */
function base_path(): string
{
    static $b = null;
    if ($b === null) {
        $doc = str_replace('\\', '/', rtrim((string) realpath($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\'));
        $root = str_replace('\\', '/', ROOT_DIR);
        $b = ($doc !== '' && strpos($root, $doc) === 0) ? substr($root, strlen($doc)) : '';
    }
    return $b;
}
/** url('public/login.php') => /RestaurantQR/public/login.php */
function url(string $path = ''): string
{
    return base_path() . '/' . ltrim($path, '/');
}
function full_url(string $path): string
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    return ($https ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'localhost') . url($path);
}
function asset(string $path): string
{
    return url('public/assets/' . $path);
}
function redirect(string $path): void
{
    header('Location: ' . (preg_match('#^(https?:)?/#', $path) ? $path : url($path)));
    exit;
}
function is_post(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}
function post(string $k, string $default = ''): string
{
    return trim((string) ($_POST[$k] ?? $default));
}
function get(string $k, string $default = ''): string
{
    return trim((string) ($_GET[$k] ?? $default));
}

/* ----- Thông báo một lần (flash) ----- */
function flash(string $msg, string $type = 'ok'): void
{
    $_SESSION['flash'][] = [$type, $msg];
}
function flash_render(): string
{
    $out = '';
    foreach ($_SESSION['flash'] ?? [] as [$t, $m]) {
        $out .= '<div class="flash flash-' . e($t) . '" role="status">' . e($m) . '</div>';
    }
    unset($_SESSION['flash']);
    return $out;
}

/* ----- CSRF: mọi form POST đều kèm mã này ----- */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}
function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}
function csrf_check(): void
{
    if (!hash_equals($_SESSION['csrf'] ?? '', (string) ($_POST['csrf'] ?? ''))) {
        throw new AppError('Phiên làm việc đã hết hạn, hãy tải lại trang và thử lại.');
    }
}

/** Chạy một thao tác POST: kiểm tra CSRF, bắt lỗi nghiệp vụ, báo kết quả rồi quay lại trang. */
function handle_post(callable $fn, ?string $back = null): void
{
    try {
        csrf_check();
        $msg = $fn();
        if (is_string($msg) && $msg !== '') {
            flash($msg);
        }
    } catch (AppError $ex) {
        flash($ex->getMessage(), 'err');
    } catch (PDOException $ex) {
        flash('Lỗi cơ sở dữ liệu: ' . $ex->getMessage(), 'err');
    }
    redirect($back ?? $_SERVER['REQUEST_URI']);
}

/* ----- Giao diện nhỏ ----- */
function pill(string $text, string $tone = 'neu'): string
{
    return '<span class="pill pill-' . e($tone) . '">' . e($text) . '</span>';
}
function mark_svg(int $size = 28): string
{
    return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 32 32" aria-hidden="true"><rect width="32" height="32" rx="7" fill="#050f16"/>'
        . '<g fill="#ff7a1f"><rect x="6" y="6" width="8" height="8" rx="1.5"/><rect x="18" y="6" width="8" height="8" rx="1.5"/><rect x="6" y="18" width="8" height="8" rx="1.5"/><rect x="18" y="18" width="3.5" height="3.5"/><rect x="22.5" y="22.5" width="3.5" height="3.5"/></g>'
        . '<g fill="#050f16"><rect x="8.5" y="8.5" width="3" height="3"/><rect x="20.5" y="8.5" width="3" height="3"/><rect x="8.5" y="20.5" width="3" height="3"/></g></svg>';
}

/** Tranh minh họa món ăn bằng SVG phẳng; nếu món có ảnh thật (image_url) thì dùng ảnh. */
function dish_art(array $m, string $class = ''): string
{
    if (!empty($m['image_url'])) {
        return '<img class="' . e($class) . '" src="' . e($m['image_url']) . '" alt="' . e($m['name']) . '" loading="lazy">';
    }
    $tones = ['#dde8f2', '#fbe4d8', '#e1eedb', '#f5ebd2', '#ebddd2', '#dbeee8'];
    $bg = $tones[((int) $m['tone']) % 6];
    return '<svg class="' . e($class) . '" viewBox="0 0 120 120" role="img" aria-label="' . e($m['name']) . '" style="background:' . $bg . '">' . dish_art_shapes($m['art']) . '</svg>';
}
function dish_art_shapes(string $kind): string
{
    $bowl = '#ffffff'; $edge = '#050f16'; $broth = '#d99a4e'; $noodle = '#f6e6bd'; $herb = '#4d9a58'; $herbD = '#2f7a42';
    $meat = '#b5503a'; $rice = '#fbf6e9'; $chili = '#d13f2c'; $egg = '#f2c14e'; $brown = '#8a5a3b'; $dark = '#5a3a28'; $cream = '#f7efd9';
    switch ($kind) {
        case 'bowl':
            return "<path d='M18 56h84a42 36 0 0 1-84 0z' fill='$bowl' stroke='$edge' stroke-width='3'/><ellipse cx='60' cy='56' rx='42' ry='9' fill='$broth' stroke='$edge' stroke-width='3'/><path d='M30 56c6-6 12-6 18 0s12 6 18 0 12-6 18 0' fill='none' stroke='$noodle' stroke-width='4' stroke-linecap='round'/><ellipse cx='44' cy='54' rx='8' ry='3.5' fill='$meat'/><ellipse cx='72' cy='55' rx='7' ry='3' fill='$meat'/><circle cx='58' cy='52' r='3' fill='$herb'/><circle cx='82' cy='52' r='2.4' fill='$herbD'/><circle cx='36' cy='52' r='2.4' fill='$herbD'/><path d='M44 38c-4-6 4-8 0-14M60 36c-4-6 4-8 0-14M76 38c-4-6 4-8 0-14' fill='none' stroke='#9aa7b4' stroke-width='3' stroke-linecap='round'/><rect x='44' y='92' width='32' height='6' rx='3' fill='$edge'/>";
        case 'plate':
            return "<ellipse cx='60' cy='68' rx='46' ry='26' fill='$bowl' stroke='$edge' stroke-width='3'/><ellipse cx='60' cy='68' rx='34' ry='18' fill='none' stroke='#c9d6e2' stroke-width='2'/><path d='M34 66c0-14 16-20 28-18 12 2 22 8 20 18-2 8-16 10-26 10s-22-2-22-10z' fill='$rice' stroke='#e8dcc0' stroke-width='2'/><rect x='68' y='56' width='22' height='9' rx='4' fill='$meat' transform='rotate(-18 79 60)'/><rect x='70' y='66' width='20' height='8' rx='4' fill='$brown' transform='rotate(10 80 70)'/><circle cx='44' cy='62' r='3' fill='$herb'/><circle cx='52' cy='58' r='2.4' fill='$herbD'/><circle cx='40' cy='70' r='2.4' fill='$egg'/>";
        case 'roll':
            return "<ellipse cx='60' cy='76' rx='44' ry='18' fill='$bowl' stroke='$edge' stroke-width='3'/><g stroke='$edge' stroke-width='2.5'><rect x='24' y='44' width='56' height='16' rx='8' fill='$cream' transform='rotate(-8 52 52)'/><rect x='34' y='58' width='56' height='16' rx='8' fill='$cream' transform='rotate(6 62 66)'/></g><circle cx='30' cy='50' r='4' fill='$meat'/><circle cx='34' cy='62' r='3' fill='$herb'/><circle cx='90' cy='68' r='4' fill='$chili'/><circle cx='92' cy='80' r='5' fill='$brown'/>";
        case 'skewer':
            $s = "<ellipse cx='60' cy='82' rx='46' ry='16' fill='$bowl' stroke='$edge' stroke-width='3'/><g stroke-linecap='round'><path d='M20 40L100 74' stroke='$dark' stroke-width='3'/><path d='M20 58L100 86' stroke='$dark' stroke-width='3'/></g>";
            foreach ([34, 48, 62, 76] as $i => $x) {
                $y = 44 + $i * 7;
                $s .= "<rect x='" . ($x - 7) . "' y='" . ($y - 6) . "' width='14' height='12' rx='5' fill='" . ($i % 2 ? $brown : $meat) . "' transform='rotate(23 $x $y)'/>";
            }
            return $s . "<circle cx='94' cy='50' r='5' fill='$herb'/><circle cx='102' cy='58' r='3.5' fill='$chili'/>";
        case 'salad':
            return "<path d='M16 54h88a44 38 0 0 1-88 0z' fill='$bowl' stroke='$edge' stroke-width='3'/><path d='M22 54c4-18 20-22 30-14 6-10 24-10 28 2 10-2 18 4 18 12z' fill='$herb'/><path d='M34 48c6-6 14-4 18 2M64 46c8-6 16-2 18 4' fill='none' stroke='$herbD' stroke-width='3' stroke-linecap='round'/><circle cx='44' cy='44' r='4' fill='$chili'/><circle cx='76' cy='42' r='3.5' fill='$egg'/><circle cx='60' cy='40' r='3' fill='$meat'/>";
        case 'pot':
            return "<path d='M20 54h80c0 28-14 42-40 42S20 82 20 54z' fill='$dark' stroke='#3b261a' stroke-width='3'/><ellipse cx='60' cy='54' rx='40' ry='9' fill='#6b3f26' stroke='#3b261a' stroke-width='3'/><ellipse cx='46' cy='53' rx='11' ry='4' fill='$brown'/><ellipse cx='74' cy='55' rx='10' ry='3.6' fill='#a8744d'/><circle cx='60' cy='52' r='2.4' fill='$herb'/><path d='M14 56c-6 0-6 8 0 8M106 56c6 0 6 8 0 8' fill='none' stroke='#3b261a' stroke-width='4' stroke-linecap='round'/>";
        case 'pancake':
            return "<ellipse cx='60' cy='74' rx='46' ry='20' fill='$bowl' stroke='$edge' stroke-width='3'/><path d='M22 72c0-22 24-34 40-34s38 12 38 34c-14 6-30 8-40 8s-26-2-38-8z' fill='$egg' stroke='#c9972f' stroke-width='2.5'/><path d='M38 66c10-6 24-6 34 0' fill='none' stroke='#fff3c4' stroke-width='4' stroke-linecap='round'/><circle cx='50' cy='60' r='4' fill='$meat'/><circle cx='72' cy='62' r='3.4' fill='#fff'/><circle cx='84' cy='56' r='4' fill='$herb'/>";
        case 'sandwich':
            return "<path d='M18 66c0-20 20-30 42-30s42 10 42 30z' fill='#e4b369' stroke='#b9822f' stroke-width='3'/><path d='M22 66h76c0 8-8 12-38 12s-38-4-38-12z' fill='$meat'/><path d='M20 72c10 8 20 4 30 8s20-4 30 0 14 0 20-8' fill='none' stroke='$herb' stroke-width='5' stroke-linecap='round'/><path d='M18 80h84c0 8-10 14-42 14S18 88 18 80z' fill='#e4b369' stroke='#b9822f' stroke-width='3'/><path d='M40 46l4 6M60 42l4 6M80 46l4 6' stroke='#fff1c9' stroke-width='3' stroke-linecap='round'/>";
        case 'glass':
            return "<path d='M34 28h52l-6 62a8 8 0 0 1-8 7H48a8 8 0 0 1-8-7z' fill='#fff' stroke='$edge' stroke-width='3'/><path d='M37 52h46l-3 38a8 8 0 0 1-8 7H48a8 8 0 0 1-8-7z' fill='$broth'/><rect x='46' y='58' width='14' height='14' rx='3' fill='#ffffffcc'/><rect x='62' y='70' width='14' height='14' rx='3' fill='#ffffffcc'/><path d='M70 20l6 40' stroke='$chili' stroke-width='4' stroke-linecap='round'/><circle cx='50' cy='42' r='3' fill='$herb'/>";
        default: // dessert
            return "<path d='M30 40h60l-6 50a8 8 0 0 1-8 7H44a8 8 0 0 1-8-7z' fill='#fff' stroke='$edge' stroke-width='3'/><rect x='34' y='50' width='52' height='11' fill='#9b4a3a'/><rect x='35' y='61' width='50' height='11' fill='$herb'/><rect x='36' y='72' width='48' height='11' fill='$egg'/><path d='M32 44h56' stroke='#fff' stroke-width='9' stroke-linecap='round'/><circle cx='46' cy='42' r='3.5' fill='#d8d0c0'/><circle cx='60' cy='40' r='3.5' fill='#d8d0c0'/><circle cx='74' cy='42' r='3.5' fill='#d8d0c0'/>";
    }
}
