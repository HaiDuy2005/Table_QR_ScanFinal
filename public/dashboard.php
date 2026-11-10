<?php
// Sau khi đăng nhập: chuyển mỗi nhân viên tới màn hình đúng vai trò của mình.
require __DIR__ . '/../app/init.php';
$u = require_role(['cashier', 'kitchen', 'waiter', 'admin']);
redirect(home_for_role($u['role']));
