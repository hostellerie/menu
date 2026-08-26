<?php

$index = file_get_contents(dirname(__DIR__) . '/admin/index.php');

$forbidden = array(
    'function MENU_',
    'DB_query(',
    'DB_save(',
    'DB_delete(',
    'DB_insertId(',
);
foreach ($forbidden as $needle) {
    if (strpos($index, $needle) !== false) {
        fwrite(STDERR, "Admin controller still contains non-routing logic: {$needle}\n");
        exit(1);
    }
}

$requiredModules = array(
    'admin_menu_views.php',
    'admin_menu_mutations.php',
    'admin_element_views.php',
);
foreach ($requiredModules as $module) {
    if (strpos($index, $module) === false) {
        fwrite(STDERR, "Admin controller missing module: {$module}\n");
        exit(1);
    }
}

if (strpos($index, '$currentSelect = $LANG_MENU01[\'configuration\'];') !== false) {
    fwrite(STDERR, "Obsolete overwritten configuration selection remains\n");
    exit(1);
}

echo "Admin controller contract tests passed\n";
