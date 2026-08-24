<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Menu Plugin 1.3.0                                                        |
// +---------------------------------------------------------------------------+
// | preview.php                                                               |
// |                                                                           |
// | Isolated menu preview for the administration interface.                   |
// +---------------------------------------------------------------------------+

require_once '../../../lib-common.php';
require_once '../../auth.inc.php';

if (!SEC_hasRights('menu.admin')) {
    header('HTTP/1.1 403 Forbidden');
    exit;
}

$menu_id = isset($_GET['menu']) ? (int) $_GET['menu'] : 0;

if ($menu_id <= 0 || !isset($Menus[$menu_id])) {
    header('HTTP/1.1 404 Not Found');
    exit;
}

/*
 * A preview must also work for a disabled menu. This change only affects the
 * current request; nothing is written to the database.
 */
$Menus[$menu_id]['active'] = 1;

$menu_name = $Menus[$menu_id]['menu_name'];
$menu_type = (int) $Menus[$menu_id]['menu_type'];
$menu_html = '';

switch ($menu_type) {
    case 1:
        $menu_html = MENU_getMenu($menu_name, 'gl_menu', 'gl_menu', '', 'parent');
        break;

    case 2:
        $menu_html = MENU_getMenu($menu_name, 'st-fmenu', '', '', '', 'st-f-last');
        break;

    case 3:
        $menu_html = phpblock_getMenu('', $menu_name);
        break;

    case 4:
        $menu_html = MENU_getMenu($menu_name, 'st-vmenu', '', '', '');
        break;
}

/*
 * Reuse the plugin's normal CSS generation. It also registers JavaScript via
 * Geeklog's $_SCRIPTS API, but this isolated preview deliberately outputs only
 * the returned CSS. This keeps the preview independent from differences in
 * asset handling between Geeklog 2.1.1 and 2.2.2.
 */
$menu_css = plugin_getheadercode_menu();

header('Content-Type: text/html; charset=utf-8');

echo '<!DOCTYPE html>' . "\n";
echo '<html><head><meta charset="utf-8">' . "\n";
echo '<meta name="viewport" content="width=device-width, initial-scale=1">' . "\n";
echo $menu_css;
echo '<style type="text/css">';
echo 'html,body{margin:0;padding:0;background:transparent;}';
echo 'body{padding:12px;box-sizing:border-box;min-height:80px;}';
/* Keep the real desktop menu visible when the iframe itself is narrow. */
if ($menu_type === 1) {
    echo '@media screen and (max-width:750px){#gl_menu' . $menu_id
        . '{display:block!important}.slicknav_menu{display:none!important}}';
}
echo '</style></head><body>' . "\n";

if ($menu_html === '') {
    echo '<p>Menu preview is empty for the current administrator.</p>';
} else {
    echo $menu_html;
}

echo "\n</body></html>";
