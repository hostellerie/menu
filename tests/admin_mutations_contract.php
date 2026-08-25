<?php

$root = dirname(__DIR__);
$index = file_get_contents($root . '/admin/index.php');
$module = file_get_contents($root . '/admin_menu_mutations.php');

$functions = array(
    'MENU_saveNewMenu',
    'MENU_saveEditMenuElement',
    'MENU_changeActiveStatusElement',
    'MENU_changeActiveStatusMenu',
    'MENU_moveElement',
    'MENU_deleteChildElements',
    'MENU_deleteElementTree',
    'MENU_setMenuConfigEnabled',
    'MENU_saveElementOrder',
    'MENU_defaultConfigValues',
    'MENU_restoreMenuDefaults',
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

$forbiddenIndexSql = array(
    'UPDATE {$_TABLES[\'menu_elements\']} SET element_order=',
    'UPDATE {$_TABLES[\'menu_config\']} SET enabled',
    'MENU_deleteChildElements($id, $menu_id)',
);
foreach ($forbiddenIndexSql as $needle) {
    if (strpos($index, $needle) !== false) {
        fwrite(STDERR, "State-changing SQL still remains in admin/index.php: {$needle}\n");
        exit(1);
    }
}


if (strpos($index, 'DB_save(') !== false || strpos($index, 'DB_query(') !== false) {
    fwrite(STDERR, "admin/index.php still contains direct database writes\n");
    exit(1);
}
if (strpos($index, 'MENU_restoreMenuDefaults($menu_id)') === false) {
    fwrite(STDERR, "Defaults branch is not routed through mutation module\n");
    exit(1);
}
if (strpos($module, 'function MENU_defaultConfigValues(') === false
    || strpos($module, 'function MENU_restoreMenuDefaults(') === false) {
    fwrite(STDERR, "Centralized defaults helpers are missing\n");
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
