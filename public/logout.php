<?php
require __DIR__ . '/../app/init.php';
$u = current_user();
if ($u) {
    audit($u['username'], 'Đăng xuất', $u['username']);
}
logout_user();
flash('Bạn đã đăng xuất.');
redirect('public/login.php');