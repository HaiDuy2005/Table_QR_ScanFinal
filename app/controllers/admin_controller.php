
<?php

function admin_save_menu(): int
{
    $u = assert_role(['admin']);
    $id = (int) post('id') ?: null;
    $name = post('name');
    $price = (int) post('price');

    if ($name === '' || mb_strlen($name) > 120) {
        throw new AppError('Tên món không được để trống.');
    }

    if ($price < 1000 || $price > 100000000) {
        throw new AppError('Giá món phải từ 1.000 ₫ trở lên.');
    }

    if (!in_array(post('category'), CATEGORIES, true)) {
        throw new AppError('Nhóm món không hợp lệ.');
    }

    $art = in_array(post('art'), ART_KINDS, true)
        ? post('art')
        : 'bowl';

    
    $oldMenu = $id ? menu_find($id) : null;
    $img = $oldMenu['image_url'] ?? null;

    
    if (
        isset($_FILES['image_file']) &&
        $_FILES['image_file']['error'] !== UPLOAD_ERR_NO_FILE
    ) {
        $file = $_FILES['image_file'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new AppError('Tải ảnh lên thất bại.');
        }

        if ($file['size'] > 5 * 1024 * 1024) {
            throw new AppError('Ảnh không được vượt quá 5 MB.');
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);

        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
        ];

        if (!isset($extensions[$mime])) {
            throw new AppError('Chỉ chấp nhận ảnh JPG, PNG hoặc WEBP.');
        }

        $uploadDir = ROOT_DIR . '/public/uploads/menu/';

        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
            throw new AppError('Không thể tạo thư mục lưu ảnh.');
        }

        $fileName = bin2hex(random_bytes(16))
            . '.' . $extensions[$mime];

        if (!move_uploaded_file(
            $file['tmp_name'],
            $uploadDir . $fileName
        )) {
            throw new AppError('Không thể lưu ảnh lên máy chủ.');
        }

        $img = url('public/uploads/menu/' . $fileName);
    }

    $newId = menu_save($id, [
        'name' => $name,
        'category' => post('category'),
        'description' => mb_substr(post('description'), 0, 300),
        'unit' => post('unit', 'phần') ?: 'phần',
        'price' => $price,
        'available' => isset($_POST['available']) ? 1 : 0,
        'art' => $art,
        'tone' => max(0, min(5, (int) post('tone'))),
        'image_url' => $img,
    ]);

    audit(
        $u['username'],
        $id ? 'Sửa món' : 'Thêm món',
        $name,
        money($price)
    );

    return $newId;
}

function admin_toggle_menu(int $id, bool $on): string
{
    $u = assert_role(['admin']);
    $m = menu_find($id);

    if (!$m) {
        throw new AppError('Không tìm thấy món.');
    }

    menu_set_available($id, $on);
    audit($u['username'], $on ? 'Mở bán món' : 'Tạm hết món', $m['name']);

    return $on ? 'Đã mở bán lại.' : 'Đã đánh dấu tạm hết.';
}

function admin_save_table(): string
{
    $u = assert_role(['admin']);
    $id = (int) post('id') ?: null;
    $code = strtoupper(post('code'));
    $name = post('name');
    $seats = (int) post('seats');

    if (!preg_match('/^[A-Z0-9]{1,10}$/', $code)) {
        throw new AppError('Mã bàn chỉ gồm chữ và số, tối đa 10 ký tự (ví dụ B16).');
    }

    if ($name === '' || $seats < 1 || $seats > 50) {
        throw new AppError('Hãy nhập tên bàn và số chỗ từ 1 đến 50.');
    }

    $other = table_by_code($code);

    if ($other && (int) $other['id'] !== $id) {
        throw new AppError('Mã bàn này đã tồn tại.');
    }

    $active = isset($_POST['active']);

    if ($id && !$active && session_open_of($id)) {
        throw new AppError('Bàn đang có phiên mở, hãy đóng phiên trước khi ngừng dùng.');
    }

    table_save($id, $code, $name, $seats, $active);
    audit($u['username'], $id ? 'Sửa bàn' : 'Thêm bàn', $name);

    return 'Đã lưu bàn.';
}

function admin_regenerate_qr(int $id): string
{
    $u = assert_role(['admin']);
    $t = table_find($id);

    if (!$t) {
        throw new AppError('Không tìm thấy bàn.');
    }

    table_regenerate_qr($id);
    audit($u['username'], 'Tạo lại mã QR', $t['name']);

    return 'Đã tạo mã QR mới, mã QR cũ hết hiệu lực.';
}

function admin_save_user(): string
{
    $me = assert_role(['admin']);
    $id = (int) post('id') ?: null;
    $username = post('username');
    $full = post('full_name');
    $role = post('role');
    $pass = (string) ($_POST['password'] ?? '');
    $locked = isset($_POST['locked']);

    if (!isset(ROLE_LABEL[$role])) {
        throw new AppError('Vai trò không hợp lệ.');
    }

    if ($full === '') {
        throw new AppError('Hãy nhập họ tên.');
    }

    if ($pass !== '' && strlen($pass) < 6) {
        throw new AppError('Mật khẩu cần ít nhất 6 ký tự.');
    }

    if (!$id) {
        if (!preg_match('/^[a-z0-9_]{3,30}$/', $username)) {
            throw new AppError('Tên đăng nhập gồm chữ thường, số, dấu gạch dưới (3–30 ký tự).');
        }

        if (user_by_username($username)) {
            throw new AppError('Tên đăng nhập đã tồn tại.');
        }

        if ($pass === '') {
            throw new AppError('Hãy đặt mật khẩu cho tài khoản mới.');
        }

        user_insert($username, $full, $pass, $role);
        audit($me['username'], 'Thêm tài khoản', $username, ROLE_LABEL[$role]);

        return 'Đã thêm tài khoản.';
    }

    $old = user_find($id);

    if (!$old) {
        throw new AppError('Không tìm thấy tài khoản.');
    }

    $stillAdmin = $role === 'admin' && !$locked;

    if (
        $old['role'] === 'admin' &&
        (int) $old['locked'] === 0 &&
        !$stillAdmin &&
        user_active_admins() <= 1
    ) {
        throw new AppError('Không thể bỏ quản trị viên cuối cùng.');
    }

    user_update($id, $full, $role, $locked, $pass);

    audit(
        $me['username'],
        'Sửa tài khoản',
        $old['username'],
        ROLE_LABEL[$role] .
        ($locked ? ', khóa' : '') .
        ($pass !== '' ? ', đổi mật khẩu' : '')
    );

    return 'Đã lưu tài khoản.';
}
