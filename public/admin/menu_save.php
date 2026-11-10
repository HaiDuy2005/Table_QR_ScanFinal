<?php

require __DIR__ . '/../../app/init.php';
require_role(['admin']);
if (!is_post()) {
    redirect('public/admin/menu.php');
}
$id = (int) post('id');
try {
    csrf_check();
    admin_save_menu();
    flash('Đã lưu món vào cơ sở dữ liệu.');
    redirect('public/admin/menu.php');
} catch (AppError $ex) {
    flash($ex->getMessage(), 'err');
    $_SESSION['old'] = $_POST;
    redirect('public/admin/menu_form.php' . ($id ? '?id=' . $id : ''));
}