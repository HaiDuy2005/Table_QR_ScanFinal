<?php
function user_find(int $id): ?array
{
    return fetch_one('SELECT * FROM users WHERE id = ?', [$id]);
}
function user_by_username(string $name): ?array
{
    return fetch_one('SELECT * FROM users WHERE username = ?', [$name]);
}
function user_all(): array
{
    return fetch_all('SELECT * FROM users ORDER BY id');
}
function user_active_admins(): int
{
    return (int) fetch_one("SELECT COUNT(*) AS n FROM users WHERE role = 'admin' AND locked = 0")['n'];
}
function user_insert(string $username, string $fullName, string $password, string $role): int
{
    q('INSERT INTO users (username, full_name, password_hash, role, locked, created_at) VALUES (?,?,?,?,0,?)', [
        $username, $fullName, password_hash($password, PASSWORD_BCRYPT), $role, now(),
    ]);
    return insert_id();
}
function user_update(int $id, string $fullName, string $role, bool $locked, string $password = ''): void
{
    q('UPDATE users SET full_name = ?, role = ?, locked = ? WHERE id = ?', [$fullName, $role, $locked ? 1 : 0, $id]);
    if ($password !== '') {
        q('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($password, PASSWORD_BCRYPT), $id]);
    }
}
