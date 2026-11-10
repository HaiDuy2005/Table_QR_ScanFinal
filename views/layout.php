<?php

function view_open(string $title, string $bodyClass = '', int $refresh = 0): void
{
    ?><!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?> · RestaurantQR</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Besley:ital,wght@0,800;1,800&family=IBM+Plex+Mono:wght@500&family=Libre+Franklin:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
</head>
<body class="<?= e($bodyClass) ?>"<?= $refresh ? ' data-refresh="' . (int) $refresh . '"' : '' ?>>
<?php
}
function view_close(): void
{
    ?>
<script src="<?= e(asset('js/qrcode.min.js')) ?>"></script>
<script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
<?php
}