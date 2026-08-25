<?php

$root = dirname(__DIR__);
$script = file_get_contents($root . '/admin/js/menu-order-handle.js');
$views = file_get_contents($root . '/admin_element_views.php');
$template = file_get_contents($root . '/templates/default/menutree.thtml');

$forbidden = array(
    'tableDnD',
    'tablednd.js',
    'tablednd_0_5.js',
    'tablednd_0_6.js',
    "addEventListener('dragstart'",
    "addEventListener('dragover'",
    "addEventListener('drop'",
);

foreach ($forbidden as $needle) {
    if (stripos($script, $needle) !== false
        || stripos($views, $needle) !== false
        || stripos($template, $needle) !== false) {
        fwrite(STDERR, "Legacy ordering dependency remains: {$needle}\n");
        exit(1);
    }
}

$requiredScript = array(
    "handle.addEventListener('mousedown'",
    "document.addEventListener('mousemove'",
    "document.addEventListener('mouseup'",
    "handle.addEventListener('touchstart'",
    "document.addEventListener('touchmove'",
    "handle.addEventListener('keydown'",
    'document.elementFromPoint',
    'XMLHttpRequest',
    "orders: order",
    "mode: 'move'",
);
foreach ($requiredScript as $needle) {
    if (strpos($script, $needle) === false) {
        fwrite(STDERR, "Pointer ordering behavior missing: {$needle}\n");
        exit(1);
    }
}

if (strpos($views, "setJavaScriptFile('menu_order_handle'") !== false) {
    fwrite(STDERR, "Ordering script must not rely on Geeklog script registration\n");
    exit(1);
}

if (substr_count($template, '{site_admin_url}/plugins/menu/js/menu-order-handle.js') !== 1) {
    fwrite(STDERR, "Ordering script must be loaded exactly once by the tree template\n");
    exit(1);
}

if (strpos($template, 'id="menu-order-token"') === false
    || strpos($template, 'data-menuid="{menuid}"') === false
    || strpos($template, 'data-post-url=') === false) {
    fwrite(STDERR, "Ordering template metadata is incomplete\n");
    exit(1);
}

echo "Pointer menu ordering contract tests passed\n";
