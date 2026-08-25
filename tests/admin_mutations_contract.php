<?php

$root = dirname(__DIR__);
$index = file_get_contents($root . '/admin/index.php');
$module = file_get_contents($root . '/admin_menu_mutations.php');

$functions = array(
    'MENU_saveNewMenu',
    'MENU_saveEditMenuElement',
    'MENU_changeActiveStatusElement',
    'MENU_changeActiveStatusMenu',
    'MENU_deleteChildElements',
    'MENU_saveMenuConfig',
);

foreach ($functions as $name) {
    $needle = 'function ' . $name;
    if (strpos($module, $needle) === false) {
        fwrite(STDERR, "Missing admin mutation helper: {$name}\n");
        exit(1);
    }
    if (strpos($index, $needle) !== false) {
        fwrite(STDERR, "Admin mutation helper still duplicated in index.php: {$name}\n");
        exit(1);
    }
}

if (strpos($index, "admin_menu_mutations.php") === false) {
    fwrite(STDERR, "admin/index.php does not load admin_menu_mutations.php\n");
    exit(1);
}

if (strpos($module, 'MENU_invalidateRuntimeCache(') === false) {
    fwrite(STDERR, "Mutation module does not use centralized cache invalidation\n");
    exit(1);
}

if (strpos($module, 'MENU_dbEscape(') === false) {
    fwrite(STDERR, "Mutation module lost database escaping\n");
    exit(1);
}

echo "Admin mutation module contract tests passed\n";
