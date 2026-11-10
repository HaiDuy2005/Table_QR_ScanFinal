<?php

date_default_timezone_set('Asia/Ho_Chi_Minh');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
define('ROOT_DIR', dirname(__DIR__));
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/helpers/functions.php';
require_once __DIR__ . '/helpers/auth.php';
foreach (['audit', 'user', 'table', 'menu', 'order', 'invoice', 'notice', 'stats'] as $m) {
    require_once __DIR__ . "/models/$m.php";
}
foreach (['customer', 'staff', 'admin'] as $c) {
    require_once __DIR__ . "/controllers/{$c}_controller.php";
}
