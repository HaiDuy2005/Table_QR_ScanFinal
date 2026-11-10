<?php
function menu_all(bool $onlyShown = false): array
{
    return fetch_all('SELECT * FROM menu_items ORDER BY sort_no, id');
}
function menu_find(int $id): ?array
{
    return fetch_one('SELECT * FROM menu_items WHERE id = ?', [$id]);
}
function menu_save(?int $id, array $d): int
{
    $p = [$d['name'], $d['category'], $d['description'], $d['unit'], $d['price'], $d['available'], $d['art'], $d['tone'], $d['image_url'] ?: null];
    if ($id) {
        q('UPDATE menu_items SET name=?, category=?, description=?, unit=?, price=?, available=?, art=?, tone=?, image_url=? WHERE id=?', array_merge($p, [$id]));
        return $id;
    }
    $max = (int) fetch_one('SELECT COALESCE(MAX(sort_no),0) AS m FROM menu_items')['m'];
    q('INSERT INTO menu_items (name, category, description, unit, price, available, art, tone, image_url, sort_no) VALUES (?,?,?,?,?,?,?,?,?,?)', array_merge($p, [$max + 1]));
    return insert_id();
}
function menu_set_available(int $id, bool $on): void
{
    q('UPDATE menu_items SET available = ? WHERE id = ?', [$on ? 1 : 0, $id]);
}
