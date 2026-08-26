<?php

define('VERSION', '2.1.1');

$_TABLES = array(
    'menu' => 'gl_menu',
    'menu_config' => 'gl_menu_config',
    'menu_elements' => 'gl_menu_elements',
);

$queries = array();
function DB_query($sql)
{
    global $queries;
    $queries[] = $sql;
    return true;
}

require_once dirname(__DIR__) . '/database.php';

if (MENU_deleteMenuData(0) !== false || !empty($queries)) {
    fwrite(STDERR, "Invalid menu ID must not issue delete queries.\n");
    exit(1);
}

if (!MENU_deleteMenuData(42)) {
    fwrite(STDERR, "Valid menu cleanup should succeed.\n");
    exit(1);
}

$expected = array(
    'DELETE FROM gl_menu_elements WHERE menu_id=42',
    'DELETE FROM gl_menu_config WHERE menu_id=42',
    'DELETE FROM gl_menu WHERE id=42',
);

if ($queries !== $expected) {
    fwrite(STDERR, "Menu cleanup queries are not fully menu-scoped.\n");
    exit(1);
}

echo "Menu database cleanup tests passed\n";
