<?php

function current_user(): ?array
{
    static $cache = false;
    if ($cache !== false) {
        return $cache;
    }
    $id = $_SESSION['user_id'] ?? null;
    $u = $id ? user_find((int) $id) : null;
    if ($u && (int) $u['locked'] === 1) { 
        unset($_SESSION['user_id']);
        $u = null;
    }
    return $cache = $u;
}

function home_for_role(string $role): string
{
    return [
        'cashier' => 'public/staff/cashier.php',
        'kitchen' => 'public/staff/kitchen.php',
        'waiter' => 'public/staff/waiter.php',
        'admin' => 'public/admin/index.php',
    ][$role] ?? 'public/login.php';
}


function require_role(array $roles): array
{
    $u = current_user();
    if (!$u) {
        flash('Hãy đăng nhập để tiếp tục.', 'err');
        redirect('public/login.php');
    }
    if ($u['role'] !== 'admin' && !in_array($u['role'], $roles, true)) {
        flash('Tài khoản ' . ROLE_LABEL[$u['role']] . ' không có quyền vào trang này.', 'err');
        redirect(home_for_role($u['role']));
    }
    return $u;
}

function assert_role(array $roles): array
{
    $u = current_user();
    if (!$u) {
        throw new AppError('Bạn chưa đăng nhập.');
    }
    if ($u['role'] !== 'admin' && !in_array($u['role'], $roles, true)) {
        throw new AppError('Bạn không có quyền thực hiện thao tác này.');
    }
    return $u;
}


function login_user(string $username, string $password): void
{
    $lock = $_SESSION['login_lock'] ?? 0;
    if ($lock > time()) {
        throw new AppError('Nhập sai nhiều lần. Hãy thử lại sau ' . ($lock - time()) . ' giây.');
    }
    $u = user_by_username($username);
    if (!$u || !password_verify($password, $u['password_hash'])) {
        $_SESSION['login_fail'] = ($_SESSION['login_fail'] ?? 0) + 1;
        if ($_SESSION['login_fail'] >= 5) {
            $_SESSION['login_lock'] = time() + 60;
            $_SESSION['login_fail'] = 0;
        }
        throw new AppError('Sai tên đăng nhập hoặc mật khẩu.');
    }
    if ((int) $u['locked'] === 1) {
        throw new AppError('Tài khoản này đã bị khóa.');
    }
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $u['id'];
    $_SESSION['login_fail'] = 0;
    audit($u['username'], 'Đăng nhập', $u['username']);
}
function logout_user(): void
{
    unset($_SESSION['user_id']);
    session_regenerate_id(true);
}
