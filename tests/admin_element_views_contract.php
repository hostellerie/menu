<?php

$root = dirname(__DIR__);
$index = file_get_contents($root . '/admin/index.php');
$module = file_get_contents($root . '/admin_element_views.php');

$functions = array(
    'MENU_displayTree',
    'MENU_createElement',
    'MENU_editElement',
    'MENU_menuConfig',
);

foreach ($functions as $name) {
    $needle = 'function ' . $name;
    if (strpos($module, $needle) === false) {
        fwrite(STDERR, "Missing admin element view helper: {$name}\n");
        exit(1);
    }
    if (strpos($index, $needle) !== false) {
        fwrite(STDERR, "Admin element view helper still duplicated in index.php: {$name}\n");
        exit(1);
    }
}

if (strpos($index, 'admin_element_views.php') === false) {
    fwrite(STDERR, "admin/index.php does not load admin_element_views.php\n");
    exit(1);
}

if (strpos($module, 'MENU_adminTokenInput()') === false) {
    fwrite(STDERR, "Admin element views lost security token fields\n");
    exit(1);
}

if (strpos($module, 'menu-order-handle.js') === false) {
    fwrite(STDERR, "Admin tree view does not load native ordering script\n");
    exit(1);
}

echo "Admin element view module contract tests passed\n";
