<?php

$root = dirname(__DIR__);
$index = file_get_contents($root . '/admin/index.php');
$views = file_get_contents($root . '/admin_menu_views.php');

$viewFunctions = array(
    'MENU_displayMenuList',
    'MENU_cloneMenu',
    'MENU_createMenu',
);

foreach ($viewFunctions as $function) {
    $signature = 'function ' . $function . '(';
    if (strpos($views, $signature) === false) {
        fwrite(STDERR, "Missing admin view function: {$function}\n");
        exit(1);
    }
    if (strpos($index, $signature) !== false) {
        fwrite(STDERR, "Admin view function still duplicated in index.php: {$function}\n");
        exit(1);
    }
}

if (strpos($index, "require_once \$_CONF['path'].'plugins/menu/admin_menu_views.php';") === false) {
    fwrite(STDERR, "admin/index.php does not load admin_menu_views.php\n");
    exit(1);
}

if (strpos($views, "'form_action'") !== false) {
    fwrite(STDERR, "Unused legacy form_action variable remains in admin views\n");
    exit(1);
}

if (strpos($index, 'Menu Plugin 1.2.8') !== false) {
    fwrite(STDERR, "Legacy 1.2.8 admin header remains\n");
    exit(1);
}

echo "Admin view module contract tests passed\n";
