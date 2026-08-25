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
    "handle.setAttribute('draggable', 'true')",
    "handle.addEventListener('dragstart'",
    "row.addEventListener('dragover'",
    "row.addEventListener('drop'",
    "handle.addEventListener('keydown'",
    'XMLHttpRequest',
    "orders: order",
    "mode: 'move'",
);
foreach ($requiredScript as $needle) {
    if (strpos($script, $needle) === false) {
        fwrite(STDERR, "Native ordering behavior missing: {$needle}\n");
        exit(1);
    }
}

if (substr_count($views, "setJavaScriptFile('menu_order_handle'") !== 1) {
    fwrite(STDERR, "Native ordering asset must be registered exactly once by the view\n");
    exit(1);
}

if (strpos($template, "plugins/menu/js/menu-order-handle.js") !== false) {
    fwrite(STDERR, "Ordering script is still loaded directly by the template\n");
    exit(1);
}

if (strpos($template, 'id="menu-order-token"') === false
    || strpos($template, 'data-menuid="{menuid}"') === false
    || strpos($template, 'data-post-url=') === false) {
    fwrite(STDERR, "Ordering template metadata is incomplete\n");
    exit(1);
}

echo "Native menu ordering contract tests passed\n";
