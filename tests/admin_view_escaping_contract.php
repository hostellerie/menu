<?php
$view = file_get_contents(dirname(__DIR__) . '/admin_element_views.php');

if (substr_count($view, '$safeMenuName = MENU_escapeStoredText(') < 3) {
    fwrite(STDERR, "Stored menu names are not normalized in all admin element views\n");
    exit(1);
}
$selfGuard = 'if ((int) $row[\'id\'] === (int) $mid || isset($blockedParentIds[(int) $row[\'id\']]))';
if (substr_count($view, $selfGuard) !== 1) {
    fwrite(STDERR, "Parent selector hierarchy guard is missing or duplicated\n");
    exit(1);
}
if (strpos($view, "'menuname'          => \$Menus[\$menu_id]['menu_name']") !== false) {
    fwrite(STDERR, "Raw stored menu name remains in tree template variable\n");
    exit(1);
}

echo "Admin view escaping contract tests passed\n";
