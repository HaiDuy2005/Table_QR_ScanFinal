<?php
require __DIR__ . '/../../app/init.php';
require ROOT_DIR . '/views/staff_layout.php';
require_role(['admin']);

$id = (int) get('id');
$m = $id ? menu_find($id) : null;
if ($id && !$m) {
    flash('Không tìm thấy món.', 'err');
    redirect('public/admin/menu.php');
}
$old = $_SESSION['old'] ?? null; // giữ lại dữ liệu đã nhập nếu lưu bị lỗi
unset($_SESSION['old']);
$v = function (string $k, $default = '') use ($m, $old) {
    if ($old !== null) {
        return $old[$k] ?? $default;
    }
    return $m[$k] ?? $default;
};
$avail = $old !== null ? isset($old['available']) : ($m ? (int) $m['available'] === 1 : true);
staff_open($m ? 'Sửa món' : 'Thêm món', 'public/admin/menu.php');
?>
<h1><?= $m ? 'Sửa món' : 'Thêm món' ?></h1>
<p class="sub">Nếu không có ảnh thật, hệ thống tự vẽ tranh minh họa theo “Kiểu tranh”.</p>
<form method="post" enctype="multipart/form-data" action="<?= e(url('public/admin/menu_save.php')) ?>" class="formgrid two-c">
  <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
  <label class="f" style="grid-column:1/-1">Tên món<input type="text" name="name" value="<?= e($v('name')) ?>" maxlength="120" required></label>
  <label class="f">Nhóm món<select name="category"><?php foreach (CATEGORIES as $c): ?><option <?= $v('category', CATEGORIES[0]) === $c ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?></select></label>
  <label class="f">Đơn vị tính<input type="text" name="unit" value="<?= e($v('unit', 'phần')) ?>" maxlength="40"></label>
  <label class="f">Giá (₫)<input type="number" name="price" value="<?= e($v('price')) ?>" min="1000" step="500" required></label>
  <label class="f">Kiểu tranh minh họa<select name="art"><?php foreach (ART_KINDS as $a): ?><option <?= $v('art', 'bowl') === $a ? 'selected' : '' ?>><?= e($a) ?></option><?php endforeach; ?></select></label>
  <label class="f">Tông nền (0–5)<input type="number" name="tone" value="<?= e($v('tone', 0)) ?>" min="0" max="5"></label>
  <label class="f">
    Ảnh 
    <span class="hint">(Tùy chọn, JPG, PNG, WEBP – tối đa 5 MB)</span>
    <input type="file" name="image_file" accept="image/jpeg,image/png,image/webp">
    <?php if (!empty($m['image_url'])): ?>
        <img src="<?= e($m['image_url']) ?>" alt="Ảnh món hiện tại"
             style="width:100px;height:100px;object-fit:cover;margin-top:8px;border-radius:8px">
        <small class="hint">Chọn ảnh mới nếu muốn thay ảnh hiện tại.</small>
    <?php endif; ?>
</label>
  <label class="f" style="grid-column:1/-1">Mô tả<textarea name="description" maxlength="300"><?= e($v('description')) ?></textarea></label>
  <label class="row" style="grid-column:1/-1;gap:8px"><input type="checkbox" name="available" <?= $avail ? 'checked' : '' ?>> Đang bán</label>
  <div class="row" style="grid-column:1/-1"><button class="btn btn-ink btn-lg" type="submit">Lưu món</button><a class="btn btn-ghost btn-lg" href="<?= e(url('public/admin/menu.php')) ?>">Hủy</a></div>
</form>
<?php staff_close(); ?>