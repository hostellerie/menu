<?php
$view = file_get_contents(dirname(__DIR__) . '/admin_element_views.php');

if (substr_count($view, '$safeMenuName = MENU_escapeStoredText(') < 4) {
    fwrite(STDERR, "Stored menu names are not normalized in all admin element views\n");
    exit(1);
}
$selfGuard = 'if ((int) $row[\'id\'] === (int) $mid || isset($blockedParentIds[(int) $row[\'id\']]))';
if (substr_count($view, $selfGuard) !== 1) {
    fwrite(STDERR, "Parent selector hierarchy guard is missing or duplicated\n");
    exit(1);
}
if (strpos($view, "'birdseed'          => '<a href=\"'.\$_CONF['site_admin_url'].'/plugins/menu/index.php\">Menu List</a> :: '.\$Menus[\$mid]['menu_name']") !== false) {
    fwrite(STDERR, "Raw stored menu name remains in configuration breadcrumb\n");
    exit(1);
}
if (strpos($view, "'menuname'          => \$Menus[\$menu_id]['menu_name']") !== false) {
    fwrite(STDERR, "Raw stored menu name remains in tree template variable\n");
    exit(1);
}

$undefinedLegacyFallback = "'menuname'          => isset(\$menu_name)";
if (strpos($view, $undefinedLegacyFallback) !== false) {
    fwrite(STDERR, "Undefined legacy menu_name fallback remains in create view\n");
    exit(1);
}
$escapedAttributeNeedles = array(
    "'menulabel'         => MENU_escapeHTML(",
    "'menuurl'           => MENU_escapeHTML(",
    "'phpfunction'       => MENU_escapeHTML("
);
foreach ($escapedAttributeNeedles as $needle) {
    if (strpos($view, $needle) === false) {
        fwrite(STDERR, "Stored element attribute value is not escaped: {$needle}\n");
        exit(1);
    }
}
if (substr_count($view, 'MENU_escapeHTML($pluginKey)') < 3) {
    fwrite(STDERR, "Plugin-provided option identifiers are not escaped\n");
    exit(1);
}

echo "Admin view escaping contract tests passed\n";
