<?php

$root = dirname(__DIR__);
$functions = file_get_contents($root . '/functions.inc');

$required = array(
    'function MENU_resolveAutotagReference',
    "DB_getItem(\$_TABLES['menu'], 'id', 'menu_name=\"'",
    'ctype_digit($reference)',
    "DB_getItem(\$_TABLES['menu'], 'menu_name', 'id=' . $id)",
    '$resolved = MENU_resolveAutotagReference($reference);',
    "$menuName = \$resolved['name'];",
    'MENU_getMenu($menuName',
    "phpblock_getMenu('',\$menuName)",
);

foreach ($required as $needle) {
    if (strpos($functions, $needle) === false) {
        fwrite(STDERR, "Autotag reference support missing: {$needle}\n");
        exit(1);
    }
}

$nameLookup = strpos($functions, "DB_getItem(\$_TABLES['menu'], 'id', 'menu_name=\"'");
$idFallback = strpos($functions, 'if (ctype_digit($reference))');
if ($nameLookup === false || $idFallback === false || $nameLookup >= $idFallback) {
    fwrite(STDERR, "Autotag must resolve exact names before numeric IDs\n");
    exit(1);
}

echo "Menu autotag name/ID reference contract passed\n";
